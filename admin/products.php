<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

$msg = "";

// KIỂM TRA QUYỀN: Giả sử role_level == 1 là Admin (Quản lý), có quyền xem/sửa Giá nhập. Các role khác là Nhân viên.
$isAdmin = ($_SESSION['user']['role_level'] == 1) ? true : false;

// 1. XỬ LÝ THÊM SẢN PHẨM MỚI
if (isset($_POST['btnAdd'])) {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $category_id = (int)$_POST['category_id'];
    
    // KiotViet Logic: Nếu là Admin thì lấy giá nhập từ Form, nếu là Nhân viên thì gán bằng 0
    $import_price = $isAdmin && isset($_POST['import_price']) ? (float)$_POST['import_price'] : 0;
    $price = (float)$_POST['price'];
    
    $unit = $conn->real_escape_string(trim($_POST['unit']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $instruction = $conn->real_escape_string(trim($_POST['instruction']));
    
    // KIỂM TRA LOGIC GIÁ (Chỉ chặn khi có giá nhập > 0)
    if ($import_price > 0 && $price <= $import_price) {
        $msg = "<div class='alert alert-danger'>Lỗi: Giá bán ra phải cao hơn Giá nhập vào!</div>";
    } else {
        $image_path = ""; 

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../assets/images/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $file_name = time() . '_' . rand(100,999) . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = 'assets/images/products/' . $file_name; 
            }
        }

        $sql_insert = "INSERT INTO products (category_id, name, image, import_price, price, stock, unit, description, instruction) 
                       VALUES ('$category_id', '$name', '$image_path', '$import_price', '$price', 0, '$unit', '$description', '$instruction')";
        
        if ($conn->query($sql_insert)) {
            header("Location: products.php?msg=add_success");
            exit();
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
        }
    }
}

// 2. XỬ LÝ CẬP NHẬT (SỬA) SẢN PHẨM
if (isset($_POST['btnUpdate'])) {
    $id = (int)$_POST['product_id'];
    $name = $conn->real_escape_string(trim($_POST['name']));
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    
    // KiotViet Logic: Bảo toàn dữ liệu Giá nhập
    if ($isAdmin && isset($_POST['import_price'])) {
        $import_price = (float)$_POST['import_price'];
    } else {
        // Nếu là nhân viên đang sửa đổi, lấy lại giá nhập cũ từ DB để không bị mất
        $res_old = $conn->query("SELECT import_price FROM products WHERE id = $id");
        $import_price = $res_old->fetch_assoc()['import_price'];
    }
    
    $unit = $conn->real_escape_string(trim($_POST['unit']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $instruction = $conn->real_escape_string(trim($_POST['instruction']));
    $old_image = $_POST['old_image']; 

    // KIỂM TRA LOGIC GIÁ (Chống rò rỉ giá gốc)
    if ($import_price > 0 && $price <= $import_price) {
        if ($isAdmin) {
            // Admin thì cho thấy rõ giá gốc để dễ sửa
            $msg = "<div class='alert alert-danger'>Lỗi: Giá bán ra phải cao hơn Giá nhập vào (".number_format($import_price)."đ)!</div>";
        } else {
            // Nhân viên gõ giá thấp hơn giá vốn -> Báo lỗi mập mờ, không tiết lộ con số
            $msg = "<div class='alert alert-danger'>Lỗi: Giá bán bạn nhập đang thấp hơn mức Giá vốn quy định. Vui lòng báo cáo Quản lý!</div>";
        }
        
    } else {
        $image_path = $old_image; 

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../assets/images/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $file_name = time() . '_' . rand(100,999) . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = 'assets/images/products/' . $file_name;
                
                if($old_image != "" && file_exists("../".$old_image)){
                    unlink("../".$old_image);
                }
            }
        }

        $sql_update = "UPDATE products SET 
                        category_id='$category_id', 
                        name='$name', 
                        image='$image_path', 
                        import_price='$import_price',
                        price='$price', 
                        unit='$unit',
                        description='$description',
                        instruction='$instruction' 
                       WHERE id=$id";    
        
        if ($conn->query($sql_update)) {
            header("Location: products.php?msg=update_success");
            exit();
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
        }
    }
}

// 3. XỬ LÝ XÓA SẢN PHẨM
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    
    $check_order = $conn->query("SELECT product_id FROM order_details WHERE product_id = $id LIMIT 1");
    if ($check_order->num_rows > 0) {
        header("Location: products.php?msg=err_in_use");
        exit();
    } else {
        $res = $conn->query("SELECT image FROM products WHERE id = $id");
        if($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if($row['image'] != "" && file_exists("../".$row['image'])) {
                unlink("../".$row['image']); 
            }
        }
        $conn->query("DELETE FROM products WHERE id = $id");
        header("Location: products.php?msg=del_success");
        exit();
    }
}

// 4. KIỂM TRA CÓ ĐANG Ở CHẾ ĐỘ SỬA KHÔNG
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $id_edit = (int)$_GET['edit_id'];
    $result_edit = $conn->query("SELECT * FROM products WHERE id = $id_edit");
    if ($result_edit->num_rows > 0) {
        $edit_data = $result_edit->fetch_assoc();
    }
}

