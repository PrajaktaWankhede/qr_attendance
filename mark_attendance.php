<?php
// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

// Add CORS headers
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Enforce HTTPS
if (!isset($_SERVER['HTTPS'])) {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Fixed location coordinates
$fixedLat = 19.878216;
$fixedLong = 75.364089;
$allowedDistance = 0.5; // 500 meters

// Function to calculate distance in kilometers
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return $dist * 60 * 1.1515 * 1.609344; // KM
}

// Get user's location from GET parameters
$userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$userLong = isset($_GET['long']) ? floatval($_GET['long']) : null;

// Verify location
$locationValid = false;
$distance = 0;
if ($userLat && $userLong) {
    $distance = calculateDistance($fixedLat, $fixedLong, $userLat, $userLong);
    $locationValid = ($distance <= $allowedDistance);
}

// Database configuration
$servername = "sg2plzcpnl505627";
$username = "Buldhana_db";
$password = "M3@{pvrYN!F{";
$dbname = "Buldhana_police";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}

// Sanitize input
$ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');

// Check if name parameter is provided
$showNameForm = !isset($_POST['user_name']);
$userName = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';

// Initialize variables
$success = false;
$inMarked = false;
$outMarked = false;
$inTime = '';
$outTime = '';

// If name form is submitted and location is valid
if (!$showNameForm && $locationValid && !empty($userName)) {
    // Check if attendance already exists for this user today
    $stmt = $conn->prepare("SELECT id, in_time, out_time FROM attendance WHERE user_name = ? AND date = ?");
    $stmt->bind_param("ss", $userName, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['in_time'] && !$row['out_time']) {
            // Mark out time
            $stmt = $conn->prepare("UPDATE attendance SET out_time = ?, latitude = ?, longitude = ? WHERE id = ?");
            $stmt->bind_param("sddi", $now, $userLat, $userLong, $row['id']);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $success = true;
                $outMarked = true;
                $outTime = date('d-m-Y h:i:s A', strtotime($now));
                $inTime = date('d-m-Y h:i:s A', strtotime($row['in_time']));
            }
        } elseif ($row['in_time'] && $row['out_time']) {
            // Both in and out already marked
            $inTime = date('d-m-Y h:i:s A', strtotime($row['in_time']));
            $outTime = date('d-m-Y h:i:s A', strtotime($row['out_time']));
        }
    } else {
        // Insert new in time (removed IP check)
        $stmt = $conn->prepare("INSERT INTO attendance (user_name, ip_address, date, in_time, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdd", $userName, $ip, $today, $now, $userLat, $userLong);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $success = true;
            $inMarked = true;
            $inTime = date('d-m-Y h:i:s A', strtotime($now));
        }
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        .card {
            background: linear-gradient(145deg, #ffffff, #f1f5f9);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-gray-200 to-gray-100 min-h-screen flex items-center justify-center">
    <div class="card p-8 rounded-2xl shadow-xl max-w-md w-full mx-4 fade-in">
        <h1 class="text-3xl font-bold text-gray-900 mb-4 flex items-center justify-center">
            Attendance Status
        </h1>
        
        <?php if ($showNameForm): ?>
            <!-- Name Entry Form -->
            <form method="POST" class="space-y-4">
                <div>
                    <label for="user_name" class="block text-sm font-medium text-gray-700 mb-1">Enter Your Full Name</label>
                    <input type="text" id="user_name" name="user_name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="hidden" name="lat" value="<?= htmlspecialchars($userLat) ?>">
                    <input type="hidden" name="long" value="<?= htmlspecialchars($userLong) ?>">
                </div>
                <button type="submit" 
                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition duration-200">
                    Submit Attendance
                </button>
            </form>
            
            <?php if (!$locationValid): ?>
                <div class="mt-4 bg-red-50 p-4 rounded-lg">
                    <p class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i> Location Verification Failed</p>
                    <p class="text-gray-700 mt-2">You must be within 500m of the designated location to mark attendance.</p>
                    <div class="mt-3 text-sm">
                        <p><span class="font-medium">Your location:</span> <?= $userLat ?>, <?= $userLong ?></p>
                        <p><span class="font-medium">Distance:</span> <?= round($distance * 1000) ?> meters</p>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Attendance Status Display -->
            <div class="text-gray-700 text-lg text-center mb-6">
                <?php
                if (!$locationValid) {
                    echo '<div class="bg-red-50 p-4 rounded-lg mb-4">
                        <p class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i> Attendance Denied</p>
                        <p class="text-gray-700 mt-2">You are too far from the required location.</p>
                        <div class="mt-3 text-sm">
                            <p><span class="font-medium">Your location:</span> ' . $userLat . ', ' . $userLong . '</p>
                            <p><span class="font-medium">Distance:</span> ' . round($distance * 1000) . ' meters</p>
                        </div>
                    </div>';
                } elseif ($success && $inMarked) {
                    echo '<div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-green-600"><i class="fas fa-check-circle mr-2"></i> In Time Marked Successfully</p>
                        <p class="text-gray-700 mt-2">Name: <strong>' . htmlspecialchars($userName) . '</strong></p>
                        <p class="text-gray-700">In Time: <strong>' . $inTime . '</strong></p>
                        <div class="mt-3 text-sm">
                            <p><span class="font-medium">Location:</span> ' . $userLat . ', ' . $userLong . '</p>
                            <p><span class="font-medium">Distance from center:</span> ' . round($distance * 1000) . ' meters</p>
                        </div>
                    </div>';
                } elseif ($success && $outMarked) {
                    echo '<div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-green-600"><i class="fas fa-check-circle mr-2"></i> Out Time Marked Successfully</p>
                        <p class="text-gray-700 mt-2">Name: <strong>' . htmlspecialchars($userName) . '</strong></p>
                        <p class="text-gray-700">In Time: <strong>' . $inTime . '</strong></p>
                        <p class="text-gray-700">Out Time: <strong>' . $outTime . '</strong></p>
                        <div class="mt-3 text-sm">
                            <p><span class="font-medium">Location:</span> ' . $userLat . ', ' . $userLong . '</p>
                            <p><span class="font-medium">Distance from center:</span> ' . round($distance * 1000) . ' meters</p>
                        </div>
                    </div>';
                } elseif ($inTime && $outTime) {
                    echo '<div class="bg-yellow-50 p-4 rounded-lg">
                        <p class="text-yellow-600"><i class="fas fa-info-circle mr-2"></i> Attendance Already Completed!</p>
                        <p class="text-gray-700 mt-2">Name: <strong>' . htmlspecialchars($userName) . '</strong></p>
                        <p class="text-gray-700">In Time: <strong>' . $inTime . '</strong></p>
                        <p class="text-gray-700">Out Time: <strong>' . $outTime . '</strong></p>
                        <p class="text-gray-700 text-sm mt-2">Both in and out times are already marked for today.</p>
                    </div>';
                } else {
                    echo '<div class="bg-yellow-50 p-4 rounded-lg">
                        <p class="text-yellow-600"><i class="fas fa-info-circle mr-2"></i> Attendance Error</p>
                        <p class="text-gray-700 mt-2">Name: <strong>' . htmlspecialchars($userName) . '</strong></p>
                        <p class="text-gray-700 text-sm mt-2">There was an issue processing your attendance. Please try again.</p>
                    </div>';
                }
                ?>
            </div>
            
            <div class="mt-4">
                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="text-indigo-600 hover:text-indigo-800">
                    <i class="fas fa-arrow-left mr-2"></i> Mark attendance for another user
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>