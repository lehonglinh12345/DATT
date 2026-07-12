<?php
$page_title = "Đóng Góp Tin Tức";
$page_desc = "Chia sẻ kinh nghiệm làm vườn, kỹ thuật canh tác và tin tức nhà nông hữu ích cùng cộng đồng Ngọc Ánh Dương.";
$active_page = 'news';

require_once __DIR__ . '/../backend/auth.php';

// Force login to submit articles
if (!auth_is_logged_in()) {
    $redirect_url = $_SERVER['REQUEST_URI'];
    header('Location: login.php?redirect=' . urlencode($redirect_url));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_csrf_verify()) {
        $error = 'Yêu cầu không hợp lệ (Lỗi bảo mật CSRF). Vui lòng thử lại.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Tin nhà nông');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $user = auth_get_user();
        $user_id = $user['id'];

        if (empty($title) || empty($content)) {
            $error = 'Vui lòng nhập đầy đủ tiêu đề và nội dung bài viết.';
        } else {
            // Slugify Title
            $covert_unicode = function($str) {
                $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
                $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
                $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
                $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
                $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
                $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
                $str = preg_replace("/(đ)/", 'd', $str);
                $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
                $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
                $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
                $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
                $str = preg_replace("/(Ù|Ú|Ụ|Ủ|U|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
                $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
                $str = preg_replace("/(Đ)/", 'D', $str);
                return strtolower($str);
            };

            $slug = $covert_unicode($title);
            $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');

            // Unique slug validation
            $checkSlugSQL = "SELECT id FROM news_articles WHERE slug = ?";
            $checkSlug = db_query($checkSlugSQL, "s", [$slug]);
            if ($checkSlug && $checkSlug->num_rows > 0) {
                $slug .= '-' . rand(100, 999);
            }

            // Image Upload Handling
            $image_path = 'images/news-4.jpg'; // default cover image
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($file_ext, $allowed_exts)) {
                    $new_file_name = 'user_news_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    $upload_dir = __DIR__ . '/images';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    if (move_uploaded_file($file_tmp, $upload_dir . '/' . $new_file_name)) {
                        $image_path = 'images/' . $new_file_name;
                    } else {
                        $error = "Có lỗi xảy ra khi lưu trữ tệp hình ảnh tải lên.";
                    }
                } else {
                    $error = "Định dạng ảnh không hợp lệ. Chỉ chấp nhận các định dạng: " . implode(', ', $allowed_exts);
                }
            }

            if (empty($error)) {
                // Insert as 'pending'
                $insertSQL = "INSERT INTO news_articles (slug, title, section, category, excerpt, image, image_alt, content, status, user_id) VALUES (?, ?, 'news', ?, ?, ?, ?, ?, 'pending', ?)";
                $insert = db_query($insertSQL, "sssssssi", [$slug, $title, $category, $excerpt, $image_path, $title, $content, $user_id]);
                if ($insert) {
                    $success = 'Đã gửi bài viết đóng góp thành công! Bài viết đang chờ Quản trị viên kiểm duyệt trước khi xuất bản.';
                    // Clear post data
                    $_POST = [];
                    $title = $category = $excerpt = $content = '';
                } else {
                    $error = 'Lỗi lưu trữ bài viết vào cơ sở dữ liệu. Vui lòng thử lại.';
                }
            }
        }
    }
}

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Custom writing form styling directly in page -->
<style>
.write-section {
    padding: 80px 0;
    background-color: var(--color-light);
}
.write-container {
    max-width: 800px;
    margin: 0 auto;
}
.write-card {
    background-color: var(--color-white);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    border: 1px solid var(--color-border);
    padding: 40px;
}
.write-header {
    text-align: center;
    margin-bottom: 35px;
}
.write-header h2 {
    color: var(--color-secondary);
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 10px;
}
.write-header p {
    color: var(--color-dark-muted);
    font-size: 1rem;
}
.write-alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
}
.alert-danger {
    background-color: #fef2f2;
    color: #ef4444;
    border: 1px solid #fee2e2;
}
.alert-success {
    background-color: #f0fdf4;
    color: #16a34a;
    border: 1px solid #dcfce7;
}
.write-form .form-group {
    margin-bottom: 25px;
}
.write-form label {
    display: block;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: 8px;
}
.write-form .required {
    color: #ef4444;
}
.write-form .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    color: var(--color-dark);
    background-color: var(--color-white);
    transition: all 0.2s ease;
}
.write-form .form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(11, 102, 35, 0.1);
}
.write-form textarea.form-control {
    resize: vertical;
    min-height: 100px;
}
.write-form input[type="file"] {
    padding: 8px;
    background-color: var(--color-light);
}
.form-actions {
    margin-top: 35px;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}
