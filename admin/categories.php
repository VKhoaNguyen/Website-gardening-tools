<?php
session_start();
require_once '../config/connect.php';

// Kiểm tra quyền truy cập (Admin hoặc Nhân viên đều được vào)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_level'], [1, 2])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='../login.php';</script>";
    exit();
}

$msg = "";

// 1. XỬ LÝ THÊM DANH MỤC (Có Upload Ảnh)
if (isset($_POST['btnAddCategory'])) {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // KIỂM TRA TRÙNG TÊN DANH MỤC TRƯỚC
    $check_exist = $conn->query("SELECT id FROM categories WHERE name = '$name'");
    if ($check_exist->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Lỗi: Danh mục mang tên <b>'$name'</b> đã tồn tại! Vui lòng chọn tên khác.</div>";
    } else {
        // Tên chưa tồn tại -> Bắt đầu xử lý Upload hình ảnh
        $image_path = NULL;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../assets/images/categories/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $new_file_name = time() . '_' . rand(100, 999) . '.' . $file_extension;
            $target_file = $target_dir . $new_file_name;
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_path = "assets/images/categories/" . $new_file_name; 
                } else {
                    $msg = "<div class='alert alert-danger'>Lỗi: Không thể tải ảnh lên thư mục!</div>";
                }
            } else {
                $msg = "<div class='alert alert-danger'>Lỗi: Chỉ chấp nhận định dạng JPG, PNG, GIF, WEBP.</div>";
            }
        }

        // Nếu không có lỗi gì trong quá trình up ảnh thì tiến hành Lưu vào CSDL
        if ($msg == "") {
            $sql_insert = "INSERT INTO categories (name, description, image, is_active) 
                           VALUES ('$name', '$description', " . ($image_path ? "'$image_path'" : "NULL") . ", $is_active)";
            if ($conn->query($sql_insert)) {
                header("Location: categories.php?msg=add_success");
                exit();
            } else {
                $msg = "<div class='alert alert-danger'>Lỗi CSDL: " . $conn->error . "</div>";
            }
        }
    }

    
    // Xử lý Upload hình ảnh
    $image_path = NULL;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Tạo thư mục nếu chưa có
        $target_dir = "../assets/images/categories/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Đổi tên file để không bị trùng (dùng hàm time)
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_file_name = time() . '_' . rand(100, 999) . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;
        
        // Chỉ cho phép file ảnh
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = "assets/images/categories/" . $new_file_name; // Đường dẫn lưu vào DB
            } else {
                $msg = "<div class='alert alert-danger'>Lỗi: Không thể tải ảnh lên thư mục!</div>";
            }
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi: Chỉ chấp nhận định dạng JPG, PNG, GIF, WEBP.</div>";
        }
    }

    if ($msg == "") {
        $sql_insert = "INSERT INTO categories (name, description, image, is_active) 
                       VALUES ('$name', '$description', " . ($image_path ? "'$image_path'" : "NULL") . ", $is_active)";
        if ($conn->query($sql_insert)) {
            header("Location: categories.php?msg=add_success");
            exit();
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi CSDL: " . $conn->error . "</div>";
        }
    }
}

// 2. XỬ LÝ XÓA DANH MỤC
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Kiểm tra xem danh mục này có đang chứa sản phẩm không
    $check_products = $conn->query("SELECT id FROM products WHERE category_id = $del_id");
    if ($check_products->num_rows > 0) {
        header("Location: categories.php?msg=err_has_products");
        exit();
    } else {
        $conn->query("DELETE FROM categories WHERE id = $del_id");
        header("Location: categories.php?msg=del_success");
        exit();
    }
}

// 3. XỬ LÝ ẨN / HIỆN DANH MỤC
if (isset($_GET['toggle_id'])) {
    $toggle_id = (int)$_GET['toggle_id'];
    $res = $conn->query("SELECT is_active FROM categories WHERE id = $toggle_id");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $new_status = ($row['is_active'] == 1) ? 0 : 1; 
        $conn->query("UPDATE categories SET is_active = $new_status WHERE id = $toggle_id");
        header("Location: categories.php?msg=toggle_success");
        exit();
    }
}

