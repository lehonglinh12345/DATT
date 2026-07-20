<?php
// frontend/profile.php
$active_page = 'profile';
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

if (!auth_is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = auth_get_user();
$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';
    
    if ($action === 'cancel_order') {
        $cancel_order_id = (int)($_POST['order_id'] ?? 0);
        $cancel_reason = trim($_POST['cancel_reason'] ?? '');
        
        if (empty($cancel_reason)) {
            $error_msg = 'Vui lòng chọn hoặc nhập lý do hủy đơn hàng.';
        } else {
            // Verify order belongs to user and is pending
            $chk_res = db_query("SELECT id FROM orders WHERE id = ? AND status = 'pending' AND (customer_email = ? OR customer_phone = ?)", "iss", [$cancel_order_id, $user['email'], $user['phone']]);
            if ($chk_res && $chk_res->num_rows > 0) {
                $cancel_update = db_query("UPDATE orders SET status = 'cancelled', cancel_reason = ? WHERE id = ?", "si", [$cancel_reason, $cancel_order_id]);
                if ($cancel_update) {
                    $success_msg = 'Đã hủy đơn hàng thành công!';
                } else {
                    $error_msg = 'Không thể hủy đơn hàng lúc này. Vui lòng thử lại sau.';
                }
            } else {
                $error_msg = 'Đơn hàng không tồn tại hoặc đã được xử lý.';
            }
        }
    } elseif ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        // If phone is empty string, set it to NULL for DB consistency
        $phone_db = $phone === '' ? null : $phone;

        if (empty($full_name)) {
            $error_msg = 'Vui lòng nhập họ và tên.';
        } elseif ($phone_db !== null && !preg_match('/^0[35789][0-9]{8}$/', $phone)) {
            $error_msg = 'Số điện thoại không hợp lệ (phải có 10 chữ số và bắt đầu bằng 0).';
        } else {
            $is_unique = true;
            if ($phone_db !== null) {
                $check_phone = db_query("SELECT id FROM users WHERE phone = ? AND id != ?", "si", [$phone_db, $user['id']]);
                if ($check_phone && $check_phone->num_rows > 0) {
                    $is_unique = false;
                    $error_msg = 'Số điện thoại này đã được đăng ký bởi một tài khoản khác.';
                }
            }

            if ($is_unique) {
                $update = db_query(
                    "UPDATE users SET full_name = ?, phone = ? WHERE id = ?",
                    "ssi",
                    [$full_name, $phone_db, $user['id']]
                );

                if ($update) {
                $success_msg = 'Cập nhật thông tin thành công!';
                $_SESSION['full_name'] = $full_name;
                $_SESSION['phone'] = $phone;
                $user = auth_get_user(); // refresh
                } else {
                    $error_msg = 'Đã có lỗi xảy ra, vui lòng thử lại.';
                }
            }
        }
    }
}

$view_order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order_detail = null;
$order_items = [];

if ($view_order_id > 0) {
    $res = db_query("SELECT * FROM orders WHERE id = ? AND (user_id = ? OR customer_email = ? OR customer_phone = ?)", "iiss", [$view_order_id, $user['id'], $user['email'], $user['phone']]);
    if ($res && $res->num_rows > 0) {
        $order_detail = $res->fetch_assoc();
        $items_res = db_query("SELECT oi.*, p.name as product_name, p.product_key, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", "i", [$view_order_id]);
        if ($items_res) {
            while ($row = $items_res->fetch_assoc()) {
                $order_items[] = $row;
            }
        }
    } else {
        $error_msg = "Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.";
    }
}

$status_colors = [
    'pending' => '#f59e0b',
    'confirmed' => '#3b82f6',
    'shipping' => '#8b5cf6',
    'completed' => '#10b981',
    'cancelled' => '#ef4444'
];
$status_labels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Chờ vận chuyển',
    'shipping' => 'Chờ nhận',
    'completed' => 'Đã nhận',
    'cancelled' => 'Đã hủy'
];
?>

<div class="page-banner" style="background-image: url('images/about-hero.jpg'); padding: 4rem 0; text-align: center; color: white;">
    <div class="container">
        <h1 style="font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Trang Cá Nhân</h1>
    </div>
</div>

