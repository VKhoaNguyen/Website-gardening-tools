<?php
session_start();
require_once 'config/connect.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập để xem lịch sử mua hàng!'); window.location.href='login.php';</script>";
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// 2. XỬ LÝ BỘ LỌC TÌM KIẾM
$search = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';
$time_filter = isset($_GET['time']) ? $_GET['time'] : 'all';

$where_clause = "o.user_id = $user_id";

// Lọc theo khoảng thời gian
if ($time_filter == '30days') {
    $where_clause .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($time_filter == '6months') {
    $where_clause .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($time_filter == '1year') {
    $where_clause .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

// Lọc theo mã đơn hàng hoặc tên sản phẩm
if ($search != '') {
    $search_id = (int)str_replace(['VX-', '#'], '', $search);
    $where_clause .= " AND (o.id = '$search_id' OR p.name LIKE '%$search%')";
}

// 3. LẤY DANH SÁCH ĐƠN HÀNG (Kết hợp bảng Promotions để lấy mã KM và Group By để không bị trùng lặp khi search SP)
$sql_orders = "SELECT o.*, pr.code as promo_code 
               FROM orders o 
               LEFT JOIN promotions pr ON o.promotion_id = pr.id
               LEFT JOIN order_details od ON o.id = od.order_id
               LEFT JOIN products p ON od.product_id = p.id
               WHERE $where_clause 
               GROUP BY o.id 
               ORDER BY o.id DESC";
$result_orders = $conn->query($sql_orders);

// Hàm tạo Badge Trạng thái rút gọn
function getStatusBadge($status) {
    switch($status) {
        case 0: return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock"></i> Chờ duyệt</span>';
        case 1: return '<span class="badge bg-info text-dark"><i class="fa-solid fa-truck-fast"></i> Đang giao</span>';
        case 2: return '<span class="badge bg-success"><i class="fa-solid fa-check-circle"></i> Hoàn thành</span>';
        case 3: return '<span class="badge bg-danger"><i class="fa-solid fa-times-circle"></i> Đã hủy</span>';
        default: return '<span class="badge bg-secondary">Không rõ</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch Sử Đơn Hàng - Vườn Xanh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .accordion-button:not(.collapsed) { background-color: #e8f5e9; color: #198754; }
        
        /* CSS CHO THANH TIẾN ĐỘ GIAO HÀNG (TRACKING PROGRESS BAR) */
        .track { position: relative; background-color: #ddd; height: 4px; display: flex; margin-bottom: 40px; margin-top: 25px; border-radius: 2px; }
        .track .step { flex-grow: 1; width: 33%; margin-top: -12px; text-align: center; position: relative; }
        .track .step::before { height: 4px; position: absolute; content: ""; width: 100%; left: 0; top: 12px; background-color: #ddd; z-index: 0; }
        .track .step.active::before { background-color: #198754; }
        .track .step .icon { display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; position: relative; border-radius: 50%; background: #ddd; color: #fff; z-index: 1; }
        .track .step.active .icon { background: #198754; }
        .track .step .text { display: block; margin-top: 8px; font-size: 0.85rem; color: #6c757d; }
        .track .step.active .text { color: #198754; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-success mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-leaf"></i> VƯỜN XANH</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3"><i class="fa-solid fa-user-circle"></i> <?php echo $user['fullname']; ?></span>
                <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-house"></i> Trang chủ</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <h3 class="mb-4 text-success border-bottom pb-2"><i class="fa-solid fa-clock-rotate-left me-2"></i>Lịch Sử Mua Hàng Của Bạn</h3>

        <form method="GET" action="orders_history.php" class="row g-2 mb-4 bg-white p-3 rounded shadow-sm border">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Nhập mã đơn hàng hoặc Tên sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select name="time" class="form-select">
                    <option value="all" <?php echo $time_filter=='all'?'selected':'';?>>Tất cả thời gian</option>
                    <option value="30days" <?php echo $time_filter=='30days'?'selected':'';?>>Trong 30 ngày qua</option>
                    <option value="6months" <?php echo $time_filter=='6months'?'selected':'';?>>Trong 6 tháng qua</option>
                    <option value="1year" <?php echo $time_filter=='1year'?'selected':'';?>>Trong 1 năm qua</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-magnifying-glass me-1"></i> Tìm đơn</button>
                <a href="orders_history.php" class="btn btn-outline-secondary" title="Bỏ lọc"><i class="fa-solid fa-rotate-right"></i></a>
            </div>
        </form>

        <?php if ($result_orders->num_rows > 0) { ?>
            <div class="accordion shadow-sm" id="accordionOrders">
                
                <?php 
                while($order = $result_orders->fetch_assoc()) { 
                    $order_id = $order['id'];
                    $formatted_id = str_pad($order_id, 5, '0', STR_PAD_LEFT);
                ?>
                    <div class="accordion-item mb-3 border rounded">
                        <h2 class="accordion-header" id="heading<?php echo $order_id; ?>">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $order_id; ?>">
                                <div class="w-100 d-flex justify-content-between align-items-center me-3">
                                    <div>
                                        <strong class="fs-5 text-dark">#VX-<?php echo $formatted_id; ?></strong>
                                        <span class="text-muted ms-2" style="font-size: 0.9rem;">
                                            <i class="fa-regular fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-danger me-3 fs-5"><?php echo number_format($order['total_money'], 0, ',', '.'); ?> đ</strong>
                                        <?php echo getStatusBadge($order['status']); ?>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $order_id; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionOrders">
                            <div class="accordion-body bg-white border-top">
                                
                                <?php if($order['status'] != 3) { ?>
                                    <div class="track">
                                        <div class="step active"> 
                                            <span class="icon"><i class="fa-solid fa-check"></i></span> 
                                            <span class="text">Chờ duyệt</span> 
                                        </div>
                                        <div class="step <?php echo ($order['status'] >= 1) ? 'active' : ''; ?>"> 
                                            <span class="icon"><i class="fa-solid fa-truck-fast"></i></span> 
                                            <span class="text">Đang giao hàng</span> 
                                        </div>
                                        <div class="step <?php echo ($order['status'] == 2) ? 'active' : ''; ?>"> 
                                            <span class="icon"><i class="fa-solid fa-box-open"></i></span> 
                                            <span class="text">Thành công</span> 
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <div class="alert alert-danger text-center py-2 mb-4"><i class="fa-solid fa-ban me-2"></i>Đơn hàng này đã bị hủy.</div>
                                <?php } ?>

                                <?php
                                $sql_details = "SELECT od.*, p.name, p.image 
                                                FROM order_details od 
                                                JOIN products p ON od.product_id = p.id 
                                                WHERE od.order_id = $order_id";
                                $result_details = $conn->query($sql_details);
                                ?>
                                
                                <h6 class="text-muted mb-3 border-bottom pb-2">Danh sách sản phẩm:</h6>
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <tbody>
                                        <?php while($item = $result_details->fetch_assoc()) { ?>
                                            <tr>
                                                <td width="70">
                                                    <?php if($item['image'] != '') { ?>
                                                        <img src="<?php echo $item['image']; ?>" class="rounded" style="width: 60px; height: 60px; object-fit: cover; border: 1px solid #ddd;">
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <span class="fw-bold d-block text-dark"><?php echo $item['name']; ?></span>
                                                    <?php if($order['status'] == 2) { ?>
                                                        <a href="#" class="btn btn-sm btn-outline-warning mt-1 py-0" style="font-size: 0.75rem;"><i class="fa-regular fa-star"></i> Đánh giá</a>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center text-muted">x <?php echo $item['quantity']; ?></td>
                                                <td class="text-end text-danger fw-bold"><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>

                                <div class="bg-light p-3 rounded mt-3">
                                    <div class="row text-muted" style="font-size: 0.95rem;">
                                        <div class="col-md-7 border-end">
                                            <strong class="text-dark"><i class="fa-solid fa-location-dot text-primary me-1"></i> Giao đến:</strong><br>
                                            <span class="d-inline-block mt-1"><?php echo $order['shipping_address']; ?></span>
                                        </div>
                                        <div class="col-md-5 ps-md-4 mt-3 mt-md-0">
                                            <strong class="text-dark"><i class="fa-solid fa-money-check-dollar text-success me-1"></i> Thông tin thanh toán:</strong><br>
                                            <div class="mt-1">Hình thức: <?php echo $order['payment_method']; ?></div>
                                            
                                            <?php if(!empty($order['promo_code'])) { ?>
                                                <div class="text-success mt-1">
                                                    <i class="fa-solid fa-ticket-simple"></i> Mã áp dụng: <b><?php echo $order['promo_code']; ?></b>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end align-items-center mt-3 pt-3 border-top gap-2">
                                    <?php if($order['status'] == 0) { ?>
                                        <a href="cancel_order.php?id=<?php echo $order_id; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');">
                                            <i class="fa-solid fa-trash-can"></i> Hủy đơn hàng
                                        </a>
                                    <?php } ?>
                                    
                                    <a href="reorder.php?id=<?php echo $order_id; ?>" class="btn btn-success btn-sm px-3">
                                        <i class="fa-solid fa-cart-shopping me-1"></i> Mua lại
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php } ?>

            </div>

        <?php } else { ?>
            <div class="text-center py-5 bg-white rounded shadow-sm border">
                <i class="fa-solid fa-box-open text-muted opacity-50" style="font-size: 80px;"></i>
                <h4 class="mt-4 text-muted">Bạn chưa có đơn hàng nào hoặc không tìm thấy kết quả</h4>
                <p>Hãy tìm kiếm với từ khóa khác hoặc quay lại cửa hàng nhé!</p>
                <a href="index.php" class="btn btn-success mt-3 px-4"><i class="fa-solid fa-bag-shopping me-2"></i>Khám phá sản phẩm</a>
            </div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>