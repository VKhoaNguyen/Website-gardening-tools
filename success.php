<?php
session_start();
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$order_id = $_GET['id'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt Hàng Thành Công</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow border-0 py-5">
                    <div class="card-body">
                        <i class="fa-solid fa-circle-check text-success" style="font-size: 80px;"></i>
                        <h2 class="mt-4 text-success fw-bold">ĐẶT HÀNG THÀNH CÔNG!</h2>
                        <p class="text-muted mt-3 mb-1">Cảm ơn bạn đã mua sắm tại Vườn Xanh.</p>
                        <p class="text-muted">Mã đơn hàng của bạn là: <strong class="text-dark">#VX-<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></strong></p>
                        
                        <div class="alert alert-info mt-4 text-start">
                            <i class="fa-solid fa-truck-fast me-2"></i> Nhân viên của chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất để xác nhận và giao hàng.
                        </div>

                        <div class="mt-4">
                            <a href="index.php" class="btn btn-success me-2"><i class="fa-solid fa-house"></i> Về trang chủ</a>
                            <a href="orders_history.php" class="btn btn-outline-success"><i class="fa-solid fa-list-check"></i> Xem lịch sử đơn</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>