<?php
session_start();
require_once '../config/connect.php';

// BẢO MẬT CẤP CAO: CHỈ CHO PHÉP ADMIN (role_level = 1) VÀO TRANG NÀY
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] != 1) {
    // Nếu là nhân viên (2) hoặc người lạ (0) -> Cảnh báo và đuổi về trang chủ Admin
    echo "<script>alert('LỖI BẢO MẬT: Bạn không có quyền truy cập chức năng này!'); window.location.href='index.php';</script>";
    exit();
}

$current_admin_id = $_SESSION['user']['id'];
$msg = "";

// 1. XỬ LÝ KHI THÊM TÀI KHOẢN MỚI
if (isset($_POST['btnAddAccount'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = md5(trim($_POST['password'])); // Mã hóa mật khẩu
    $phone = trim($_POST['phone']);
    $role_level = (int)$_POST['role_level'];
    $address = "Nội bộ"; // Gán mặc định vì Admin/Nhân viên không cần địa chỉ giao hàng

    // Kiểm tra trùng Email
    $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Email này đã được sử dụng! Vui lòng chọn email khác.</div>";
    } else {
        // Mặc định is_active = 1 khi tạo mới
        $sql_insert = "INSERT INTO users (fullname, email, password, phone, address, role_level, is_active) 
                       VALUES ('$fullname', '$email', '$password', '$phone', '$address', $role_level, 1)";
        if ($conn->query($sql_insert)) {
            // Ngắt form resubmission (Chống F5)
            header("Location: accounts.php?msg=add_success");
            exit();
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi thêm tài khoản: " . $conn->error . "</div>";
        }
    }
}

// 2. XỬ LÝ KHI XÓA TÀI KHOẢN
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // BẢO MẬT: Không cho phép tự xóa chính mình
    if ($del_id == $current_admin_id) {
        header("Location: accounts.php?msg=err_self_delete");
        exit();
    } else {
        $conn->query("DELETE FROM users WHERE id = $del_id AND role_level IN (1, 2)");
        header("Location: accounts.php?msg=del_success");
        exit();
    }
}

// 2.5. XỬ LÝ KHÓA / MỞ KHÓA TÀI KHOẢN (TÍNH NĂNG MỚI THÊM)
if (isset($_GET['toggle_id'])) {
    $toggle_id = (int)$_GET['toggle_id'];
    
    // Không cho phép tự khóa chính mình
    if ($toggle_id == $current_admin_id) {
        header("Location: accounts.php?msg=err_self_toggle");
        exit();
    } else {
        // Lấy trạng thái hiện tại
        $res = $conn->query("SELECT is_active FROM users WHERE id = $toggle_id");
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            // Đảo ngược trạng thái: 1 thành 0, 0 thành 1
            $new_status = ($row['is_active'] == 1) ? 0 : 1; 
            
            $conn->query("UPDATE users SET is_active = $new_status WHERE id = $toggle_id");
            header("Location: accounts.php?msg=toggle_success");
            exit();
        }
    }
}

// 3. BẮT THÔNG BÁO TỪ URL
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'add_success') {
        $msg = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i> Thêm tài khoản thành công!</div>";
    } elseif ($_GET['msg'] == 'del_success') {
        $msg = "<div class='alert alert-success'><i class='fa-solid fa-trash-can me-2'></i> Xóa tài khoản thành công!</div>";
    } elseif ($_GET['msg'] == 'err_self_delete') {
        $msg = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation me-2'></i> Lỗi: Bạn không thể tự xóa tài khoản đang đăng nhập của chính mình!</div>";
    } elseif ($_GET['msg'] == 'toggle_success') {
        $msg = "<div class='alert alert-success'><i class='fa-solid fa-power-off me-2'></i> Cập nhật trạng thái hoạt động thành công!</div>";
    } elseif ($_GET['msg'] == 'err_self_toggle') {
        $msg = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation me-2'></i> Lỗi: Bạn không thể tự khóa tài khoản của chính mình!</div>";
    }
}

