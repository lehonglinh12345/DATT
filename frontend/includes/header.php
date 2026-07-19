<?php
require_once __DIR__ . '/../../backend/auth.php';

$nav_items = [
    ['href' => 'index.php',                'label' => 'Trang chủ',  'key' => 'home'],
    ['href' => 'about.php',                'label' => 'Giới thiệu', 'key' => 'about'],
    ['href' => 'products.php',             'label' => 'Sản phẩm',   'key' => 'products'],
    ['href' => 'planting-techniques.php',  'label' => 'Kỹ thuật',   'key' => 'tech'],
    ['href' => 'news.php',                 'label' => 'Tin tức',    'key' => 'news'],
    ['href' => 'contact.php',              'label' => 'Liên hệ',    'key' => 'contact'],
];

// Reusable logo — dùng chung desktop & mobile
function render_logo(string $href = 'index.php', bool $show_sub = true): string {
    $sub = $show_sub ? '<span class="brand-sub">IMPORT CHEMICAL</span>' : '';
    return <<<HTML
    <a href="{$href}" class="logo">
        <div class="logo-icon">
            <img src="images/logo.jpg" alt="Ngọc Ánh Dương" class="logo-bg">
            <svg class="logo-pill" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" fill="none">
                <rect x="2" y="15" width="44" height="18" rx="9" fill="url(#pill-hdr)"/>
                <rect x="2" y="15" width="22" height="18" rx="9" fill="rgba(255,255,255,0.14)"/>
                <line x1="24" y1="15" x2="24" y2="33" stroke="rgba(255,255,255,0.55)" stroke-width="1.2"/>
                <path d="M28 24 C30 19.5,38 19.5,38 24 C38 28.5,30 28.5,28 24Z"
                      fill="rgba(255,255,255,0.26)" stroke="rgba(255,255,255,0.6)" stroke-width="0.8"/>
                <line x1="28" y1="24" x2="38" y2="24" stroke="rgba(255,255,255,0.4)" stroke-width="0.7"/>
                <rect x="5" y="17" width="13" height="3.5" rx="1.75" fill="rgba(255,255,255,0.16)"/>
                <defs>
                    <linearGradient id="pill-hdr" x1="2" y1="15" x2="46" y2="33" gradientUnits="userSpaceOnUse">
                        <stop offset="0%"   stop-color="#0b6623"/>
                        <stop offset="100%" stop-color="#38b249"/>
                    </linearGradient>
                </defs>
            </svg>
        </div>
       <div class="logo-text">
        
        <!-- Dòng trên -->
        <span class="company-line">
            CÔNG TY CỔ PHẦN
        </span>

        <!-- Tên chính -->
        <span class="brand-name">
            NGỌC ÁNH DƯƠNG
        </span>

        <!-- Dòng dưới -->
        <span class="brand-sub">
            HÓA CHẤT NHẬP KHẨU
        </span>

    </div>
    </a>
    HTML;
}
?>

<!-- TOP BAR -->
<div class="top-bar">
    <div class="container top-bar-container">
        <div class="top-contacts">
            <a href="tel:0976828171"><i class="fa-solid fa-phone"></i> Hotline: 0976.828.171</a>
            <a href="mailto:ngocanhduongchemical@gmail.com"><i class="fa-solid fa-envelope"></i> ngocanhduongchemical@gmail.com</a>
        </div>
        <div class="top-meta">
            <span><i class="fa-solid fa-clock"></i> 7:30 – 17:00</span>
            <div class="lang-switch">
                <span class="active">VI</span> | <span>EN</span>
            </div>
        </div>
    </div>
</div>

<!-- PHONE PROMPT BANNER -->
<?php if (auth_is_logged_in() && empty(auth_get_user()['phone'])): ?>
<div class="phone-prompt-banner" style="background: #fff3cd; color: #856404; padding: 0.5rem; text-align: center; font-size: 0.9rem; font-weight: 500; border-bottom: 1px solid #ffeeba;">
    <div class="container">
        <i class="fa-solid fa-bell" style="color: #d39e00;"></i> 
        Bạn chưa cập nhật Số điện thoại. Vui lòng <a href="profile.php" style="color: #0b6623; text-decoration: underline; font-weight: 700;">cập nhật ngay</a> để Admin liên hệ báo giá.
    </div>
