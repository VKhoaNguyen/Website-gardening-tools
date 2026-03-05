<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

$page = 'suppliers'; // Để sidebar sáng đèn (nếu bạn có mục này)
$msg = "";

// 1. XỬ LÝ THÊM NHÀ CUNG CẤP MỚI
if (isset($_POST['btnAdd'])) {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $address = $conn->real_escape_string(trim($_POST['address']));

    $sql_insert = "INSERT INTO suppliers (name, phone, email, address) VALUES ('$name', '$phone', '$email', '$address')";
    if ($conn->query($sql_insert)) {
        $msg = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i> Đã thêm nhà cung cấp mới thành công!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
    }
}

// 2. XỬ LÝ BẬT/TẮT TRẠNG THÁI GIAO DỊCH (Tương tự như khóa khách hàng)
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    
    // Lấy trạng thái hiện tại
    $res = $conn->query("SELECT is_active FROM suppliers WHERE id = $id");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        // Đảo ngược trạng thái
        $new_status = ($row['is_active'] == 1) ? 0 : 1; 
        
        $conn->query("UPDATE suppliers SET is_active = $new_status WHERE id = $id");
        
        $status_text = ($new_status == 1) ? "mở lại giao dịch" : "ngừng giao dịch";
        header("Location: suppliers.php?msg=toggled&action=$status_text");
        exit();
    }
}

// Bắt thông báo từ URL khi Toggle
if (isset($_GET['msg']) && $_GET['msg'] == 'toggled') {
    $action = $_GET['action'];
    $msg = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i> Đã $action với nhà cung cấp thành công!</div>";
}

// 3. LẤY DANH SÁCH NHÀ CUNG CẤP
$sql_suppliers = "SELECT * FROM suppliers ORDER BY id DESC";
$suppliers = $conn->query($sql_suppliers);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Nhà cung cấp - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3 border-bottom">
                <h5 class="mb-0 text-muted">Quản lý Đối tác & Nhà cung cấp</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span>
                </div>
            </div>

            <div class="content p-4">
                <?php if($msg != "") echo $msg; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-primary mb-0"><i class="fa-solid fa-handshake me-2"></i>Danh Sách Nhà Cung Cấp</h4>
                    <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                        <i class="fa-solid fa-plus-circle me-2"></i>Thêm Mới
                    </button>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th width="25%">Tên Nhà cung cấp</th>
                                    <th>Thông tin liên hệ</th>
                                    <th width="25%">Địa chỉ</th>
                                    <th>Trạng thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($suppliers->num_rows > 0) {
                                    while($row = $suppliers->fetch_assoc()) { 
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted">#<?php echo $row['id']; ?></td>
                                        <td class="fw-bold text-dark"><?php echo $row['name']; ?></td>
                                        <td>
                                            <div style="font-size: 0.9rem;">
                                                <i class="fa-solid fa-phone text-muted"></i> <?php echo $row['phone']; ?><br>
                                                <?php if(!empty($row['email'])) { ?>
                                                    <i class="fa-solid fa-envelope text-muted"></i> <?php echo $row['email']; ?>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        <td><small><?php echo $row['address']; ?></small></td>
                                        
                                        <td>
                                            <?php if ($row['is_active'] == 1) { ?>
                                                <span class="badge bg-success"><i class="fa-solid fa-check"></i> Đang giao dịch</span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary"><i class="fa-solid fa-ban"></i> Đã ngừng</span>
                                            <?php } ?>
                                        </td>

                                        <td class="text-center">
                                            <?php if ($row['is_active'] == 1) { ?>
                                                <a href="suppliers.php?toggle_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Bạn có chắc chắn muốn NGỪNG giao dịch với nhà cung cấp này?');" title="Ngừng giao dịch">
                                                    <i class="fa-solid fa-power-off"></i> Tạm ngưng
                                                </a>
                                            <?php } else { ?>
                                                <a href="suppliers.php?toggle_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('MỞ LẠI giao dịch với nhà cung cấp này?');" title="Mở lại giao dịch">
                                                    <i class="fa-solid fa-rotate-right"></i> Mở lại
                                                </a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php 
                                    } 
                                } else {
                                    echo "<tr><td colspan='6' class='text-center text-muted py-5'>Chưa có nhà cung cấp nào.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> 
        </div>
    </div>

    <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>Thêm Nhà Cung Cấp Mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên công ty / Đối tác <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="VD: Công ty TNHH Hạt Giống Xanh">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required placeholder="0912345678">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="lienhe@hatgiong.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Số nhà, Tên đường..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="btnAdd" class="btn btn-primary fw-bold">Lưu Thông Tin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>