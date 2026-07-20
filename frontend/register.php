<?php
$page_title = "Đăng Ký Tài Khoản";
require_once __DIR__ . '/../backend/auth.php';

// Redirect if already logged in
if (auth_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_csrf_verify()) {
        $error = 'Yêu cầu không hợp lệ (Lỗi bảo mật CSRF). Vui lòng thử lại.';
    } else {
        $username   = trim($_POST['username'] ?? '');
        $full_name  = trim($_POST['full_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $password   = $_POST['password'] ?? '';
        $confirm_pw = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password) || empty($confirm_pw)) {
            $error = 'Vui lòng điền đầy đủ các trường thông tin bắt buộc.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Địa chỉ email không đúng định dạng.';
        } elseif (strlen($username) < 4) {
            $error = 'Tên đăng nhập phải từ 4 ký tự trở lên.';
        } elseif (strlen($password) < 6) {
            $error = 'Mật khẩu phải từ 6 ký tự trở lên.';
        } elseif ($password !== $confirm_pw) {
            $error = 'Xác nhận mật khẩu không khớp.';
        } elseif ($phone !== '' && !preg_match('/^0[35789][0-9]{8}$/', $phone)) {
            $error = 'Số điện thoại không hợp lệ (phải có 10 chữ số và bắt đầu bằng 0).';
        } else {
            // Check if username, email, or phone already exists
            $checkUser = db_query("SELECT id, username, email, phone FROM users WHERE username = ? OR email = ? OR (phone = ? AND phone IS NOT NULL)", "sss", [$username, $email, $phone !== '' ? $phone : null]);
            if ($checkUser && $checkUser->num_rows > 0) {
                $row = $checkUser->fetch_assoc();
                if (strcasecmp($row['username'], $username) === 0) {
                    $error = 'Tên đăng nhập đã được đăng ký sử dụng.';
                } elseif (strcasecmp($row['email'], $email) === 0) {
                    $error = 'Email đã được đăng ký sử dụng.';
                } else {
                    $error = 'Số điện thoại đã được đăng ký bởi một tài khoản khác.';
                }
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $phone_db = $phone === '' ? null : $phone;
                $insert = db_query(
                    "INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'customer')",
                    "sssss",
                    [$username, $email, $hashed_password, $full_name, $phone_db]
                );

                if ($insert) {
                    $success = 'Đăng ký tài khoản thành công! Đang chuyển hướng đến trang đăng nhập...';
                    header('refresh:2;url=login.php');
                } else {
                    $error = 'Đã xảy ra lỗi trong quá trình tạo tài khoản. Vui lòng thử lại.';
                }
            }
        }
    }
}

include 'includes/head.php';
include 'includes/header.php';
?>

<div class="auth-section">
    <div class="container auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Đăng Ký Tài Khoản</h2>
                <p>Trở thành thành viên để được nhận báo giá và theo dõi dịch vụ tốt nhất</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="auth-alert alert-danger auto-hide-alert" style="transition: opacity 0.5s ease;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="auth-alert alert-success auto-hide-alert" style="transition: opacity 0.5s ease;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= h($success) ?></span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="auth-form" id="registerForm">
                <?= auth_csrf_token_field() ?>

                <div class="form-group">
                    <label for="username">Tên đăng nhập <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập (ít nhất 4 ký tự)" required value="<?= isset($_POST['username']) ? h($_POST['username']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name">Họ và tên</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-id-card"></i>
                        <input type="text" id="full_name" name="full_name" placeholder="Nhập họ và tên của bạn" value="<?= isset($_POST['full_name']) ? h($_POST['full_name']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Địa chỉ Email <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Ví dụ: email@domain.com" required value="<?= isset($_POST['email']) ? h($_POST['email']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại của bạn" value="<?= isset($_POST['phone']) ? h($_POST['phone']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-circle-check"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu để xác minh" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-auth" id="btnRegisterSubmit">
                    Đăng Ký Tài Khoản <i class="fa-solid fa-user-plus"></i>
                </button>
            </form>

            <div class="auth-divider">
                <span>HOẶC</span>
            </div>

            <a href="google_login.php" class="btn btn-block btn-google">
                <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" width="20">
                Đăng ký bằng Google
            </a>

            <div class="auth-footer">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>
</div>

<style>
/* Styling for Auth Section */
.auth-section {
    padding: 6rem 0;
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.9) 0%, rgba(226, 232, 240, 0.9) 100%),
                url('images/about-hero.jpg') center/cover no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
}
.auth-container {
    display: flex;
    justify-content: center;
}
.auth-card {
    background-color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    box-shadow: var(--shadow-lg);
    padding: 3rem;
    width: 100%;
    max-width: 500px;
    border: 1px solid rgba(255, 255, 255, 0.6);
}
@media (max-width: 576px) {
    .auth-section {
        padding: 4rem 1rem;
    }
    .auth-card {
        padding: 2rem 1.5rem;
        border-radius: 16px;
    }
}
.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}
.auth-header h2 {
    color: var(--color-secondary);
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.auth-header p {
    color: var(--color-dark-muted);
    font-size: 0.9rem;
}
.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--color-dark);
}
.form-group label .required {
    color: #ef4444;
}
.input-wrapper {
    position: relative;
}
.input-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-dark-muted);
    font-size: 0.9rem;
    pointer-events: none;
}
.input-wrapper input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    font-family: var(--font-primary);
    font-size: 0.9rem;
    transition: var(--transition-normal);
}
.input-wrapper input:focus {
    border-color: var(--color-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(11, 102, 37, 0.1);
}
.btn-auth {
    margin-top: 1rem;
    padding: 0.85rem 2rem;
    font-size: 1rem;
    border-radius: 12px;
}
.btn-block {
    width: 100%;
}
.auth-alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 10px;
    font-size: 0.88rem;
    line-height: 1.4;
    margin-bottom: 1.5rem;
}
.alert-danger {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}
.alert-success {
    background-color: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
}
.auth-footer {
    text-align: center;
    margin-top: 2rem;
    font-size: 0.9rem;
    color: var(--color-dark-muted);
}
.auth-footer a {
    color: var(--color-primary);
    font-weight: 600;
}
.auth-footer a:hover {
    text-decoration: underline;
}
.auth-divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 1.5rem 0;
    color: var(--color-dark-muted);
    font-size: 0.85rem;
    font-weight: 600;
}
.auth-divider::before, .auth-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid var(--color-border);
}
.auth-divider span {
    padding: 0 10px;
}
.btn-google {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background-color: white;
    color: #3c4043;
    border: 1px solid #dadce0;
    padding: 0.75rem 2rem;
    font-weight: 500;
    transition: background-color 0.2s, box-shadow 0.2s;
    border-radius: 12px;
    text-decoration: none;
    font-family: "Google Sans", arial, sans-serif;
}
.btn-google:hover {
    background-color: #f8f9fa;
    box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
}
</style>

<?php
include 'includes/footer.php';
?>
