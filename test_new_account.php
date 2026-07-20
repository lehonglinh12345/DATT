<?php
require 'backend/auth.php';

// Simulate a new account
$user_id = 999;
$username = 'testnew';
$email = 'testnew@example.com';
$fullname = 'Test New Account';

// Clear DB for test
db_query("DELETE FROM users WHERE id = ?", "i", [$user_id]);

// Insert new user
db_query(
    "INSERT INTO users (id, username, email, password, full_name, role) VALUES (?, ?, ?, 'pass', ?, 'customer')",
    "isss",
    [$user_id, $username, $email, $fullname]
);

// Login
$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;
$_SESSION['full_name'] = $fullname;
$_SESSION['role'] = 'customer';
$_SESSION['phone'] = null; // New accounts might have null

// 1. Test updating profile
$full_name = $fullname;
$phone = '0123456789';

$update = db_query(
    "UPDATE users SET full_name = ?, phone = ? WHERE id = ?",
    "ssi",
    [$full_name, $phone, $user_id]
);

echo "Update Profile Result: " . ($update ? "SUCCESS" : "FAILED") . "\n";

// Check if phone was updated in DB
$res = db_query("SELECT phone FROM users WHERE id = ?", "i", [$user_id]);
echo "Phone in DB: " . ($res->fetch_assoc()['phone']) . "\n";

// 2. Test Checkout
$customer_name = $fullname;
$customer_phone = '0123456789';
$customer_email = $email;
$customer_address = '123 Test St';
$total_amount = 100000;
$payment_method = 'cod';
$notes = '';

$res = db_query(
    "INSERT INTO orders (user_id, customer_name, customer_phone, customer_email, customer_address, total_price, payment_method, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
    "issssdss",
    [$user_id, $customer_name, $customer_phone, $customer_email, $customer_address, $total_amount, $payment_method, $notes]
);

echo "Checkout Order Result: " . ($res ? "SUCCESS" : "FAILED") . "\n";

if ($res) {
    global $database;
    echo "Inserted Order ID: " . $database->insert_id . "\n";
} else {
    global $database;
    echo "DB Error: " . $database->error . "\n";
}

// Clean up
db_query("DELETE FROM users WHERE id = ?", "i", [$user_id]);
