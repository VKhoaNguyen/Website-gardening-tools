<?php
session_start();
require_once 'config/connect.php'; 

// KIỂM TRA ĐĂNG NHẬP (Bắt buộc phải đăng nhập mới truy xuất được ví P-Coin)
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập để xem giỏ hàng và sử dụng P-Coin!'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user']['id'];
$error_msg = "";
$success_msg = "";

// Lấy số dư P-Coin mới nhất của khách hàng từ Database
$user_query = $conn->query("SELECT p_coin FROM users WHERE id = $user_id");
$current_p_coin = 0;
if($user_query->num_rows > 0) {
    $current_p_coin = (int)$user_query->fetch_assoc()['p_coin'];
}

// Đếm số lượng loại sản phẩm trong giỏ
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// 1. XỬ LÝ CẬP NHẬT HOẶC XÓA SẢN PHẨM KHỎI GIỎ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. Cập nhật số lượng
    if (isset($_POST['btnUpdate'])) {
        foreach ($_POST['qty'] as $id => $quantity) {
            $quantity = (int)$quantity; 
            $res = $conn->query("SELECT stock FROM products WHERE id = $id");
            if ($res->num_rows > 0) {
                $stock = $res->fetch_assoc()['stock'];
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$id]); 
                } elseif ($quantity > $stock) {
                    $_SESSION['cart'][$id]['quantity'] = $stock;
                    $error_msg = "<div class='alert alert-warning'>Một số sản phẩm đã được điều chỉnh lại số lượng do kho không đủ đáp ứng!</div>";
                } else {
                    $_SESSION['cart'][$id]['quantity'] = $quantity;
                }
            }
        }
        $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    } 
    // B. Xóa sản phẩm
    elseif (isset($_POST['btnRemove'])) {
        $remove_id = $_POST['remove_id'];
        unset($_SESSION['cart'][$remove_id]);
        $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    }
}

// 2. TÍNH TỔNG TIỀN HÀNG (Base Total)
$total_money = 0;
if ($cart_count > 0) {
    foreach ($_SESSION['cart'] as $item) { 
        $total_money += $item['price'] * $item['quantity'];
    }
}

// 3. XỬ LÝ MÃ GIẢM GIÁ (Voucher)
if (isset($_POST['btnRemovePromo'])) {
    unset($_SESSION['promo']);
    $success_msg = "<div class='alert alert-info'>Đã gỡ mã giảm giá.</div>";
} elseif (isset($_POST['btnApplyPromo'])) {
    $promo_code = strtoupper($conn->real_escape_string(trim($_POST['promo_code'])));
    $now = date('Y-m-d H:i:s');
    $res_promo = $conn->query("SELECT * FROM promotions WHERE code = '$promo_code'");
    
    if ($res_promo->num_rows > 0) {
        $promo = $res_promo->fetch_assoc();
        if ($now < $promo['start_date']) {
            $error_msg = "<div class='alert alert-danger'>Mã chưa đến thời gian sử dụng!</div>";
        } elseif ($now > $promo['end_date']) {
            $error_msg = "<div class='alert alert-danger'>Mã đã hết hạn!</div>";
        } elseif ($promo['usage_limit'] > 0 && $promo['used_count'] >= $promo['usage_limit']) {
            $error_msg = "<div class='alert alert-danger'>Mã đã hết lượt sử dụng!</div>";
        } elseif ($total_money < $promo['min_order_value']) {
            $error_msg = "<div class='alert alert-danger'>Đơn hàng chưa đạt mức tối thiểu (".number_format($promo['min_order_value'])."đ)!</div>";
        } else {
            $_SESSION['promo'] = $promo;
            $success_msg = "<div class='alert alert-success'>Áp dụng mã <b>".$promo['code']."</b> thành công!</div>";
        }
    } else {
        $error_msg = "<div class='alert alert-danger'>Mã giảm giá không hợp lệ!</div>";
    }
}