<div class="container" style="padding: 4rem 0; max-width: 800px;">
    <?php if ($order_detail): ?>
        <div class="profile-card" style="background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">
                    <?php
                    $product_names = array_column($order_items, 'product_name');
                    $title = !empty($product_names) ? implode(', ', $product_names) : "Chi Tiết Đơn Hàng #" . $order_detail['id'];
                    echo h($title);
                    ?>
                </h3>
                <a href="profile.php" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <h4 style="color: #475569; font-size: 1rem; margin-bottom: 0.5rem;">Thông Tin Nhận Hàng</h4>
                    <p style="margin: 0.25rem 0; color: #1e293b;"><strong>Người nhận:</strong> <?= h($order_detail['customer_name']) ?></p>
                    <p style="margin: 0.25rem 0; color: #1e293b;"><strong>Điện thoại:</strong> <?= h($order_detail['customer_phone']) ?></p>
                    <p style="margin: 0.25rem 0; color: #1e293b;"><strong>Địa chỉ:</strong> <?= h($order_detail['customer_address']) ?></p>
                    <p style="margin: 0.25rem 0; color: #1e293b;"><strong>Ghi chú:</strong> <?= h($order_detail['notes'] ?: 'Không có') ?></p>
                </div>
                <div>
                    <h4 style="color: #475569; font-size: 1rem; margin-bottom: 0.5rem;">Thông Tin Đơn Hàng</h4>
                    <p style="margin: 0.25rem 0; color: #1e293b;"><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order_detail['created_at'])) ?></p>
                    <p style="margin: 0.25rem 0; color: #1e293b;"><strong>Trạng thái:</strong> 
                        <span style="background: <?= $status_colors[$order_detail['status']] ?>15; color: <?= $status_colors[$order_detail['status']] ?>; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                            <?= $status_labels[$order_detail['status']] ?? 'Khác' ?>
                        </span>
                    </p>
                    <p style="margin: 0.25rem 0; color: #1e293b;"><strong>Thanh toán:</strong> <?= $order_detail['payment_method'] === 'transfer' ? 'Chuyển khoản' : 'Thanh toán khi nhận hàng (COD)' ?></p>
                </div>
            </div>

            <h4 style="color: #475569; font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; margin-bottom: 1rem;">Danh Sách Sản Phẩm</h4>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 500px;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 1rem; font-weight: 600; color: #475569;">Sản Phẩm</th>
                            <th style="padding: 1rem; font-weight: 600; color: #475569;">Đơn Giá</th>
                            <th style="padding: 1rem; font-weight: 600; color: #475569; text-align: center;">SL</th>
                            <th style="padding: 1rem; font-weight: 600; color: #475569; text-align: right;">Thành Tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 1rem; color: #1e293b; display: flex; align-items: center; gap: 1rem;">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= h($item['image']) ?>" alt="<?= h($item['product_name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;">
                                    <?php endif; ?>
                                    <a href="product-detail.php?id=<?= $item['product_key'] ?>" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">
                                        <?= h($item['product_name']) ?>
                                    </a>
                                </td>
                                <td style="padding: 1rem; color: #64748b;"><?= number_format($item['price']) ?>đ</td>
                                <td style="padding: 1rem; color: #1e293b; text-align: center; font-weight: 600;"><?= $item['quantity'] ?></td>
                                <td style="padding: 1rem; font-weight: 600; color: var(--color-primary); text-align: right;">
                                    <?= number_format($item['price'] * $item['quantity']) ?>đ
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem; font-size: 1.25rem;">
                <div style="background: #f8fafc; padding: 1rem 2rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="color: #475569; margin-right: 1rem;">Tổng cộng:</span>
                    <strong style="color: var(--color-primary);"><?= number_format($order_detail['total_price']) ?>đ</strong>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="profile-card" style="background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <?php if (empty($user['phone'])): ?>
                <div style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #ffeeba;">
                    <strong><i class="fa-solid fa-triangle-exclamation"></i> Chú ý:</strong> 
                    Bạn chưa cập nhật Số điện thoại. Xin vui lòng cập nhật để Admin có thể liên hệ báo giá nhanh nhất.
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="auto-hide-alert" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; transition: opacity 0.5s ease;">
                    <?= h($error_msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <div class="auto-hide-alert" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; transition: opacity 0.5s ease;">
                    <?= h($success_msg) ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 1px solid #e2e8f0;">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= h($user['avatar']) ?>" alt="Avatar" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 1rem;">
                <?php else: ?>
                    <div style="width: 120px; height: 120px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; font-size: 3.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; border: 4px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <i class="fa-solid fa-user"></i>
                    </div>
                <?php endif; ?>
                <h3 style="margin: 0 0 0.25rem 0; color: #1e293b; font-size: 1.5rem; font-weight: 700;"><?= h($user['full_name']) ?></h3>
                <p style="margin: 0; color: #64748b;"><?= h($user['email']) ?></p>
            </div>

            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div style="margin-bottom: 1.5rem;">
                        <label for="username" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Tên Đăng Nhập</label>
                        <input type="text" id="username" value="<?= h($user['username']) ?>" disabled style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email</label>
                        <input type="email" id="email" value="<?= h($user['email']) ?>" disabled style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div style="margin-bottom: 1.5rem;">
                        <label for="full_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Họ và Tên <span style="color: red;">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="<?= h($user['full_name']) ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="phone" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Số Điện Thoại</label>
                        <input type="tel" id="phone" name="phone" value="<?= h($user['phone']) ?>" placeholder="Nhập số điện thoại của bạn..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
                    </div>
                </div>

                <button type="submit" id="btnUpdateProfile" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem; font-size: 1.1rem; border-radius: 8px; transition: all 0.3s ease;">
                    Cập Nhật Thông Tin <i class="fa-solid fa-floppy-disk" style="margin-left: 0.5rem;"></i>
                </button>
            </form>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fullNameInput = document.getElementById('full_name');
                const phoneInput = document.getElementById('phone');
                const btnUpdate = document.getElementById('btnUpdateProfile');
                const form = btnUpdate.closest('form');
                
                if (fullNameInput && phoneInput && btnUpdate) {
                    const originalFullName = fullNameInput.value.trim();
                    const originalPhone = phoneInput.value.trim();
                    
                    form.addEventListener('submit', function(e) {
                        if (fullNameInput.value.trim() === originalFullName && phoneInput.value.trim() === originalPhone) {
                            e.preventDefault();
                            alert('Bạn chưa thay đổi thông tin nào để cập nhật.');
                        }
                    });
                }
                
                // Auto hide alerts after 5 seconds
                const alerts = document.querySelectorAll('.auto-hide-alert');
                alerts.forEach(alert => {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.style.display = 'none', 500);
                    }, 5000);
                });
            });
            </script>
        </div>

        <div class="profile-card mt-4" style="background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 2rem;">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.5rem; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem;">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--color-primary); margin-right: 0.5rem;"></i> Lịch Sử Mua Hàng
            </h3>

            <?php
            $tab = $_GET['tab'] ?? 'all';
            $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
            $limit = 5;
            $offset = ($page - 1) * $limit;

            $status_condition = "";
            $params = [$user['id'], $user['email'], $user['phone']];
            $types = "iss";

            if ($tab === 'wait_pay') {
                $status_condition = " AND status = 'pending' AND payment_method = 'transfer'";
            } elseif ($tab === 'pending') {
                $status_condition = " AND status = 'pending' AND payment_method != 'transfer'";
            } elseif ($tab === 'confirmed') {
                $status_condition = " AND status = 'confirmed'";
            } elseif ($tab === 'shipping') {
                $status_condition = " AND status = 'shipping'";
            } elseif ($tab === 'completed') {
                $status_condition = " AND status = 'completed'";
            } elseif ($tab === 'cancelled') {
                $status_condition = " AND status = 'cancelled'";
            }

            $total_res = db_query("SELECT COUNT(*) AS count FROM orders WHERE (user_id = ? OR customer_email = ? OR customer_phone = ?)" . $status_condition, $types, $params);
            $total_orders = $total_res ? $total_res->fetch_assoc()['count'] : 0;
            $total_pages = ceil($total_orders / $limit);

            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";

            $orders_res = db_query(
                "SELECT * FROM orders WHERE (user_id = ? OR customer_email = ? OR customer_phone = ?)" . $status_condition . " ORDER BY created_at DESC LIMIT ? OFFSET ?",
                $types,
                $params
            );
            ?>

            <!-- Tabs Navigation -->
            <div style="display: flex; gap: 1rem; border-bottom: 2px solid #e2e8f0; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.5rem; white-space: nowrap;">
                <a href="profile.php?tab=all" style="padding: 0.5rem 1rem; text-decoration: none; font-weight: 600; color: <?= $tab === 'all' ? 'var(--color-primary)' : '#64748b' ?>; border-bottom: 3px solid <?= $tab === 'all' ? 'var(--color-primary)' : 'transparent' ?>; margin-bottom: -10px;">Tất cả</a>
                <a href="profile.php?tab=wait_pay" style="padding: 0.5rem 1rem; text-decoration: none; font-weight: 600; color: <?= $tab === 'wait_pay' ? 'var(--color-primary)' : '#64748b' ?>; border-bottom: 3px solid <?= $tab === 'wait_pay' ? 'var(--color-primary)' : 'transparent' ?>; margin-bottom: -10px;">Chờ thanh toán</a>
                <a href="profile.php?tab=pending" style="padding: 0.5rem 1rem; text-decoration: none; font-weight: 600; color: <?= $tab === 'pending' ? 'var(--color-primary)' : '#64748b' ?>; border-bottom: 3px solid <?= $tab === 'pending' ? 'var(--color-primary)' : 'transparent' ?>; margin-bottom: -10px;">Chờ xác nhận</a>
                <a href="profile.php?tab=confirmed" style="padding: 0.5rem 1rem; text-decoration: none; font-weight: 600; color: <?= $tab === 'confirmed' ? 'var(--color-primary)' : '#64748b' ?>; border-bottom: 3px solid <?= $tab === 'confirmed' ? 'var(--color-primary)' : 'transparent' ?>; margin-bottom: -10px;">Chờ vận chuyển</a>
                <a href="profile.php?tab=shipping" style="padding: 0.5rem 1rem; text-decoration: none; font-weight: 600; color: <?= $tab === 'shipping' ? 'var(--color-primary)' : '#64748b' ?>; border-bottom: 3px solid <?= $tab === 'shipping' ? 'var(--color-primary)' : 'transparent' ?>; margin-bottom: -10px;">Chờ nhận</a>
                <a href="profile.php?tab=completed" style="padding: 0.5rem 1rem; text-decoration: none; font-weight: 600; color: <?= $tab === 'completed' ? 'var(--color-primary)' : '#64748b' ?>; border-bottom: 3px solid <?= $tab === 'completed' ? 'var(--color-primary)' : 'transparent' ?>; margin-bottom: -10px;">Đã nhận</a>
                <a href="profile.php?tab=cancelled" style="padding: 0.5rem 1rem; text-decoration: none; font-weight: 600; color: <?= $tab === 'cancelled' ? 'var(--color-primary)' : '#64748b' ?>; border-bottom: 3px solid <?= $tab === 'cancelled' ? 'var(--color-primary)' : 'transparent' ?>; margin-bottom: -10px;">Đã hủy</a>
            </div>

            <?php if ($orders_res && $orders_res->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 500px;">
                        <thead>
                            <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 1rem; font-weight: 600; color: #475569;">Tên Sản Phẩm</th>
                                <th style="padding: 1rem; font-weight: 600; color: #475569; text-align: center;">Số Lượng</th>
                                <th style="padding: 1rem; font-weight: 600; color: #475569;">Ngày Đặt</th>
                                <th style="padding: 1rem; font-weight: 600; color: #475569;">Tổng Tiền</th>
                                <th style="padding: 1rem; font-weight: 600; color: #475569;">Trạng Thái</th>
                                <th style="padding: 1rem; font-weight: 600; color: #475569; text-align: center;">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders_res->fetch_assoc()): 
                                $items_res = db_query("SELECT p.name, p.image, oi.quantity FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", "i", [$order['id']]);
                                $products = [];
                                if ($items_res) {
                                    while ($item = $items_res->fetch_assoc()) {
                                        $products[] = [
                                            'name' => $item['name'] ? h($item['name']) : 'Sản phẩm đã xóa',
                                            'image' => !empty($item['image']) ? h($item['image']) : 'images/placeholder.jpg',
                                            'qty' => $item['quantity']
                                        ];
                                    }
                                }
                            ?>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 1rem; font-weight: 600; color: #1e293b; max-width: 300px; vertical-align: top;">
                                        <?php foreach($products as $prod): ?>
                                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                                                <img src="<?= $prod['image'] ?>" alt="" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                                <div style="font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= $prod['name'] ?>"><?= $prod['name'] ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; color: #64748b; vertical-align: top;">
                                        <?php foreach($products as $prod): ?>
                                            <div style="height: 40px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; font-size: 0.95rem;">x<?= $prod['qty'] ?></div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td style="padding: 1rem; color: #64748b;"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td style="padding: 1rem; font-weight: 600; color: var(--color-primary);"><?= number_format($order['total_price']) ?>đ</td>
                                    <td style="padding: 1rem;">
                                        <span style="background: <?= $status_colors[$order['status']] ?>15; color: <?= $status_colors[$order['status']] ?>; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600; display: inline-block;">
                                            <?= $status_labels[$order['status']] ?? 'Khác' ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: center;">
                                            <a href="profile.php?order_id=<?= $order['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; border-radius: 6px; width: 100px;">Xem chi tiết</a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; border-radius: 6px; color: #ef4444; border-color: #ef4444; width: 100px;" onclick="openCancelModal(<?= $order['id'] ?>)">Hủy đơn</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 2rem;">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="profile.php?tab=<?= $tab ?>&page=<?= $i ?>" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.2s; <?= $i === $page ? 'background: var(--color-primary); color: white; border-color: var(--color-primary);' : 'background: white; color: #475569;' ?>:hover { background: #f8fafc; }">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #64748b; background: #f8fafc; border-radius: 8px;">
                    <i class="fa-solid fa-box-open" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                    <p style="margin: 0;">Bạn chưa có đơn hàng nào.</p>
                    <a href="products.php" class="btn btn-outline" style="margin-top: 1rem;">Khám Phá Sản Phẩm</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Hủy Đơn Hàng -->
