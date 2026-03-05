<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

$user_admin = $_SESSION['user'];

// ==========================================
// 1. TÍNH TOÁN CÁC CON SỐ THỐNG KÊ (DASHBOARD)
// ==========================================

// 1.1 Đơn hàng mới (Chờ duyệt - status = 0)
$sql_new_orders = "SELECT COUNT(*) as total FROM orders WHERE status = 0";
$new_orders_count = $conn->query($sql_new_orders)->fetch_assoc()['total'];

// 1.2 Doanh thu trong tháng hiện tại (Chỉ tính đơn đã Giao thành công - status = 2)
$current_month = date('m');
$current_year = date('Y');
$sql_revenue = "SELECT SUM(total_money) as revenue FROM orders 
                WHERE status = 2 AND MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'";
$revenue_result = $conn->query($sql_revenue)->fetch_assoc()['revenue'];
$monthly_revenue = $revenue_result ? $revenue_result : 0; // Nếu chưa bán được đồng nào thì gán bằng 0

// 1.3 Sản phẩm sắp hết hàng (Tồn kho dưới 10)
$sql_low_stock = "SELECT COUNT(*) as total FROM products WHERE stock < 10";
$low_stock_count = $conn->query($sql_low_stock)->fetch_assoc()['total'];

// 1.4 Tổng số lượng khách hàng (role_level = 0)
$sql_customers = "SELECT COUNT(*) as total FROM users WHERE role_level = 0";
$customers_count = $conn->query($sql_customers)->fetch_assoc()['total'];

// ==========================================
// 2. LẤY DANH SÁCH ĐƠN HÀNG CẦN XỬ LÝ GẤP
// ==========================================
// Lấy 10 đơn hàng mới nhất đang chờ duyệt (0) hoặc đang giao (1)
$sql_pending_orders = "SELECT orders.*, users.fullname, users.email 
                       FROM orders 
                       JOIN users ON orders.user_id = users.id 
                       WHERE orders.status IN (0, 1) 
                       ORDER BY orders.id DESC LIMIT 10";
$pending_orders = $conn->query($sql_pending_orders);

// ==========================================
// 3. LẤY DANH SÁCH SẢN PHẨM BÁN CHẠY (TOP 5)
// ==========================================
// Lấy 5 sản phẩm có số lượng bán (sold_count) cao nhất và lớn hơn 0
$sql_top_products = "SELECT id, name, image, price, sold_count FROM products WHERE sold_count > 0 ORDER BY sold_count DESC LIMIT 5";
$top_products = $conn->query($sql_top_products);


// Hàm hiển thị Badge trạng thái
function getDashboardBadge($status) {
    if($status == 0) return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock"></i> Chờ duyệt</span>';
    if($status == 1) return '<span class="badge bg-info text-dark"><i class="fa-solid fa-truck-fast"></i> Đang giao</span>';
    return '';
}

