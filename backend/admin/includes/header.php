<?php
require_once __DIR__ . '/../../auth.php';

// Enforce admin permission
auth_require_role('admin');

$current_admin = auth_get_user();
$active_tab = $active_admin_tab ?? 'dashboard';

// Fetch unread messages count
$new_msg_res = db_query("SELECT COUNT(*) as cnt FROM contact_messages WHERE status = 'new'");
$new_msg_count = 0;
if ($new_msg_res) {
    $row = $new_msg_res->fetch_assoc();
    $new_msg_count = (int)$row['cnt'];
}

// Fetch unread quote requests count
$new_quote_res = db_query("SELECT COUNT(*) as cnt FROM quote_requests WHERE status = 'new'");
$new_quote_count = 0;
if ($new_quote_res) {
    $row = $new_quote_res->fetch_assoc();
    $new_quote_count = (int)$row['cnt'];
}

// Fetch pending user submitted articles count
$new_article_res = db_query("SELECT COUNT(*) as cnt FROM news_articles WHERE status = 'pending'");
$new_article_count = 0;
if ($new_article_res) {
    $row = $new_article_res->fetch_assoc();
    $new_article_count = (int)$row['cnt'];
}

// Fetch pending orders count
$new_order_res = db_query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
$new_order_count = 0;
if ($new_order_res) {
    $row = $new_order_res->fetch_assoc();
    $new_order_count = (int)$row['cnt'];
}

$total_unread_notifications = $new_msg_count + $new_quote_count + $new_article_count + $new_order_count;

// Fetch latest unread notifications (up to 5 items)
$notifications_list = [];
$notif_res_msg = db_query("SELECT id, name, created_at, 'message' as type FROM contact_messages WHERE status = 'new' ORDER BY id DESC LIMIT 5");
if ($notif_res_msg) {
    while ($r = $notif_res_msg->fetch_assoc()) {
        $notifications_list[] = $r;
    }
}
$notif_res_quote = db_query("SELECT id, name, created_at, 'quote' as type, product_name FROM quote_requests WHERE status = 'new' ORDER BY id DESC LIMIT 5");
if ($notif_res_quote) {
    while ($r = $notif_res_quote->fetch_assoc()) {
        $notifications_list[] = $r;
    }
}
$notif_res_art = db_query("SELECT id, title as name, created_at, 'article' as type FROM news_articles WHERE status = 'pending' ORDER BY id DESC LIMIT 5");
if ($notif_res_art) {
    while ($r = $notif_res_art->fetch_assoc()) {
        $notifications_list[] = $r;
    }
}
$notif_res_order = db_query("SELECT id, customer_name as name, created_at, 'order' as type FROM orders WHERE status = 'pending' ORDER BY id DESC LIMIT 5");
if ($notif_res_order) {
    while ($r = $notif_res_order->fetch_assoc()) {
        $notifications_list[] = $r;
    }
}

