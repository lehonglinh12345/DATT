<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Get dynamic frontend url
 */
function get_frontend_url(string $page): string {
    $script_name = $_SERVER['SCRIPT_NAME'];
    if (strpos($script_name, '/backend/') !== false) {
        $pos = strpos($script_name, '/backend/');
        $base = substr($script_name, 0, $pos);
        return $base . '/frontend/' . $page;
    } elseif (strpos($script_name, '/frontend/') !== false) {
        $pos = strpos($script_name, '/frontend/');
        $base = substr($script_name, 0, $pos);
        return $base . '/frontend/' . $page;
    } else {
        return 'frontend/' . $page;
    }
}

// Auto-create users table and seed data if not exists to avoid MySQL errors
try {
    $table_check = $database->query("SHOW TABLES LIKE 'users'");
    if ($table_check && $table_check->num_rows === 0) {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `users` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(80) NOT NULL,
          `email` VARCHAR(150) NOT NULL,
          `password` VARCHAR(255) NOT NULL,
          `full_name` VARCHAR(150) DEFAULT NULL,
          `phone` VARCHAR(50) DEFAULT NULL,
          `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_users_username` (`username`),
          UNIQUE KEY `uq_users_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $database->query($createTableSQL);

        // Seed default accounts
        $adminPass = password_hash("admin123", PASSWORD_DEFAULT);
        $custPass = password_hash("customer123", PASSWORD_DEFAULT);
        
        $database->query("INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES ('admin', 'admin@ngocanhduong.com', '$adminPass', 'Quản trị viên', 'admin')");
        $database->query("INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES ('customer', 'customer@gmail.com', '$custPass', 'Khách hàng mẫu', 'customer')");
    }
} catch (mysqli_sql_exception $e) {
    // Let database.php connection errors be handled natively
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Check if the user is logged in
 */
function auth_is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged in user details
 */
function auth_get_user(): ?array {
    if (!auth_is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'email'     => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'phone'     => $_SESSION['phone'] ?? ''
    ];
}

/**
 * Enforce authentication and optionally a specific role
 */
function auth_require_role(string $role): void {
    if (!auth_is_logged_in()) {
        $redirect_url = $_SERVER['REQUEST_URI'];
        header('Location: ' . get_frontend_url('login.php') . '?redirect=' . urlencode($redirect_url));
        exit;
    }
    
    if ($_SESSION['role'] !== $role) {
        // Logged in but not authorized -> Show beautiful 403 error page
        http_response_code(403);
        $page_title = "Không Có Quyền Truy Cập";
        
        // Load layout manually so we don't depend on relative paths in deep folders
        include_once __DIR__ . '/../frontend/includes/head.php';
        include_once __DIR__ . '/../frontend/includes/header.php';
        ?>
        <div class="container" style="padding: 8rem 1.5rem; text-align: center;">
            <div style="font-size: 5rem; color: var(--color-secondary); margin-bottom: 2rem; animation: pulse 2s infinite;">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <h1 style="color: var(--color-secondary); font-size: 2.5rem; margin-bottom: 1rem;">Không Có Quyền Truy Cập</h1>
            <p style="color: var(--color-dark-muted); font-size: 1.1rem; max-width: 500px; margin: 0 auto 2.5rem auto;">
                Bạn đã đăng nhập dưới tài khoản <strong><?= h($_SESSION['username']) ?></strong> nhưng không có đủ thẩm quyền truy cập nội dung này.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="<?= get_frontend_url('index.php') ?>" class="btn btn-primary">Quay Lại Trang Chủ</a>
                <a href="<?= get_frontend_url('logout.php') ?>" class="btn btn-outline">Đăng Xuất / Đổi Tài Khoản</a>
            </div>
        </div>
        <style>
            @keyframes pulse {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.05); opacity: 0.8; }
                100% { transform: scale(1); opacity: 1; }
            }
        </style>
        <?php
        include_once __DIR__ . '/../frontend/includes/footer.php';
        exit;
    }
}

/**
 * Generate CSRF hidden input field for form
 */
function auth_csrf_token_field(): string {
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

/**
 * Verify CSRF token from a POST request
 */
function auth_csrf_verify(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
    }
    return true;
}

/**
 * Simple mobile detection (used to tweak UX like redirect delays)
 */
function auth_is_mobile(): bool {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return (bool) preg_match('/Mobile|Android|Silk\/|Kindle|BlackBerry|Opera Mini|IEMobile|iPhone|iPad|iPod/i', $ua);
}
?>
