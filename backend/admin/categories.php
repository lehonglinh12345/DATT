<?php
$page_title = "Quản Lý Danh Mục";
$active_admin_tab = "categories";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --------------------------------------------------------------------------
// Delete Category
// --------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } else {
        // Safe check: verify if category has products
        $checkProds = db_query("SELECT id FROM products WHERE category_id = ?", "i", [$id]);
        if ($checkProds && $checkProds->num_rows > 0) {
            $error = "Không thể xóa danh mục này vì đang có sản phẩm thuộc danh mục. Vui lòng chuyển các sản phẩm sang danh mục khác trước.";
        } else {
            $delete = db_query("DELETE FROM categories WHERE id = ?", "i", [$id]);
            if ($delete) {
                $success = "Xóa danh mục thành công!";
            } else {
                $error = "Không thể xóa danh mục. Có lỗi xảy ra trong cơ sở dữ liệu.";
            }
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
        $slug = trim($_POST['slug'] ?? '');
        $type = trim($_POST['type'] ?? 'product');

        if (empty($name)) {
            $error = "Tên danh mục là thông tin bắt buộc.";
        } else {
            // Auto-slug if empty
            if (empty($slug)) {
                $slug = strtolower($name);
                $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
                $slug = preg_replace('/-+/', '-', $slug);
                $slug = trim($slug, '-');
            }

            // Check uniqueness of slug
            $checkSlug = db_query("SELECT id FROM categories WHERE slug = ? AND id != ?", "si", [$slug, $id]);
            if ($checkSlug && $checkSlug->num_rows > 0) {
                $slug .= '-' . rand(10, 99);
            }

            if ($action === 'add') {
                $insert = db_query("INSERT INTO categories (name, slug, type) VALUES (?, ?, ?)", "sss", [$name, $slug, $type]);
                if ($insert) {
                    $success = "Thêm danh mục mới thành công!";
                    header('refresh:1.5;url=categories.php');
                    $action = 'list';
                } else {
                    $error = "Lỗi khi thêm danh mục vào cơ sở dữ liệu.";
                }
            } else {
                $update = db_query("UPDATE categories SET name = ?, slug = ?, type = ? WHERE id = ?", "sssi", [$name, $slug, $type, $id]);
                if ($update) {
                    $success = "Cập nhật danh mục thành công!";
                    header('refresh:1.5;url=categories.php');
                    $action = 'list';
                } else {
                    $error = "Lỗi khi cập nhật cơ sở dữ liệu.";
                }
            }
        }
    }
}

// --------------------------------------------------------------------------
// Form Rendering (Add / Edit)
// --------------------------------------------------------------------------
if ($action === 'add' || $action === 'edit'):
    $category = ['name' => '', 'slug' => '', 'type' => 'product'];
    if ($action === 'edit' && $id > 0) {
        $res = db_query("SELECT * FROM categories WHERE id = ?", "i", [$id]);
        if ($res && $res->num_rows > 0) {
            $category = $res->fetch_assoc();
        } else {
            $error = "Không tìm thấy danh mục.";
            $action = 'list';
        }
    }
    
    if ($action !== 'list'):
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-tag"></i> <?= $action === 'add' ? 'Thêm Danh Mục Mới' : 'Sửa Danh Mục' ?></h3>
            <a href="categories.php" class="btn btn-outline btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </div>
        <div class="admin-card-body">
            <?php if (!empty($error)): ?>
                <div class="admin-alert admin-alert-danger"><?= h($error) ?></div>
            <?php endif; ?>
            
            <form action="categories.php?action=<?= $action ?>&id=<?= $id ?>" method="POST" class="admin-form">
                <?= auth_csrf_token_field() ?>

                <div class="form-group">
                    <label for="name">Tên danh mục <span style="color:red">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Ví dụ: Hóa chất xi mạ" required value="<?= h($category['name']) ?>">
                </div>

                <div class="form-group">
                    <label for="slug">Đường dẫn tĩnh (Slug) - Để trống sẽ tự tạo</label>
                    <input type="text" id="slug" name="slug" class="form-control" placeholder="Ví dụ: hoa-chat-xi-ma" value="<?= h($category['slug']) ?>">
                </div>

                <div class="form-group">
                    <label for="type">Loại danh mục</label>
                    <select id="type" name="type" class="form-control">
                        <option value="product" <?= $category['type'] === 'product' ? 'selected' : '' ?>>Sản phẩm (Product)</option>
                        <option value="news" <?= $category['type'] === 'news' ? 'selected' : '' ?>>Kỹ thuật / Tin tức (News)</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;">
                        Lưu Thay Đổi <i class="fa-solid fa-floppy-disk"></i>
                    </button>
                    <a href="categories.php" class="btn btn-outline" style="border-radius: 8px; border-color: var(--color-admin-border); color: var(--color-admin-text-dark);">Hủy bỏ</a>
                </div>
            </form>
        </div>
    </div>
<?php 
    endif;
endif;

// --------------------------------------------------------------------------
// Categories List View
// --------------------------------------------------------------------------
if ($action === 'list'):
    $categories = db_query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY c.id DESC");
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-tags"></i> Danh Sách Danh Mục</h3>
            <a href="categories.php?action=add" class="btn btn-primary btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-plus"></i> Thêm danh mục mới</a>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            
            <?php if (!empty($success)): ?>
                <div style="padding: 1rem 2rem 0 2rem;">
                    <div class="admin-alert admin-alert-success"><?= h($success) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div style="padding: 1rem 2rem 0 2rem;">
                    <div class="admin-alert admin-alert-danger"><?= h($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="admin-table responsive-cards-mobile">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Tên danh mục</th>
                            <th>Đường dẫn tĩnh (Slug)</th>
                            <th>Loại</th>
                            <th>Số lượng sản phẩm liên kết</th>
                            <th>Ngày khởi tạo</th>
                            <th style="width: 100px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories && $categories->num_rows > 0): ?>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="ID"><?= $cat['id'] ?></td>
                                    <td data-label="Tên danh mục" style="font-weight: 600;"><?= h($cat['name']) ?></td>
                                    <td data-label="Slug"><code><?= h($cat['slug']) ?></code></td>
                                    <td data-label="Loại">
                                        <?php if ($cat['type'] === 'product'): ?>
                                            <span class="badge badge-customer"><i class="fa-solid fa-box"></i> Sản phẩm</span>
                                        <?php else: ?>
                                            <span class="badge badge-admin"><i class="fa-solid fa-file-lines"></i> Tin tức</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Số sản phẩm liên kết" style="font-weight: bold;"><?= $cat['product_count'] ?></td>
                                    <td data-label="Ngày khởi tạo"><?= date('d/m/Y', strtotime($cat['created_at'])) ?></td>
                                    <td data-label="Thao tác">
                                        <div class="actions-cell" style="justify-content: center;">
                                            <a href="categories.php?action=edit&id=<?= $cat['id'] ?>" class="btn-icon-only btn-edit" title="Sửa danh mục">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="categories.php?action=delete&id=<?= $cat['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon-only btn-delete" title="Xóa danh mục" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--color-admin-text-muted);">
                                    Chưa có danh mục nào được khởi tạo.
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
?>
