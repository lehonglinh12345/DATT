<?php
ob_start();
$page_title = "Quản Lý Sản Phẩm";
$active_admin_tab = "products";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Categories for dropdown
$categories_res = db_query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
if ($categories_res) {
    while ($cat = $categories_res->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// --------------------------------------------------------------------------
// Delete Product
// --------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } else {
        // Find product first to delete image file if custom
        $check = db_query("SELECT image FROM products WHERE id = ?", "i", [$id]);
        if ($check && $check->num_rows > 0) {
            $product = $check->fetch_assoc();
            $delete = db_query("DELETE FROM products WHERE id = ?", "i", [$id]);
            if ($delete) {
                // Optionally delete physical image if it is an uploaded file
                if (!empty($product['image']) && file_exists(__DIR__ . '/../../frontend/' . $product['image']) && !in_array($product['image'], ['images/tang-luc-x3.jpg', 'images/nuoi-dong.jpg', 'images/bio-prep.jpg', 'images/chem-bag.jpg'])) {
                    @unlink(__DIR__ . '/../../frontend/' . $product['image']);
                }
                $success = "Xóa sản phẩm thành công!";
            } else {
                $error = "Không thể xóa sản phẩm. Có thể có ràng buộc khóa ngoại.";
            }
        } else {
            $error = "Sản phẩm không tồn tại.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// Process Save (Add / Edit)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    if (!auth_csrf_verify()) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } else {
        $name = trim($_POST['name'] ?? '');
        $product_key = trim($_POST['product_key'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = trim($_POST['price'] ?? 'Liên hệ báo giá');
        $origin = trim($_POST['origin'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $badge_class = trim($_POST['badge_class'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $views = (int)($_POST['views'] ?? 0);
        $sales_count = (int)($_POST['sales_count'] ?? 0);

        // Auto-slugify product key if blank
        if (empty($product_key) && !empty($name)) {
            // Simple slug conversion
            $slug = strtolower($name);
            $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $product_key = trim($slug, '-');
        }

        if (empty($name) || $category_id <= 0) {
            $error = "Tên sản phẩm và Danh mục là thông tin bắt buộc.";
        } else {
            // Check uniqueness of product_key
            $checkKeySQL = "SELECT id FROM products WHERE product_key = ? AND id != ?";
            $checkKey = db_query($checkKeySQL, "si", [$product_key, $id]);
            if ($checkKey && $checkKey->num_rows > 0) {
                // Append random string to duplicate product key to avoid conflict
                $product_key .= '-' . rand(100, 999);
            }

            // Handle Image Upload
            $image_path = $_POST['existing_image'] ?? 'images/chem-bag.jpg';
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['product_image']['tmp_name'];
                $file_name = $_FILES['product_image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($file_ext, $allowed_exts)) {
                    // Make unique name
                    $new_file_name = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    $upload_dir = __DIR__ . '/../../frontend/images';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    if (move_uploaded_file($file_tmp, $upload_dir . '/' . $new_file_name)) {
                        $image_path = 'images/' . $new_file_name;
                        // Clean up old custom image
                        if ($action === 'edit' && !empty($_POST['existing_image']) && !in_array($_POST['existing_image'], ['images/tang-luc-x3.jpg', 'images/nuoi-dong.jpg', 'images/bio-prep.jpg', 'images/chem-bag.jpg'])) {
                            @unlink(__DIR__ . '/../../frontend/' . $_POST['existing_image']);
                        }
                    } else {
                        $error = "Lỗi khi lưu trữ file hình ảnh.";
                    }
                } else {
                    $error = "Định dạng file ảnh không hợp lệ. Chỉ chấp nhận: " . implode(', ', $allowed_exts);
                }
            }

            if (empty($error)) {
                if ($action === 'add') {
                    $insertSQL = "INSERT INTO products (product_key, name, category_id, badge, badge_class, origin, price, image, views, sales_count, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insert = db_query($insertSQL, "ssisssssiis", [$product_key, $name, $category_id, $badge, $badge_class, $origin, $price, $image_path, $views, $sales_count, $description]);
                    if ($insert) {
                        $id = $database->insert_id;
                    } else {
                        $error = "Lỗi khi lưu sản phẩm vào cơ sở dữ liệu.";
                    }
                } else {
                    $updateSQL = "UPDATE products SET product_key = ?, name = ?, category_id = ?, badge = ?, badge_class = ?, origin = ?, price = ?, image = ?, views = ?, sales_count = ?, description = ? WHERE id = ?";
                    $update = db_query($updateSQL, "ssisssssiisi", [$product_key, $name, $category_id, $badge, $badge_class, $origin, $price, $image_path, $views, $sales_count, $description, $id]);
                    if (!$update) {
                        $error = "Lỗi khi cập nhật cơ sở dữ liệu.";
                    }
                }

                if (empty($error) && $id > 0) {
                    // Handle deletion of existing additional images
                    if (isset($_POST['delete_additional_images']) && is_array($_POST['delete_additional_images'])) {
                        foreach ($_POST['delete_additional_images'] as $del_img_id) {
                            $del_img_id = (int)$del_img_id;
                            $img_info = db_query("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?", "ii", [$del_img_id, $id]);
                            if ($img_info && $img_info->num_rows > 0) {
                                $img_row = $img_info->fetch_assoc();
                                if (file_exists(__DIR__ . '/../../frontend/' . $img_row['image_path']) && !in_array($img_row['image_path'], ['images/nuoi-dong.jpg', 'images/bio-prep.jpg'])) {
                                    @unlink(__DIR__ . '/../../frontend/' . $img_row['image_path']);
                                }
                                db_query("DELETE FROM product_images WHERE id = ?", "i", [$del_img_id]);
                            }
                        }
                    }

                    // Handle upload of new additional images
                    if (isset($_FILES['additional_images'])) {
                        $files = $_FILES['additional_images'];
                        $file_count = count($files['name']);
                        for ($i = 0; $i < $file_count; $i++) {
                            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                                $file_tmp = $files['tmp_name'][$i];
                                $file_name = $files['name'][$i];
                                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                                if (in_array($file_ext, $allowed_exts)) {
                                    $new_file_name = 'prod_add_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                                    $upload_dir = __DIR__ . '/../../frontend/images';
                                    if (move_uploaded_file($file_tmp, $upload_dir . '/' . $new_file_name)) {
                                        $add_image_path = 'images/' . $new_file_name;
                                        db_query("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)", "isi", [$id, $add_image_path, $i]);
                                    }
                                }
                            }
                        }
                    }

                    $success = ($action === 'add') ? "Thêm sản phẩm mới thành công!" : "Cập nhật thông tin sản phẩm thành công!";
                    header('refresh:1.5;url=products.php');
                    $action = 'list';
                }
            }
        }
    }
}

// --------------------------------------------------------------------------
// Form Rendering (Add / Edit)
// --------------------------------------------------------------------------
if ($action === 'add' || $action === 'edit'):
    $product = [
        'name' => '', 'product_key' => '', 'category_id' => '', 'price' => 'Liên hệ báo giá', 
        'origin' => '', 'badge' => '', 'badge_class' => '', 'image' => '', 'description' => '',
        'views' => 0, 'sales_count' => 0
    ];
    if ($action === 'edit' && $id > 0) {
        $res = db_query("SELECT * FROM products WHERE id = ?", "i", [$id]);
        if ($res && $res->num_rows > 0) {
            $product = $res->fetch_assoc();
        } else {
            $error = "Không tìm thấy sản phẩm.";
            $action = 'list';
        }
    }
    
    if ($action !== 'list'):
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-square-plus"></i> <?= $action === 'add' ? 'Thêm Sản Phẩm Mới' : 'Sửa Thông Tin Sản Phẩm' ?></h3>
            <a href="products.php" class="btn btn-outline btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </div>
        <div class="admin-card-body">
            <?php if (!empty($error)): ?>
                <div class="admin-alert admin-alert-danger"><?= h($error) ?></div>
            <?php endif; ?>
            
            <form action="products.php?action=<?= $action ?>&id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="admin-form">
                <?= auth_csrf_token_field() ?>
                <input type="hidden" name="existing_image" value="<?= h($product['image']) ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Tên sản phẩm <span style="color:red">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Ví dụ: Phân bón lá Amino" required value="<?= h($product['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="product_key">Mã định danh (Slug Key) - Để trống sẽ tự tạo</label>
                        <input type="text" id="product_key" name="product_key" class="form-control" placeholder="Ví dụ: phan-bon-amino" value="<?= h($product['product_key']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Danh mục sản phẩm <span style="color:red">*</span></label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= h($cat['name']) ?> (<?= h($cat['type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price">Giá hiển thị</label>
                        <input type="text" id="price" name="price" class="form-control" placeholder="Ví dụ: Liên hệ báo giá, 50.000đ" value="<?= h($product['price']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="origin">Xuất xứ / Thương hiệu</label>
                        <input type="text" id="origin" name="origin" class="form-control" placeholder="Ví dụ: Nhập khẩu Hàn Quốc" value="<?= h($product['origin']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="badge">Nhãn sản phẩm (Badge)</label>
                        <input type="text" id="badge" name="badge" class="form-control" placeholder="Ví dụ: Nông Nghiệp, Hóa Chất" value="<?= h($product['badge']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="badge_class">Lớp CSS cho nhãn (Badge Class)</label>
                        <select id="badge_class" name="badge_class" class="form-control">
                            <option value="" <?= empty($product['badge_class']) ? 'selected' : '' ?>>Mặc định (Màu xanh lá)</option>
                            <option value="badge-bio" <?= $product['badge_class'] === 'badge-bio' ? 'selected' : '' ?>>Vi Sinh (Màu xanh dương)</option>
                            <option value="badge-chemical" <?= $product['badge_class'] === 'badge-chemical' ? 'selected' : '' ?>>Hóa Chất (Màu cam)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product_image">Hình ảnh sản phẩm</label>
                        <input type="file" id="product_image" name="product_image" class="form-control" accept="image/*">
                        <?php if (!empty($product['image'])): ?>
                            <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                                <img src="../../frontend/<?= h($product['image']) ?>" alt="Ảnh hiện tại" style="max-height: 80px; border-radius: 6px; border: 1px solid var(--color-admin-border);">
                                <span style="font-size: 0.8rem; color: var(--color-admin-text-muted);">Đường dẫn: <?= h($product['image']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="views">Số lượt xem</label>
                        <input type="number" id="views" name="views" class="form-control" min="0" value="<?= isset($product['views']) ? (int)$product['views'] : 0 ?>">
                    </div>
                    <div class="form-group">
                        <label for="sales_count">Số lượng đã bán</label>
                        <input type="number" id="sales_count" name="sales_count" class="form-control" min="0" value="<?= isset($product['sales_count']) ? (int)$product['sales_count'] : 0 ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="additional_images">Hình ảnh bổ sung (Trưng bày Slider - Chọn nhiều ảnh)</label>
                    <input type="file" id="additional_images" name="additional_images[]" class="form-control" accept="image/*" multiple style="margin-bottom: 10px;">
                    
                    <?php
                    // Fetch existing additional images for editing
                    $additional_images = [];
                    if ($action === 'edit' && $id > 0) {
                        $img_res = db_query("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", "i", [$id]);
                        if ($img_res) {
                            while ($img_row = $img_res->fetch_assoc()) {
                                $additional_images[] = $img_row;
                            }
                        }
                    }
                    if (!empty($additional_images)):
                    ?>
                        <label style="font-size: 0.88rem; font-weight: 600; margin-top: 10px; display: block;">Hình ảnh bổ sung hiện tại (Tích để chọn xóa):</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
                            <?php foreach ($additional_images as $img): ?>
                                <div style="position: relative; width: 90px; height: 90px; border: 1px solid var(--color-admin-border); border-radius: 8px; overflow: hidden; background: #f8fafc; padding: 4px; box-sizing: border-box; display: flex; align-items: center; justify-content: center;">
                                    <img src="../../frontend/<?= h($img['image_path']) ?>" alt="Ảnh phụ" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(231, 76, 60, 0.85); display: flex; justify-content: center; align-items: center; padding: 3px;">
                                        <input type="checkbox" name="delete_additional_images[]" value="<?= $img['id'] ?>" title="Chọn để xóa" style="cursor: pointer; width: 14px; height: 14px; margin: 0;">
                                        <span style="color: white; font-size: 0.68rem; font-weight: 700; margin-left: 4px; cursor: pointer;">XÓA</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Mô tả sản phẩm</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Nhập nội dung mô tả chi tiết sản phẩm..."><?= h($product['description']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;">
                        Lưu Thay Đổi <i class="fa-solid fa-floppy-disk"></i>
                    </button>
                    <a href="products.php" class="btn btn-outline" style="border-radius: 8px; border-color: var(--color-admin-border); color: var(--color-admin-text-dark);">Hủy bỏ</a>
                </div>
            </form>
        </div>
    </div>
<?php 
    endif;
endif; 

// --------------------------------------------------------------------------
// Products List View
// --------------------------------------------------------------------------
if ($action === 'list'):
    // Setup Search & Category Filter
    $search = trim($_GET['search'] ?? '');
    $cat_filter = (int)($_GET['category'] ?? 0);

    $sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1=1";
    $types = "";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (p.name LIKE ? OR p.product_key LIKE ?)";
        $search_wildcard = "%$search%";
        $types .= "ss";
        $params[] = $search_wildcard;
        $params[] = $search_wildcard;
    }

    if ($cat_filter > 0) {
        $sql .= " AND p.category_id = ?";
        $types .= "i";
        $params[] = $cat_filter;
    }

    $sql .= " ORDER BY p.id DESC";

    $products = db_query($sql, !empty($types) ? $types : null, !empty($params) ? $params : null);
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-boxes-stacked"></i> Danh Sách Sản Phẩm</h3>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="exportTableToCSV('productsTable', 'danh-sach-san-pham.csv')" class="btn btn-outline btn-sm" style="border-radius: 8px; border-color: var(--color-admin-border); color: var(--color-admin-text-dark); background-color: var(--color-white);"><i class="fa-solid fa-file-export"></i> Xuất Excel (CSV)</button>
                <a href="products.php?action=add" class="btn btn-primary btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-plus"></i> Thêm sản phẩm mới</a>
            </div>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <!-- Filters -->
            <div style="padding: 1.5rem 2rem; border-bottom: 1px solid var(--color-admin-border); background-color: #f8fafc;">
                <form action="products.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" id="productsSearchInput" name="search" class="form-control" placeholder="Tìm kiếm nhanh tên, slug... (gõ để lọc ngay)" value="<?= h($search) ?>">
                    </div>
                    <div style="min-width: 200px;">
                        <select name="category" class="form-control">
                            <option value="">-- Tất cả danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= h($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary" style="border-radius: 8px; padding: 0.5rem 1.5rem;">Lọc sản phẩm</button>
                    <?php if (!empty($search) || $cat_filter > 0): ?>
                        <a href="products.php" class="btn btn-outline" style="border-radius: 8px; padding: 0.5rem 1.5rem; border-color: var(--color-admin-border); color: var(--color-admin-text-dark);">Xóa lọc</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($success)): ?>
                <div style="padding: 1rem 2rem 0 2rem;">
                    <div class="admin-alert admin-alert-success"><?= h($success) ?></div>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table id="productsTable" class="admin-table responsive-cards-mobile">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Hình</th>
                            <th>Mã định danh (Slug)</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Xuất xứ</th>
                            <th>Nhãn</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products && $products->num_rows > 0): ?>
                            <?php while ($prod = $products->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Hình ảnh">
                                        <img src="../../frontend/<?= h($prod['image'] ?: 'images/chem-bag.jpg') ?>" alt="Ảnh" style="max-height: 48px; max-width: 48px; object-fit: contain; border-radius: 4px; border: 1px solid var(--color-admin-border);">
                                    </td>
                                    <td data-label="Slug Key"><code><?= h($prod['product_key']) ?></code></td>
                                    <td data-label="Tên sản phẩm" style="font-weight: 600;">
                                        <a href="products.php?action=edit&id=<?= $prod['id'] ?>" style="color: var(--color-secondary); text-decoration: underline;"><?= h($prod['name']) ?></a>
                                    </td>
                                    <td data-label="Danh mục"><?= h($prod['category_name']) ?></td>
                                    <td data-label="Giá hiển thị"><span style="color: var(--color-primary); font-weight: 600;"><?= h($prod['price']) ?></span></td>
                                    <td data-label="Xuất xứ"><?= h($prod['origin'] ?: '-') ?></td>
                                    <td data-label="Nhãn">
                                        <?php if (!empty($prod['badge'])): ?>
                                            <span class="badge <?= $prod['badge_class'] === 'badge-bio' ? 'badge-bio' : ($prod['badge_class'] === 'badge-chemical' ? 'badge-chemical' : 'badge-customer') ?>">
                                                <?= h($prod['badge']) ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Thao tác">
                                        <div class="actions-cell" style="justify-content: center;">
                                            <a href="product-detail.php?id=<?= $prod['product_key'] ?>" class="btn-icon-only btn-view" target="_blank" title="Xem ngoài web">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                            <a href="products.php?action=edit&id=<?= $prod['id'] ?>" class="btn-icon-only btn-edit" title="Sửa thông tin">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="products.php?action=delete&id=<?= $prod['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon-only btn-delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem; color: var(--color-admin-text-muted);">
                                    Không tìm thấy sản phẩm nào phù hợp.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
ob_end_flush();
?>
