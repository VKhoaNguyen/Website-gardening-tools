<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

$page = 'receipts'; // Khai báo để Sidebar sáng đèn

// BẮT THÔNG BÁO TỪ TRANG TẠO PHIẾU TRUYỀN VỀ (NẾU CÓ)
$msg = "";
if (isset($_GET['msg']) && $_GET['msg'] == 'success') {
    $msg = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i> Nhập kho thành công! Số lượng sản phẩm đã được cộng dồn.</div>";
}

// XỬ LÝ KHI BẤM "HOÀN TẤT PHIẾU NHẬP"
if (isset($_GET['complete_id'])) {
    $receipt_id = (int)$_GET['complete_id'];
    
    // Kiểm tra phiếu nhập này có tồn tại và đang ở trạng thái Nháp (0) không
    $check_sql = "SELECT status FROM stock_receipts WHERE id = $receipt_id";
    $check_res = $conn->query($check_sql);
    
    if ($check_res->num_rows > 0) {
        $receipt = $check_res->fetch_assoc();
        if ($receipt['status'] == 0) {
            // 1. Cập nhật trạng thái thành Hoàn tất (1)
            $conn->query("UPDATE stock_receipts SET status = 1 WHERE id = $receipt_id");
            
            // 2. Lấy chi tiết phiếu nhập để cộng dồn số lượng vào kho
            $details_sql = "SELECT product_id, quantity FROM receipt_details WHERE receipt_id = $receipt_id";
            $details_res = $conn->query($details_sql);
            
            while ($detail = $details_res->fetch_assoc()) {
                $p_id = $detail['product_id'];
                $qty = $detail['quantity'];
                
                // Cộng dồn vào cột stock trong bảng products
                $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $p_id");
            }
            
            $msg = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i> Đã hoàn tất phiếu nhập #PN-" . str_pad($receipt_id, 5, '0', STR_PAD_LEFT) . " và cộng dồn số lượng vào kho thành công!</div>";
        }
    }
}

// LẤY DANH SÁCH LỊCH SỬ PHIẾU NHẬP
// Dùng JOIN để lấy tên Nhà cung cấp (từ bảng suppliers) và tên Người lập phiếu (từ bảng users)
$sql_receipts = "SELECT r.*, s.name as supplier_name, u.fullname as creator_name 
                 FROM stock_receipts r 
                 JOIN suppliers s ON r.supplier_id = s.id 
                 JOIN users u ON r.user_id = u.id 
                 ORDER BY r.id DESC";
$receipts = $conn->query($sql_receipts);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử Nhập Kho - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        
        <?php include 'sidebar.php'; ?>

        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Quản lý Nhập Kho</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if($msg != "") echo $msg; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-primary mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Lịch Sử Phiếu Nhập</h4>
                    
                    <a href="receipt_create.php" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                        <i class="fa-solid fa-plus-circle me-2"></i>Tạo Phiếu Nhập Mới
                    </a>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Mã Phiếu</th>
                                    <th>Ngày nhập</th>
                                    <th>Nhà cung cấp</th>
                                    <th>Người lập phiếu</th>
                                    <th>Tổng tiền nhập</th>
                                    <th>Trạng thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($receipts->num_rows > 0) {
                                    while($row = $receipts->fetch_assoc()) { 
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-primary">#PN-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                        <td class="fw-bold"><?php echo $row['supplier_name']; ?></td>
                                        <td><i class="fa-solid fa-user-tie text-muted me-1"></i> <?php echo $row['creator_name']; ?></td>
                                        <td class="text-danger fw-bold"><?php echo number_format($row['total_money'], 0, ',', '.'); ?> đ</td>
                                        
                                        <td>
                                            <?php if ($row['status'] == 1) { ?>
                                                <span class="badge bg-success"><i class="fa-solid fa-check-double"></i> Đã nhập kho</span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary"><i class="fa-solid fa-file-pen"></i> Bản nháp</span>
                                            <?php } ?>
                                        </td>

                                        <td class="text-center">
    <?php if ($row['status'] == 0) { ?>
        <a href="receipts.php?complete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Xác nhận HOÀN TẤT phiếu nhập này?\nHệ thống sẽ cộng dồn số lượng vào kho và không thể hoàn tác thao tác này!');">
            <i class="fa-solid fa-check"></i> Hoàn tất
        </a>
    <?php } ?>
    
    <a href="receipt_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="Xem chi tiết">
        <i class="fa-solid fa-eye"></i> Xem
    </a>
</td>
                                    </tr>
                                <?php 
                                    } 
                                } else {
                                    echo "<tr><td colspan='7' class='text-center text-muted py-5'>
                                            <i class='fa-solid fa-box-open fs-1 text-light mb-3 d-block'></i>
                                            Chưa có phiếu nhập kho nào trong hệ thống.
                                          </td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> 
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>