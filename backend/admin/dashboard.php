<?php
$page_title = "Bảng Điều Khiển Tổng Quan";
$active_admin_tab = "dashboard";
require_once __DIR__ . '/includes/header.php';

// Fetch statistics
$prod_count = 0;
$cat_count = 0;
$news_count = 0;
$msg_count = 0;
$user_count = 0;

// Products count
$res = db_query("SELECT COUNT(*) as cnt FROM products");
if ($res) {
    $row = $res->fetch_assoc();
    $prod_count = $row['cnt'];
}

// Categories count
$res = db_query("SELECT COUNT(*) as cnt FROM categories");
if ($res) {
    $row = $res->fetch_assoc();
    $cat_count = $row['cnt'];
}

// News articles count (section = 'news')
$res = db_query("SELECT COUNT(*) as cnt FROM news_articles WHERE section = 'news'");
$news_count = 0;
if ($res) {
    $row = $res->fetch_assoc();
    $news_count = $row['cnt'];
}

// Planting techniques articles count (section = 'tech')
$res = db_query("SELECT COUNT(*) as cnt FROM news_articles WHERE section = 'tech'");
$tech_count = 0;
if ($res) {
    $row = $res->fetch_assoc();
    $tech_count = $row['cnt'];
}

// Unread/New messages count
$res = db_query("SELECT COUNT(*) as cnt FROM contact_messages WHERE status = 'new'");
if ($res) {
    $row = $res->fetch_assoc();
    $msg_count = $row['cnt'];
}

// Customers count
$res = db_query("SELECT COUNT(*) as cnt FROM users WHERE role = 'customer'");
if ($res) {
    $row = $res->fetch_assoc();
    $user_count = $row['cnt'];
}

// Unread/New quote requests count
$quote_count = 0;
$res = db_query("SELECT COUNT(*) as cnt FROM quote_requests WHERE status = 'new'");
if ($res) {
    $row = $res->fetch_assoc();
    $quote_count = (int)$row['cnt'];
}

// New orders count
$new_order_count = 0;
$res = db_query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
if ($res) {
    $row = $res->fetch_assoc();
    $new_order_count = (int)$row['cnt'];
}

// Fetch recent orders
$recent_orders = db_query("
    SELECT o.*, 
    (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id) AS product_names 
    FROM orders o 
    ORDER BY o.id DESC LIMIT 5
");

// Fetch recent messages
$recent_messages = db_query("SELECT * FROM contact_messages ORDER BY id DESC LIMIT 5");

// Fetch recent quote requests
$recent_quotes = db_query("SELECT * FROM quote_requests ORDER BY id DESC LIMIT 5");

// --------------------------------------------------------------------------
// Chart Data Fetching
// --------------------------------------------------------------------------
// 1. Fetch Category Product distribution
$cat_labels = [];
$cat_counts = [];
$cat_prod_res = db_query("SELECT c.name, COUNT(p.id) as cnt FROM categories c LEFT JOIN products p ON p.category_id = c.id WHERE c.type = 'product' GROUP BY c.id ORDER BY cnt DESC");
if ($cat_prod_res) {
    while ($row = $cat_prod_res->fetch_assoc()) {
        $cat_labels[] = $row['name'];
        $cat_counts[] = (int)$row['cnt'];
    }
}

// 2. Fetch Trend data for Messages & Quotes (Last 6 Months)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('m/Y', strtotime("-$i months"));
}

$msg_monthly = array_fill_keys($months, 0);
$msg_trend_res = db_query("SELECT DATE_FORMAT(created_at, '%m/%Y') as month_val, COUNT(*) as cnt FROM contact_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month_val");
if ($msg_trend_res) {
    while ($row = $msg_trend_res->fetch_assoc()) {
        if (isset($msg_monthly[$row['month_val']])) {
            $msg_monthly[$row['month_val']] = (int)$row['cnt'];
        }
    }
}

$quote_monthly = array_fill_keys($months, 0);
$quote_trend_res = db_query("SELECT DATE_FORMAT(created_at, '%m/%Y') as month_val, COUNT(*) as cnt FROM quote_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month_val");
if ($quote_trend_res) {
    while ($row = $quote_trend_res->fetch_assoc()) {
        if (isset($quote_monthly[$row['month_val']])) {
            $quote_monthly[$row['month_val']] = (int)$row['cnt'];
        }
    }
}

