<?php
session_start(); // Gọi session ra để biết đường mà xóa

// Chỉ xóa thông tin đăng nhập của người dùng
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

// LƯU Ý HAY: Chúng ta dùng unset() để CHỈ xóa tài khoản, 
// KHÔNG dùng session_destroy() vì nó sẽ xóa luôn cả Giỏ hàng của khách!

// Xóa xong thì đuổi (chuyển hướng) về lại trang chủ
header("Location: index.php");
exit();
?>