// 5. LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");

$sql_prods = "SELECT products.*, categories.name as cat_name 
              FROM products 
              LEFT JOIN categories ON products.category_id = categories.id 
              ORDER BY products.id DESC";
$products = $conn->query($sql_prods);

if (isset($_GET['msg']) && $msg == "") {
    if ($_GET['msg'] == 'add_success') {
        $msg = "<div class='alert alert-success'>Thêm sản phẩm thành công!</div>";
    } elseif ($_GET['msg'] == 'update_success') {
        $msg = "<div class='alert alert-success'>Cập nhật sản phẩm thành công!</div>";
    } elseif ($_GET['msg'] == 'del_success') {
        $msg = "<div class='alert alert-success'>Xóa sản phẩm thành công!</div>";
    } elseif ($_GET['msg'] == 'err_in_use') {
        $msg = "<div class='alert alert-warning'>Không thể xóa! Sản phẩm này đã phát sinh giao dịch (nằm trong Đơn hàng).</div>";
    }
}

$page = 'products';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Sản phẩm - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Quản lý Sản phẩm</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span>
                </div>
            </div>

            <div class="content p-3">
                <?php if($msg != "") echo $msg; ?>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 fw-bold text-primary">
                                <i class="fa-solid fa-box-open me-2"></i><?php echo $edit_data ? "Sửa Thông Tin Sản Phẩm" : "Thêm Sản Phẩm Mới"; ?>
                            </div>
                            <div class="card-body bg-light">
                                <form method="POST" action="products.php" enctype="multipart/form-data">
                                    
                                    <?php if ($edit_data) { ?>
                                        <input type="hidden" name="product_id" value="<?php echo $edit_data['id']; ?>">
                                        <input type="hidden" name="old_image" value="<?php echo $edit_data['image']; ?>">
                                    <?php } ?>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Tên sản phẩm <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required value="<?php echo $edit_data ? $edit_data['name'] : ''; ?>" placeholder="VD: Kéo cắt cành mỏ cong">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold small">Danh mục <span class="text-danger">*</span></label>
                                            <select name="category_id" class="form-select" required>
                                                <option value="">-- Chọn --</option>
                                                <?php 
                                                if ($categories->num_rows > 0) {
                                                    while($cat = $categories->fetch_assoc()) {
                                                        $selected = ($edit_data && $edit_data['category_id'] == $cat['id']) ? 'selected' : '';
                                                        echo "<option value='".$cat['id']."' $selected>".$cat['name']."</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold small">Đơn vị tính <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select name="unit" id="unitSelect" class="form-select" required>
                                                    <?php 
                                                    $units = ['Cái', 'Bao', 'Hộp', 'Chai', 'Gói', 'Kg', 'Lít'];
                                                    $cur_unit = $edit_data ? $edit_data['unit'] : 'Cái';
                                                    
                                                    if (!in_array($cur_unit, $units) && $cur_unit != '') {
                                                        array_unshift($units, $cur_unit); 
                                                    }
                                                    
                                                    foreach($units as $u) {
                                                        $sel = ($cur_unit == $u) ? 'selected' : '';
                                                        echo "<option value='$u' $sel>$u</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#addUnitModal" title="Thêm đơn vị mới"><i class="fa-solid fa-plus"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <?php if ($isAdmin) { ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold small text-secondary">Giá nhập (VNĐ) <span class="text-danger">*</span></label>
                                                <input type="number" name="import_price" class="form-control" required value="<?php echo $edit_data ? $edit_data['import_price'] : '0'; ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold small text-success">Giá xuất (VNĐ) <span class="text-danger">*</span></label>
                                                <input type="number" name="price" class="form-control border-success" required value="<?php echo $edit_data ? $edit_data['price'] : ''; ?>">
                                            </div>
                                        <?php } else { ?>
                                            <div class="col-12 mb-3">
                                                <label class="form-label fw-bold small text-success">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                                                <input type="number" name="price" class="form-control border-success" required value="<?php echo $edit_data ? $edit_data['price'] : ''; ?>">
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Hình ảnh <?php echo $edit_data ? "<small class='text-muted'>(Bỏ trống nếu giữ nguyên)</small>" : "<span class='text-danger'>*</span>"; ?></label>
                                        <input type="file" name="image" class="form-control" accept="image/*" <?php echo $edit_data ? "" : "required"; ?>>
                                        
                                        <?php if ($edit_data && $edit_data['image'] != '') { ?>
                                            <div class="mt-2 text-center border p-2 bg-white rounded">
                                                <small class="text-muted d-block mb-1">Ảnh hiện tại:</small>
                                                <img src="../<?php echo $edit_data['image']; ?>" style="height: 80px; object-fit: contain;">
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Mô tả sản phẩm</label>
                                        <textarea name="description" class="form-control" rows="3"><?php echo $edit_data ? $edit_data['description'] : ''; ?></textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold small">Hướng dẫn sử dụng</label>
                                        <textarea name="instruction" class="form-control" rows="2" placeholder="Cách dùng, tỷ lệ pha..."><?php echo $edit_data ? $edit_data['instruction'] : ''; ?></textarea>
                                    </div>
                                    
                                    <?php if ($edit_data) { ?>
                                        <button type="submit" name="btnUpdate" class="btn btn-warning w-100 mb-2 fw-bold"><i class="fa-solid fa-save me-2"></i>Lưu Thay Đổi</button>
                                        <a href="products.php" class="btn btn-outline-secondary w-100 fw-bold">Hủy Bỏ</a>
                                    <?php } else { ?>
                                        <button type="submit" name="btnAdd" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-plus me-2"></i>Thêm Sản Phẩm</button>
                                    <?php } ?>

                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 fw-bold text-primary">
                                <i class="fa-solid fa-list me-2"></i>Danh Sách Dụng Cụ & Vật Tư
                            </div>
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">STT</th>
                                            <th>Sản phẩm</th>
                                            <th>Kho</th>
                                            <?php if ($isAdmin) { ?>
                                                <th>Giá nhập / Giá xuất</th>
                                            <?php } else { ?>
                                                <th>Giá bán / Đơn vị</th>
                                            <?php } ?>
                                            <th>Thống kê</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($products->num_rows > 0) {
                                            $stt = 1;
                                            while($row = $products->fetch_assoc()) { 
                                        ?>
                                            <tr>
                                                <td class="ps-3 fw-bold text-secondary"><?php echo $stt++; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <?php if($row['image'] != '') { ?>
                                                                <img src="../<?php echo $row['image']; ?>" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                                            <?php } else { ?>
                                                                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="fa-solid fa-box"></i></div>
                                                            <?php } ?>
                                                        </div>
                                                        <div>
                                                            <span class="fw-bold text-dark d-block"><?php echo mb_strimwidth($row['name'], 0, 40, "..."); ?></span>
                                                            <span class="badge bg-info-subtle text-info border border-info-subtle mt-1"><?php echo $row['cat_name']; ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                
                                                <td>
                                                    <?php if ($row['stock'] > 10) { ?>
                                                        <span class="badge bg-success rounded-pill px-2"><?php echo $row['stock']; ?></span>
                                                    <?php } elseif ($row['stock'] > 0) { ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-2"><?php echo $row['stock']; ?> (Sắp hết)</span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-danger rounded-pill px-2">Hết hàng</span>
                                                    <?php } ?>
                                                </td>
                                                
                                                <td>
                                                    <?php if ($isAdmin) { ?>
                                                        <span class="text-secondary small">Nhập: <?php echo number_format($row['import_price'], 0, ',', '.'); ?> ₫</span><br>
                                                    <?php } ?>
                                                    <span class="text-danger fw-bold">Xuất: <?php echo number_format($row['price'], 0, ',', '.'); ?> ₫</span>
                                                    <small class="text-muted">/ <?php echo $row['unit']; ?></small>
                                                </td>
                                                
                                                <td>
                                                    <small class="text-muted d-block"><i class="fa-regular fa-eye me-1"></i> Xem: <b><?php echo $row['views']; ?></b></small>
                                                    <small class="text-success"><i class="fa-solid fa-cart-shopping me-1"></i> Bán: <b><?php echo $row['sold_count']; ?></b></small>
                                                </td>

                                                <td class="text-center">
                                                    <a href="products.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" title="Sửa">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="products.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');" title="Xóa">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php 
                                            } 
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center text-muted py-4'>Chưa có sản phẩm nào. Hãy thêm mới ở form bên cạnh.</td></tr>";
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

    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6 fw-bold" id="addUnitModalLabel"><i class="fa-solid fa-circle-plus me-2"></i>Thêm Đơn Vị Tính Mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Tên đơn vị tính <span class="text-danger">*</span></label>
                        <input type="text" id="newUnitInput" class="form-control" placeholder="VD: Cuộn, Lố, Mét, Gram...">
                        <span id="unitError" class="text-danger small d-none mt-1">Vui lòng nhập tên đơn vị tính!</span>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveNewUnit()">Lưu Thông Tin</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // JS Xử lý Lưu Đơn vị tính từ Modal vào thẻ select
        function saveNewUnit() {
            let inputEl = document.getElementById("newUnitInput");
            let newUnit = inputEl.value.trim();
            let errorSpan = document.getElementById("unitError");
            
            if (newUnit === "") {
                errorSpan.classList.remove("d-none");
                inputEl.focus();
                return;
            }
            errorSpan.classList.add("d-none");
            
            let select = document.getElementById("unitSelect");
            
            // Kiểm tra chống trùng lặp
            let exists = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value.toLowerCase() === newUnit.toLowerCase()) {
                    exists = true;
                    break;
                }
            }
            
            if (!exists) {
                let option = document.createElement("option");
                option.text = newUnit;
                option.value = newUnit;
                select.add(option);
            }
            
            select.value = newUnit; 
            inputEl.value = "";
            
            let modalEl = document.getElementById('addUnitModal');
            let modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance.hide();
        }

        // Bắt sự kiện Enter trong Modal
        document.getElementById("newUnitInput").addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault(); 
                saveNewUnit();
            }
        });
    </script>
</body>
</html>