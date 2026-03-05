<?php
session_start();
require_once 'config/connect.php';

// 1. Kiểm tra đăng nhập và ID đơn hàng
if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$order_id = $_GET['id'];

// 2. Kiểm tra xem đơn hàng này CÓ PHẢI CỦA USER NÀY không và CÒN Ở TRẠNG THÁI 0 (Chờ duyệt) không
$sql_check = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id AND status = 0";
$result_check = $conn->query($sql_check);

if ($result_check->num_rows > 0) {
    // Nếu hợp lệ, bắt đầu quá trình Hủy đơn
    
    // BƯỚC QUAN TRỌNG: Hoàn trả lại số lượng tồn kho cho các sản phẩm trong đơn này
    $sql_details = "SELECT product_id, quantity FROM order_details WHERE order_id = $order_id";
    $result_details = $conn->query($sql_details);
    
    while($item = $result_details->fetch_assoc()) {
        $p_id = $item['product_id'];
        $qty = $item['quantity'];
        // Cộng lại số lượng vào kho
        $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $p_id");
    }

    // Sau khi hoàn kho xong, đổi trạng thái đơn hàng thành 3 (Đã hủy)
    $sql_cancel = "UPDATE orders SET status = 3 WHERE id = $order_id";
    if ($conn->query($sql_cancel)) {
        echo "<script>alert('Hủy đơn hàng thành công! Số lượng sản phẩm đã được hoàn lại kho.'); window.location.href='orders_history.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi hủy đơn: " . $conn->error . "'); window.location.href='orders_history.php';</script>";
    }

} else {
    // Nếu đơn hàng không tồn tại, không phải của mình, hoặc đã được duyệt/giao
    echo "<script>alert('Đơn hàng này không thể hủy (đã được duyệt hoặc không tồn tại)!'); window.location.href='orders_history.php';</script>";
}
?>