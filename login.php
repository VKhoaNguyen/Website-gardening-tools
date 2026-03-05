<?php
session_start();
require_once 'config/connect.php';

$error = '';

// Xử lý khi người dùng bấm nút Đăng nhập
if (isset($_POST['btnLogin'])) {
    // 1. Lấy dữ liệu và dùng real_escape_string để chống lỗi bảo mật SQL Injection
    $email = $conn->real_escape_string($_POST['email']);
    $password = MD5($_POST['password']); // Mã hóa pass nhập vào để so sánh với DB

    // 2. Truy vấn kiểm tra xem email và password có khớp không
    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 3. KIỂM TRA TÍNH TRẠNG TÀI KHOẢN (Tính năng Soft Delete vừa thêm)
        if ($user['is_active'] == 0) {
            // Nếu is_active = 0 -> Bị khóa -> Văng lỗi và không cho tạo Session
            $error = "Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động. Vui lòng liên hệ Admin!";
        } else {
            // Tài khoản bình thường -> Khởi tạo phiên làm việc
            $_SESSION['user'] = $user; // Lưu thông tin vào session

            // Phân quyền chuyển hướng
            if ($user['role_level'] == 2 || $user['role_level'] == 1) {
                header("Location: admin/index.php"); // Vào trang Admin
            } else {
                header("Location: index.php"); // Về trang chủ mua hàng
            }
            exit();
        }
    } else {
        $error = "Email hoặc mật khẩu không chính xác!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Vườn Xanh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h3 class="text-center text-success mb-4">ĐĂNG NHẬP</h3>
                        
                        <?php if($error != '') echo "<div class='alert alert-danger'>$error</div>"; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label>Email:</label>
                                <input type="email" name="email" class="form-control" required placeholder="admin@vuonxanh.com">
                            </div>
                            <div class="mb-3">
                                <label>Mật khẩu:</label>
                                <input type="password" name="password" class="form-control" required placeholder="******">
                            </div>
                            <button type="submit" name="btnLogin" class="btn btn-success w-100">Đăng Nhập</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="index.php" class="text-decoration-none text-muted">< Quay lại trang chủ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>