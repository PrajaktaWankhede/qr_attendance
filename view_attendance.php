<?php
// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

// Add CORS headers
header("Access-Control-Allow-Origin: https://mahilabalvikas.in");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Enforce HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Database configuration
$servername = "sg2plzcpnl505627";
$username = "Buldhana_db";
$password = "M3@{pvrYN!F{";
$dbname = "Buldhana_police";

// DB connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Get filter period and month from POST or default to 'daily' and current month
$period = isset($_POST['period']) ? $_POST['period'] : 'daily';
$selectedMonth = isset($_POST['month']) ? $_POST['month'] : date('Y-m');

// Prepare SQL query based on period
$today = date('Y-m-d');
$sql = "SELECT id, user_name, ip_address, date, in_time, out_time, latitude, longitude FROM attendance";
$where = "";
$params = [];

if ($period === 'daily') {
    $where = " WHERE DATE(date) = ?";
    $params[] = $today;
} elseif ($period === 'weekly') {
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $where = " WHERE DATE(date) >= ?";
    $params[] = $weekStart;
} elseif ($period === 'monthly') {
    // Use selected month or current month
    $monthStart = date('Y-m-01', strtotime($selectedMonth . '-01'));
    $monthEnd = date('Y-m-t', strtotime($selectedMonth . '-01'));
    $where = " WHERE DATE(date) BETWEEN ? AND ?";
    $params[] = $monthStart;
    $params[] = $monthEnd;
}

$sql .= $where . " ORDER BY date DESC, in_time DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records</title>
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
        .attendance-table th {
            background: #4F46E5;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .attendance-table tr:nth-child(even) {
            background: #f8fafc;
        }
        /* Responsive table styles */
        .attendance-table {
            min-width: 800px; /* Ensure table doesn't collapse too much */
        }
        /* Adjust font sizes and padding for mobile */
        @media (max-width: 640px) {
            .attendance-table th,
            .attendance-table td {
                font-size: 0.75rem; /* Smaller text on mobile */
                padding: 0.5rem; /* Reduced padding */
            }
            .attendance-table {
                min-width: 600px; /* Slightly smaller min-width for mobile */
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-gray-200 to-gray-100 min-h-screen flex items-center justify-center py-4">
    <div class="card p-4 sm:p-6 md:p-8 rounded-2xl shadow-xl w-full max-w-full sm:max-w-4xl md:max-w-7xl mx-4 fade-in">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6 flex items-center justify-center">
            <i class="fas fa-list-check mr-2"></i> Attendance Records
        </h2>

        <!-- Filter Form -->
        <form method="POST" class="mb-6 flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-center sm:space-x-4 sm:space-y-0">
            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
                <label for="period" class="text-sm font-medium text-gray-700">Filter by:</label>
                <select id="period" name="period" onchange="this.form.submit()"
                        class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm sm:text-base">
                    <option value="daily" <?= $period === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= $period === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= $period === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                </select>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
                <label for="month" class="text-sm font-medium text-gray-700">Select Month:</label>
                <input type="month" id="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" 
                       onchange="this.form.submit()"
                       class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm sm:text-base">
            </div>
        </form>

        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="attendance-table w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium">ID</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium">Name</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium">IP Address</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium">Date</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium">In Time</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium">Out Time</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium">Latitude</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium">Longitude</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    if ($result->num_rows === 0) {
                        echo "<tr><td colspan='8' class='px-3 sm:px-6 py-4 text-center text-xs sm:text-sm text-gray-500'>No records found for the selected period.</td></tr>";
                    } else {
                        while ($row = $result->fetch_assoc()) {
                            $formattedDate = date('d-m-Y', strtotime($row['date']));
                            $formattedInTime = $row['in_time'] ? date('h:i:s A', strtotime($row['in_time'])) : '-';
                            $formattedOutTime = $row['out_time'] ? date('h:i:s A', strtotime($row['out_time'])) : '-';
                            echo "<tr>
                                    <td class='px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm'>" . htmlspecialchars($row['id']) . "</td>
                                    <td class='px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm'>" . htmlspecialchars($row['user_name']) . "</td>
                                    <td class='px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm'>" . htmlspecialchars($row['ip_address']) . "</td>
                                    <td class='px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm'>" . htmlspecialchars($formattedDate) . "</td>
                                    <td class='px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm'>" . htmlspecialchars($formattedInTime) . "</td>
                                    <td class='px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm'>" . htmlspecialchars($formattedOutTime) . "</td>
                                    <td class='px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm'>" . htmlspecialchars($row['latitude']) . "</td>
                                    <td class='px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm'>" . htmlspecialchars($row['longitude']) . "</td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php 
    $stmt->close();
    $conn->close(); 
    ?>
</body>
</html>