$msg_counts = array_values($msg_monthly);
$quote_counts = array_values($quote_monthly);
?>

<!-- Metrics Grid -->
<div class="admin-metrics-grid">
    <a href="orders.php" class="admin-metric-link">
    <div class="admin-metric-card">
        <div class="admin-metric-info">
            <span class="admin-metric-label">Đơn Hàng Mới</span>
            <span class="admin-metric-value"><?= $new_order_count ?></span>
        </div>
        <div class="admin-metric-icon icon-danger" style="background-color: rgba(245, 158, 11, 0.1); color: #f59e0b;">
            <i class="fa-solid fa-cart-arrow-down"></i>
        </div>
    </div>
    </a>

    <a href="products.php" class="admin-metric-link">
    <div class="admin-metric-card">
        <div class="admin-metric-info">
            <span class="admin-metric-label">Sản Phẩm</span>
            <span class="admin-metric-value"><?= $prod_count ?></span>
        </div>
        <div class="admin-metric-icon icon-primary">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>
    </a>
    
    <a href="categories.php" class="admin-metric-link">
    <div class="admin-metric-card">
        <div class="admin-metric-info">
            <span class="admin-metric-label">Danh Mục</span>
            <span class="admin-metric-value"><?= $cat_count ?></span>
        </div>
        <div class="admin-metric-icon icon-secondary">
            <i class="fa-solid fa-tags"></i>
        </div>
    </div>
    </a>

    <a href="news.php" class="admin-metric-link">
    <div class="admin-metric-card">
        <div class="admin-metric-info">
            <span class="admin-metric-label">Tin Tức</span>
            <span class="admin-metric-value"><?= $news_count ?></span>
        </div>
        <div class="admin-metric-icon icon-success">
            <i class="fa-solid fa-newspaper"></i>
        </div>
    </div>
    </a>

    <a href="techniques.php" class="admin-metric-link">
    <div class="admin-metric-card">
        <div class="admin-metric-info">
            <span class="admin-metric-label">Kỹ Thuật Trồng</span>
            <span class="admin-metric-value"><?= $tech_count ?></span>
        </div>
        <div class="admin-metric-icon icon-warning">
            <i class="fa-solid fa-seedling"></i>
        </div>
    </div>
    </a>

    <a href="messages.php" class="admin-metric-link">
    <div class="admin-metric-card">
        <div class="admin-metric-info">
            <span class="admin-metric-label">Tin Nhắn Mới</span>
            <span class="admin-metric-value"><?= $msg_count ?></span>
        </div>
        <div class="admin-metric-icon icon-danger">
            <i class="fa-solid fa-comment-dots"></i>
        </div>
    </div>
    </a>
    
    <a href="quotes.php" class="admin-metric-link">
    <div class="admin-metric-card">
        <div class="admin-metric-info">
            <span class="admin-metric-label">Báo Giá Mới</span>
            <span class="admin-metric-value"><?= $quote_count ?></span>
        </div>
        <div class="admin-metric-icon icon-success" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
    </div>
    </a>

    <a href="users.php" class="admin-metric-link">
    <div class="admin-metric-card">
        <div class="admin-metric-info">
            <span class="admin-metric-label">Khách Hàng</span>
            <span class="admin-metric-value"><?= $user_count ?></span>
        </div>
        <div class="admin-metric-icon icon-success" style="background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
    </a>
</div>