$page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <style>
    /* CSS cho các thẻ thống kê đẹp mắt */
    .stat-card {
        border-radius: 10px;
        color: white;
        padding: 20px;
        transition: transform 0.2s;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        position: relative; /* Thêm dòng này để nhốt icon lại */
        overflow: hidden; /* Thêm dòng này để icon không bị tràn ra ngoài viền */
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .bg-primary-custom { background: linear-gradient(45deg, #0d6efd, #0dcaf0); }
    .bg-success-custom { background: linear-gradient(45deg, #198754, #20c997); }
    .bg-warning-custom { background: linear-gradient(45deg, #ffc107, #fd7e14); }
    .bg-info-custom { background: linear-gradient(45deg, #0dcaf0, #0d6efd); }
</style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Xin chào, <?php echo $user_admin['fullname']; ?></h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> Quản trị viên</span>
                </div>
            </div>

            <div class="content">
                <h3 class="mb-4">Dashboard (Tổng quan)</h3>

                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary-custom">
                            <h5 class="fw-bold mb-1">Đơn hàng chờ duyệt</h5>
                            <h2 class="fw-bold mb-0"><?php echo $new_orders_count; ?></h2>
                            <i class="fa-solid fa-box-open fs-1 position-absolute" style="top: 20px; right: 20px; opacity: 0.3;"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success-custom">
                            <h5 class="fw-bold mb-1">Doanh thu tháng <?php echo $current_month; ?></h5>
                            <h2 class="fw-bold mb-0"><?php echo number_format($monthly_revenue, 0, ',', '.'); ?> đ</h2>
                            <i class="fa-solid fa-money-bill-trend-up fs-1 position-absolute" style="top: 20px; right: 20px; opacity: 0.3;"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning-custom text-dark">
                            <h5 class="fw-bold mb-1">Sản phẩm sắp hết</h5>
                            <h2 class="fw-bold mb-0"><?php echo $low_stock_count; ?></h2>
                            <i class="fa-solid fa-triangle-exclamation fs-1 position-absolute" style="top: 20px; right: 20px; opacity: 0.3;"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info-custom">
                            <h5 class="fw-bold mb-1">Tổng khách hàng</h5>
                            <h2 class="fw-bold mb-0"><?php echo $customers_count; ?></h2>
                            <i class="fa-solid fa-users fs-1 position-absolute" style="top: 20px; right: 20px; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-list-check me-2"></i>Đơn hàng cần xử lý</h5>
                            </div>
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã ĐH</th>
                                            <th>Khách hàng</th>
                                            <th>Ngày đặt</th>
                                            <th>Tổng tiền</th>
                                            <th>Trạng thái</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($pending_orders->num_rows > 0) {
                                            while($row = $pending_orders->fetch_assoc()) { 
                                        ?>
                                            <tr>
                                                <td class="fw-bold text-primary">#VX-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <span class="fw-bold"><?php echo $row['fullname']; ?></span><br>
                                                    <small class="text-muted"><?php echo $row['email']; ?></small>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                                <td class="text-danger fw-bold"><?php echo number_format($row['total_money'], 0, ',', '.'); ?> đ</td>
                                                <td><?php echo getDashboardBadge($row['status']); ?></td>
                                                <td class="text-center">
                                                    <a href="orders.php" class="btn btn-sm btn-outline-primary">Xử lý ngay <i class="fa-solid fa-arrow-right"></i></a>
                                                </td>
                                            </tr>
                                        <?php 
                                            } 
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center text-muted py-5'><i class='fa-regular fa-face-smile-wink fs-4 d-block mb-2'></i> Tuyệt vời! Bạn không có đơn hàng nào bị tồn đọng.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold text-danger"><i class="fa-solid fa-fire me-2"></i>Bán chạy nhất</h5>
                                <a href="products.php" class="btn btn-sm btn-link text-decoration-none">Xem tất cả</a>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php 
                                    if ($top_products->num_rows > 0) {
                                        while($prod = $top_products->fetch_assoc()) { 
                                    ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <div class="d-flex align-items-center" style="width: 75%;">
                                                <div class="me-3">
                                                    <div class="bg-light text-danger fw-bold text-center rounded px-2 py-1" style="font-size: 0.8rem;">
                                                        Top
                                                    </div>
                                                </div>
                                                <div class="text-truncate">
                                                    <span class="fw-bold" title="<?php echo $prod['name']; ?>"><?php echo $prod['name']; ?></span><br>
                                                    <span class="text-danger fw-bold" style="font-size: 0.9rem;"><?php echo number_format($prod['price'], 0, ',', '.'); ?> đ</span>
                                                </div>
                                            </div>
                                            <span class="badge bg-success rounded-pill px-3 py-2">
                                                Đã bán: <?php echo $prod['sold_count']; ?>
                                            </span>
                                        </li>
                                    <?php 
                                        } 
                                    } else {
                                        echo "<div class='text-center text-muted py-5'>Chưa có sản phẩm nào được bán ra.</div>";
                                    }
                                    ?>
                                </ul>
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