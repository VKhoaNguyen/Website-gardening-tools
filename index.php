<?php
// Bắt buộc khởi tạo session ở dòng đầu tiên để sau này làm Giỏ hàng
session_start();

// Nhúng file kết nối CSDL
require_once 'config/connect.php';

// Câu lệnh SQL lấy danh sách sản phẩm (Lấy 8 sản phẩm mới nhất)
$sql = "SELECT * FROM products ORDER BY id DESC LIMIT 8";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cửa hàng Dụng Cụ Làm Vườn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-product:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }
        .price {
            color: #d9534f;
            font-weight: bold;
            font-size: 1.2rem;
        }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-leaf"></i> VƯỜN XANH</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Danh mục</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Cẩm nang</a></li>
                </ul>
                <div class="d-flex align-items-center">
    <a href="cart.php" class="btn btn-outline-light me-3">
        <i class="fa-solid fa-cart-shopping"></i> Giỏ hàng (0)
        
    </a>

    
    

    <?php 
    // KIỂM TRA: Nếu đã đăng nhập
    if(isset($_SESSION['user'])) { 
        $user = $_SESSION['user'];
    ?>
        <div class="dropdown">
            <button class="btn btn-warning dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-user-circle"></i><?php echo $user['fullname']; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
    <?php if($user['role_level'] == 1 || $user['role_level'] == 2) { ?>
        <li><a class="dropdown-item fw-bold text-primary py-2" href="admin/index.php"><i class="fa-solid fa-gauge me-2"></i> Về trang Quản trị</a></li>
        <li><hr class="dropdown-divider"></li>
    <?php } ?>
    
    <li><a class="dropdown-item py-2" href="profile.php"><i class="fa-solid fa-user-pen text-muted me-2"></i> Cập nhật thông tin</a></li>
    <li><a class="dropdown-item py-2" href="change_password.php"><i class="fa-solid fa-key text-muted me-2"></i> Đổi mật khẩu</a></li>
    <li><a class="dropdown-item py-2" href="orders_history.php"><i class="fa-solid fa-clock-rotate-left text-muted me-2"></i> Lịch sử đơn hàng</a></li>
    
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất</a></li>
</ul>
        </div>

    <?php } else { ?>
    <a href="login.php" class="btn btn-outline-light me-2">Đăng nhập</a>
    <a href="register.php" class="btn btn-warning">Đăng ký</a>
    <?php } ?>


</div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="p-5 text-center bg-white rounded-3 shadow-sm">
            <h1 class="text-success fw-bold">Dụng Cụ Làm Vườn Chuyên Nghiệp</h1>
            <p class="lead">Đồng hành cùng đam mê trồng trọt và chăm sóc cây cảnh của bạn.</p>
        </div>
    </div>

    <div class="container mt-5 mb-5">
        <h3 class="border-bottom pb-2 mb-4">Sản Phẩm Nổi Bật</h3>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            
            <?php
            // Kiểm tra xem có sản phẩm nào không
            if ($result->num_rows > 0) {
                // Vòng lặp hiển thị từng sản phẩm ra giao diện
                while($row = $result->fetch_assoc()) {
            ?>
                    <div class="col">
                        <div class="card h-100 card-product">
                            <img src="<?php echo $row['image']; ?>" class="card-img-top" alt="Ảnh sản phẩm">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                <p class="card-text text-muted small"><?php echo mb_strimwidth($row['description'], 0, 50, '...'); ?></p>
                                <p class="price mt-auto"><?php echo number_format($row['price'], 0, ',', '.'); ?> đ</p>
                                <a href="product_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-success w-100 mt-2">Xem chi tiết</a>
                                <?php if ($row['stock'] > 0) { ?>
    <a href="add_to_cart.php?id=<?php echo $row['id']; ?>" class="btn btn-success w-100 mt-2">
        <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
    </a>
<?php } else { ?>
    <button class="btn btn-secondary w-100 mt-2" disabled>
        <i class="fa-solid fa-ban"></i> Đã hết hàng
    </button>
<?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php
                } // Kết thúc vòng lặp while
            } else {
                echo "<div class='col-12'><p class='alert alert-warning'>Chưa có sản phẩm nào trong cửa hàng.</p></div>";
            }
            ?>

        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <p class="mb-0">© 2026 Đồ Án Website Dụng Cụ Làm Vườn. All rights reserved.</p>
    </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body>
</html>