<!-- Charts Section -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="admin-charts-row">
    <div class="admin-chart-card">
        <h4><i class="fa-solid fa-chart-pie"></i> Cơ Cấu Sản Phẩm Theo Danh Mục</h4>
        <div class="chart-container">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <div class="admin-chart-card">
        <h4><i class="fa-solid fa-chart-column"></i> Xu Hướng Báo Giá & Tin Nhắn (6 Tháng)</h4>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <div class="admin-chart-card admin-shortcuts-card">
        <h4><i class="fa-solid fa-bolt"></i> Thao Tác Nhanh</h4>
        <div class="admin-shortcuts-grid">
            <a href="products.php?action=add" class="shortcut-btn">
                <i class="fa-solid fa-plus"></i> Thêm Sản Phẩm
            </a>
            <a href="news.php?action=add" class="shortcut-btn">
                <i class="fa-solid fa-file-pen"></i> Viết Tin Tức
            </a>
            <a href="techniques.php?action=add" class="shortcut-btn">
                <i class="fa-solid fa-seedling"></i> Viết Kỹ Thuật
            </a>
            <a href="categories.php?action=add" class="shortcut-btn">
                <i class="fa-solid fa-tag"></i> Tạo Danh Mục
            </a>
            <a href="messages.php" class="shortcut-btn">
                <i class="fa-solid fa-envelope"></i> Thư Liên Hệ
            </a>
            <a href="../../frontend/index.php" target="_blank" class="shortcut-btn shortcut-home">
                <i class="fa-solid fa-globe"></i> Xem Trang Chủ
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Number counter animation
    const valueElements = document.querySelectorAll('.admin-metric-value');
    valueElements.forEach(el => {
        const targetValue = parseInt(el.innerText.replace(/,/g, ''), 10) || 0;
        let currentValue = 0;
        const increment = targetValue / 40; // 40 steps
        
        if (targetValue > 0) {
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= targetValue) {
                    el.innerText = targetValue.toLocaleString('en-US');
                    clearInterval(timer);
                } else {
                    el.innerText = Math.ceil(currentValue).toLocaleString('en-US');
                }
            }, 25);
        }
    });

    // 1. Doughnut Chart — Category Distribution
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($cat_counts) ?>,
                backgroundColor: [
                    '#0b6623', // Primary green
                    '#0f4c81', // Secondary blue
                    '#d98a2b', // Accent orange
                    '#10b981', // Emerald green
                    '#3b82f6', // Light blue
                    '#64748b'  // Slate gray
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: {
                            family: 'Inter',
                            size: 11
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.label + ': ' + context.raw + ' sản phẩm';
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // 2. Bar Chart — Trends
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [
                {
                    label: 'Yêu cầu báo giá',
                    data: <?= json_encode($quote_counts) ?>,
                    backgroundColor: 'rgba(11, 102, 35, 0.85)',
                    borderColor: '#0b6623',
                    borderWidth: 1,
                    borderRadius: 6
                },
                {
                    label: 'Tin nhắn liên hệ',
                    data: <?= json_encode($msg_counts) ?>,
                    backgroundColor: 'rgba(15, 76, 129, 0.85)',
                    borderColor: '#0f4c81',
                    borderWidth: 1,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: {
                            family: 'Inter'
                        }
                    },
                    grid: {
                        color: '#e2e8f0'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Inter'
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                }
            }
        }
    });
});
</script>

