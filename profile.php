<?php
session_start();
require_once 'config/connect.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$msg = "";

// 2. Xử lý khi bấm nút Cập nhật
if (isset($_POST['btnUpdateProfile'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Cập nhật vào Database
    $sql_update = "UPDATE users SET fullname = '$fullname', phone = '$phone', address = '$address' WHERE id = $user_id";
    
    if ($conn->query($sql_update)) {
        // BƯỚC QUAN TRỌNG: Cập nhật lại biến Session để giao diện đổi tên ngay lập tức
        $_SESSION['user']['fullname'] = $fullname;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['address'] = $address;
        
        $msg = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i> Cập nhật thông tin cá nhân thành công!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
    }
}

// 3. Lấy thông tin mới nhất từ Session (để điền sẵn vào Form)
$current_user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân - Vườn Xanh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-success mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-leaf"></i> VƯỜN XANH</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 fw-bold"><i class="fa-solid fa-user-circle"></i> <?php echo $current_user['fullname']; ?></span>
                <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-house"></i> Trang chủ</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white py-3 border-bottom text-center">
                        <h5 class="mb-0 fw-bold text-success"><i class="fa-solid fa-user-pen me-2"></i>Hồ Sơ Của Tôi</h5>
                        <p class="text-muted small mb-0 mt-1">Quản lý thông tin để bảo mật tài khoản</p>
                    </div>
                    <div class="card-body p-4">

                        <?php if($msg != "") echo $msg; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Email đăng nhập</label>
                                <input type="email" class="form-control bg-light" value="<?php echo $current_user['email']; ?>" readonly>
                                <small class="text-danger">(*) Không thể thay đổi địa chỉ email.</small>
                            </div>
                            
                            <hr class="my-4">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="fullname" class="form-control" required value="<?php echo $current_user['fullname']; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" required value="<?php echo $current_user['phone']; ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control" rows="3" required><?php echo $current_user['address']; ?></textarea>
                            </div>
                            <button type="submit" name="btnUpdateProfile" class="btn btn-success w-100 py-2 fw-bold">LƯU THAY ĐỔI</button>
                        </form>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>