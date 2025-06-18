# qr_attendance
## 💡 Features

- ✅ Location-verified attendance (within 50 meters)
- 🔐 Secure and real-time QR-based access
- 📍 Only users at the defined coordinates can mark attendance
- 🌐 Mobile-friendly UI with Tailwind CSS
- 🌀 Auto-refreshing QR code for enhanced security

  ## 📍 Fixed Attendance Location

- **Latitude:** `19.878216`
- **Longitude:** `75.364089`
- **Venue:** Digispect Technologies Pvt. Ltd., Chhatrapati Sambhajinagar

## 🛠️ Technologies Used

- **Frontend**: HTML, Tailwind CSS, JavaScript
- **Backend**: PHP
- **API**: `https://api.qrserver.com` for QR Code generation

## 🚀 How It Works

1. User scans the QR code.
2. The link opens `mark_attendance.php` with location coordinates.
3. `check_location.php` verifies if the device’s GPS is within a valid range.
4. If valid, attendance is marked and recorded in the log.

## 📸 Screenshots 
![image](https://github.com/user-attachments/assets/620352cf-1636-4511-9b3d-5da7f24043dc)
