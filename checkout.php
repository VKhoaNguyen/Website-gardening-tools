<?php
session_start();
require_once 'config/connect.php';

// 1. KIỂM TRA ĐĂNG NHẬP & GIỎ HÀNG
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập để tiến hành thanh toán!'); window.location.href='login.php';</script>";
    exit();
}

if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header("Location: cart.php"); 
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// --- BƯỚC A: TÍNH TIỀN VÀ KHUYẾN MÃI ---
$total_money = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_money += $item['price'] * $item['quantity'];
}

$discount_amount = 0;
$promotion_id = "NULL"; 
if (isset($_SESSION['promo'])) {
    $promo = $_SESSION['promo'];
    if ($total_money >= $promo['min_order_value']) {
        $promotion_id = $promo['id'];
        if ($promo['discount_type'] == 1) {
            $discount_amount = $promo['discount_value'];
        } else {
            $discount_amount = ($total_money * $promo['discount_value']) / 100;
        }
    } else {
        unset($_SESSION['promo']);
    }
}

// --- BƯỚC B: XỬ LÝ P-COIN ---
// 1. Lấy số dư xu mới nhất từ CSDL để chống hack mở 2 tab
$check_coin = $conn->query("SELECT p_coin FROM users WHERE id = $user_id");
$current_db_coin = ($check_coin->num_rows > 0) ? (int)$check_coin->fetch_assoc()['p_coin'] : 0;

$used_coin = isset($_SESSION['used_coin']) ? (int)$_SESSION['used_coin'] : 0;

// Ép lại số xu dùng nếu xu trong kho ít hơn xu yêu cầu
if ($used_coin > $current_db_coin) {
    $used_coin = $current_db_coin;
}

// 2. TỔNG TIỀN CUỐI CÙNG SAU KHI TRỪ VOUCHER VÀ XU
$final_total = max(0, $total_money - $discount_amount - $used_coin);

// 3. TÍNH ĐIỂM THƯỞNG SẼ NHẬN ĐƯỢC (Ví dụ: Cứ tiêu 1.000đ được 1 Xu)
$earned_coin = floor($final_total / 100); 

