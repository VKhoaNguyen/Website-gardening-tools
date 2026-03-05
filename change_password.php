<?php
session_start();
require_once 'config/connect.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$msg = "";

// 2. Xử lý khi bấm nút Đổi mật khẩu
if (isset($_POST['btnChangePass'])) {
    // Mã hóa md5 các mật khẩu nhập vào để so sánh với Database
    $old_password = md5(trim($_POST['old_password']));
    $new_password = md5(trim($_POST['new_password']));
    $confirm_password = md5(trim($_POST['confirm_password']));

    // Lấy mật khẩu cũ đang lưu trong Database ra
    $sql_check = "SELECT password FROM users WHERE id = $user_id";
    $result = $conn->query($sql_check);
    $row = $result->fetch_assoc();
    $db_password = $row['password'];

    // Kiểm tra các điều kiện
    if ($old_password != $db_password) {
        $msg = "<div class='alert alert-danger'><i class='fa-solid fa-circle-exclamation me-2'></i> Mật khẩu cũ không chính xác!</div>";
    } elseif ($new_password != $confirm_password) {
        $msg = "<div class='alert alert-danger'><i class='fa-solid fa-circle-exclamation me-2'></i> Mật khẩu mới không khớp nhau!</div>";
    } else {
        // Nếu đúng hết -> Tiến hành cập nhật
        $sql_update = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
        if ($conn->query($sql_update)) {
            $msg = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i> Đổi mật khẩu thành công! Lần đăng nhập sau hãy dùng mật khẩu mới nhé.</div>";
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
    <title>Đổi Mật Khẩu - Vườn Xanh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-success mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-leaf"></i> VƯỜN XANH</a>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-house"></i> Trang chủ</a>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white py-3 border-bottom text-center">
                        <h5 class="mb-0 fw-bold text-success"><i class="fa-solid fa-shield-halved me-2"></i>Đổi Mật Khẩu</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="text-center mb-4">
                            <i class="fa-solid fa-user-circle text-muted" style="font-size: 50px;"></i>
                            <h6 class="mt-2 text-primary fw-bold"><?php echo $user['fullname']; ?></h6>
                            <p class="text-muted small"><?php echo $user['email']; ?></p>
                        </div>

                        <?php if($msg != "") echo $msg; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                <input type="password" name="old_password" class="form-control" required placeholder="Nhập mật khẩu đang dùng">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" name="new_password" class="form-control" required placeholder="Tạo mật khẩu mới">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control" required placeholder="Xác nhận mật khẩu mới">
                            </div>
                            <button type="submit" name="btnChangePass" class="btn btn-success w-100 py-2 fw-bold">CẬP NHẬT MẬT KHẨU</button>
                        </form>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>