// Tính tiền Voucher
$discount_amount = 0;
if (isset($_SESSION['promo'])) {
    $promo = $_SESSION['promo'];
    if ($total_money >= $promo['min_order_value']) {
        if ($promo['discount_type'] == 1) { 
            $discount_amount = $promo['discount_value'];
        } else { 
            $discount_amount = ($total_money * $promo['discount_value']) / 100;
        }
    } else {
        unset($_SESSION['promo']);
        $error_msg = "<div class='alert alert-warning'>Mã giảm giá đã bị gỡ do tổng đơn không còn đủ điều kiện!</div>";
    }
}

// 4. XỬ LÝ SỬ DỤNG P-COIN
// Bật/Tắt P-Coin
if (isset($_POST['btnToggleCoin'])) {
    if (isset($_SESSION['use_coin'])) {
        unset($_SESSION['use_coin']); // Tắt
    } else {
        if ($current_p_coin > 0) {
            $_SESSION['use_coin'] = true; // Bật
        } else {
            $error_msg = "<div class='alert alert-warning'>Bạn chưa có P-Coin nào để sử dụng!</div>";
        }
    }
}

// Tính tiền P-Coin (Chỉ trừ số xu tối đa bằng với số tiền còn lại sau khi áp Voucher)
$coin_discount = 0;
$coins_to_use = 0;
$remaining_after_promo = max(0, $total_money - $discount_amount); // Tiền còn lại

if (isset($_SESSION['use_coin']) && $current_p_coin > 0) {
    // Lấy số nhỏ hơn giữa [Xu hiện có] và [Tiền còn phải trả]
    $coins_to_use = min($current_p_coin, $remaining_after_promo); 
    $coin_discount = $coins_to_use; // 1 Xu = 1 VNĐ
} else {
    unset($_SESSION['use_coin']);
}

// Lưu số xu đã dùng vào session để mang sang trang checkout.php
$_SESSION['used_coin'] = $coins_to_use;

