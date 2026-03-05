<?php
session_start();
require_once 'config/connect.php';

// 1. Kiểm tra xem có truyền ID sản phẩm không
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$err_msg = "";

// 2. Lấy thông tin chi tiết của sản phẩm (Kết hợp bảng categories để lấy tên danh mục)
$sql = "SELECT p.*, c.name as cat_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Nếu ai đó gõ bừa 1 ID không tồn tại trên thanh URL thì đuổi về trang chủ
    header("Location: index.php");
    exit();
}
$product = $result->fetch_assoc();

// 3. Xử lý khi khách hàng bấm nút "Thêm vào giỏ" ngay tại trang này (Có chọn số lượng)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $qty_to_add = (int)$_POST['quantity'];
    $stock = $product['stock'];

    // Lấy số lượng đang có sẵn trong giỏ hàng (nếu có)
    $current_qty_in_cart = isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id]['quantity'] : 0;

    // Kiểm tra kho
    if ($current_qty_in_cart + $qty_to_add > $stock) {
        $err_msg = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation'></i> Rất tiếc! Số lượng bạn chọn vượt quá tồn kho. Chúng tôi chỉ còn $stock sản phẩm này.</div>";
    } else {
        // Khởi tạo giỏ nếu chưa có
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Thêm vào giỏ
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += $qty_to_add;
        } else {
            $_SESSION['cart'][$id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $qty_to_add
            ];
        }
        // Thêm xong chuyển ngay sang giỏ hàng
        header("Location: cart.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $product['name']; ?> - Vườn Xanh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-leaf"></i> VƯỜN XANH</a>
            <div class="d-flex align-items-center">
                <?php 
                $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                ?>
                <a href="cart.php" class="btn btn-outline-light me-3">
                    <i class="fa-solid fa-cart-shopping"></i> Giỏ hàng (<?php echo $cart_count; ?>)
                </a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-success text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="#" class="text-success text-decoration-none"><?php echo $product['cat_name']; ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $product['name']; ?></li>
            </ol>
        </nav>

        <?php if($err_msg != "") echo $err_msg; ?>

        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="row g-0">
                <div class="col-md-5 text-center p-4 bg-white border-end">
                    <?php if($product['image'] != '') { ?>
                        <img src="<?php echo $product['image']; ?>" class="img-fluid rounded" style="max-height: 400px; object-fit: contain;" alt="<?php echo $product['name']; ?>">
                    <?php } else { ?>
                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                            <span>Chưa có hình ảnh</span>
                        </div>
                    <?php } ?>
                </div>

                <div class="col-md-7">
                    <div class="card-body p-5">
                        <span class="badge bg-info text-dark mb-2"><?php echo $product['cat_name']; ?></span>
                        <h2 class="card-title fw-bold mb-3"><?php echo $product['name']; ?></h2>
                        
                        <h3 class="text-danger fw-bold mb-4"><?php echo number_format($product['price'], 0, ',', '.'); ?> ₫</h3>
                        
                        <div class="mb-4 text-muted" style="line-height: 1.8;">
                            <?php echo nl2br($product['description']); ?>
                        </div>
                        
                        <hr class="mb-4">

                        <?php if ($product['stock'] > 0) { ?>
                            <p class="mb-3">Trạng thái: <span class="badge bg-success">Còn hàng (<?php echo $product['stock']; ?> sẵn có)</span></p>
                            
                            <form method="POST" action="">
                                <div class="row align-items-center g-3">
                                    <div class="col-auto">
                                        <label for="quantity" class="col-form-label fw-bold">Số lượng:</label>
                                    </div>
                                    <div class="col-auto">
                                        <input type="number" id="quantity" name="quantity" class="form-control text-center" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 100px;">
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" name="add_to_cart" class="btn btn-success btn-lg px-4">
                                            <i class="fa-solid fa-cart-plus me-2"></i>Thêm vào giỏ
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php } else { ?>
                            <p class="mb-3">Trạng thái: <span class="badge bg-danger">Đã hết hàng</span></p>
                            <button class="btn btn-secondary btn-lg px-4" disabled>
                                <i class="fa-solid fa-ban me-2"></i> Không thể mua
                            </button>
                        <?php } ?>
                        
                        <div class="mt-5 p-3 bg-light rounded border">
                            <div class="row text-center g-3 text-muted" style="font-size: 0.9rem;">
                                <div class="col-4">
                                    <i class="fa-solid fa-truck-fast fs-4 mb-2 text-success"></i><br> Giao hàng toàn quốc
                                </div>
                                <div class="col-4">
                                    <i class="fa-solid fa-shield-halved fs-4 mb-2 text-success"></i><br> Đảm bảo chất lượng
                                </div>
                                <div class="col-4">
                                    <i class="fa-solid fa-rotate-left fs-4 mb-2 text-success"></i><br> Đổi trả 7 ngày
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <p class="mb-0">© 2026 Đồ Án Website Dụng Cụ Làm Vườn. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>