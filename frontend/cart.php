<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';

// Require login to view cart
if (!auth_is_logged_in()) {
    header("Location: login.php?redirect=" . urlencode('cart.php'));
    exit;
}

$page_title = "Giỏ Hàng Của Bạn";
$active_page = 'cart';
include 'includes/head.php';
?>
<link rel="stylesheet" href="css/products.css">
<style>
.cart-section { padding: 4rem 0; min-height: 60vh; }
.cart-container { max-width: 1000px; margin: 0 auto; }
.cart-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
.cart-table th { background: #f8fafc; padding: 1rem; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0; }
.cart-table td { padding: 1rem; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
.cart-item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
.cart-item-name { font-weight: 600; color: #1e293b; text-decoration: none; }
.cart-item-price { color: #0b6623; font-weight: 600; }
.qty-control { display: flex; align-items: center; border: 1px solid #e2e8f0; border-radius: 6px; width: fit-content; overflow: hidden; }
.qty-control button { width: 30px; height: 30px; background: #f8fafc; border: none; cursor: pointer; color: #64748b; font-weight: bold; }
.qty-control button:hover { background: #e2e8f0; }
.qty-control input { width: 40px; height: 30px; text-align: center; border: none; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; font-weight: 600; }
.btn-remove { color: #ef4444; background: none; border: none; cursor: pointer; padding: 0.5rem; transition: 0.2s; }
.btn-remove:hover { color: #dc2626; transform: scale(1.1); }
.cart-summary { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-top: 2rem; text-align: right; }
.cart-summary-row { display: flex; justify-content: flex-end; gap: 2rem; margin-bottom: 1rem; font-size: 1.1rem; }
.cart-summary-total { display: flex; justify-content: flex-end; gap: 2rem; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 700; color: #0b6623; }
.cart-empty { text-align: center; padding: 4rem 0; }
.cart-empty i { font-size: 4rem; color: #cbd5e1; margin-bottom: 1rem; }
.cart-empty h2 { color: #475569; margin-bottom: 1rem; }

@media (max-width: 768px) {
    .cart-table th { display: none; }
    .cart-table td { display: block; text-align: right; border-bottom: none; }
    .cart-table td::before { content: attr(data-label); float: left; font-weight: 600; color: #64748b; }
    .cart-table tr { display: block; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1rem; }
    .cart-table td:first-child { display: flex; align-items: center; justify-content: flex-end; gap: 1rem; }
    .cart-table td:first-child::before { display: none; }
    .qty-control { margin-left: auto; }
}
</style>

<?php include 'includes/header.php'; ?>

<section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/about-hero.jpg') center/cover;">
    <div class="container">
        <h1>Giỏ Hàng</h1>
        <div class="breadcrumbs">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <span>Giỏ hàng</span>
        </div>
    </div>
</section>

<section class="section cart-section">
    <div class="container cart-container">
        <?php if (!empty($_SESSION['cart'])): ?>
            <form action="checkout.php" method="POST" id="cart-form">
            <table class="cart-table" id="cartTable">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="check-all" checked style="width: 18px; height: 18px; accent-color: var(--color-primary); cursor: pointer;"></th>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    foreach ($_SESSION['cart'] as $item): 
                        $item_price = isset($item['numeric_price']) ? $item['numeric_price'] : (preg_match('/[\d\.,]+/', $item['price'], $m) ? (float)preg_replace('/[\.,]/', '', $m[0]) : 0);
                        $item_total = $item_price * $item['quantity'];
                        $total += $item_total;
                    ?>
                        <tr data-id="<?= $item['id'] ?>" class="cart-row">
                            <td style="text-align: center;">
                                <input type="checkbox" name="selected_items[]" value="<?= $item['id'] ?>" class="item-checkbox" checked style="width: 18px; height: 18px; accent-color: var(--color-primary); cursor: pointer;">
                            </td>
                            <td data-label="Sản phẩm" style="display: flex; align-items: center; gap: 1rem; text-align: left;">
                                <img src="<?= htmlspecialchars($item['image'] ?: 'images/placeholder.jpg') ?>" class="cart-item-img" alt="IMG">
                                <a href="product-detail.php?id=<?= htmlspecialchars($item['product_key'] ?? $item['id']) ?>" class="cart-item-name"><?= htmlspecialchars($item['name']) ?></a>
                            </td>
                            <td data-label="Đơn giá" class="cart-item-price"><?= number_format($item_price) ?>đ</td>
                            <td data-label="Số lượng">
                                <div class="qty-control">
                                    <button type="button" class="btn-qty-minus">-</button>
                                    <input type="number" value="<?= $item['quantity'] ?>" min="1" class="qty-input">
                                    <button type="button" class="btn-qty-plus">+</button>
                                </div>
                            </td>
                            <td data-label="Thành tiền" class="item-total-price" style="font-weight: 600;"><?= number_format($item_total) ?>đ</td>
                            <td data-label="Xóa" style="text-align: center;">
                                <button type="button" class="btn-remove" title="Xóa sản phẩm"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <div class="cart-summary-total">
                    <span>Tổng cộng:</span>
                    <span id="cartTotalDisplay"><?= number_format($total) ?>đ</span>
                </div>
                <div>
                    <a href="products.php" class="btn btn-outline" style="border-color: var(--color-primary); color: var(--color-primary); margin-right: 10px;">Tiếp tục mua hàng</a>
                    <button type="submit" class="btn btn-primary" id="btn-checkout" style="padding: 12px 30px; font-size: 1.1rem; cursor: pointer;"><i class="fa-solid fa-credit-card"></i> Thanh Toán</button>
                </div>
            </div>
            </form>
            
        <?php else: ?>
            <div class="cart-empty">
                <i class="fa-solid fa-cart-shopping"></i>
                <h2>Giỏ hàng của bạn đang trống</h2>
                <p style="color: #64748b; margin-bottom: 2rem;">Hãy tham khảo các sản phẩm chất lượng của chúng tôi.</p>
                <a href="products.php" class="btn btn-primary">Mua sắm ngay</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartTable = document.getElementById('cartTable');
    if (!cartTable) return;
    
    function updateCart(productId, quantity, row) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        fetch('ajax_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update badge
                const badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.textContent = data.total_items;
                    if (data.total_items === 0) badge.style.display = 'none';
                }
                
                if (quantity === 0) {
                    row.remove();
                    if (data.total_items === 0) {
                        location.reload(); // Reload to show empty cart message
                    }
                } else {
                    row.querySelector('.item-total-price').textContent = new Intl.NumberFormat('vi-VN').format(data.item_total) + 'đ';
                    row.dataset.price = data.item_total;
                }
                recalculateTotal();
            }
        });
    }

    cartTable.addEventListener('click', function(e) {
        if (e.target.closest('.btn-qty-minus')) {
            const row = e.target.closest('tr');
            const input = row.querySelector('.qty-input');
            let val = parseInt(input.value);
            if (val > 1) {
                val--;
                input.value = val;
                updateCart(row.dataset.id, val, row);
            }
        }
        else if (e.target.closest('.btn-qty-plus')) {
            const row = e.target.closest('tr');
            const input = row.querySelector('.qty-input');
            let val = parseInt(input.value);
            val++;
            input.value = val;
            updateCart(row.dataset.id, val, row);
        }
        else if (e.target.closest('.btn-remove')) {
            if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
                const row = e.target.closest('tr');
                updateCart(row.dataset.id, 0, row);
            }
        }
    });

    cartTable.addEventListener('change', function(e) {
        if (e.target.classList.contains('qty-input')) {
            const row = e.target.closest('tr');
            let val = parseInt(e.target.value);
            if (isNaN(val) || val < 1) {
                val = 1;
                e.target.value = 1;
            }
            updateCart(row.dataset.id, val, row);
        }
    });

    const checkAll = document.getElementById('check-all');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const btnCheckout = document.getElementById('btn-checkout');

    function recalculateTotal() {
        let total = 0;
        let checkedCount = 0;
        document.querySelectorAll('.cart-row').forEach(row => {
            const cb = row.querySelector('.item-checkbox');
            if (cb && cb.checked) {
                checkedCount++;
                const priceText = row.querySelector('.item-total-price').textContent.replace(/[^\d]/g, '');
                total += parseInt(priceText) || 0;
            }
        });
        document.getElementById('cartTotalDisplay').textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
        
        if (checkAll) {
            checkAll.checked = (checkedCount === itemCheckboxes.length && itemCheckboxes.length > 0);
        }
        if (btnCheckout) {
            btnCheckout.disabled = checkedCount === 0;
            btnCheckout.style.opacity = checkedCount === 0 ? '0.5' : '1';
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function(e) {
            itemCheckboxes.forEach(cb => cb.checked = e.target.checked);
            recalculateTotal();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', recalculateTotal);
    });

    // Initialize total parsing data attributes
    document.querySelectorAll('.cart-row').forEach(row => {
        const priceText = row.querySelector('.item-total-price').textContent.replace(/[^\d]/g, '');
        row.dataset.price = priceText;
    });
    recalculateTotal();
});
</script>

<?php include 'includes/footer.php'; ?>
