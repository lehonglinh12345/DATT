<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sync cart to DB before logging out to ensure nothing is lost
auth_sync_cart_to_db();

// Unset user authentication variables AND the cart
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['email']);
unset($_SESSION['full_name']);
unset($_SESSION['role']);
unset($_SESSION['phone']);
unset($_SESSION['avatar']);
unset($_SESSION['cart']); // Clear cart from session when logging out
unset($_SESSION['checkout_items']);

// Optional: Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Redirect to home page
header("Location: index.php");
exit;
?>
