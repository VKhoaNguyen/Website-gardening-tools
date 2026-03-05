<?php $page = isset($page) ? $page : ''; ?>
<div class="sidebar flex-shrink-0" style="width: 250px;">
    <h4 class="text-center py-4 mb-0 fw-bold border-bottom border-light border-opacity-25">ADMIN PANEL</h4>
    <div class="list-group list-group-flush mt-3">
        <a href="index.php" class="<?php echo ($page == 'dashboard') ? 'active' : ''; ?>"><i class="fa-solid fa-house me-2"></i> Dashboard</a>
        
        <div class="text-white-50 px-3 py-2 small fw-bold mt-2">KHO & SẢN PHẨM</div>
        <a href="categories.php" class="<?php echo ($page == 'categories') ? 'active' : ''; ?>"><i class="fa-solid fa-list me-2"></i> Danh mục</a>
        <a href="products.php" class="<?php echo ($page == 'products') ? 'active' : ''; ?>"><i class="fa-solid fa-box me-2"></i> Sản phẩm</a>
        <a href="suppliers.php" class="<?php echo ($page == 'suppliers') ? 'active' : ''; ?>"><i class="fa-solid fa-truck-field me-2"></i> Nhà cung cấp</a>
        <a href="receipts.php" class="<?php echo ($page == 'receipts') ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Phiếu nhập kho</a>
        
        <div class="text-white-50 px-3 py-2 small fw-bold mt-2">BÁN HÀNG</div>
        <a href="orders.php" class="<?php echo ($page == 'orders') ? 'active' : ''; ?>"><i class="fa-solid fa-cart-flatbed me-2"></i> Đơn hàng</a>
        <a href="customers.php" class="<?php echo ($page == 'customers') ? 'active' : ''; ?>"><i class="fa-solid fa-users me-2"></i> Khách hàng</a>
        <a href="promotions.php" class="<?php echo ($page == 'promotions') ? 'active' : ''; ?>"><i class="fa-solid fa-ticket-simple me-2"></i> Khuyến mãi</a>
        
        <div class="text-white-50 px-3 py-2 small fw-bold mt-2">HỆ THỐNG</div>
        <?php if(isset($_SESSION['user']) && $_SESSION['user']['role_level'] == 1) { ?>
            <a href="accounts.php" class="<?php echo ($page == 'accounts') ? 'active' : ''; ?>"><i class="fa-solid fa-user-shield me-2"></i> QL Tài khoản</a>
        <?php } ?>
        <a href="../index.php" class="text-warning"><i class="fa-solid fa-store me-2"></i> Xem Trang Chủ</a>
        <a href="../logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất</a>
    </div>
</div>