<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['cart'])) {
    header('Location: products.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selected_items'])) {
        // Transition from cart to checkout
        $_SESSION['checkout_items'] = $_POST['selected_items'];
    } else {
        $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cod');
    
    if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
        $error = 'Vui lòng điền đầy đủ các thông tin bắt buộc (Tên, Số điện thoại, Địa chỉ).';
    } elseif (!preg_match('/^0[35789][0-9]{8}$/', $customer_phone)) {
        $error = "Số điện thoại không hợp lệ (phải có 10 chữ số và bắt đầu bằng 0).";
    } else {
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $id => $item) {
            if (isset($_SESSION['checkout_items']) && !in_array($id, $_SESSION['checkout_items'])) continue;
            $item_price = isset($item['numeric_price']) ? $item['numeric_price'] : (preg_match('/[\d\.,]+/', $item['price'], $m) ? (float)preg_replace('/[\.,]/', '', $m[0]) : 0);
            $total_amount += $item_price * $item['quantity'];
        }
        $user_id = auth_is_logged_in() ? $_SESSION['user_id'] : null;
        $res = db_query(
            "INSERT INTO orders (user_id, customer_name, customer_phone, customer_email, customer_address, total_price, payment_method, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
            "issssdss",
            [$user_id, $customer_name, $customer_phone, $customer_email, $customer_address, $total_amount, $payment_method, $notes]
        );
        
        if ($res) {
            $order_id = $database->insert_id;
            
            // Auto update user's phone if it's empty
            if ($user_id && empty($_SESSION['phone']) && !empty($customer_phone)) {
                $check_phone = db_query("SELECT id FROM users WHERE phone = ?", "s", [$customer_phone]);
                if ($check_phone && $check_phone->num_rows == 0) {
                    db_query("UPDATE users SET phone = ? WHERE id = ?", "si", [$customer_phone, $user_id]);
                    $_SESSION['phone'] = $customer_phone;
                }
            }

            $ordered_product_names = [];
            
            foreach ($_SESSION['cart'] as $id => $item) {
                if (isset($_SESSION['checkout_items']) && !in_array($id, $_SESSION['checkout_items'])) continue;
                $item_price = isset($item['numeric_price']) ? $item['numeric_price'] : (preg_match('/[\d\.,]+/', $item['price'], $m) ? (float)preg_replace('/[\.,]/', '', $m[0]) : 0);
                db_query(
                    "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)",
                    "iiid",
                    [$order_id, $item['id'], $item['quantity'], $item_price]
                );
                $ordered_product_names[] = $item['name'];
                // Remove ordered items from cart
                unset($_SESSION['cart'][$id]);
            }
            $ordered_products_str = implode(', ', $ordered_product_names);
            
            unset($_SESSION['checkout_items']);
            auth_sync_cart_to_db(); // Sync the updated cart to database
            
            $success = true;
        } else {
            $error = 'Đã có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.';
        }
    }
    }
}

