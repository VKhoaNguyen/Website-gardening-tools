<?php
session_start();
require_once 'config/connect.php';

$msg = "";

if (isset($_POST['btnRegister'])) {
    $fullname = $_POST['fullname'];
    $email = trim($_POST['email']);
$password = md5($_POST['password']);
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // 1. Kiểm tra xem Email đã tồn tại trong hệ thống chưa
    $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Email này đã được đăng ký! Vui lòng chọn email khác.</div>";
    } else {
        // 2. Lưu vào DB với role_level = 0 (Khách hàng bình thường)
        $sql = "INSERT INTO users (fullname, email, password, phone, address, role_level) 
                VALUES ('$fullname', '$email', '$password', '$phone', '$address', 0)";
        
        if ($conn->query($sql)) {
            echo "<script>alert('Đăng ký tài khoản thành công! Vui lòng đăng nhập.'); window.location.href='login.php';</script>";
            exit();
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản - Vườn Xanh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow border-0 rounded-3">
                    <div class="card-header bg-success text-white text-center py-3">
                        <h4 class="mb-0"><i class="fa-solid fa-user-plus me-2"></i>Đăng Ký Tài Khoản</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if($msg != "") echo $msg; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="fullname" class="form-control" required placeholder="VD: Nguyễn Văn A">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email (Tên đăng nhập) <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required placeholder="VD: email@gmail.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required placeholder="Tạo mật khẩu">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" required placeholder="Dùng để liên hệ giao hàng">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Địa chỉ giao hàng mặc định <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control" rows="3" required placeholder="Nhập số nhà, tên đường, phường/xã..."></textarea>
                            </div>
                            <button type="submit" name="btnRegister" class="btn btn-success w-100 py-2 fw-bold">ĐĂNG KÝ NGAY</button>
                        </form>
                        <div class="text-center mt-3">
                            <span>Đã có tài khoản? </span><a href="login.php" class="text-success fw-bold text-decoration-none">Đăng nhập tại đây</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>