<!-- Recent Orders Section -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-cart-shopping"></i> Đơn Hàng Mới Nhất</h3>
        <a href="orders.php" class="btn btn-outline btn-sm" style="border-radius: 8px;">Xem tất cả</a>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table responsive-cards-mobile">
                <thead>
                    <tr>
                        <th style="width: 80px;">Mã ĐH</th>
                        <th>Khách hàng</th>
                        <th>Sản phẩm</th>
                        <th style="text-align: right;">Tổng Tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày Đặt</th>
                        <th style="width: 100px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                        <?php while ($order = $recent_orders->fetch_assoc()): 
                            $status_badge = '';
                            switch ($order['status']) {
                                case 'pending': $status_badge = '<span class="status-badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706;"><i class="fa-regular fa-clock"></i> Chờ xử lý</span>'; break;
                                case 'confirmed': $status_badge = '<span class="status-badge" style="background: rgba(59, 130, 246, 0.15); color: #2563eb;"><i class="fa-solid fa-check"></i> Đã xác nhận</span>'; break;
                                case 'shipping': $status_badge = '<span class="status-badge" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed;"><i class="fa-solid fa-truck"></i> Đang giao</span>'; break;
                                case 'completed': $status_badge = '<span class="status-badge" style="background: rgba(16, 185, 129, 0.15); color: #059669;"><i class="fa-solid fa-box"></i> Hoàn thành</span>'; break;
                                case 'cancelled': $status_badge = '<span class="status-badge" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;"><i class="fa-solid fa-times"></i> Đã hủy</span>'; break;
                            }
                        ?>
                            <tr>
                                <td data-label="Mã ĐH" style="font-weight: 600;">#<?= $order['id'] ?></td>
                                <td data-label="Khách hàng">
                                    <div style="font-weight: 600;"><a href="orders.php?action=view&id=<?= $order['id'] ?>" style="color: var(--color-secondary); text-decoration: underline;"><?= h($order['customer_name']) ?></a></div>
                                    <div style="font-size: 0.8rem; color: var(--color-admin-text-muted);"><?= h($order['customer_phone']) ?></div>
                                </td>
                                <td data-label="Sản phẩm" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= h($order['product_names'] ?? '') ?>">
                                    <?= h($order['product_names'] ?? '') ?>
                                </td>
                                <td data-label="Tổng Tiền" style="text-align: right; font-weight: 600; color: var(--color-primary);"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</td>
                                <td data-label="Trạng thái"><?= $status_badge ?></td>
                                <td data-label="Ngày Đặt"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td data-label="Thao tác">
                                    <div class="actions-cell">
                                        <a href="orders.php?action=view&id=<?= $order['id'] ?>" class="btn-icon-only btn-view" title="Xem chi tiết">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--color-admin-text-muted);">
                                Chưa có đơn hàng nào được tạo.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Submissions Section -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-bell"></i> Tin Nhắn Liên Hệ Gần Đây</h3>
        <a href="messages.php" class="btn btn-outline btn-sm" style="border-radius: 8px;">Xem tất cả</a>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table responsive-cards-mobile">
                <thead>
                    <tr>
                        <th>Người gửi</th>
                        <th>Số điện thoại</th>
                        <th>Chủ đề</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th style="width: 100px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_messages && $recent_messages->num_rows > 0): ?>
                        <?php while ($msg = $recent_messages->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Người gửi">
                                    <div style="font-weight: 600;">
                                        <a href="messages.php?action=view&id=<?= $msg['id'] ?>" style="color: var(--color-secondary); text-decoration: underline;"><?= h($msg['name']) ?></a>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--color-admin-text-muted);"><?= h($msg['email']) ?></div>
                                </td>
                                <td data-label="Số điện thoại"><?= h($msg['phone']) ?></td>
                                <td data-label="Chủ đề"><?= h($msg['subject'] ?: '(Không có tiêu đề)') ?></td>
                                <td data-label="Ngày gửi"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></td>
                                <td data-label="Trạng thái">
                                    <?php if ($msg['status'] === 'new'): ?>
                                        <span class="badge badge-new">Mới</span>
                                    <?php elseif ($msg['status'] === 'read'): ?>
                                        <span class="badge badge-read">Đã đọc</span>
                                    <?php else: ?>
                                        <span class="badge badge-closed">Đã xử lý</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Thao tác">
                                    <div class="actions-cell">
                                        <a href="messages.php?action=view&id=<?= $msg['id'] ?>" class="btn-icon-only btn-view" title="Xem chi tiết">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--color-admin-text-muted);">
                                Chưa có tin nhắn liên hệ nào được gửi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Quote Requests Section -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-file-invoice-dollar"></i> Yêu Cầu Báo Giá Gần Đây</h3>
        <a href="quotes.php" class="btn btn-outline btn-sm" style="border-radius: 8px;">Xem tất cả</a>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table responsive-cards-mobile">
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Sản phẩm yêu cầu</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th style="width: 100px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_quotes && $recent_quotes->num_rows > 0): ?>
                        <?php while ($quote = $recent_quotes->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Khách hàng">
                                    <div style="font-weight: 600;">
                                        <a href="quotes.php?action=view&id=<?= $quote['id'] ?>" style="color: var(--color-secondary); text-decoration: underline;"><?= h($quote['name']) ?></a>
                                    </div>
                                </td>
                                <td data-label="Số điện thoại"><?= h($quote['phone']) ?></td>
                                <td data-label="Sản phẩm"><?= h($quote['product_name']) ?></td>
                                <td data-label="Ngày gửi"><?= date('d/m/Y H:i', strtotime($quote['created_at'])) ?></td>
                                <td data-label="Trạng thái">
                                    <?php if ($quote['status'] === 'new'): ?>
                                        <span class="badge badge-new">Mới</span>
                                    <?php elseif ($quote['status'] === 'read'): ?>
                                        <span class="badge badge-read">Đã đọc</span>
                                    <?php else: ?>
                                        <span class="badge badge-closed" style="background-color: #10b981;">Đã xử lý</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Thao tác">
                                    <div class="actions-cell">
                                        <a href="quotes.php?action=view&id=<?= $quote['id'] ?>" class="btn-icon-only btn-view" title="Xem chi tiết">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--color-admin-text-muted);">
                                Chưa có yêu cầu báo giá nào được gửi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