$page_title = "Thanh Toán";
$active_page = 'cart';
include 'includes/head.php';
?>
<style>
.checkout-section { padding: 4rem 0; min-height: 60vh; background: #f8fafc; }
.checkout-grid { display: grid; grid-template-columns: 3fr 2fr; gap: 2rem; }
.checkout-panel { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
.checkout-panel h3 { font-size: 1.25rem; color: #1e293b; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0; }
.form-group { margin-bottom: 1.25rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; }
.form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 1rem; }
.form-control:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
.required { color: #ef4444; }
.order-summary-item { display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px dashed #e2e8f0; }
.order-summary-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.order-summary-name { flex-grow: 1; padding-right: 1rem; color: #334155; }
.order-summary-price { font-weight: 600; color: #1e293b; white-space: nowrap; }
.order-total-row { display: flex; justify-content: space-between; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #e2e8f0; font-size: 1.25rem; font-weight: 700; color: #0b6623; }
.success-box { text-align: center; padding: 3rem 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
.success-icon { font-size: 4rem; color: #10b981; margin-bottom: 1.5rem; }

@media (max-width: 768px) {
    .checkout-grid { grid-template-columns: 1fr; }
    .checkout-panel { order: 2; }
    .checkout-panel:last-child { order: 1; }
}
</style>

<?php include 'includes/header.php'; ?>

<section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/about-hero.jpg') center/cover;">
    <div class="container">
        <h1>Thanh Toán</h1>
        <div class="breadcrumbs">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <a href="cart.php">Giỏ hàng</a>
            <span>/</span>
            <span>Thanh toán</span>
        </div>
    </div>
</section>

<section class="section checkout-section">
    <div class="container">
        <?php if ($success): ?>
            <div class="success-box" style="max-width: 800px;">
                <i class="fa-solid fa-circle-check success-icon"></i>
                <h2><?= (isset($payment_method) && $payment_method === 'transfer') ? 'Chờ Thanh Toán!' : 'Đặt Hàng Thành Công!' ?></h2>
                <p style="color: #64748b; margin: 1rem 0;">Sản phẩm bạn đã đặt: <strong style="color: #334155;"><?= htmlspecialchars($ordered_products_str ?? '') ?></strong></p>
                <?php if (isset($payment_method) && $payment_method === 'transfer'): ?>
                    <p style="color: #0b6623; margin-bottom: 2rem; font-weight: 500;">Vui lòng hoàn tất thanh toán bằng cách quét mã QR dưới đây. Đơn hàng sẽ được xử lý ngay sau khi chúng tôi nhận được thanh toán.</p>
                <?php else: ?>
                    <p style="color: #64748b; margin-bottom: 2rem;">Cảm ơn bạn đã đặt hàng. Đơn hàng của bạn đã được ghi nhận và chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất.</p>
                <?php endif; ?>
                
                <?php if (isset($total_amount) && isset($order_id) && isset($payment_method) && $payment_method === 'transfer'): 
                    // Configure your bank details here
                    $bank_id = "vcb"; // e.g. vcb, mbbank, techcombank, viettinbank...
                    $account_no = "0123456789"; 
                    $account_name = "CONG TY CP NGOC ANH DUONG";
                    $add_info = "Thanh toan don hang " . $order_id;
                    $qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-compact2.png?amount={$total_amount}&addInfo=" . urlencode($add_info) . "&accountName=" . urlencode($account_name);
                ?>
                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <h3 style="color: #0b6623; margin-bottom: 1rem; font-size: 1.25rem;"><i class="fa-solid fa-qrcode"></i> Quét Mã Thanh Toán (<?= number_format($total_amount) ?>đ)</h3>
                    <div style="background: white; padding: 10px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <img src="<?= $qr_url ?>" alt="Mã QR Thanh Toán" style="max-width: 300px; height: auto;">
                    </div>
                    <div style="margin-top: 1.5rem; text-align: left; color: #475569; font-size: 0.95rem;">
                        <p><strong>Ngân hàng:</strong> Vietcombank (Ví dụ)</p>
                        <p><strong>Chủ tài khoản:</strong> CÔNG TY CP NGỌC ÁNH DƯƠNG</p>
                        <p><strong>Số tài khoản:</strong> 0123456789</p>
                        <p><strong>Nội dung:</strong> Thanh toan don hang <?= $order_id ?></p>
                    </div>
                </div>
                <?php elseif (isset($payment_method) && $payment_method === 'cod'): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; color: #166534;">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.1rem;"><i class="fa-solid fa-truck"></i> Thanh Toán Khi Nhận Hàng (COD)</h3>
                    <p style="margin: 0; font-size: 0.95rem;">Vui lòng chuẩn bị số tiền <strong><?= number_format($total_amount ?? 0) ?>đ</strong> để thanh toán cho nhân viên giao hàng.</p>
                </div>
                <?php endif; ?>
                
                <a href="products.php" class="btn btn-primary">Tiếp tục mua sắm</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger auto-hide-alert" style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; transition: opacity 0.5s ease;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form action="checkout.php" method="POST">
                <div class="checkout-grid">
                    <div class="checkout-panel">
                        <h3>Thông Tin Nhận Hàng</h3>
                        
                        <div class="form-group">
                            <label>Họ và Tên <span class="required">*</span></label>
                            <input type="text" name="customer_name" class="form-control" required value="<?= isset($_POST['customer_name']) ? h($_POST['customer_name']) : (auth_is_logged_in() ? h(auth_get_user()['full_name']) : '') ?>">
                        </div>
                        
                        <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label>Số Điện Thoại <span class="required">*</span></label>
                                <input type="tel" name="customer_phone" class="form-control" required value="<?= isset($_POST['customer_phone']) ? h($_POST['customer_phone']) : (auth_is_logged_in() ? h(auth_get_user()['phone']) : '') ?>">
                            </div>
                            <div>
                                <label>Email</label>
                                <input type="email" name="customer_email" class="form-control" value="<?= isset($_POST['customer_email']) ? h($_POST['customer_email']) : (auth_is_logged_in() ? h(auth_get_user()['email']) : '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Địa Chỉ Giao Hàng <span class="required">*</span></label>
                            <input type="text" name="customer_address" class="form-control" required value="<?= isset($_POST['customer_address']) ? h($_POST['customer_address']) : (auth_is_logged_in() ? h(auth_get_user()['address'] ?? '') : '') ?>" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố">
                        </div>
                        
                        <div class="form-group">
                            <label>Ghi Chú Đơn Hàng</label>
                            <textarea name="notes" class="form-control" rows="4" placeholder="Ghi chú về giao hàng, đóng gói..."><?= isset($_POST['notes']) ? h($_POST['notes']) : '' ?></textarea>
                        </div>
                    </div>
                    
                    <div class="checkout-panel">
                        <h3>Đơn Hàng Của Bạn</h3>
                        
                        <div class="order-summary-list">
                            <?php 
                            $total = 0;
                            foreach ($_SESSION['cart'] as $id => $item): 
                                if (isset($_SESSION['checkout_items']) && !in_array($id, $_SESSION['checkout_items'])) continue;
                                $item_price = isset($item['numeric_price']) ? $item['numeric_price'] : (preg_match('/[\d\.,]+/', $item['price'], $m) ? (float)preg_replace('/[\.,]/', '', $m[0]) : 0);
                                $item_total = $item_price * $item['quantity'];
                                $total += $item_total;
                            ?>
                                <div class="order-summary-item">
                                    <div class="order-summary-name">
                                        <?= htmlspecialchars($item['name']) ?> <strong style="color: #64748b;">x <?= $item['quantity'] ?></strong>
                                    </div>
                                    <div class="order-summary-price">
                                        <?= number_format($item_total) ?>đ
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-total-row">
                            <span>Tổng Cộng:</span>
                            <span><?= number_format($total) ?>đ</span>
                        </div>
                        
                        <div style="margin-top: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 6px; font-size: 0.95rem; color: #334155; margin-bottom: 1.5rem; border: 1px solid #e2e8f0;">
                            <h4 style="margin-bottom: 1rem; color: #1e293b; font-size: 1.1rem;"><i class="fa-solid fa-money-bill-wave" style="color: var(--color-primary);"></i> Phương Thức Thanh Toán</h4>
                            
                            <label style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; cursor: pointer; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 8px; transition: all 0.2s;" class="payment-method-label">
                                <input type="radio" name="payment_method" value="cod" required checked style="width: 18px; height: 18px; accent-color: var(--color-primary);">
                                <div style="flex-grow: 1;">
                                    <strong style="display: block; font-size: 1rem; color: #1e293b;">Thanh toán khi nhận hàng (COD)</strong>
                                    <span style="color: #64748b; font-size: 0.85rem;">Thanh toán bằng tiền mặt khi giao hàng tận nơi.</span>
                                </div>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 8px; transition: all 0.2s;" class="payment-method-label">
                                <input type="radio" name="payment_method" value="transfer" required style="width: 18px; height: 18px; accent-color: var(--color-primary);">
                                <div style="flex-grow: 1;">
                                    <strong style="display: block; font-size: 1rem; color: #1e293b;">Chuyển khoản qua ngân hàng (Quét mã QR)</strong>
                                    <span style="color: #64748b; font-size: 0.85rem;">Quét mã VietQR để thanh toán nhanh chóng và tiện lợi.</span>
                                </div>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;"><i class="fa-solid fa-check"></i> Xác Nhận Đặt Hàng</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