</div>
<?php endif; ?>

<!-- MAIN HEADER -->
<header class="main-header" id="mainHeader">
    <div class="container header-container">

        <?php echo render_logo('index.php', true); ?>

        <nav class="nav-menu" aria-label="Điều hướng chính">
            <ul>
                <?php foreach ($nav_items as $item):
                    $active = isset($active_page) && $active_page === $item['key'];
                ?>
                <li>
                    <a href="<?= $item['href'] ?>"
                       class="<?= $active ? 'active' : '' ?>"
                       <?= $active ? 'aria-current="page"' : '' ?>>
                        <?= $item['label'] ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="header-actions">
            <?php if (auth_is_logged_in()): 
                $curr_user = auth_get_user();
            ?>
                <!-- NOTIFICATION BELL -->
                <div class="user-menu-wrapper notif-wrapper" style="margin-right: 15px;">
                    <button class="user-menu-btn" id="notifBtn" aria-label="Thông báo" style="position: relative;">
                        <i class="fa-solid fa-bell" style="font-size: 1.2rem; color: #475569;"></i>
                        <span class="badge" id="notifBadge" style="display: none; position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; font-weight: bold; line-height: 18px; text-align: center;">0</span>
                    </button>
                    <div class="user-dropdown" id="notifDropdown" style="width: 320px; right: -50px; padding: 0;">
                        <div style="padding: 1rem; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-radius: 12px 12px 0 0;">
                            <strong style="color: var(--color-dark);">Thông báo</strong>
                            <button id="markAllReadBtn" style="background: none; border: none; color: var(--color-primary); font-size: 0.8rem; cursor: pointer; font-weight: 600;">Đánh dấu đã đọc</button>
                        </div>
                        <div id="notifList" style="max-height: 350px; overflow-y: auto; padding: 0;">
                            <div style="padding: 1.5rem; text-align: center; color: var(--color-dark-muted); font-size: 0.9rem;">Đang tải...</div>
                        </div>
                    </div>
                </div>

                <div class="user-menu-wrapper">
                    <button class="user-menu-btn" id="userMenuBtn" aria-label="Menu người dùng">
                        <i class="fa-solid fa-circle-user" style="font-size: 1.1rem;"></i>
                        <span class="user-name-text"><?= h($curr_user['full_name'] ?: $curr_user['username']) ?></span>
                        <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; opacity: 0.7;"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fa-solid fa-id-badge"></i> Trang Cá Nhân
                        </a>
                        <?php if ($curr_user['role'] === 'admin'): ?>
                            <a href="../backend/admin/dashboard.php" class="dropdown-item">
                                <i class="fa-solid fa-chart-line"></i> Trang Quản Trị
                            </a>
                        <?php endif; ?>
                        <a href="write-news.php" class="dropdown-item">
                            <i class="fa-solid fa-pen-to-square"></i> Đóng Góp Tin Tức
                        </a>
                        <a href="logout.php" class="dropdown-item logout-link">
                            <i class="fa-solid fa-right-from-bracket"></i> Đăng Xuất
                        </a>
                    </div>
                    <script>
                        (function(){
                            // User Dropdown
                            var userBtn = document.getElementById('userMenuBtn');
                            var userDropdown = document.getElementById('userDropdown');
                            
                            // Notif Dropdown
                            var notifBtn = document.getElementById('notifBtn');
                            var notifDropdown = document.getElementById('notifDropdown');

                            function closeAll() {
                                if(userDropdown) userDropdown.classList.remove('open');
                                if(notifDropdown) notifDropdown.classList.remove('open');
                            }

                            if (userBtn && userDropdown) {
                                userBtn.addEventListener('click', function(e){ 
                                    e.preventDefault(); e.stopPropagation(); 
                                    var wasOpen = userDropdown.classList.contains('open');
                                    closeAll();
                                    if(!wasOpen) userDropdown.classList.add('open'); 
                                });
                            }
                            if (notifBtn && notifDropdown) {
                                notifBtn.addEventListener('click', function(e){ 
                                    e.preventDefault(); e.stopPropagation(); 
                                    var wasOpen = notifDropdown.classList.contains('open');
                                    closeAll();
                                    if(!wasOpen) {
                                        notifDropdown.classList.add('open'); 
                                        loadNotifications(); // Reload when open
                                    }
                                });
                            }
                            
                            document.addEventListener('click', function(e){ 
                                if (userBtn && !userBtn.contains(e.target) && !userDropdown.contains(e.target) && 
                                    notifBtn && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) { 
                                    closeAll();
                                } 
                            });
                            document.addEventListener('keydown', function(e){ 
                                if (e.key === 'Escape') closeAll(); 
                            });

                            // Notification logic
                            var notifList = document.getElementById('notifList');
                            var notifBadge = document.getElementById('notifBadge');
                            var markAllBtn = document.getElementById('markAllReadBtn');

                            function loadNotifications() {
                                if(!notifList) return;
                                fetch('../backend/api_notifications.php?action=get')
                                    .then(res => res.json())
                                    .then(data => {
                                        if(data.success) {
                                            renderNotifications(data.data.notifications);
                                            updateBadge(data.data.unread_count);
                                        }
                                    });
                            }

                            function updateBadge(count) {
                                if (count > 0) {
                                    notifBadge.style.display = 'block';
                                    notifBadge.innerText = count > 99 ? '99+' : count;
                                } else {
                                    notifBadge.style.display = 'none';
                                }
                            }

                            function renderNotifications(items) {
                                if(items.length === 0) {
                                    notifList.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: var(--color-dark-muted); font-size: 0.9rem;">Bạn không có thông báo nào.</div>';
                                    return;
                                }
                                let html = '';
                                items.forEach(item => {
                                    let bg = item.is_read == 0 ? '#f0fdf4' : '#ffffff';
                                    let fw = item.is_read == 0 ? '700' : '500';
                                    let href = item.link ? item.link : '#';
                                    html += `
                                        <a href="${href}" onclick="markRead(${item.id}, event, '${href}')" style="display: block; padding: 1rem; border-bottom: 1px solid var(--color-border); background: ${bg}; text-decoration: none; transition: background 0.2s;">
                                            <div style="font-weight: ${fw}; color: var(--color-dark); font-size: 0.95rem; margin-bottom: 0.25rem;">${item.title}</div>
                                            <div style="color: var(--color-dark-muted); font-size: 0.85rem; line-height: 1.4; margin-bottom: 0.4rem;">${item.message}</div>
                                            <div style="color: #94a3b8; font-size: 0.75rem;">${new Date(item.created_at).toLocaleString('vi-VN')}</div>
                                        </a>
                                    `;
                                });
                                notifList.innerHTML = html;
                            }

                            window.markRead = function(id, e, href) {
                                e.preventDefault();
                                const formData = new FormData();
                                formData.append('action', 'mark_read');
                                formData.append('notification_id', id);
                                fetch('../backend/api_notifications.php', { method: 'POST', body: formData })
                                    .then(() => {
                                        if (href && href !== '#') window.location.href = href;
                                        else loadNotifications();
                                    });
                            }

                            if (markAllBtn) {
                                markAllBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    const formData = new FormData();
                                    formData.append('action', 'mark_all_read');
                                    fetch('../backend/api_notifications.php', { method: 'POST', body: formData })
                                        .then(() => loadNotifications());
                                });
                            }

                            // Initial load
                            if(notifBtn) loadNotifications();
                        })();
                    </script>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline header-login-btn" style="padding: 0.6rem 1.25rem; font-size: 0.85rem; border-radius: 50px;">
                    <i class="fa-solid fa-right-to-bracket"></i> <span class="login-text">Đăng Nhập</span>
                </a>
            <?php endif; ?>

            

            <a href="contact.php" class="btn btn-primary btn-quote">
                <i class="fa-solid fa-tag"></i> Báo Giá
            </a>
            <button class="mobile-toggle" id="mobileMenuToggle"
                    aria-label="Mở menu" aria-expanded="false" aria-controls="mobileNav">
                <span></span><span></span><span></span>
            </button>
        </div>

    </div>
