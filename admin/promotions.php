<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

$msg = "";

// 1. XỬ LÝ THÊM MÃ KHUYẾN MÃI
if (isset($_POST['btnAdd'])) {
    $code = strtoupper($conn->real_escape_string(trim($_POST['code']))); 
    $name = $conn->real_escape_string(trim($_POST['name'])); // LẤY TÊN CHƯƠNG TRÌNH
    $discount_type = (int)$_POST['discount_type'];
    $discount_value = (int)$_POST['discount_value'];
    $min_order_value = (int)$_POST['min_order_value'];
    $usage_limit = (int)$_POST['usage_limit'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Kiểm tra trùng mã
    $check = $conn->query("SELECT id FROM promotions WHERE code = '$code'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Lỗi: Mã giảm giá <b>$code</b> đã tồn tại!</div>";
    } elseif ($start_date >= $end_date) {
        $msg = "<div class='alert alert-danger'>Lỗi: Ngày kết thúc phải sau Ngày bắt đầu!</div>";
    } else {
        // THÊM CỘT name VÀO CÂU LỆNH INSERT
        $sql = "INSERT INTO promotions (code, name, discount_type, discount_value, min_order_value, usage_limit, start_date, end_date) 
                VALUES ('$code', '$name', $discount_type, $discount_value, $min_order_value, $usage_limit, '$start_date', '$end_date')";
        if ($conn->query($sql)) {
            header("Location: promotions.php?msg=add_success"); exit();
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi CSDL: " . $conn->error . "</div>";
        }
    }
}

// 2. XỬ LÝ CẬP NHẬT
if (isset($_POST['btnUpdate'])) {
    $id = (int)$_POST['id'];
    $code = strtoupper($conn->real_escape_string(trim($_POST['code'])));
    $name = $conn->real_escape_string(trim($_POST['name'])); // LẤY TÊN CHƯƠNG TRÌNH
    $discount_type = (int)$_POST['discount_type'];
    $discount_value = (int)$_POST['discount_value'];
    $min_order_value = (int)$_POST['min_order_value'];
    $usage_limit = (int)$_POST['usage_limit'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $check = $conn->query("SELECT id FROM promotions WHERE code = '$code' AND id != $id");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Lỗi: Mã giảm giá <b>$code</b> đã bị trùng với mã khác!</div>";
    } elseif ($start_date >= $end_date) {
        $msg = "<div class='alert alert-danger'>Lỗi: Ngày kết thúc phải sau Ngày bắt đầu!</div>";
    } else {
        // CẬP NHẬT THÊM CỘT name
        $sql = "UPDATE promotions SET code='$code', name='$name', discount_type=$discount_type, discount_value=$discount_value, 
                min_order_value=$min_order_value, usage_limit=$usage_limit, start_date='$start_date', end_date='$end_date' WHERE id=$id";
        if ($conn->query($sql)) {
            header("Location: promotions.php?msg=update_success"); exit();
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi CSDL: " . $conn->error . "</div>";
        }
    }
}

// 3. XỬ LÝ XÓA
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM promotions WHERE id = $id");
    header("Location: promotions.php?msg=del_success"); exit();
}

// LẤY DỮ LIỆU ĐỂ SỬA
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $id_edit = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM promotions WHERE id = $id_edit");
    if ($res->num_rows > 0) $edit_data = $res->fetch_assoc();
}

// LẤY DANH SÁCH MÃ GIẢM GIÁ
$promotions = $conn->query("SELECT * FROM promotions ORDER BY id DESC");

// Bắt thông báo
if (isset($_GET['msg']) && $msg == "") {
    if ($_GET['msg'] == 'add_success') $msg = "<div class='alert alert-success'>Thêm mã giảm giá thành công!</div>";
    elseif ($_GET['msg'] == 'update_success') $msg = "<div class='alert alert-success'>Cập nhật mã giảm giá thành công!</div>";
    elseif ($_GET['msg'] == 'del_success') $msg = "<div class='alert alert-success'>Xóa mã thành công!</div>";
}