<div id="cancelModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem; color: #1e293b;">Lý do hủy đơn hàng</h3>
            <button type="button" onclick="closeCancelModal()" style="background: none; border: none; font-size: 1.25rem; color: #64748b; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="cancel_order">
            <input type="hidden" name="order_id" id="cancel_order_id" value="">
            <div style="padding: 1.5rem;">
                <p style="margin-top: 0; margin-bottom: 1rem; color: #64748b; font-size: 0.95rem;">Vui lòng cho chúng tôi biết lý do bạn muốn hủy đơn hàng này:</p>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="cancel_reason" value="Tôi muốn cập nhật địa chỉ/sdt nhận hàng" style="margin-top: 0.25rem;" required>
                        <span style="color: #334155;">Tôi muốn cập nhật địa chỉ/sdt nhận hàng</span>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="cancel_reason" value="Tôi muốn thay đổi sản phẩm (Thêm/Bớt/Đổi màu sắc)" style="margin-top: 0.25rem;">
                        <span style="color: #334155;">Tôi muốn thay đổi sản phẩm (Thêm/Bớt/Đổi màu sắc)</span>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="cancel_reason" value="Thủ tục thanh toán quá rắc rối" style="margin-top: 0.25rem;">
                        <span style="color: #334155;">Thủ tục thanh toán quá rắc rối</span>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="cancel_reason" value="Tôi tìm thấy chỗ khác giá tốt hơn" style="margin-top: 0.25rem;">
                        <span style="color: #334155;">Tôi tìm thấy chỗ khác giá tốt hơn</span>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="cancel_reason" value="Tôi không có nhu cầu mua nữa" style="margin-top: 0.25rem;">
                        <span style="color: #334155;">Tôi không có nhu cầu mua nữa</span>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="cancel_reason" value="Lý do khác" style="margin-top: 0.25rem;" id="radio_other_reason">
                        <span style="color: #334155;">Lý do khác</span>
                    </label>
                </div>
                
                <div id="other_reason_container" style="display: none; margin-top: 1rem;">
                    <textarea id="other_reason_text" placeholder="Nhập lý do của bạn..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; resize: vertical; min-height: 80px;"></textarea>
                </div>
            </div>
            <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-outline" onclick="closeCancelModal()" style="border-radius: 8px;">Không</button>
                <button type="submit" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444; border-radius: 8px;" onclick="prepareCancelSubmit(event)">Xác nhận Hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
    const cancelModal = document.getElementById('cancelModal');
    const radioOther = document.getElementById('radio_other_reason');
    const otherReasonContainer = document.getElementById('other_reason_container');
    const otherReasonText = document.getElementById('other_reason_text');
    const cancelRadios = document.querySelectorAll('input[name="cancel_reason"]');

    function openCancelModal(orderId) {
        document.getElementById('cancel_order_id').value = orderId;
        cancelModal.style.display = 'flex';
        // reset form
        cancelRadios.forEach(r => r.checked = false);
        otherReasonContainer.style.display = 'none';
        otherReasonText.value = '';
    }

    function closeCancelModal() {
        cancelModal.style.display = 'none';
    }

    // Handle "Other" reason toggle
    cancelRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.id === 'radio_other_reason') {
                otherReasonContainer.style.display = 'block';
                otherReasonText.focus();
            } else {
                otherReasonContainer.style.display = 'none';
            }
        });
    });

    function prepareCancelSubmit(e) {
        if (radioOther.checked) {
            if (otherReasonText.value.trim() === '') {
                e.preventDefault();
                alert('Vui lòng nhập lý do khác của bạn.');
                otherReasonText.focus();
                return false;
            }
            radioOther.value = otherReasonText.value.trim();
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
