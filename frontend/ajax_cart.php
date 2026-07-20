<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if (!auth_is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.', 'redirect' => 'login.php']);
        exit;
    }

    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }
    
    // Fetch product to ensure it exists and get price/name
    $res = db_query("SELECT id, product_key, name, price, image FROM products WHERE id = ?", "i", [$product_id]);
    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'product_key' => $product['product_key'],
                'name' => $product['name'],
                'price' => $product['price'],
                'numeric_price' => preg_match('/[\d\.,]+/', $product['price'], $m) ? (float)preg_replace('/[\.,]/', '', $m[0]) : 0,
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
        
        // Count total items
        $total_items = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_items += $item['quantity'];
        }
        
        auth_sync_cart_to_db(); // Sync to db
        
        echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ hàng!', 'total_items' => $total_items]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
    }
    exit;
} elseif ($action === 'update') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
        
        // Calculate new totals
        $total_items = 0;
        $total_price = 0;
        foreach ($_SESSION['cart'] as $item) {
            $item_price = isset($item['numeric_price']) ? $item['numeric_price'] : (preg_match('/[\d\.,]+/', $item['price'], $m) ? (float)preg_replace('/[\.,]/', '', $m[0]) : 0);
            $total_items += $item['quantity'];
            $total_price += $item_price * $item['quantity'];
        }
        
        $current_item_price = isset($_SESSION['cart'][$product_id]['numeric_price']) ? $_SESSION['cart'][$product_id]['numeric_price'] : (preg_match('/[\d\.,]+/', $_SESSION['cart'][$product_id]['price'] ?? '0', $m) ? (float)preg_replace('/[\.,]/', '', $m[0] ?? '0') : 0);
        
        auth_sync_cart_to_db(); // Sync to db

        echo json_encode([
            'success' => true, 
            'total_items' => $total_items,
            'item_total' => isset($_SESSION['cart'][$product_id]) ? $current_item_price * $quantity : 0,
            'cart_total' => $total_price
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng.']);
    }
    exit;
} elseif ($action === 'remove') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        
        $total_items = 0;
        $total_price = 0;
        foreach ($_SESSION['cart'] as $item) {
            $item_price = isset($item['numeric_price']) ? $item['numeric_price'] : (preg_match('/[\d\.,]+/', $item['price'], $m) ? (float)preg_replace('/[\.,]/', '', $m[0]) : 0);
            $total_items += $item['quantity'];
            $total_price += $item_price * $item['quantity'];
        }
        
        auth_sync_cart_to_db(); // Sync to db

        echo json_encode([
            'success' => true, 
            'total_items' => $total_items,
            'cart_total' => $total_price
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
