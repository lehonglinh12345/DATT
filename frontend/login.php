<?php
$page_title = "Đăng Nhập Hệ Thống";
require_once __DIR__ . '/../backend/auth.php';

// Redirect if already logged in
if (auth_is_logged_in()) {
    $user = auth_get_user();
    if ($user['role'] === 'admin') {
        header('Location: ../backend/admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';
$success = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_csrf_verify()) {
        $error = 'Yêu cầu không hợp lệ (Lỗi bảo mật CSRF). Vui lòng thử lại.';
    } else {
        $username_or_email = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = trim($_POST['redirect'] ?? '');

        if (empty($username_or_email) || empty($password)) {
            $error = 'Vui lòng điền đầy đủ tên đăng nhập/email và mật khẩu.';
        } else {
            // Find user by username or email
            $stmt = db_query("SELECT * FROM users WHERE username = ? OR email = ?", "ss", [$username_or_email, $username_or_email]);
            
            if ($stmt && $stmt->num_rows > 0) {
                $user = $stmt->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                        // Start session & store info
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['phone'] = $user['phone'];

                        // Immediately redirect on successful login for snappier UX
                        if ($user['role'] === 'admin') {
                            $target = '../backend/admin/dashboard.php';
                        } else {
                            $target = !empty($redirect) ? $redirect : 'index.php';
                        }

                        header('Location: ' . $target);
                        exit;
                } else {
                    $error = 'Mật khẩu đăng nhập không chính xác.';
                }
            } else {
                $error = 'Tài khoản đăng nhập không tồn tại trên hệ thống.';
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
                <h2>Đăng Nhập</h2>
                <p>Đăng nhập tài khoản của bạn để truy cập hệ thống</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="auth-alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="auth-alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= h($success) ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="auth-form" id="loginForm">
                <?= auth_csrf_token_field() ?>
                <input type="hidden" name="redirect" value="<?= h($redirect) ?>">

                <div class="form-group">
                    <label for="username">Tên đăng nhập hoặc Email <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập hoặc email" required value="<?= isset($_POST['username']) ? h($_POST['username']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label for="password">Mật khẩu <span class="required">*</span></label>
                    </div>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu của bạn" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-auth" id="btnLoginSubmit">
                    Đăng Nhập Hệ Thống <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>

            <div class="auth-divider">
                <span>HOẶC</span>
            </div>

            <a href="google_login.php" class="btn btn-block btn-google">
                <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" width="20">
                Đăng nhập bằng Google
            </a>

            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký tài khoản</a></p>
            </div>
        </div>
    </div>
</div>

<style>
/* Reuse of Register styles for design consistency */
.auth-section {
    padding: 6rem 0;
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.9) 0%, rgba(226, 232, 240, 0.9) 100%),
                url('images/hero-bg.jpg') center/cover no-repeat;
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