// 4. LẤY DANH SÁCH ADMIN & NHÂN VIÊN
$sql_accounts = "SELECT * FROM users WHERE role_level IN (1, 2) ORDER BY role_level ASC, id DESC";
$result_accounts = $conn->query($sql_accounts);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Tài khoản - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
    
        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Phân quyền Hệ thống</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span>
                </div>
            </div>

            <div class="content p-3">
                <?php if($msg != "") echo $msg; ?>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 fw-bold text-primary">
                                <i class="fa-solid fa-user-plus me-2"></i>Cấp Tài Khoản Mới
                            </div>
                            <div class="card-body bg-light">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Họ và tên nhân sự <span class="text-danger">*</span></label>
                                        <input type="text" name="fullname" class="form-control" required placeholder="VD: Nguyễn Văn A">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Email đăng nhập <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" required placeholder="Email công việc">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Số điện thoại <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" class="form-control" required placeholder="SĐT liên hệ">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Mật khẩu khởi tạo <span class="text-danger">*</span></label>
                                        <input type="password" name="password" class="form-control" required placeholder="Nhập mật khẩu">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold small">Vai trò / Cấp bậc <span class="text-danger">*</span></label>
                                        <select name="role_level" class="form-select" required>
                                            <option value="2">Nhân viên (Có thể quản lý kho, đơn hàng)</option>
                                            <option value="1">Quản trị viên (Toàn quyền hệ thống)</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="btnAddAccount" class="btn btn-primary w-100 fw-bold">
                                        <i class="fa-solid fa-plus me-2"></i>Tạo Tài Khoản
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 fw-bold text-primary">
                                <i class="fa-solid fa-users-gear me-2"></i>Danh Sách Nhân Sự
                            </div>
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">STT</th>
                                            <th>Thông tin nhân sự</th>
                                            <th>Vai trò</th>
                                            <th>Trạng thái</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result_accounts->num_rows > 0) {
                                            $stt = 1;
                                            while($row = $result_accounts->fetch_assoc()) { 
                                        ?>
                                            <tr>
                                                <td class="ps-3 text-muted fw-bold"><?php echo $stt++; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <i class="fa-solid fa-circle-user fs-2 text-secondary"></i>
                                                        </div>
                                                        <div>
                                                            <span class="fw-bold text-dark"><?php echo $row['fullname']; ?></span><br>
                                                            <small class="text-muted"><i class="fa-solid fa-envelope me-1"></i><?php echo $row['email']; ?></small><br>
                                                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo $row['phone']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if($row['role_level'] == 1) { ?>
                                                        <span class="badge bg-danger text-white px-2 py-1"><i class="fa-solid fa-star me-1"></i> Quản trị viên</span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-primary text-white px-2 py-1"><i class="fa-solid fa-user-tie me-1"></i> Nhân viên</span>
                                                    <?php } ?>
                                                </td>
                                                
                                                <td>
                                                    <?php if ($row['is_active'] == 1) { ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="fa-solid fa-check me-1"></i>Hoạt động</span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="fa-solid fa-lock me-1"></i>Bị khóa</span>
                                                    <?php } ?>
                                                </td>

                                                <td class="text-center">
                                                    <?php if ($row['id'] == $current_admin_id) { ?>
                                                        <button class="btn btn-sm btn-secondary" disabled title="Bạn đang đăng nhập bằng tài khoản này">
                                                            <i class="fa-solid fa-user-check"></i> Đang dùng
                                                        </button>
                                                    <?php } else { ?>
                                                        
                                                        <?php if ($row['is_active'] == 1) { ?>
                                                            <a href="accounts.php?toggle_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Bạn có chắc chắn muốn KHÓA tài khoản này?');" title="Khóa">
                                                                <i class="fa-solid fa-lock"></i>
                                                            </a>
                                                        <?php } else { ?>
                                                            <a href="accounts.php?toggle_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Bạn muốn MỞ KHÓA tài khoản này?');" title="Mở khóa">
                                                                <i class="fa-solid fa-lock-open"></i>
                                                            </a>
                                                        <?php } ?>

                                                        <a href="accounts.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Bạn có chắc chắn muốn XÓA vĩnh viễn tài khoản này không?');" title="Xóa">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </a>

                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php 
                                            } 
                                        } 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div> 
            </div> 
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>