// --- BƯỚC C: LƯU ĐƠN HÀNG VÀO DATABASE ---
if (isset($_POST['btnCheckout'])) {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    
    $shipping_address = $fullname . " | SĐT: " . $phone . " | Đ/c: " . $address;

    // Lưu đơn có kèm used_coin và earned_coin
    $sql_order = "INSERT INTO orders (user_id, total_money, status, shipping_address, payment_method, recipient_name, recipient_phone, promotion_id, used_coin, earned_coin) 
                  VALUES ('$user_id', '$final_total', 0, '$shipping_address', '$payment_method', '$fullname', '$phone', $promotion_id, $used_coin, $earned_coin)";
    
    if ($conn->query($sql_order)) {
        $order_id = $conn->insert_id;

        // Cập nhật lượt dùng Mã giảm giá
        if ($promotion_id != "NULL") {
            $conn->query("UPDATE promotions SET used_count = used_count + 1 WHERE id = $promotion_id");
        }

        // TRỪ LẬP TỨC P-COIN TRONG VÍ CỦA KHÁCH HÀNG
        if ($used_coin > 0) {
            $conn->query("UPDATE users SET p_coin = p_coin - $used_coin WHERE id = $user_id");
        }

        // Lưu chi tiết sản phẩm và trừ tồn kho
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $price = $item['price']; 
            $quantity = $item['quantity'];
            
            $sql_detail = "INSERT INTO order_details (order_id, product_id, price, quantity) 
                           VALUES ('$order_id', '$product_id', '$price', '$quantity')";
            $conn->query($sql_detail);
            $conn->query("UPDATE products SET stock = stock - $quantity WHERE id = $product_id");
        }

        // Xóa sạch dấu vết trong Session
        unset($_SESSION['cart']);
        unset($_SESSION['promo']);
        unset($_SESSION['used_coin']);
        unset($_SESSION['use_coin']);

        header("Location: success.php?id=" . $order_id);
        exit();
    } else {
        $error = "Có lỗi xảy ra khi tạo đơn hàng: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh Toán - Vườn Xanh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-success mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-leaf"></i> VƯỜN XANH</a>
            <a href="cart.php" class="btn btn-outline-light"><i class="fa-solid fa-arrow-left"></i> Quay lại Giỏ hàng</a>
        </div>
    </nav>

    <div class="container mb-5">
        <h3 class="mb-4">Thông Tin Thanh Toán</h3>
        
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold text-success">
                            <i class="fa-solid fa-location-dot me-2"></i> Địa chỉ giao hàng
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label>Họ và tên người nhận <span class="text-danger">*</span></label>
                                <input type="text" name="fullname" class="form-control" required value="<?php echo $user['fullname']; ?>">
                            </div>
                            <div class="mb-3">
                                <label>Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" required value="<?php echo $user['phone'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label>Địa chỉ nhận hàng cụ thể <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control" rows="3" required placeholder="Số nhà, Tên đường, Phường/Xã, Quận/Huyện, Tỉnh/Thành phố..."><?php echo $user['address'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 fw-bold text-success">
                            <i class="fa-solid fa-credit-card me-2"></i> Phương thức thanh toán
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked>
                                <label class="form-check-label fw-bold" for="cod">Thanh toán khi nhận hàng (COD)</label>
                                <p class="text-muted small">Bạn sẽ thanh toán bằng tiền mặt khi shipper giao hàng tới.</p>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank" value="Chuyển khoản">
                                <label class="form-check-label fw-bold" for="bank">Chuyển khoản ngân hàng</label>
                                <p class="text-muted small">Tính năng đang được phát triển, vui lòng chọn COD.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-white py-3 fw-bold text-success">
                            <i class="fa-solid fa-box-open me-2"></i> Đơn hàng của bạn
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($_SESSION['cart'] as $item) { ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-secondary rounded-pill me-2"><?php echo $item['quantity']; ?></span>
                                            <span class="text-truncate" style="max-width: 200px;"><?php echo $item['name']; ?></span>
                                        </div>
                                        <span class="text-danger fw-bold"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ</span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <div class="card-footer bg-white py-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tổng tiền hàng:</span>
                                <strong><?php echo number_format($total_money, 0, ',', '.'); ?> đ</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Phí vận chuyển:</span>
                                <span class="text-success fw-bold">Miễn phí</span>
                            </div>
                            
                            <?php if ($discount_amount > 0) { ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Voucher giảm giá:</span>
                                    <strong class="text-success">- <?php echo number_format($discount_amount, 0, ',', '.'); ?> đ</strong>
                                </div>
                            <?php } ?>

                            <?php if ($used_coin > 0) { ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Trừ P-Coin:</span>
                                    <strong class="text-warning">- <?php echo number_format($used_coin, 0, ',', '.'); ?> đ</strong>
                                </div>
                            <?php } ?>

                            <div class="d-flex justify-content-between mb-3 align-items-center border-top pt-3">
                                <span class="fw-bold fs-5">Tổng cộng:</span>
                                <span class="text-danger fw-bold fs-4"><?php echo number_format($final_total, 0, ',', '.'); ?> đ</span>
                            </div>

                            <?php if ($earned_coin > 0) { ?>
                                <div class="alert alert-success py-2 text-center small mb-3 border border-success-subtle">
                                    <i class="fa-solid fa-gift text-success me-1"></i> Hoàn tất đơn, bạn sẽ được nhận <b>+<?php echo number_format($earned_coin, 0, ',', '.'); ?> Xu</b>
                                </div>
                            <?php } ?>

                            <button type="submit" name="btnCheckout" class="btn btn-success w-100 py-3 fw-bold fs-5">
                                XÁC NHẬN ĐẶT HÀNG
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>
</html>