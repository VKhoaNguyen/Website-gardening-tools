<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

$msg = "";
$current_employee_id = $_SESSION['user']['id'];

// 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
if (isset($_POST['btnUpdateStatus'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = (int)$_POST['status'];
    
    // LẤY THÔNG TIN CŨ: Lấy thêm used_coin và earned_coin để xử lý hoàn xu/tặng xu
    $res_old = $conn->query("SELECT status, user_id, total_money, payment_method, used_coin, earned_coin FROM orders WHERE id = $order_id");
    $order_info = $res_old->fetch_assoc();
    
    $old_status = $order_info['status'];
    $user_id = $order_info['user_id'];
    $total_money = $order_info['total_money'];
    $payment_method = $order_info['payment_method'];
    $used_coin = $order_info['used_coin'];
    $earned_coin = $order_info['earned_coin'];

    if ($new_status != $old_status) {
        $update_extra = ""; // Chuỗi chứa các cột cần update thêm

        // NGHIỆP VỤ 1: KHO BÃI & HOÀN XU (Hủy đơn / Phục hồi đơn)
        if ($new_status == 3 && $old_status != 3) {
            // Hủy -> Hoàn trả lại kho (Gộp gọn thành 1 lệnh SQL)
            $conn->query("UPDATE products p JOIN order_details od ON p.id = od.product_id SET p.stock = p.stock + od.quantity WHERE od.order_id = $order_id");
            
            // Hoàn lại Xu khách đã xài cho đơn này
            if ($used_coin > 0) $conn->query("UPDATE users SET p_coin = p_coin + $used_coin WHERE id = $user_id");
            
            $msg = "<div class='alert alert-warning'>Đã hủy đơn hàng #VX-".str_pad($order_id, 5, '0', STR_PAD_LEFT).", hoàn trả kho và hoàn lại xu.</div>";
        } 
        elseif ($old_status == 3 && $new_status != 3) {
            // Phục hồi -> Trừ lại kho
            $conn->query("UPDATE products p JOIN order_details od ON p.id = od.product_id SET p.stock = p.stock - od.quantity WHERE od.order_id = $order_id");
            
            // Trừ lại Xu (vì khôi phục lại đơn)
            if ($used_coin > 0) $conn->query("UPDATE users SET p_coin = p_coin - $used_coin WHERE id = $user_id");
            
            $msg = "<div class='alert alert-success'>Phục hồi đơn hàng #VX-".str_pad($order_id, 5, '0', STR_PAD_LEFT)." thành công.</div>";
        }

        // NGHIỆP VỤ 2: HOÀN THÀNH ĐƠN (Giao hàng thành công & Thu tiền)
        if ($new_status == 2 && $old_status != 2) {
            $update_extra = ", is_paid = 1, paid_at = CURRENT_TIMESTAMP, delivered_at = CURRENT_TIMESTAMP";

            // Cộng tổng chi tiêu và Điểm thưởng (earned_coin) vào ví khách hàng
            $conn->query("UPDATE users SET total_spent = total_spent + $total_money, p_coin = p_coin + $earned_coin WHERE id = $user_id");

            // Tăng số lượng đã bán (sold_count) cho sản phẩm
            $conn->query("UPDATE products p JOIN order_details od ON p.id = od.product_id SET p.sold_count = p.sold_count + od.quantity WHERE od.order_id = $order_id");
        }

        // NGHIỆP VỤ 3: QUAY XE (Lỡ tay bấm Hoàn thành, giờ sửa lại)
        if ($old_status == 2 && $new_status != 2) {
            // Gỡ trạng thái thanh toán
            if ($payment_method == 'COD') {
                $update_extra = ", is_paid = 0, paid_at = NULL, delivered_at = NULL";
            } else {
                $update_extra = ", delivered_at = NULL"; 
            }

            // Thu hồi điểm thưởng và tổng chi tiêu
            $conn->query("UPDATE users SET total_spent = total_spent - $total_money, p_coin = p_coin - $earned_coin WHERE id = $user_id");

            // Trừ lại số lượng đã bán
            $conn->query("UPDATE products p JOIN order_details od ON p.id = od.product_id SET p.sold_count = p.sold_count - od.quantity WHERE od.order_id = $order_id");
        }

        // CẬP NHẬT TRẠNG THÁI VÀ NGƯỜI XỬ LÝ VÀO DATABASE
        $sql_update = "UPDATE orders SET status = $new_status, employee_id = $current_employee_id $update_extra WHERE id = $order_id";
        if ($conn->query($sql_update)) {
            if (empty($msg)) $msg = "<div class='alert alert-success'>Cập nhật trạng thái đơn hàng #VX-".str_pad($order_id, 5, '0', STR_PAD_LEFT)." thành công!</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi cập nhật: " . $conn->error . "</div>";
        }
    }
}

// 2. XỬ LÝ BỘ LỌC TÌM KIẾM (FILTER)
$where_clauses = ["1=1"]; // Điều kiện mặc định luôn đúng

if (isset($_GET['search_id']) && trim($_GET['search_id']) != '') {
    $search_id = (int)str_replace(['VX-', 'vx-', '#'], '', trim($_GET['search_id']));
    $where_clauses[] = "orders.id = $search_id";
}
if (isset($_GET['from_date']) && trim($_GET['from_date']) != '') {
    $from = $conn->real_escape_string($_GET['from_date']) . " 00:00:00";
    $where_clauses[] = "orders.created_at >= '$from'";
}
if (isset($_GET['to_date']) && trim($_GET['to_date']) != '') {
    $to = $conn->real_escape_string($_GET['to_date']) . " 23:59:59";
    $where_clauses[] = "orders.created_at <= '$to'";
}
if (isset($_GET['status_filter']) && trim($_GET['status_filter']) != '') {
    $st = (int)$_GET['status_filter'];
    $where_clauses[] = "orders.status = $st";
}

// Nối các điều kiện lại với nhau bằng AND
$where_sql = implode(' AND ', $where_clauses);

// 3. LẤY DANH SÁCH ĐƠN HÀNG ĐÃ LỌC
$sql_orders = "SELECT orders.*, kh.email, nv.fullname AS employee_name 
               FROM orders 
               JOIN users kh ON orders.user_id = kh.id 
               LEFT JOIN users nv ON orders.employee_id = nv.id 
               WHERE $where_sql
               ORDER BY orders.id DESC";
$result_orders = $conn->query($sql_orders);

// Hàm tạo Badge màu sắc cho Trạng thái giao hàng
function getAdminStatusBadge($status) {
    switch($status) {
        case 0: return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock"></i> Chờ duyệt</span>';
        case 1: return '<span class="badge bg-info text-dark"><i class="fa-solid fa-truck-fast"></i> Đang giao</span>';
        case 2: return '<span class="badge bg-success"><i class="fa-solid fa-check-circle"></i> Hoàn thành</span>';
        case 3: return '<span class="badge bg-danger"><i class="fa-solid fa-times-circle"></i> Đã hủy</span>';
        default: return '<span class="badge bg-secondary">Không rõ</span>';
    }
}

$page = 'orders';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn hàng - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Quản lý Đơn Hàng</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span>
                </div>
            </div>

            <div class="content p-3">
                <?php if($msg != "") echo $msg; ?>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body bg-light">
                        <form method="GET" action="orders.php" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Tìm mã đơn hàng</label>
                                <input type="text" name="search_id" class="form-control" placeholder="VD: VX-00001" value="<?php echo isset($_GET['search_id']) ? $_GET['search_id'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Từ ngày</label>
                                <input type="date" name="from_date" class="form-control" value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Đến ngày</label>
                                <input type="date" name="to_date" class="form-control" value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : ''; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Trạng thái</label>
                                <select name="status_filter" class="form-select">
                                    <option value="">Tất cả</option>
                                    <option value="0" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == '0') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                    <option value="1" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == '1') ? 'selected' : ''; ?>>Đang giao</option>
                                    <option value="2" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == '2') ? 'selected' : ''; ?>>Hoàn thành</option>
                                    <option value="3" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == '3') ? 'selected' : ''; ?>>Đã hủy</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i> Lọc</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <span class="fw-bold text-primary"><i class="fa-solid fa-list-check me-2"></i>Danh Sách Đơn Hàng</span>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Mã ĐH</th>
                                    <th width="25%">Người nhận / Giao hàng</th>
                                    <th>Thanh toán</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th class="text-center">Thao tác / Cập nhật</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result_orders->num_rows > 0) {
                                    while($row = $result_orders->fetch_assoc()) { 
                                        $order_id = $row['id'];
                                ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-primary">
                                            #VX-<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?><br>
                                            <small class="text-muted fw-normal"><i class="fa-regular fa-calendar-days"></i> <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.9rem;">
                                                <b><i class="fa-solid fa-user"></i> <?php echo isset($row['recipient_name']) && $row['recipient_name'] != '' ? $row['recipient_name'] : 'Khách hàng'; ?></b> 
                                                - <i class="fa-solid fa-phone"></i> <?php echo isset($row['recipient_phone']) ? $row['recipient_phone'] : ''; ?><br>
                                                <i class="fa-solid fa-location-dot text-danger"></i> <?php echo $row['shipping_address']; ?><br>
                                                <?php if(!empty($row['note'])) { ?>
                                                    <span class="text-warning"><i class="fa-solid fa-note-sticky"></i> Ghi chú: <?php echo $row['note']; ?></span>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-wallet text-primary"></i> <?php echo ($row['payment_method'] == 'COD') ? 'Thanh toán khi nhận hàng' : $row['payment_method']; ?></span><br>
                                            
                                            <?php if(isset($row['is_paid']) && $row['is_paid'] == 1) { ?>
                                                <span class="badge bg-success-subtle text-success mt-1"><i class="fa-solid fa-check"></i> Đã thanh toán</span>
                                            <?php } else { ?>
                                                <span class="badge bg-danger-subtle text-danger mt-1"><i class="fa-solid fa-xmark"></i> Chưa thanh toán</span>
                                            <?php } ?>
                                        </td>

                                        <td class="text-danger fw-bold"><?php echo number_format($row['total_money'], 0, ',', '.'); ?> đ</td>
                                        
                                       <td>
    <?php echo getAdminStatusBadge($row['status']); ?>
    <?php if (!empty($row['employee_name'])) { ?>
        <div class="mt-2" style="font-size: 0.8rem; color: #6c757d;">
            <i class="fa-solid fa-user-check"></i> Duyệt bởi: <b><?php echo $row['employee_name']; ?></b>
        </div>
    <?php } ?>
</td>
                                        
                                        <td>
                                            <div class="d-flex flex-column align-items-center gap-2 pe-3">
                                                <button class="btn btn-sm btn-outline-info w-100" type="button" data-bs-toggle="collapse" data-bs-target="#details<?php echo $order_id; ?>">
                                                    <i class="fa-solid fa-eye"></i> Xem SP
                                                </button>
                                                
                                                <form method="POST" action="orders.php" class="d-flex w-100">
                                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                                    <select name="status" class="form-select form-select-sm me-1" <?php echo ($row['status'] == 3) ? 'disabled' : ''; ?>>
                                                        <option value="0" <?php echo ($row['status'] == 0) ? 'selected' : ''; ?>>Chờ duyệt</option>
                                                        <option value="1" <?php echo ($row['status'] == 1) ? 'selected' : ''; ?>>Đang giao</option>
                                                        <option value="2" <?php echo ($row['status'] == 2) ? 'selected' : ''; ?>>Hoàn thành</option>
                                                        <option value="3" <?php echo ($row['status'] == 3) ? 'selected' : ''; ?>>Hủy đơn</option>
                                                    </select>
                                                    <button type="submit" name="btnUpdateStatus" class="btn btn-sm btn-primary" <?php echo ($row['status'] == 3) ? 'disabled' : ''; ?>>Lưu</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="collapse bg-light" id="details<?php echo $order_id; ?>">
                                        <td colspan="6" class="p-3 border-bottom">
                                            <h6 class="text-primary mb-2"><i class="fa-solid fa-box-open me-2"></i>Các sản phẩm trong đơn hàng:</h6>
                                            <table class="table table-sm table-bordered bg-white mb-0">
                                                
                                            <thead class="table-secondary text-center">
                                                    <tr>
                                                        <th width="70">Hình ảnh</th>
                                                        <th class="text-start">Tên sản phẩm</th>
                                                        <th>Đơn giá mua</th>
                                                        <th>Số lượng</th>
                                                        <th>Thành tiền</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-center">
                                                    <?php
                                                    $sql_details = "SELECT od.*, p.name, p.image 
                                                                    FROM order_details od 
                                                                    JOIN products p ON od.product_id = p.id 
                                                                    WHERE od.order_id = $order_id";
                                                    $result_details = $conn->query($sql_details);
                                                    while($item = $result_details->fetch_assoc()) { 
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <?php if(!empty($item['image'])) { ?>
                                                                    <img src="../<?php echo $item['image']; ?>" class="rounded border" style="width: 45px; height: 45px; object-fit: cover;">
                                                                <?php } else { ?>
                                                                    <div class="bg-light text-muted border rounded d-flex align-items-center justify-content-center mx-auto" style="width: 45px; height: 45px; font-size: 10px;">No img</div>
                                                                <?php } ?>
                                                            </td>
                                                            <td class="text-start fw-bold text-secondary"><?php echo $item['name']; ?></td>
                                                            <td><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                                                            <td>x <?php echo $item['quantity']; ?></td>
                                                            <td class="text-danger fw-bold"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ</td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                <?php 
                                    } 
                                } else {
                                    echo "<tr><td colspan='6' class='text-center text-muted py-5'>Không tìm thấy đơn hàng nào phù hợp với bộ lọc.</td></tr>";
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy tất cả các thẻ select có tên là 'status'
            const statusSelects = document.querySelectorAll('select[name="status"]');
            
            statusSelects.forEach(select => {
                // Lưu lại trạng thái ban đầu để phục hồi nếu người dùng bấm "Cancel"
                const initialStatus = select.value;
                
                select.addEventListener('change', function() {
                    let isConfirmed = true;
                    
                    if (this.value === '3') { 
                        // Nếu chọn Hủy đơn (3)
                        isConfirmed = confirm('CẢNH BÁO!\n\nBạn có chắc chắn muốn HỦY đơn hàng này không?\nThao tác này sẽ tự động hoàn trả số lượng sản phẩm vào kho.');
                    } else if (initialStatus === '3' && this.value !== '3') {
                        // Nếu đang Hủy (3) mà chọn lại thành trạng thái khác
                        isConfirmed = confirm('BẠN ĐANG PHỤC HỒI ĐƠN HÀNG ĐÃ HỦY!\n\nHệ thống sẽ trừ lại số lượng sản phẩm trong kho.\nBạn có muốn tiếp tục?');
                    }
                    
                    // Nếu bấm Cancel, đưa thẻ select về lại giá trị ban đầu
                    if (!isConfirmed) {
                        this.value = initialStatus;
                    }
                });
            });
        });
    </script>
</body>
</html>