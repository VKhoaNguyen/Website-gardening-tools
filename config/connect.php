<?php
$host = "localhost";
$username = "root";   // XAMPP mặc định là root
$password = "vertrigo";       // XAMPP mặc định để trống
$dbname = "db_gardening";

// Kết nối MySQLi
$conn = new mysqli($host, $username, $password, $dbname);

// Kiểm tra lỗi
if ($conn->connect_error) {
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}

// Set charset để không lỗi font tiếng Việt
$conn->set_charset("utf8mb4");

// Bỏ comment dòng dưới đây để test thử xem kết nối thành công chưa
// echo "Kết nối MySQL thành công!";
?>