$page = 'promotions';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Mã Giảm Giá - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Quản lý Khuyến Mãi</h5>
                <div><span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span></div>
            </div>

            <div class="content p-3">
                <?php if($msg != "") echo $msg; ?>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 fw-bold text-primary">
                                <i class="fa-solid fa-ticket me-2"></i><?php echo $edit_data ? "Sửa Mã Giảm Giá" : "Tạo Mã Mới"; ?>
                            </div>
                            <div class="card-body bg-light">
                                <form method="POST" action="promotions.php">
                                    <?php if ($edit_data) { ?>
                                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                    <?php } ?>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Tên chương trình <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required value="<?php echo $edit_data ? $edit_data['name'] : ''; ?>" placeholder="VD: Khuyến mãi Tết, Mừng khai trương...">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Mã Code (Tự động in hoa) <span class="text-danger">*</span></label>
                                        <input type="text" name="code" class="form-control text-uppercase fw-bold text-primary" required value="<?php echo $edit_data ? $edit_data['code'] : ''; ?>" placeholder="VD: SALE2026, FREESHIP...">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-5 mb-3">
                                            <label class="form-label small fw-bold">Loại giảm <span class="text-danger">*</span></label>
                                            <select name="discount_type" class="form-select" required>
                                                <option value="1" <?php echo ($edit_data && $edit_data['discount_type'] == 1) ? 'selected' : ''; ?>>Tiền mặt (₫)</option>
                                                <option value="2" <?php echo ($edit_data && $edit_data['discount_type'] == 2) ? 'selected' : ''; ?>>Phần trăm (%)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-7 mb-3">
                                            <label class="form-label small fw-bold text-danger">Mức giảm <span class="text-danger">*</span></label>
                                            <input type="number" name="discount_value" class="form-control border-danger" required value="<?php echo $edit_data ? $edit_data['discount_value'] : ''; ?>" placeholder="VD: 50000 hoặc 10">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">Đơn tối thiểu <span class="text-danger">*</span></label>
                                            <input type="number" name="min_order_value" class="form-control" required value="<?php echo $edit_data ? $edit_data['min_order_value'] : '0'; ?>" placeholder="0 = Mọi đơn">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">Giới hạn số lần <span class="text-danger">*</span></label>
                                            <input type="number" name="usage_limit" class="form-control" required value="<?php echo $edit_data ? $edit_data['usage_limit'] : '0'; ?>" placeholder="0 = Vô hạn">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Thời gian áp dụng <span class="text-danger">*</span></label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text bg-white small">Từ</span>
                                            <input type="datetime-local" name="start_date" class="form-control" required value="<?php echo $edit_data ? date('Y-m-d\TH:i', strtotime($edit_data['start_date'])) : ''; ?>">
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white small">Đến</span>
                                            <input type="datetime-local" name="end_date" class="form-control" required value="<?php echo $edit_data ? date('Y-m-d\TH:i', strtotime($edit_data['end_date'])) : ''; ?>">
                                        </div>
                                    </div>

                                    <button type="submit" name="<?php echo $edit_data ? 'btnUpdate' : 'btnAdd'; ?>" class="btn btn-<?php echo $edit_data ? 'warning' : 'primary'; ?> w-100 fw-bold">
                                        <i class="fa-solid fa-save me-2"></i>Lưu Mã Giảm Giá
                                    </button>
                                    <?php if($edit_data) echo '<a href="promotions.php" class="btn btn-outline-secondary w-100 mt-2">Hủy Bỏ</a>'; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 fw-bold text-primary">
                                <i class="fa-solid fa-list me-2"></i>Danh Sách Mã Khuyến Mãi
                            </div>
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Thông tin mã</th>
                                            <th>Mức giảm</th>
                                            <th>Điều kiện</th>
                                            <th>Thời hạn / Trạng thái</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($promotions->num_rows > 0) {
                                            $now = date('Y-m-d H:i:s');
                                            while($row = $promotions->fetch_assoc()) { 
                                                // Xử lý Trạng thái hiển thị
                                                $status_html = "";
                                                if ($row['usage_limit'] > 0 && $row['used_count'] >= $row['usage_limit']) {
                                                    $status_html = '<span class="badge bg-secondary">Hết lượt dùng</span>';
                                                } elseif ($now < $row['start_date']) {
                                                    $status_html = '<span class="badge bg-warning text-dark">Sắp diễn ra</span>';
                                                } elseif ($now > $row['end_date']) {
                                                    $status_html = '<span class="badge bg-danger">Đã kết thúc</span>';
                                                } else {
                                                    $status_html = '<span class="badge bg-success">Đang diễn ra</span>';
                                                }
                                        ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="fw-bold fs-6 text-primary border border-primary border-2 border-dashed px-2 py-1 rounded d-inline-block bg-primary-subtle mb-1">
                                                        <i class="fa-solid fa-ticket-simple me-1"></i><?php echo $row['code']; ?>
                                                    </span><br>
                                                    <small class="text-muted"><i class="fa-solid fa-tag me-1"></i><?php echo $row['name']; ?></small>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-danger">
                                                        <?php 
                                                        if($row['discount_type'] == 1) echo number_format($row['discount_value']) . " ₫";
                                                        else echo $row['discount_value'] . " %";
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="d-block text-muted">Đơn từ: <b class="text-dark"><?php echo number_format($row['min_order_value']); ?>đ</b></small>
                                                    <small class="d-block text-muted">Đã dùng: <b class="text-dark"><?php echo $row['used_count']; ?></b> / <?php echo $row['usage_limit'] == 0 ? '∞' : $row['usage_limit']; ?></small>
                                                </td>
                                                <td>
                                                    <small class="d-block text-muted"><i class="fa-regular fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($row['end_date'])); ?></small>
                                                    <div class="mt-1"><?php echo $status_html; ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="promotions.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-pen"></i></a>
                                                    <a href="promotions.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Xóa mã này?');"><i class="fa-solid fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php 
                                            } 
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center text-muted py-4'>Chưa có mã giảm giá nào.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>