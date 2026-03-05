<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

// 1. XỬ LÝ XÓA KHÁCH HÀNG (Lưu ý: Chỉ xóa user có role_level = 0 để tránh vô tình xóa nhầm Admin)
// 1. XỬ LÝ KHÓA / MỞ KHÓA KHÁCH HÀNG
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    
    // Lấy trạng thái hiện tại của khách
    $res = $conn->query("SELECT is_active FROM users WHERE id = $id AND role_level = 0");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        // Đảo ngược trạng thái: Đang 1 thì thành 0, đang 0 thì thành 1
        $new_status = ($row['is_active'] == 1) ? 0 : 1; 
        
        $conn->query("UPDATE users SET is_active = $new_status WHERE id = $id");
    }
    header("Location: customers.php");
    exit();
}

// 2. LẤY DANH SÁCH KHÁCH HÀNG
$sql_customers = "SELECT * FROM users WHERE role_level = 0 ORDER BY id DESC";
$customers = $conn->query($sql_customers);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Khách hàng - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Quản lý Khách hàng</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> Admin</span>
                </div>
            </div>

            <div class="content">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-primary">Danh Sách Khách Hàng Đăng Ký</h5>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>STT</th>
                                    <th>Họ và Tên</th>
                                    <th>Email</th>
                                    <th>Số điện thoại</th>
                                    <th width="20%">Địa chỉ</th>
<th>Chi tiêu & Điểm</th>
<th>Ngày đăng ký</th>
                              
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($customers->num_rows > 0) {
                                    $stt = 1;
                                    while($row = $customers->fetch_assoc()) { 
                                ?>
                                    <tr>
                                        <td class="text-center text-muted fw-bold"><?php echo $stt++; ?></td>
                                        <td class="fw-bold text-primary"><?php echo $row['fullname']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo $row['phone']; ?></td>
                                        <td><?php echo $row['address']; ?></td>
<td>
    <span class="text-danger fw-bold"><?php echo number_format($row['total_spent'], 0, ',', '.'); ?> đ</span><br>
    <span class="badge bg-warning text-dark mt-1"><i class="fa-solid fa-coins"></i> <?php echo $row['p_coin']; ?> điểm</span>
</td>
<td><?php echo isset($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : 'N/A'; ?></td>
                                        <td class="text-center">
    <?php if ($row['is_active'] == 1) { ?>
        <a href="customers.php?toggle_status=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Bạn có muốn KHÓA tài khoản khách hàng này? Họ sẽ không thể đăng nhập mua hàng nữa.');">
            <i class="fa-solid fa-lock"></i> Khóa
        </a>
    <?php } else { ?>
        <a href="customers.php?toggle_status=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Bạn có muốn MỞ KHÓA tài khoản này?');">
            <i class="fa-solid fa-lock-open"></i> Mở khóa
        </a>
    <?php } ?>
</td>
                                    </tr>
                                <?php 
                                    } 
                                } else {
                                    echo "<tr><td colspan='8' class='text-center text-muted py-4'>Chưa có khách hàng nào đăng ký.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> 
        </div>
    </div>
</body>
</html>