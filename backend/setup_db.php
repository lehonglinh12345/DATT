<?php
require_once __DIR__ . '/db.php';

// Prepare CSS styles for a premium setup page
$page_title = "Thiết Lập Cơ Sở Dữ Liệu";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Hóa Chất Ngọc Ánh Dương</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primary: #0b6623;
            --color-secondary: #0f4c81;
            --color-dark: #121820;
            --color-light: #f8fafc;
            --color-white: #ffffff;
            --color-success: #10b981;
            --color-error: #ef4444;
            --color-border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-light);
            color: var(--color-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .setup-card {
            background-color: var(--color-white);
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(18,24,32,0.08);
            width: 100%;
            max-width: 600px;
            padding: 40px;
            text-align: center;
            border: 1px solid var(--color-border);
        }
        .setup-icon {
            font-size: 4rem;
            color: var(--color-primary);
            margin-bottom: 20px;
        }
        h1 {
            color: var(--color-secondary);
            font-size: 1.75rem;
            margin-bottom: 15px;
        }
        p {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }
        .log-box {
            background-color: #0f172a;
            color: #38bdf8;
            padding: 20px;
            border-radius: 12px;
            text-align: left;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 250px;
            overflow-y: auto;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .log-entry { margin-bottom: 8px; }
        .log-success { color: #4ade80; }
        .log-error { color: #f87171; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: var(--color-primary);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(11, 102, 37, 0.3);
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="setup-icon">
            <i class="fa-solid fa-database"></i>
        </div>
        <h1>Cấu Hình Cơ Sở Dữ Liệu</h1>
        <p>Đang tiến hành di chuyển bảng và thiết lập dữ liệu ban đầu cho hệ thống...</p>
        
        <div class="log-box">
            <?php
            $logs = [];
            
            // 1. Create table users
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
            
            if ($database->query($createTableSQL)) {
                echo "<div class='log-entry log-success'>[Thành công] Đã tạo/kiểm tra bảng `users`.</div>";
            } else {
                echo "<div class='log-entry log-error'>[Lỗi] Không thể tạo bảng `users`: " . h($database->error) . "</div>";
            }
            
            // 2. Check and Seed Admin
            $checkAdmin = db_query("SELECT id FROM users WHERE username = ?", "s", ["admin"]);
            if ($checkAdmin && $checkAdmin->num_rows === 0) {
                $adminPass = password_hash("admin123", PASSWORD_DEFAULT);
                $seedAdmin = db_query(
                    "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)",
                    "sssss",
                    ["admin", "admin@ngocanhduong.com", $adminPass, "Quản trị viên", "admin"]
                );
                if ($seedAdmin) {
                    echo "<div class='log-entry log-success'>[Thành công] Đã tạo tài khoản Admin: admin / admin123</div>";
                } else {
                    echo "<div class='log-entry log-error'>[Lỗi] Không thể tạo tài khoản Admin mẫu.</div>";
                }
            } else {
                echo "<div class='log-entry'>[Thông tin] Tài khoản Admin (admin) đã tồn tại.</div>";
            }
            
            // 3. Check and Seed Customer
            $checkCust = db_query("SELECT id FROM users WHERE username = ?", "s", ["customer"]);
            if ($checkCust && $checkCust->num_rows === 0) {
                $custPass = password_hash("customer123", PASSWORD_DEFAULT);
                $seedCust = db_query(
                    "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)",
                    "sssss",
                    ["customer", "customer@gmail.com", $custPass, "Khách hàng mẫu", "customer"]
                );
                if ($seedCust) {
                    echo "<div class='log-entry log-success'>[Thành công] Đã tạo tài khoản Khách hàng: customer / customer123</div>";
                } else {
                    echo "<div class='log-entry log-error'>[Lỗi] Không thể tạo tài khoản Khách hàng mẫu.</div>";
                }
            } else {
                echo "<div class='log-entry'>[Thông tin] Tài khoản Khách hàng (customer) đã tồn tại.</div>";
            }
            
            // 4. Create quote_requests table if not exists
            $createQuotesSQL = "CREATE TABLE IF NOT EXISTS `quote_requests` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `product_name` VARCHAR(255) NOT NULL,
              `product_key` VARCHAR(100) DEFAULT NULL,
              `product_link` VARCHAR(512) DEFAULT NULL,
              `name` VARCHAR(150) NOT NULL,
              `phone` VARCHAR(50) NOT NULL,
              `message` TEXT DEFAULT NULL,
              `status` ENUM('new','read','completed') NOT NULL DEFAULT 'new',
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_quote_requests_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            if ($database->query($createQuotesSQL)) {
                echo "<div class='log-entry log-success'>[Thành công] Đã tạo/kiểm tra bảng `quote_requests`.</div>";
            } else {
                echo "<div class='log-entry log-error'>[Lỗi] Không thể tạo bảng `quote_requests`: " . h($database->error) . "</div>";
            }

            // 5. Create chat_conversations table if not exists
            $createChatSQL = "CREATE TABLE IF NOT EXISTS `chat_conversations` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `session_id` VARCHAR(150) NOT NULL,
              `role` ENUM('user', 'assistant') NOT NULL,
              `message` TEXT NOT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_chat_conversations_session_id` (`session_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            if ($database->query($createChatSQL)) {
                echo "<div class='log-entry log-success'>[Thành công] Đã tạo/kiểm tra bảng `chat_conversations`.</div>";
            } else {
                echo "<div class='log-entry log-error'>[Lỗi] Không thể tạo bảng `chat_conversations`: " . h($database->error) . "</div>";
            }

            echo "<div class='log-entry log-success'>[Hoàn thành] Tất cả các tác vụ đã được xử lý xong!</div>";
            ?>
        </div>
        
        <a href="../frontend/login.php" class="btn">
            Đi tới trang Đăng Nhập <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</body>
</html>