// BẮT THÔNG BÁO TỪ URL
if (isset($_GET['msg']) && $msg == "") {
    if ($_GET['msg'] == 'add_success') $msg = "<div class='alert alert-success'>Thêm danh mục thành công!</div>";
    elseif ($_GET['msg'] == 'del_success') $msg = "<div class='alert alert-success'>Xóa danh mục thành công!</div>";
    elseif ($_GET['msg'] == 'toggle_success') $msg = "<div class='alert alert-success'>Cập nhật trạng thái thành công!</div>";
    elseif ($_GET['msg'] == 'err_has_products') $msg = "<div class='alert alert-warning'>Không thể xóa! Danh mục này đang chứa sản phẩm. Gợi ý: Hãy dùng nút Ẩn thay vì Xóa.</div>";
}

// LẤY DANH SÁCH DANH MỤC
$sql_categories = "SELECT * FROM categories ORDER BY id DESC";
$result_categories = $conn->query($sql_categories);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Danh mục - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
    
        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Quản lý Danh mục</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span>
                </div>
            </div>

            <div class="content p-3">
                <?php if($msg != "") echo $msg; ?>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 fw-bold text-success">
                                <i class="fa-solid fa-folder-plus me-2"></i>Thêm Danh Mục Mới
                            </div>
                            <div class="card-body bg-light">
                                <form method="POST" action="categories.php" enctype="multipart/form-data">                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Tên danh mục <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required placeholder="VD: Phân bón hóa học">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Hình ảnh đại diện</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                        <small class="text-muted">Chỉ hỗ trợ JPG, PNG, WEBP</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Mô tả chi tiết</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả ngắn..."></textarea>
                                    </div>
                                    <div class="mb-4 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                        <label class="form-check-label fw-bold small" for="isActive">Hiển thị trên Website</label>
                                    </div>
                                    <button type="submit" name="btnAddCategory" class="btn btn-success w-100 fw-bold">
                                        <i class="fa-solid fa-plus me-2"></i>Lưu Danh Mục
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 fw-bold text-success">
                                <i class="fa-solid fa-list me-2"></i>Danh Sách Danh Mục
                            </div>
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">ID</th>
                                            <th>Ảnh</th>
                                            <th>Tên danh mục</th>
                                            <th>Trạng thái</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result_categories->num_rows > 0) {
                                            while($row = $result_categories->fetch_assoc()) { 
                                        ?>
                                            <tr>
                                                <td class="ps-3 text-muted fw-bold">#<?php echo $row['id']; ?></td>
                                                <td>
                                                    <?php if ($row['image']) { ?>
                                                        <img src="../<?php echo $row['image']; ?>" alt="Ảnh" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php } else { ?>
                                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px;">
                                                            <i class="fa-solid fa-image"></i>
                                                        </div>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark"><?php echo $row['name']; ?></span><br>
                                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;">
                                                        <?php echo $row['description'] ?: 'Không có mô tả'; ?>
                                                    </small>
                                                </td>
                                                
                                                <td>
                                                    <?php if ($row['is_active'] == 1) { ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Đang hiển thị</span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Đang ẩn</span>
                                                    <?php } ?>
                                                </td>

                                                <td class="text-center">
                                                    <?php if ($row['is_active'] == 1) { ?>
                                                        <a href="categories.php?toggle_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" title="Ẩn danh mục">
                                                            <i class="fa-solid fa-eye-slash"></i>
                                                        </a>
                                                    <?php } else { ?>
                                                        <a href="categories.php?toggle_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" title="Hiện danh mục">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </a>
                                                    <?php } ?>

                                                    <a href="categories.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');" title="Xóa">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php 
                                            } 
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center py-4'>Chưa có danh mục nào!</td></tr>";
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