.btn-submit {
    padding: 12px 28px;
    font-weight: 600;
    border-radius: 30px;
}
.btn-back {
    padding: 12px 28px;
    font-weight: 600;
    border-radius: 30px;
    color: var(--color-dark);
    border-color: var(--color-border);
    background-color: transparent;
}
.btn-back:hover {
    background-color: var(--color-light);
}
</style>

<!-- Banner Header -->
<section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/hero-bg.jpg') center/cover;">
    <div class="container">
        <h1>Đóng Góp Tin Tức</h1>
        <div class="breadcrumbs">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <a href="news.php">Tin tức</a>
            <span>/</span>
            <span>Đóng góp bài viết</span>
        </div>
    </div>
</section>

<!-- Form Container -->
<section class="write-section">
    <div class="container write-container">
        <div class="write-card">
            <div class="write-header">
                <h2>Viết Bài Đóng Góp</h2>
                <p>Chia sẻ kinh nghiệm làm vườn, cách phòng trừ sâu bệnh hoặc câu chuyện nhà nông của bạn.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="write-alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.2rem;"></i>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="write-alert alert-success">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>

            <form action="write-news.php" method="POST" enctype="multipart/form-data" class="write-form">
                <?= auth_csrf_token_field() ?>

                <div class="form-group">
                    <label for="title">Tiêu đề bài viết <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Nhập tiêu đề ấn tượng..." required value="<?= isset($_POST['title']) ? h($_POST['title']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="category">Danh mục bài viết</label>
                    <select id="category" name="category" class="form-control">
                        <option value="Tin nhà nông" <?= (isset($_POST['category']) && $_POST['category'] === 'Tin nhà nông') ? 'selected' : '' ?>>Tin nhà nông</option>
                        <option value="Kỹ thuật trồng trọt" <?= (isset($_POST['category']) && $_POST['category'] === 'Kỹ thuật trồng trọt') ? 'selected' : '' ?>>Kỹ thuật trồng trọt</option>
                        <option value="Kinh nghiệm thực tế" <?= (isset($_POST['category']) && $_POST['category'] === 'Kinh nghiệm thực tế') ? 'selected' : '' ?>>Kinh nghiệm thực tế</option>
                        <option value="Thị trường" <?= (isset($_POST['category']) && $_POST['category'] === 'Thị trường') ? 'selected' : '' ?>>Thị trường vật tư nông nghiệp</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Ảnh đại diện bài viết (Cover Image)</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    <small style="color: var(--color-dark-muted); display: block; margin-top: 5px;">Hỗ trợ định dạng JPG, PNG, WEBP, GIF. Khuyên dùng ảnh nằm ngang.</small>
                </div>

                <div class="form-group">
                    <label for="excerpt">Mô tả ngắn / Tóm tắt bài viết</label>
                    <textarea id="excerpt" name="excerpt" class="form-control" placeholder="Tóm tắt ngắn gọn nội dung bài viết từ 2-3 câu..." style="min-height: 80px;"><?= isset($_POST['excerpt']) ? h($_POST['excerpt']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label for="content">Nội dung chi tiết bài viết <span class="required">*</span></label>
                    <textarea id="content" name="content" class="form-control" placeholder="Viết nội dung bài viết của bạn ở đây..." style="min-height: 300px;" required><?= isset($_POST['content']) ? h($_POST['content']) : '' ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="news.php" class="btn btn-outline btn-back">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary btn-submit">Gửi bài viết đóng góp <i class="fa-solid fa-paper-plane" style="margin-left: 5px;"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
?>