// Sort notifications by date desc
usort($notifications_list, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$notifications_list = array_slice($notifications_list, 0, 5);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title ?? 'Trang Quản Trị') ?> - Hóa Chất Ngọc Ánh Dương</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <!-- theme script removed for single-theme site -->
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-sidebar-header">
                <a href="dashboard.php" class="admin-logo">
                    <span>ADMIN</span>
                </a>
            </div>
            <nav class="admin-nav">
                <ul class="admin-nav-list">
                    <li class="admin-nav-item <?= $active_tab === 'dashboard' ? 'active' : '' ?>">
                        <a href="dashboard.php">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Tổng Quan</span>
                        </a>
                    </li>
                    <li class="admin-nav-item <?= $active_tab === 'products' ? 'active' : '' ?>">
                        <a href="products.php">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span>Sản Phẩm</span>
                        </a>
                    </li>
                    <li class="admin-nav-item <?= $active_tab === 'categories' ? 'active' : '' ?>">
                        <a href="categories.php">
                            <i class="fa-solid fa-tags"></i>
                            <span>Danh Mục</span>
                        </a>
                    </li>
                    <li class="admin-nav-item <?= $active_tab === 'news' ? 'active' : '' ?>">
                        <a href="news.php" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span style="display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-newspaper"></i>
                                <span>Tin Tức</span>
                            </span>
                            <?php if ($new_article_count > 0): ?>
                                <span class="badge-msg-count" style="background-color: #f59e0b; color: white; border-radius: 50px; padding: 2px 8px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; height: 18px; min-width: 18px; line-height: 1;"><?= $new_article_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="admin-nav-item <?= $active_tab === 'techniques' ? 'active' : '' ?>">
                        <a href="techniques.php">
                            <i class="fa-solid fa-seedling"></i>
                            <span>Kỹ Thuật Trồng</span>
                        </a>
                    </li>
                    <li class="admin-nav-item <?= $active_tab === 'messages' ? 'active' : '' ?>">
                        <a href="messages.php" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span style="display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-envelope-open-text"></i>
                                <span>Tin Nhắn</span>
                            </span>
                            <?php if ($new_msg_count > 0): ?>
                                <span class="badge-msg-count" style="background-color: #ef4444; color: white; border-radius: 50px; padding: 2px 8px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; height: 18px; min-width: 18px; line-height: 1;"><?= $new_msg_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="admin-nav-item <?= $active_tab === 'quotes' ? 'active' : '' ?>">
                        <a href="quotes.php" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span style="display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                <span>Báo Giá</span>
                            </span>
                            <?php if ($new_quote_count > 0): ?>
                                <span class="badge-msg-count" style="background-color: #0b6623; color: white; border-radius: 50px; padding: 2px 8px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; height: 18px; min-width: 18px; line-height: 1;"><?= $new_quote_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="admin-nav-item <?= $active_tab === 'orders' ? 'active' : '' ?>">
                        <a href="orders.php" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span style="display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <span>Đơn Hàng</span>
                            </span>
                            <?php if ($new_order_count > 0): ?>
                                <span class="badge-msg-count" style="background-color: #3b82f6; color: white; border-radius: 50px; padding: 2px 8px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; height: 18px; min-width: 18px; line-height: 1;"><?= $new_order_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="admin-nav-item <?= $active_tab === 'users' ? 'active' : '' ?>">
                        <a href="users.php">
                            <i class="fa-solid fa-users-gear"></i>
                            <span>Người Dùng</span>
                        </a>
                    </li>
                    <li style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 1rem;" class="admin-nav-item">
                        <a href="../../frontend/index.php">
                            <i class="fa-solid fa-house"></i>
                            <span>Xem Trang Chủ</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="admin-sidebar-footer">
                &copy; 2026 Ngọc Ánh Dương
            </div>
        </aside>

        <!-- Main Panel -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="admin-header-title">
                    <button class="mobile-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle Sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    <h2><?= h($page_title ?? 'Trang Quản Trị') ?></h2>
                </div>
                <div class="admin-header-user" style="gap: 1.25rem;">
                    
                    <!-- Theme toggle removed (site uses single light theme) -->

                    <!-- Notifications Bell Dropdown -->
                    <div class="admin-notif-dropdown-wrapper" style="position: relative;">
                        <button class="admin-notif-btn" id="adminNotifBtn" title="Thông báo" style="width: 38px; height: 38px; border-radius: 10px; background-color: #f1f5f9; color: var(--color-admin-text-dark); border: 1px solid var(--color-admin-border); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; position: relative;">
                            <i class="fa-solid fa-bell" style="font-size: 1.1rem;"></i>
                            <?php if ($total_unread_notifications > 0): ?>
                                <span class="admin-notif-badge" style="position: absolute; top: -5px; right: -5px; background-color: #ef4444; color: white; font-size: 0.65rem; font-weight: 700; border-radius: 50%; min-width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; padding: 0 4px; border: 2px solid white;"><?= $total_unread_notifications ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <div class="admin-notif-dropdown" id="adminNotifDropdown" style="position: absolute; top: calc(100% + 10px); right: 0; width: 320px; background-color: white; border: 1px solid var(--color-admin-border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.2s ease; z-index: 1000;">
                            <div class="admin-notif-header" style="padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--color-admin-border); font-weight: 700; font-size: 0.88rem; color: var(--color-secondary); display: flex; justify-content: space-between; align-items: center;">
                                <span>Thông báo mới</span>
                                <span style="background-color: var(--color-primary); color: white; font-size: 0.7rem; padding: 1px 6px; border-radius: 50px;"><?= $total_unread_notifications ?> chưa đọc</span>
                            </div>
                            <div class="admin-notif-body" style="max-height: 280px; overflow-y: auto;">
                                <?php if (!empty($notifications_list)): ?>
                                    <?php foreach ($notifications_list as $notif): ?>
                                        <?php
                                        $notifUrl = 'messages.php?action=view&id='.$notif['id'];
                                        $bgColor = 'background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;';
                                        $iconClass = 'fa-envelope';
                                        $descText = 'Gửi tin nhắn liên hệ mới';
                                        
                                        if ($notif['type'] === 'quote') {
                                            $notifUrl = 'quotes.php?action=view&id='.$notif['id'];
                                            $bgColor = 'background-color: rgba(16, 185, 129, 0.1); color: #10b981;';
                                            $iconClass = 'fa-file-invoice-dollar';
                                            $descText = 'Yêu cầu báo giá: ' . h($notif['product_name'] ?? '');
                                        } elseif ($notif['type'] === 'article') {
                                            $notifUrl = 'news.php?action=edit&id='.$notif['id'];
                                            $bgColor = 'background-color: rgba(245, 158, 11, 0.1); color: #f59e0b;';
                                            $iconClass = 'fa-newspaper';
                                            $descText = 'Đóng góp bài viết mới chờ duyệt';
                                        } elseif ($notif['type'] === 'order') {
                                            $notifUrl = 'orders.php?action=view&id='.$notif['id'];
                                            $bgColor = 'background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;';
                                            $iconClass = 'fa-cart-shopping';
                                            $descText = 'Có đơn đặt hàng mới';
                                        }
                                        ?>
                                        <a href="<?= $notifUrl ?>" class="admin-notif-item" style="display: flex; gap: 0.75rem; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--color-admin-border); font-size: 0.82rem; color: var(--color-admin-text-dark); transition: background-color 0.2s ease; text-decoration: none; align-items: flex-start;">
                                            <div class="admin-notif-icon" style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.85rem; <?= $bgColor ?>">
                                                <i class="fa-solid <?= $iconClass ?>"></i>
                                            </div>
                                            <div class="admin-notif-content" style="flex-grow: 1; display: flex; flex-direction: column; gap: 2px;">
                                                <span class="admin-notif-title" style="font-weight: 600; color: #1e293b;"><?= h($notif['name']) ?></span>
                                                <span class="admin-notif-desc" style="color: var(--color-admin-text-muted); font-size: 0.78rem;">
                                                    <?= $descText ?>
                                                </span>
                                                <span class="admin-notif-time" style="font-size: 0.72rem; color: var(--color-admin-text-muted);"><i class="fa-regular fa-clock" style="margin-right: 3px;"></i><?= date('H:i d/m/Y', strtotime($notif['created_at'])) ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="admin-notif-empty" style="padding: 2rem 1rem; text-align: center; color: var(--color-admin-text-muted); font-size: 0.82rem;">
                                        <i class="fa-regular fa-bell-slash" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block; opacity: 0.5;"></i>
                                        Không có thông báo chưa đọc
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="admin-notif-footer" style="padding: 0.75rem 1.25rem; border-top: 1px solid var(--color-admin-border); text-align: center; background-color: #f8fafc; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                <a href="messages.php" style="font-size: 0.8rem; font-weight: 600; color: var(--color-primary); text-decoration: none;">Xem tất cả liên hệ</a>
                            </div>
                        </div>
                    </div>

                    <div class="admin-user-profile-wrapper" style="display: flex; align-items: center;">
                        <div class="admin-user-profile">
                            <div class="admin-avatar">
                                <?= strtoupper(substr($current_admin['username'], 0, 1)) ?>
                            </div>
                            <div class="admin-user-details">
                                <span class="admin-user-name"><?= h($current_admin['full_name'] ?: $current_admin['username']) ?></span>
                                <span class="admin-user-role">Quản trị viên</span>
                            </div>
                        </div>
                        <button class="admin-logout-btn" onclick="location.href='../../frontend/logout.php'" title="Đăng xuất">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </div>
                </div>

                <!-- Admin Notifications and Theme Toggle Script -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Theme toggle removed — single light theme enforced

                    // Bell dropdown toggle
                    const notifBtn = document.getElementById('adminNotifBtn');
                    const notifDropdown = document.getElementById('adminNotifDropdown');
                    
                    if (notifBtn && notifDropdown) {
                        notifBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            notifDropdown.style.opacity = notifDropdown.style.opacity === '1' ? '0' : '1';
                            notifDropdown.style.visibility = notifDropdown.style.visibility === 'visible' ? 'hidden' : 'visible';
                            notifDropdown.style.transform = notifDropdown.style.transform === 'translateY(0px)' ? 'translateY(10px)' : 'translateY(0px)';
                        });
                        
                        document.addEventListener('click', function(e) {
                            if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                                notifDropdown.style.opacity = '0';
                                notifDropdown.style.visibility = 'hidden';
                                notifDropdown.style.transform = 'translateY(10px)';
                            }
                        });
                    }

                    // Admin Mobile Sidebar Toggle
                    // Admin Mobile Sidebar Toggle is handled in admin_footer.php to avoid duplicate listeners
                });
                </script>
            </header>

            <!-- Content Area -->
            <div class="admin-content">
