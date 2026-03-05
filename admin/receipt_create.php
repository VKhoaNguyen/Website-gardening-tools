<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_level'] == 0) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/connect.php';

$page = 'receipts'; 
$msg = "";

// 1. LẤY DANH SÁCH NHÀ CUNG CẤP VÀ SẢN PHẨM
// FIX: Chỉ lấy những nhà cung cấp ĐANG GIAO DỊCH (is_active = 1)
$suppliers = $conn->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name ASC");

$products = $conn->query("SELECT id, name FROM products ORDER BY name ASC");
$product_options = "";
while($p = $products->fetch_assoc()) {
    $product_options .= "<option value='".$p['id']."'>".htmlspecialchars($p['name'], ENT_QUOTES)."</option>";
}

// 2. XỬ LÝ LƯU PHIẾU VÀO DATABASE
if (isset($_POST['btnSaveReceipt'])) {
    $supplier_id = (int)$_POST['supplier_id'];
    $user_id = $_SESSION['user']['id'];
    $note = $conn->real_escape_string($_POST['note']);

    // BẢO MẬT: Kiểm tra lại 1 lần nữa xem NCC này còn active không (Phòng hờ lỗi F5)
    $check_sup = $conn->query("SELECT is_active FROM suppliers WHERE id = $supplier_id");
    if ($check_sup->num_rows > 0) {
        $sup_status = $check_sup->fetch_assoc()['is_active'];
        if ($sup_status == 0) {
            $msg = "<div class='alert alert-danger'>Lỗi: Nhà cung cấp này hiện đang bị Tạm ngừng giao dịch! Vui lòng chọn nhà cung cấp khác.</div>";
        } else {
            // Mảng dữ liệu ok, tiếp tục xử lý
            $product_ids = isset($_POST['product_id']) ? $_POST['product_id'] : [];
            $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];
            $import_prices = isset($_POST['import_price']) ? $_POST['import_price'] : [];

            if (empty($product_ids)) {
                $msg = "<div class='alert alert-danger'>Vui lòng thêm ít nhất 1 sản phẩm vào phiếu nhập!</div>";
            } else {
                // A. Tính tổng tiền
                $total_money = 0;
                for ($i = 0; $i < count($product_ids); $i++) {
                    $total_money += ((int)$quantities[$i] * (int)$import_prices[$i]);
                }

                // B. Lưu Phiếu Nhập
                $sql_receipt = "INSERT INTO stock_receipts (supplier_id, user_id, total_money, note, status) 
                                VALUES ($supplier_id, $user_id, $total_money, '$note', 0)";
                
                if ($conn->query($sql_receipt)) {
                    $receipt_id = $conn->insert_id; 

                    // C. Lưu Chi tiết
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $p_id = (int)$product_ids[$i];
                        $qty = (int)$quantities[$i];
                        $price = (int)$import_prices[$i];

                        if ($qty > 0) {
                            $conn->query("INSERT INTO receipt_details (receipt_id, product_id, quantity, import_price) 
                                          VALUES ($receipt_id, $p_id, $qty, $price)");
                        }
                    }

                    header("Location: receipts.php?msg=success");
                    exit();
                } else {
                    $msg = "<div class='alert alert-danger'>Lỗi DB: " . $conn->error . "</div>";
                }
            }
        }
    } else {
        $msg = "<div class='alert alert-danger'>Lỗi: Không tìm thấy Nhà cung cấp hợp lệ!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lập Phiếu Nhập - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="w-100">
            <div class="topbar d-flex justify-content-between align-items-center p-3">
                <h5 class="mb-0 text-muted">Lập Phiếu Nhập Kho</h5>
                <div>
                    <span class="fw-bold"><i class="fa-solid fa-user-circle fs-4 text-primary align-middle"></i> <?php echo $_SESSION['user']['fullname']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if($msg != "") echo $msg; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-primary mb-0"><i class="fa-solid fa-file-circle-plus me-2"></i>Tạo Phiếu Nhập Kho</h4>
                    <a href="receipts.php" class="btn btn-outline-secondary shadow-sm">
                        <i class="fa-solid fa-arrow-left me-2"></i> Quay lại danh sách
                    </a>
                </div>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-3 fw-bold text-primary">
                                    <i class="fa-solid fa-file-invoice me-2"></i>Thông Tin Phiếu
                                </div>
                                <div class="card-body bg-light">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Nhà cung cấp <span class="text-danger">*</span></label>
                                        <select name="supplier_id" class="form-select" required>
                                            <option value="">-- Chọn Nhà cung cấp --</option>
                                            <?php 
                                            if ($suppliers->num_rows > 0) {
                                                while($sup = $suppliers->fetch_assoc()) {
                                                    // Dropdown giờ đây chỉ hiện những người có is_active = 1
                                                    echo "<option value='".$sup['id']."'>".$sup['name']."</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold small">Ghi chú phiếu nhập</label>
                                        <textarea name="note" class="form-control" rows="3" placeholder="Nhập đợt 1, thanh toán tiền mặt..."></textarea>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <span class="fw-bold fs-5">TỔNG TIỀN:</span>
                                        <span class="text-danger fw-bold fs-4" id="displayTotal">0 đ</span>
                                    </div>
                                    <button type="submit" name="btnSaveReceipt" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                                        <i class="fa-solid fa-floppy-disk me-2"></i>LƯU PHIẾU NHÁP
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <div class="fw-bold text-primary"><i class="fa-solid fa-boxes-stacked me-2"></i>Danh Sách Sản Phẩm Nhập</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addRow()">
                                        <i class="fa-solid fa-plus me-1"></i> Thêm Dòng
                                    </button>
                                </div>
                                <div class="card-body p-0 table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="receiptTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="45%" class="ps-3">Tên Dụng cụ / Sản phẩm</th>
                                                <th width="20%">Số lượng</th>
                                                <th width="25%">Giá nhập (đ/cái)</th>
                                                <th width="10%" class="text-center">Xóa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div> 
                </form>
            </div> 
        </div>
    </div>

    <script>
        const productOptions = `<?php echo $product_options; ?>`;

        function addRow() {
            const tbody = document.querySelector("#receiptTable tbody");
            const tr = document.createElement("tr");
            
            tr.innerHTML = `
                <td class="ps-3">
                    <select name="product_id[]" class="form-select" required>
                        <option value="">-- Chọn sản phẩm --</option>
                        ${productOptions}
                    </select>
                </td>
                <td>
                    <input type="number" name="quantity[]" class="form-control text-center qty-input" min="1" value="1" required onchange="calculateTotal()" onkeyup="calculateTotal()">
                </td>
                <td>
                    <input type="number" name="import_price[]" class="form-control text-end price-input" min="0" value="0" required onchange="calculateTotal()" onkeyup="calculateTotal()">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            calculateTotal(); 
        }

        function removeRow(button) {
            const tr = button.closest("tr");
            tr.remove();
            calculateTotal(); 
        }

        function calculateTotal() {
            const qtyInputs = document.querySelectorAll('.qty-input');
            const priceInputs = document.querySelectorAll('.price-input');
            let total = 0;

            for (let i = 0; i < qtyInputs.length; i++) {
                const qty = parseInt(qtyInputs[i].value) || 0;
                const price = parseInt(priceInputs[i].value) || 0;
                total += (qty * price);
            }

            document.getElementById('displayTotal').innerText = total.toLocaleString('vi-VN') + ' đ';
        }

        window.onload = function() {
            addRow();
        };
    </script>
</body>
</html>