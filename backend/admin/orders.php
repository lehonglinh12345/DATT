<?php
$page_title = "Quản Lý Đơn Hàng";
$active_admin_tab = "orders";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --------------------------------------------------------------------------
// Update Status
// --------------------------------------------------------------------------
if ($action === 'status' && $id > 0) {
    $status = $_GET['status'] ?? '';
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } elseif (!in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])) {
        $error = "Trạng thái không hợp lệ.";
    } else {
        $update = db_query("UPDATE orders SET status = ? WHERE id = ?", "si", [$status, $id]);
        if ($update) {
            $success = "Cập nhật trạng thái đơn hàng thành công!";
        } else {
            $error = "Không thể cập nhật trạng thái.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// Delete Order
// --------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } else {
        $delete = db_query("DELETE FROM orders WHERE id = ?", "i", [$id]);
        if ($delete) {
            $success = "Xóa đơn hàng thành công!";
        } else {
            $error = "Không thể xóa đơn hàng.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// View Details
// --------------------------------------------------------------------------
if ($action === 'view' && $id > 0):
    $res = db_query("SELECT * FROM orders WHERE id = ?", "i", [$id]);
    if ($res && $res->num_rows > 0) {
        $order = $res->fetch_assoc();
        $items_res = db_query("SELECT oi.*, p.name as product_name, p.product_key FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", "i", [$id]);
        $items = [];
        if ($items_res) {
            while ($row = $items_res->fetch_assoc()) {
                $items[] = $row;
            }
        }
    } else {
        $error = "Không tìm thấy đơn hàng.";
        $action = 'list';
    }

    if ($action !== 'list'):
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <?php
            $product_names_arr = array_column($items, 'product_name');
            $product_names_str = !empty($product_names_arr) ? implode(', ', $product_names_arr) : "Chi Tiết Đơn Hàng";
            ?>
            <h3><i class="fa-solid fa-cart-shopping"></i> <?= h($product_names_str) ?> #<?= $order['id'] ?></h3>
            <div style="display: flex; gap: 0.5rem;">
                <a href="orders.php" class="btn btn-outline btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
            </div>
        </div>
        <div class="admin-card-body">
            <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
                <div style="background-color: #fef2f2; border: 1px solid #f87171; border-left: 5px solid #ef4444; color: #991b1b; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-circle-exclamation"></i> Khách hàng đã hủy đơn này
                    </h4>
                    <p style="margin: 0; font-size: 0.95rem;"><strong>Lý do:</strong> <?= h($order['cancel_reason']) ?></p>
                </div>
            <?php endif; ?>

            <div class="message-detail-grid">
                <!-- Main Content -->
                <div style="background-color: #f8fafc; border-radius: 12px; padding: 2rem; border: 1px solid var(--color-admin-border);">
                    <div style="border-bottom: 1px solid var(--color-admin-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                        <h4 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 0.5rem; font-weight: 700;">
                            Danh Sách Sản Phẩm
                        </h4>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sản Phẩm</th>
                                    <th style="text-align: right;">Đơn Giá</th>
                                    <th style="text-align: center;">SL</th>
                                    <th style="text-align: right;">Thành Tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?= h($item['product_name']) ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b;">Mã: <?= h($item['product_key']) ?></div>
                                    </td>
                                    <td style="text-align: right;"><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                    <td style="text-align: center;"><?= $item['quantity'] ?></td>
                                    <td style="text-align: right; color: var(--color-primary); font-weight: 600;"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: right; font-size: 1.2rem;">
                        Tổng Cộng: <strong style="color: #dc3545; font-size: 1.5rem;"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</strong>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                    <div style="margin-top: 2rem; font-size: 0.85rem; color: var(--color-admin-text-muted); text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 600;">Ghi chú của khách hàng:</div>
                    <div style="line-height: 1.8; color: var(--color-admin-text-dark); white-space: pre-wrap; font-size: 0.95rem; background: #fff; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--color-admin-border);">
                        <?= h($order['notes']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sender Details -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="background-color: #fff; border-radius: 12px; padding: 1.5rem; border: 1px solid var(--color-admin-border); box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                        <h5 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--color-admin-text-dark); border-bottom: 2px solid var(--color-admin-border); padding-bottom: 0.75rem;">Thông Tin Khách Hàng</h5>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background-color: rgba(11, 102, 35, 0.1); display: flex; align-items: center; justify-content: center; color: var(--color-primary); font-size: 1.25rem;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: var(--color-admin-text-dark); font-size: 1.05rem;"><?= h($order['customer_name']) ?></div>
                                <div style="color: var(--color-admin-text-muted); font-size: 0.85rem;">Khách hàng <?= $order['user_id'] ? 'Thành viên' : 'Vãng lai' ?></div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                                <i class="fa-solid fa-phone" style="color: var(--color-primary); margin-top: 4px; width: 16px; text-align: center;"></i>
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--color-admin-text-muted); text-transform: uppercase; font-weight: 600;">Số điện thoại</div>
                                    <div style="font-size: 0.95rem; color: var(--color-admin-text-dark); font-weight: 500;">
                                        <a href="tel:<?= h($order['customer_phone']) ?>" style="color: inherit; text-decoration: none;"><?= h($order['customer_phone']) ?></a>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                                <i class="fa-solid fa-location-dot" style="color: var(--color-primary); margin-top: 4px; width: 16px; text-align: center;"></i>
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--color-admin-text-muted); text-transform: uppercase; font-weight: 600;">Địa chỉ giao hàng</div>
                                    <div style="font-size: 0.95rem; color: var(--color-admin-text-dark); line-height: 1.5;"><?= h($order['customer_address']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="background-color: #fff; border-radius: 12px; padding: 1.5rem; border: 1px solid var(--color-admin-border); box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                        <h5 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--color-admin-text-dark); border-bottom: 2px solid var(--color-admin-border); padding-bottom: 0.75rem;">Cập Nhật Trạng Thái</h5>
                        
                        <?php 
                        $status_colors = [
                            'pending' => '#f59e0b',
                            'confirmed' => '#3b82f6',
                            'shipping' => '#8b5cf6',
                            'completed' => '#10b981',
                            'cancelled' => '#ef4444'
                        ];
                        $status_labels = [
                            'pending' => 'Chờ xử lý',
                            'confirmed' => 'Đã xác nhận',
                            'shipping' => 'Đang giao hàng',
                            'completed' => 'Hoàn thành',
                            'cancelled' => 'Đã hủy'
                        ];
                        $curr_color = $status_colors[$order['status']] ?? '#64748b';
                        $curr_label = $status_labels[$order['status']] ?? 'Không xác định';
                        ?>
                        
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; font-weight: 600; font-size: 1rem; color: <?= $curr_color ?>">
                            <i class="fa-solid fa-circle-dot" style="font-size: 0.75rem;"></i> Hiện tại: <?= $curr_label ?>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php if ($order['status'] !== 'confirmed' && $order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                            <a href="?action=status&id=<?= $order['id'] ?>&status=confirmed&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn" style="background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; text-align: left; padding: 0.75rem 1rem;"><i class="fa-solid fa-check-circle" style="width: 24px;"></i> Chuyển sang: Đã xác nhận</a>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'confirmed'): ?>
                            <a href="?action=status&id=<?= $order['id'] ?>&status=shipping&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn" style="background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6; text-align: left; padding: 0.75rem 1rem;"><i class="fa-solid fa-truck" style="width: 24px;"></i> Chuyển sang: Đang giao hàng</a>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'shipping'): ?>
                            <a href="?action=status&id=<?= $order['id'] ?>&status=completed&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981; text-align: left; padding: 0.75rem 1rem;"><i class="fa-solid fa-box-open" style="width: 24px;"></i> Chuyển sang: Đã hoàn thành</a>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                            <a href="?action=status&id=<?= $order['id'] ?>&status=cancelled&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; text-align: left; padding: 0.75rem 1rem;" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');"><i class="fa-solid fa-times-circle" style="width: 24px;"></i> Hủy đơn hàng</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php 
    endif;
endif;
// --------------------------------------------------------------------------
// List Orders
// --------------------------------------------------------------------------
if ($action === 'list'):
    
    // Pagination & Filtering
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    $filter_status = $_GET['filter_status'] ?? '';
    
    $where = [];
    $params = [];
    $types = "";
    
    if ($filter_status !== '') {
        $where[] = "o.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    $where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
    
    $total_res = db_query("SELECT COUNT(*) as total FROM orders o $where_sql", $types, $params);
    $total_rows = $total_res ? $total_res->fetch_assoc()['total'] : 0;
    $total_pages = ceil($total_rows / $limit);
    
    $sql = "
        SELECT o.*, u.avatar
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id
        $where_sql 
        ORDER BY o.created_at DESC 
        LIMIT ?, ?
    ";
    $types .= "ii";
    $params[] = $offset;
    $params[] = $limit;
    
    $res = db_query($sql, $types, $params);
?>
    
    <div class="admin-header-actions">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--color-admin-text-dark); margin: 0;">Danh Sách Đơn Hàng</h2>
        
        <form method="GET" action="orders.php" style="display: flex; gap: 0.5rem; align-items: center; background: #fff; padding: 0.25rem; border-radius: 8px; border: 1px solid var(--color-admin-border);">
            <select name="filter_status" class="admin-input" style="border: none; background: transparent; padding-right: 2rem;">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                <option value="shipping" <?= $filter_status === 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
                <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 6px;"><i class="fa-solid fa-filter"></i> Lọc</button>
            <?php if ($filter_status !== ''): ?>
                <a href="orders.php" class="btn btn-outline btn-sm" style="border-radius: 6px; padding: 0.25rem 0.5rem;"><i class="fa-solid fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success"><i class="fa-solid fa-circle-check"></i> <?= h($success) ?></div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Mã ĐH</th>
                            <th>Khách hàng</th>
                            <th>Sản phẩm</th>
                            <th style="text-align: right;">Tổng Tiền</th>
                            <th style="width: 150px;">Trạng thái</th>
                            <th style="width: 150px;">Ngày Đặt</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res && $res->num_rows > 0): ?>
                            <?php while ($row = $res->fetch_assoc()): 
                                $status_badge = '';
                                switch ($row['status']) {
                                    case 'pending': $status_badge = '<span class="status-badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706;"><i class="fa-regular fa-clock"></i> Chờ xử lý</span>'; break;
                                    case 'confirmed': $status_badge = '<span class="status-badge" style="background: rgba(59, 130, 246, 0.15); color: #2563eb;"><i class="fa-solid fa-check"></i> Đã xác nhận</span>'; break;
                                    case 'shipping': $status_badge = '<span class="status-badge" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed;"><i class="fa-solid fa-truck"></i> Đang giao</span>'; break;
                                    case 'completed': $status_badge = '<span class="status-badge" style="background: rgba(16, 185, 129, 0.15); color: #059669;"><i class="fa-solid fa-box"></i> Hoàn thành</span>'; break;
                                    case 'cancelled': $status_badge = '<span class="status-badge" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;"><i class="fa-solid fa-times"></i> Đã hủy</span>'; break;
                                }
                                
                                // Render Customer Avatar
                                $avatarHtml = '';
                                if (!empty($row['avatar'])) {
                                    $avatarHtml = '<img src="' . h($row['avatar']) . '" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">';
                                } else {
                                    $initials = strtoupper(mb_substr(trim($row['customer_name']), 0, 1));
                                    if (!$initials) $initials = 'U';
                                    $avatarHtml = '<div style="width: 32px; height: 32px; border-radius: 50%; background: #0b6623; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">' . $initials . '</div>';
                                }

                                // Fetch Products
                                $items_res = db_query("SELECT p.name, p.image, oi.quantity FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", "i", [$row['id']]);
                                $products = [];
                                if ($items_res) {
                                    while ($item = $items_res->fetch_assoc()) {
                                        $products[] = [
                                            'name' => $item['name'] ? h($item['name']) : 'Sản phẩm đã xóa',
                                            'image' => !empty($item['image']) ? h($item['image']) : 'images/chem-bag.jpg',
                                            'qty' => $item['quantity']
                                        ];
                                    }
                                }
                            ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--color-admin-text-dark); vertical-align: top; padding-top: 1rem;">#<?= $row['id'] ?></td>
                                    <td style="vertical-align: top; padding-top: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <?= $avatarHtml ?>
                                            <div>
                                                <div style="font-weight: 600; color: var(--color-admin-text-dark);"><?= h($row['customer_name']) ?></div>
                                                <div style="font-size: 0.8rem; color: var(--color-admin-text-muted);"><a href="tel:<?= h($row['customer_phone']) ?>" style="color: inherit; text-decoration: none;"><?= h($row['customer_phone']) ?></a></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="vertical-align: top; padding-top: 1rem; max-width: 250px;">
                                        <?php foreach($products as $prod): ?>
                                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                <img src="../../frontend/<?= $prod['image'] ?>" alt="" style="width: 30px; height: 30px; border-radius: 4px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                                <div style="font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= $prod['name'] ?>"><?= $prod['name'] ?> (x<?= $prod['qty'] ?>)</div>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: var(--color-primary); vertical-align: top; padding-top: 1rem;"><?= number_format($row['total_price'], 0, ',', '.') ?>đ</td>
                                    <td style="vertical-align: top; padding-top: 1rem;"><?= $status_badge ?></td>
                                    <td style="color: var(--color-admin-text-muted); font-size: 0.9rem; vertical-align: top; padding-top: 1rem;"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td style="text-align: center; vertical-align: top; padding-top: 1rem;">
                                        <div class="action-buttons" style="justify-content: center;">
                                            <a href="?action=view&id=<?= $row['id'] ?>" class="btn-action view" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                                            <a href="?action=delete&id=<?= $row['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn-action delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này? Thao tác này không thể hoàn tác.')"><i class="fa-solid fa-trash-can"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="empty-state">Chưa có đơn hàng nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="admin-pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&filter_status=<?= urlencode($filter_status) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
