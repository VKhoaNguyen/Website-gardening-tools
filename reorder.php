<?php
session_start();
require_once 'config/connect.php';

// Kiểm tra bảo mật
if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header("Location: index.php"); exit();
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user']['id'];

// Xác thực đơn hàng đúng là của user đang đăng nhập
$check = $conn->query("SELECT id FROM orders WHERE id = $order_id AND user_id = $user_id");
if ($check->num_rows == 0) {
    header("Location: orders_history.php"); exit();
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Truy vấn sản phẩm từ đơn cũ (Lấy giá và tồn kho MỚI NHẤT từ bảng products)
$sql = "SELECT od.product_id, od.quantity, p.name, p.price, p.image, p.stock 
        FROM order_details od 
        JOIN products p ON od.product_id = p.id 
        WHERE od.order_id = $order_id";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $pid = $row['product_id'];
    $qty = $row['quantity'];
    
    // Chỉ thêm vào giỏ nếu kho còn hàng
    if ($row['stock'] > 0) {
        $add_qty = ($qty > $row['stock']) ? $row['stock'] : $qty; // Ép về mức tồn kho tối đa nếu kho sắp hết
        
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] += $add_qty;
            if($_SESSION['cart'][$pid]['quantity'] > $row['stock']) {
                $_SESSION['cart'][$pid]['quantity'] = $row['stock'];
            }
        } else {
            $_SESSION['cart'][$pid] = [
                'name' => $row['name'],
                'price' => $row['price'], // Cập nhật lại giá mới nhất (lỡ shop tăng/giảm giá)
                'image' => $row['image'],
                'quantity' => $add_qty
            ];
        }
    }
}

// Gom xong thì tự động đẩy qua Giỏ hàng
header("Location: cart.php");
exit();
?>