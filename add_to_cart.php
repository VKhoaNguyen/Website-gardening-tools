<?php
session_start();
require_once 'config/connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM products WHERE id = $id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $stock = $product['stock']; // Lấy số lượng thực tế trong kho

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Tính xem trong giỏ đang có bao nhiêu cái rồi
        $current_qty_in_cart = isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id]['quantity'] : 0;
        
        // KIỂM TRA TỒN KHO: Nếu số lượng định thêm vượt quá kho -> Chặn lại
        if ($current_qty_in_cart + 1 > $stock) {
            echo "<script>alert('Rất tiếc! Sản phẩm này trong kho chỉ còn tối đa $stock sản phẩm.'); window.history.back();</script>";
            exit();
        }

        // Nếu qua được vòng kiểm tra trên thì mới cho phép thêm vào giỏ
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => 1
            ];
        }

        header("Location: cart.php");
        exit();
    }
}

header("Location: index.php");
exit();
?>