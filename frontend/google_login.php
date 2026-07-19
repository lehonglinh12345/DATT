<?php
// frontend/google_login.php
session_start();
require_once __DIR__ . '/../backend/google_config.php';

// Generate a random state for CSRF protection
$_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));

// Xây dựng URL xác thực
$auth_url = GOOGLE_OAUTH_URL . '?' . http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URL,
    'response_type' => 'code',
    'scope'         => GOOGLE_OAUTH_SCOPE,
    'state'         => $_SESSION['google_oauth_state'],
    'access_type'   => 'online',
    'prompt'        => 'select_account'
]);

// Chuyển hướng người dùng sang Google
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit;
