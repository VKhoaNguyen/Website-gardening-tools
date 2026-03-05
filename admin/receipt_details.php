<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

$page = 'receipts'; // Để Sidebar sáng đèn mục Nhập kho

// 1. KIỂM TRA ID PHIẾU NHẬP
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: receipts.php");
    exit();
}

$receipt_id = (int)$_GET['id'];

// 2. LẤY THÔNG TIN CHUNG CỦA PHIẾU NHẬP (Lấy thêm SĐT và Địa chỉ nhà cung cấp)
$sql_master = "SELECT r.*, s.name as supplier_name, s.phone as supplier_phone, s.address as supplier_address, u.fullname as creator_name 
               FROM stock_receipts r 
               JOIN suppliers s ON r.supplier_id = s.id 
               JOIN users u ON r.user_id = u.id 
               WHERE r.id = $receipt_id";
$result_master = $conn->query($sql_master);

if ($result_master->num_rows == 0) {
    echo "<script>alert('Không tìm thấy phiếu nhập này!'); window.location.href='receipts.php';</script>";
    exit();
}
$receipt = $result_master->fetch_assoc();

// 3. LẤY DANH SÁCH SẢN PHẨM TRONG PHIẾU NHẬP
$sql_details = "SELECT d.*, p.name as product_name 
                FROM receipt_details d 
                JOIN products p ON d.product_id = p.id 
                WHERE d.receipt_id = $receipt_id";
$details = $conn->query($sql_details);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Phiếu Nhập #PN-<?php echo str_pad($receipt_id, 5, '0', STR_PAD_LEFT); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <style>
        /* CSS Ẩn Sidebar và các nút bấm khi bấm In (Ctrl + P) */
        @media print {
            .sidebar, .topbar, .btn-print, .btn-back { display: none !important; }
            .content { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .card { border: none !important; box-shadow: none !important; }
            body { background-color: #fff !important; }
        }
        .invoice-title { letter-spacing: 2px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="d-flex">
        
        <?php include 'sidebar.php'; ?>

        <div class="w-100 content-wrapper">
            <div class="topbar d-flex justify-content-between align-items-center p-3 border-bottom">
                <h5 class="mb-0 text-muted">Chi tiết Phiếu Nhập Kho</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span>
                </div>
            </div>

            <div class="content p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="receipts.php" class="btn btn-outline-secondary btn-back">
                        <i class="fa-solid fa-arrow-left me-2"></i> Quay lại danh sách
                    </a>
                    <button onclick="window.print()" class="btn btn-primary btn-print shadow-sm">
                        <i class="fa-solid fa-print me-2"></i> In Phiếu Nhập
                    </button>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-5">
                        
                        <div class="row border-bottom pb-4 mb-4">
                            <div class="col-sm-6">
                                <h2 class="fw-bold text-success mb-0"><i class="fa-solid fa-leaf me-2"></i>VƯỜN XANH</h2>
                                <p class="text-muted mt-2 mb-0">Hệ thống quản lý vật tư & cây cảnh</p>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <h3 class="fw-bold text-primary invoice-title">PHIẾU NHẬP KHO</h3>
                                <h5 class="fw-bold text-dark">#PN-<?php echo str_pad($receipt_id, 5, '0', STR_PAD_LEFT); ?></h5>
                                <div class="mt-2">
                                    <?php if ($receipt['status'] == 1) { ?>
                                        <span class="badge bg-success fs-6"><i class="fa-solid fa-check-double"></i> Đã hoàn tất nhập kho</span>
                                    <?php } else { ?>
                                        <span class="badge bg-secondary fs-6"><i class="fa-solid fa-file-pen"></i> Bản nháp (Chưa cập nhật kho)</span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-sm-6">
                                <h6 class="fw-bold text-muted mb-3">THÔNG TIN NHÀ CUNG CẤP:</h6>
                                <h5 class="fw-bold"><?php echo $receipt['supplier_name']; ?></h5>
                                <p class="mb-1"><i class="fa-solid fa-phone text-muted me-2"></i> SĐT: <?php echo $receipt['supplier_phone']; ?></p>
                                <p class="mb-1"><i class="fa-solid fa-location-dot text-muted me-2"></i> Địa chỉ: <?php echo empty($receipt['supplier_address']) ? 'Chưa cập nhật' : $receipt['supplier_address']; ?></p>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <h6 class="fw-bold text-muted mb-3">THÔNG TIN CHỨNG TỪ:</h6>
                                <p class="mb-1"><strong>Ngày lập phiếu:</strong> <?php echo date('d/m/Y H:i', strtotime($receipt['created_at'])); ?></p>
                                <p class="mb-1"><strong>Nhân viên lập:</strong> <?php echo $receipt['creator_name']; ?></p>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered border-dark align-middle">
                                <thead class="table-light border-dark text-center">
                                    <tr>
                                        <th width="5%">STT</th>
                                        <th width="45%" class="text-start">Tên hàng hóa / Sản phẩm</th>
                                        <th width="15%">Đơn giá nhập</th>
                                        <th width="15%">Số lượng</th>
                                        <th width="20%" class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($details->num_rows > 0) {
                                        $stt = 1;
                                        while($item = $details->fetch_assoc()) { 
                                            $thanh_tien = $item['quantity'] * $item['import_price'];
                                    ?>
                                        <tr>
                                            <td class="text-center"><?php echo $stt++; ?></td>
                                            <td class="fw-bold"><?php echo $item['product_name']; ?></td>
                                            <td class="text-center"><?php echo number_format($item['import_price'], 0, ',', '.'); ?> đ</td>
                                            <td class="text-center fw-bold"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end text-danger fw-bold"><?php echo number_format($thanh_tien, 0, ',', '.'); ?> đ</td>
                                        </tr>
                                    <?php 
                                        } 
                                    } 
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold fs-6">TỔNG TIỀN THANH TOÁN:</td>
                                        <td class="text-end fw-bold fs-5 text-danger"><?php echo number_format($receipt['total_money'], 0, ',', '.'); ?> đ</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row mt-4 pt-2">
                            <div class="col-sm-6">
                                <h6 class="fw-bold">Ghi chú:</h6>
                                <p class="text-muted fst-italic">
                                    <?php echo empty($receipt['note']) ? 'Không có ghi chú thêm cho phiếu nhập này.' : $receipt['note']; ?>
                                </p>
                            </div>
                            <div class="col-sm-6 text-center">
                                <div class="row">
                                    <div class="col-6">
                                        <h6 class="fw-bold mb-5">Người giao hàng</h6>
                                        <p class="text-muted fst-italic mt-5">(Ký, ghi rõ họ tên)</p>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="fw-bold mb-5">Thủ kho / Người lập</h6>
                                        <p class="text-muted fst-italic mt-5"><?php echo $receipt['creator_name']; ?></p>
                                    </div>
                                </div>
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