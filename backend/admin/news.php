<?php
$page_title = "Quản Lý Tin Tức";
$active_admin_tab = "news";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$section = 'news';

// --------------------------------------------------------------------------
// CKEditor Image Upload Endpoint
// --------------------------------------------------------------------------
if ($action === 'upload_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (!auth_csrf_verify()) {
        http_response_code(403);
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Yêu cầu không hợp lệ (Lỗi CSRF token).']]);
        exit;
    }

    if (!isset($_FILES['upload']) || $_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Không có hình ảnh hợp lệ được gửi lên.']]);
        exit;
    }

    $file = $_FILES['upload'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Kích thước ảnh quá lớn. Giới hạn 5MB.']]);
        exit;
    }

    if (!in_array($file_ext, $allowed_exts, true)) {
        http_response_code(400);
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG, WEBP, GIF.']]);
        exit;
    }

    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false || !in_array($image_info['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
        http_response_code(400);
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Tệp tải lên không phải là ảnh hợp lệ.']]);
        exit;
    }

    $upload_dir = __DIR__ . '/../../frontend/uploads/news';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $safe_file_name = 'news_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    $target_path = $upload_dir . '/' . $safe_file_name;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        http_response_code(500);
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Không thể lưu ảnh lên máy chủ.']]);
        exit;
    }

    $public_url = 'uploads/news/' . $safe_file_name;
    echo json_encode(['uploaded' => 1, 'url' => $public_url]);
    exit;
}

// --------------------------------------------------------------------------
// Approve Article
// --------------------------------------------------------------------------
if ($action === 'approve' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } else {
        $update = db_query("UPDATE news_articles SET status = 'published', published_at = NOW() WHERE id = ? AND section = ?", "is", [$id, $section]);
        if ($update) {
            $success = "Phê duyệt và xuất bản bài viết thành công!";
        } else {
            $error = "Không thể phê duyệt bài viết. Có lỗi xảy ra.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// Delete Article
// --------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } else {
        $check = db_query("SELECT image FROM news_articles WHERE id = ? AND section = ?", "is", [$id, $section]);
        if ($check && $check->num_rows > 0) {
            $article = $check->fetch_assoc();
            $delete = db_query("DELETE FROM news_articles WHERE id = ? AND section = ?", "is", [$id, $section]);
            if ($delete) {
                // Delete physical image if custom uploaded
                if (!empty($article['image']) && file_exists(__DIR__ . '/../../frontend/' . $article['image']) && !in_array($article['image'], ['images/news-1.jpg', 'images/news-2.jpg', 'images/news-3.jpg', 'images/news-4.jpg', 'images/news-5.jpg', 'images/news-6.jpg', 'images/news-7.jpg', 'images/news-8.jpg', 'images/news-9.jpg'])) {
                    @unlink(__DIR__ . '/../../frontend/' . $article['image']);
                }
                $success = "Xóa bài viết tin tức thành công!";
            } else {
                $error = "Không thể xóa bài viết. Có lỗi hệ thống xảy ra.";
            }
        } else {
            $error = "Bài viết không tồn tại.";
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
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $category = trim($_POST['category'] ?? 'Tin nhà nông');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = trim($_POST['status'] ?? 'published');

        // Auto-slugify if empty
        if (empty($slug) && !empty($title)) {
            $slug = strtolower($title);
            // Convert accented Vietnamese characters to normal english chars
            $covert_unicode = function($str) {
                $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
                $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
                $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
                $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
                $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
                $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
                $str = preg_replace("/(đ)/", 'd', $str);
                return $str;
            };
            $slug = $covert_unicode($slug);
            $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
        }

        if (empty($title) || empty($content)) {
            $error = "Tiêu đề và Nội dung bài viết là bắt buộc.";
        } else {
            // Check slug uniqueness
            $checkSlugSQL = "SELECT id FROM news_articles WHERE slug = ? AND id != ?";
            $checkSlug = db_query($checkSlugSQL, "si", [$slug, $id]);
            if ($checkSlug && $checkSlug->num_rows > 0) {
                $slug .= '-' . rand(100, 999);
            }

            // Image Upload Handling
            $image_path = $_POST['existing_image'] ?? 'images/news-4.jpg';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($file_ext, $allowed_exts)) {
                    $new_file_name = 'news_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    $upload_dir = __DIR__ . '/../../frontend/images';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    if (move_uploaded_file($file_tmp, $upload_dir . '/' . $new_file_name)) {
                        $image_path = 'images/' . $new_file_name;
                        // Delete old custom image
                        if ($action === 'edit' && !empty($_POST['existing_image']) && !in_array($_POST['existing_image'], ['images/news-1.jpg', 'images/news-2.jpg', 'images/news-3.jpg', 'images/news-4.jpg', 'images/news-5.jpg', 'images/news-6.jpg', 'images/news-7.jpg', 'images/news-8.jpg', 'images/news-9.jpg'])) {
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
                    $insertSQL = "INSERT INTO news_articles (slug, title, section, category, excerpt, image, image_alt, content, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insert = db_query($insertSQL, "sssssssss", [$slug, $title, $section, $category, $excerpt, $image_path, $title, $content, $status]);
                    if ($insert) {
                        $success = "Thêm bài viết tin tức mới thành công!";
                        if ($status === 'published') {
                            require_once __DIR__ . '/../notification_helper.php';
                            send_global_notification("Bài viết mới", "Một bài viết tin tức mới vừa được đăng: " . $title, "../frontend/news.php");
                        }
                        echo '<script>setTimeout(function(){ window.location.href = "news.php"; }, 1500);</script>';
                        $action = 'list';
                    } else {
                        $error = "Lỗi khi lưu bài viết vào CSDL.";
                    }
                } else {
                    $updateSQL = "UPDATE news_articles SET slug = ?, title = ?, category = ?, excerpt = ?, image = ?, image_alt = ?, content = ?, status = ? WHERE id = ? AND section = ?";
                    $update = db_query($updateSQL, "ssssssssis", [$slug, $title, $category, $excerpt, $image_path, $title, $content, $status, $id, $section]);
                    if ($update) {
                        $success = "Cập nhật bài viết tin tức thành công!";
                        echo '<script>setTimeout(function(){ window.location.href = "news.php"; }, 1500);</script>';
                        $action = 'list';
                    } else {
                        $error = "Lỗi khi cập nhật cơ sở dữ liệu.";
                    }
                }
            }
        }
    }
}