</header>

<!-- MOBILE DRAWER -->
<div class="mobile-nav" id="mobileNav" role="dialog"
    aria-label="Menu điều hướng" aria-hidden="true" inert>

    <div class="mobile-nav-header">
        <?php echo render_logo('index.php', false); ?>
        <button class="mobile-close" id="mobileMenuClose" aria-label="Đóng menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="mobile-search">
        <form action="products.php" method="GET" role="search">
            <input type="text" name="search" placeholder="Tìm sản phẩm…"
                   aria-label="Tìm kiếm sản phẩm">
            <button type="submit" aria-label="Tìm kiếm">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>
    </div>

    <ul>
        <?php foreach ($nav_items as $item):
            $active = isset($active_page) && $active_page === $item['key'];
        ?>
        <li>
            <a href="<?= $item['href'] ?>"
               class="<?= $active ? 'active' : '' ?>"
               <?= $active ? 'aria-current="page"' : '' ?>>
                <?= $item['label'] ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="mobile-contacts">
        <?php if (auth_is_logged_in()): 
            $curr_user = auth_get_user();
        ?>
            <div class="mobile-user-info" style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--color-border);">
                <div style="font-weight: 700; color: var(--color-secondary); margin-bottom: 0.25rem; font-size: 1.05rem;">
                    <i class="fa-solid fa-circle-user"></i> Xin chào, <?= h($curr_user['full_name'] ?: $curr_user['username']) ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--color-dark-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                    Vai trò: <strong><?= $curr_user['role'] === 'admin' ? 'Quản trị viên' : 'Khách hàng' ?></strong>
                </div>
            </div>
            <?php if ($curr_user['role'] === 'admin'): ?>
                <a href="../backend/admin/dashboard.php" class="btn btn-outline" style="margin-bottom:.5rem;justify-content:center;width:100%;border-radius:8px;padding:0.6rem;">
                    <i class="fa-solid fa-chart-line"></i> Trang Quản Trị
                </a>
            <?php endif; ?>
            <a href="write-news.php" class="btn btn-outline" style="margin-bottom:.5rem;justify-content:center;width:100%;border-radius:8px;padding:0.6rem;">
                <i class="fa-solid fa-pen-to-square"></i> Đóng Góp Tin Tức
            </a>
            <a href="logout.php" class="btn btn-outline" style="margin-bottom:.5rem;justify-content:center;width:100%;border-radius:8px;padding:0.6rem;border-color:#ef4444;color:#ef4444;">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng Xuất
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline" style="margin-bottom:.5rem;justify-content:center;width:100%;border-radius:8px;padding:0.6rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập
            </a>
            <a href="register.php" class="btn btn-outline" style="margin-bottom:.5rem;justify-content:center;width:100%;border-radius:8px;padding:0.6rem;">
                <i class="fa-solid fa-user-plus"></i> Đăng Ký
            </a>
        <?php endif; ?>

        <div style="margin-top: 1rem; border-top: 1px solid var(--color-border); padding-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
            <a href="tel:0976828171"><i class="fa-solid fa-phone" style="color: var(--color-primary);"></i> 0976.828.171</a>
            <a href="mailto:ngocanhduongchemical@gmail.com"><i class="fa-solid fa-envelope" style="color: var(--color-primary);"></i> ngocanhduongchemical@gmail.com</a>
        </div>
        <a href="contact.php" class="btn btn-primary" style="margin-top:.8rem;justify-content:center;border-radius:8px;padding:0.6rem;">
            <i class="fa-solid fa-tag"></i> Nhận Báo Giá
        </a>
    </div>
</div>

<div class="mobile-overlay" id="mobileOverlay" aria-hidden="true"></div>