// 5. TỔNG THANH TOÁN CUỐI CÙNG
$final_total = max(0, $total_money - $discount_amount - $coin_discount);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ Hàng Của Bạn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-success mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-leaf"></i> VƯỜN XANH</a>
            <a href="index.php" class="btn btn-outline-light"><i class="fa-solid fa-arrow-left"></i> Tiếp tục mua sắm</a>
        </div>
    </nav>

    <div class="container mb-5">
        <h3 class="mb-4">Giỏ Hàng Của Bạn (<?php echo $cart_count; ?> sản phẩm)</h3>
        
        <?php if($error_msg != "") echo $error_msg; ?>
        <?php if($success_msg != "") echo $success_msg; ?>

        <?php if ($cart_count > 0) { ?>
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0 table-responsive">
                            <form method="POST" action="cart.php">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Sản phẩm</th>
                                            <th>Đơn giá</th>
                                            <th width="15%">Số lượng</th>
                                            <th>Thành tiền</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        foreach ($_SESSION['cart'] as $id => $item) { 
                                            $thanh_tien = $item['price'] * $item['quantity'];
                                        ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="d-flex align-items-center">
                                                        <?php if($item['image'] != '') { ?>
                                                            <img src="<?php echo $item['image']; ?>" class="rounded me-3 border" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <?php } ?>
                                                        <span class="fw-bold text-dark"><?php echo $item['name']; ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-muted"><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                                                <td>
                                                    <input type="number" name="qty[<?php echo $id; ?>]" value="<?php echo $item['quantity']; ?>" class="form-control form-control-sm text-center" min="1">
                                                </td>
                                                <td class="text-danger fw-bold"><?php echo number_format($thanh_tien, 0, ',', '.'); ?> đ</td>
                                                <td class="text-end pe-3">
                                                    <button type="submit" name="btnRemove" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('remove_id').value = <?php echo $id; ?>;">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="remove_id" id="remove_id" value="">
                                
                                <div class="card-footer bg-white py-3">
                                    <button type="submit" name="btnUpdate" class="btn btn-primary"><i class="fa-solid fa-rotate"></i> Cập nhật giỏ hàng</button>
                                </div>
                            </form> 
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <h6 class="card-title fw-bold mb-3"><i class="fa-solid fa-ticket text-primary me-2"></i>Mã Giảm Giá</h6>
                            <?php if(isset($_SESSION['promo'])) { ?>
                                <div class="d-flex justify-content-between align-items-center bg-success-subtle border border-success border-dashed p-2 rounded">
                                    <span class="fw-bold text-success"><i class="fa-solid fa-check-circle me-1"></i><?php echo $_SESSION['promo']['code']; ?></span>
                                    <form method="POST" action="cart.php" class="m-0">
                                        <button type="submit" name="btnRemovePromo" class="btn btn-sm btn-outline-danger py-0 px-2"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </div>
                            <?php } else { ?>
                                <form method="POST" action="cart.php" class="d-flex">
                                    <input type="text" name="promo_code" class="form-control text-uppercase" placeholder="Nhập mã..." required>
                                    <button type="submit" name="btnApplyPromo" class="btn btn-dark ms-2">Áp dụng</button>
                                </form>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-4 border-warning border-top border-3">
                        <div class="card-body">
                            <form method="POST" action="cart.php" class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title fw-bold text-warning mb-1"><i class="fa-solid fa-coins me-2"></i>P-Coin của bạn</h6>
                                    <small class="text-muted">Đang có: <b><?php echo number_format($current_p_coin, 0, ',', '.'); ?> Xu</b></small>
                                </div>
                                
                                <?php if($current_p_coin > 0) { ?>
                                    <button type="submit" name="btnToggleCoin" class="btn btn-sm <?php echo isset($_SESSION['use_coin']) ? 'btn-danger' : 'btn-warning'; ?> fw-bold shadow-sm">
                                        <?php echo isset($_SESSION['use_coin']) ? 'Hủy dùng Xu' : 'Dùng Xu [-'.number_format(min($current_p_coin, $remaining_after_promo), 0, ',', '.').'đ]'; ?>
                                    </button>
                                <?php } else { ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>Không có Xu</button>
                                <?php } ?>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-4 fw-bold">Tóm tắt đơn hàng</h5>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tổng tiền hàng:</span>
                                <strong><?php echo number_format($total_money, 0, ',', '.'); ?> đ</strong>
                            </div>
                            
                            <?php if ($discount_amount > 0) { ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Voucher giảm giá:</span>
                                    <strong class="text-success">- <?php echo number_format($discount_amount, 0, ',', '.'); ?> đ</strong>
                                </div>
                            <?php } ?>

                            <?php if ($coin_discount > 0) { ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Trừ P-Coin (<small><?php echo number_format($coins_to_use, 0, ',', '.'); ?> xu</small>):</span>
                                    <strong class="text-warning">- <?php echo number_format($coin_discount, 0, ',', '.'); ?> đ</strong>
                                </div>
                            <?php } ?>

                            <hr class="text-muted">
                            
                            <div class="d-flex justify-content-between mb-4 align-items-end">
                                <span class="fw-bold fs-6">Tổng thanh toán:</span>
                                <div class="text-end">
                                    <h4 class="text-danger fw-bold mb-0"><?php echo number_format($final_total, 0, ',', '.'); ?> đ</h4>
                                </div>
                            </div>
                            
                            <a href="checkout.php" class="btn btn-success w-100 py-2 fw-bold fs-6"><i class="fa-solid fa-credit-card me-2"></i>TIẾN HÀNH THANH TOÁN</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else { ?>
            <div class="text-center py-5 bg-white rounded shadow-sm border-0">
                <i class="fa-solid fa-cart-arrow-down text-muted opacity-50" style="font-size: 80px;"></i>
                <h4 class="mt-4 text-secondary fw-bold">Giỏ hàng của bạn đang trống</h4>
                <a href="index.php" class="btn btn-success mt-3 px-4 py-2">Mua sắm ngay</a>
            </div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>