// --------------------------------------------------------------------------
if ($action === 'add' || $action === 'edit'):
    $article = [
        'title' => '', 'slug' => '', 'category' => 'Tin nhà nông', 
        'excerpt' => '', 'image' => '', 'content' => '', 'status' => 'published'
    ];
    $author_display = 'Quản trị viên';
    if ($action === 'edit' && $id > 0) {
        $res = db_query("SELECT n.*, u.full_name, u.username FROM news_articles n LEFT JOIN users u ON n.user_id = u.id WHERE n.id = ? AND n.section = ?", "is", [$id, $section]);
        if ($res && $res->num_rows > 0) {
            $article = $res->fetch_assoc();
            if (!empty($article['full_name'])) {
                $author_display = $article['full_name'];
            } elseif (!empty($article['username'])) {
                $author_display = $article['username'];
            }
        } else {
            $error = "Không tìm thấy bài viết.";
            $action = 'list';
        }
    }
    
    if ($action !== 'list'):
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-file-pen"></i> <?= $action === 'add' ? 'Thêm Bài Viết Tin Tức Mới' : 'Sửa Bài Viết Tin Tức' ?></h3>
            <a href="news.php" class="btn btn-outline btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </div>
        <div class="admin-card-body">
            <?php if (!empty($error)): ?>
                <div class="admin-alert admin-alert-danger"><?= h($error) ?></div>
            <?php endif; ?>
            
            <form action="news.php?action=<?= $action ?>&id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="admin-form">
                <?= auth_csrf_token_field() ?>
                <input type="hidden" name="existing_image" value="<?= h($article['image']) ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Tiêu đề bài viết <span style="color:red">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="Nhập tiêu đề tin tức..." required value="<?= h($article['title']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="slug">Đường dẫn tĩnh (Slug) - Để trống tự động tạo</label>
                        <input type="text" id="slug" name="slug" class="form-control" placeholder="Ví dụ: tin-nong-nghiep-moi" value="<?= h($article['slug']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Danh mục / Chủ đề</label>
                        <input type="text" id="category" name="category" class="form-control" placeholder="Ví dụ: Tin nhà nông, Thị trường" value="<?= h($article['category']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Người viết / Tác giả</label>
                        <input type="text" class="form-control" readonly disabled value="<?= h($author_display) ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status" class="form-control">
                            <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Xuất bản (Published)</option>
                            <option value="pending" <?= $article['status'] === 'pending' ? 'selected' : '' ?>>Chờ duyệt (Pending)</option>
                            <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Bản nháp (Draft)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Hình ảnh đại diện</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    <?php if (!empty($article['image'])): ?>
                        <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                            <img src="../../frontend/<?= h($article['image']) ?>" alt="Ảnh hiện tại" style="max-height: 80px; border-radius: 6px; border: 1px solid var(--color-admin-border);">
                            <span style="font-size: 0.8rem; color: var(--color-admin-text-muted);">Đường dẫn: <?= h($article['image']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="excerpt">Mô tả ngắn / Tóm tắt (Hiển thị ngoài trang danh sách)</label>
                    <textarea id="excerpt" name="excerpt" class="form-control" placeholder="Nhập tóm tắt bài viết..." style="min-height: 80px;"><?= h($article['excerpt']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="content">Nội dung chi tiết</label>
                    <textarea id="content" name="content" class="form-control editor-content" placeholder="Viết nội dung bài viết ở đây..." style="min-height: 250px;" required><?= h($article['content']) ?></textarea>
                    <p style="margin-top: 0.75rem; color: var(--color-admin-text-muted); font-size: 0.92rem;">
                        Hỗ trợ định dạng: Heading 1/2/3, in đậm, in nghiêng, danh sách, trích dẫn, liên kết, bảng, ảnh, căn lề, code block, hoàn tác/làm lại.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;">
                        Lưu Bài Viết <i class="fa-solid fa-floppy-disk"></i>
                    </button>
                    <a href="news.php" class="btn btn-outline" style="border-radius: 8px; border-color: var(--color-admin-border); color: var(--color-admin-text-dark);">Hủy bỏ</a>
                </div>
            </form>
        </div>
    </div>
<?php 
    endif;
endif;

// --------------------------------------------------------------------------
// Articles List View
// --------------------------------------------------------------------------
if ($action === 'list'):
    $search = trim($_GET['search'] ?? '');
    
    // Pagination parameters
    $per_page = 5;
    $current_page = max(1, intval($_GET['page'] ?? 1));
    
    // Build query with search
    $countSQL = "SELECT COUNT(*) as total FROM news_articles WHERE section = ?";
    $listSQL = "SELECT n.*, DATE_FORMAT(n.published_at, '%d/%m/%Y') AS date, u.full_name AS author_name, u.username AS author_username 
                FROM news_articles n 
                LEFT JOIN users u ON n.user_id = u.id 
                WHERE n.section = ?";
    $types = "s";
    $params = [$section];

    if (!empty($search)) {
        $countSQL .= " AND (title LIKE ? OR excerpt LIKE ?)";
        $listSQL .= " AND (n.title LIKE ? OR n.excerpt LIKE ?)";
        $search_wildcard = "%$search%";
        $types .= "ss";
        $params[] = $search_wildcard;
        $params[] = $search_wildcard;
    }

    $countRes = db_query($countSQL, $types, $params);
    $totalArticles = 0;
    if ($countRes) {
        $row = $countRes->fetch_assoc();
        $totalArticles = (int)$row['total'];
    }

    $total_pages = max(1, ceil($totalArticles / $per_page));
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }
    $offset = ($current_page - 1) * $per_page;

    // Execute paginated listing
    $listSQL .= " ORDER BY n.id DESC LIMIT ? OFFSET ?";
    $types .= "ii";
    $params[] = $per_page;
    $params[] = $offset;
    $articles = db_query($listSQL, $types, $params);
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-newspaper"></i> Danh Sách Bài Viết Tin Tức</h3>
            <a href="news.php?action=add" class="btn btn-primary btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-plus"></i> Thêm bài viết mới</a>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <!-- Filter search -->
            <div style="padding: 1.5rem 2rem; border-bottom: 1px solid var(--color-admin-border); background-color: #f8fafc;">
                <form action="news.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm bài viết theo tiêu đề, tóm tắt..." value="<?= h($search) ?>">
                    </div>
                    <button type="submit" class="btn btn-secondary" style="border-radius: 8px; padding: 0.5rem 1.5rem;">Tìm kiếm</button>
                    <?php if (!empty($search)): ?>
                        <a href="news.php" class="btn btn-outline" style="border-radius: 8px; padding: 0.5rem 1.5rem; border-color: var(--color-admin-border); color: var(--color-admin-text-dark);">Xóa lọc</a>
                    <?php endif; ?>
                </form>
            </div>

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
                            <th style="width: 80px;">Hình</th>
                            <th>Tiêu đề bài viết</th>
                            <th>Danh mục</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($articles && $articles->num_rows > 0): ?>
                            <?php while ($art = $articles->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Hình">
                                        <img src="../../frontend/<?= h($art['image'] ?: 'images/news-4.jpg') ?>" alt="Hình ảnh" style="height: 48px; width: 64px; object-fit: cover; border-radius: 6px; border: 1px solid var(--color-admin-border);">
                                    </td>
                                    <td data-label="Tiêu đề bài viết">
                                        <div style="text-align: right;">
                                            <div style="font-weight: 600; font-size: 0.95rem; line-height: 1.4;"><?= h($art['title']) ?></div>
                                            <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); margin-top: 4px;">
                                                Người viết: <strong><?= !empty($art['author_name']) ? h($art['author_name']) : (!empty($art['author_username']) ? h($art['author_username']) : 'Quản trị viên') ?></strong>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); margin-top: 4px; word-break: break-all;">
                                                <code>slug: <?= h($art['slug']) ?></code>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Danh mục"><?= h($art['category'] ?: '-') ?></td>
                                    <td data-label="Trạng thái">
                                        <?php if ($art['status'] === 'published'): ?>
                                            <span class="badge badge-customer">Công khai</span>
                                        <?php elseif ($art['status'] === 'pending'): ?>
                                            <span class="badge badge-closed" style="background-color: #f59e0b; color: white;">Chờ duyệt</span>
                                        <?php else: ?>
                                            <span class="badge badge-closed">Bản nháp</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Ngày đăng"><?= h($art['date']) ?></td>
                                    <td data-label="Thao tác">
                                        <div class="actions-cell" style="justify-content: center;">
                                            <?php if ($art['status'] === 'pending'): ?>
                                                <a href="news.php?action=approve&id=<?= $art['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon-only btn-view" title="Phê duyệt & Xuất bản" style="background-color: #10b981; color: white;" onclick="return confirm('Bạn có chắc chắn muốn phê duyệt và xuất bản bài viết này?');">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="../../frontend/news.php?article=<?= $art['slug'] ?>" class="btn-icon-only btn-view" target="_blank" title="Xem ngoài web">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                            <a href="news.php?action=edit&id=<?= $art['id'] ?>" class="btn-icon-only btn-edit" title="Sửa bài">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="news.php?action=delete&id=<?= $art['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon-only btn-delete" title="Xóa bài" onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem; color: var(--color-admin-text-muted);">
                                    Không có bài viết tin tức nào được tìm thấy.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination controls -->
            <?php if ($total_pages > 1): ?>
                <div style="padding: 1.5rem 2rem; border-top: 1px solid var(--color-admin-border); display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php $isActive = $i === $current_page; ?>
                        <a href="news.php?search=<?= urlencode($search) ?>&page=<?= $i ?>" class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; background-color: <?= $isActive ? 'var(--color-primary)' : 'white' ?>; color: <?= $isActive ? 'white' : 'var(--color-admin-text-dark)' ?>; border-color: <?= $isActive ? 'var(--color-primary)' : 'var(--color-admin-border)' ?>;"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
