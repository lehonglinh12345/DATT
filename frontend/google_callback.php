<?php
// frontend/google_callback.php
require_once __DIR__ . '/../backend/auth.php'; // Includes db.php and session_start()
require_once __DIR__ . '/../backend/google_config.php';

// Kiểm tra lỗi nếu người dùng từ chối cấp quyền
if (isset($_GET['error'])) {
    die("Lỗi xác thực Google: " . htmlspecialchars($_GET['error']));
}

// Kiểm tra trạng thái CSRF
if (empty($_GET['state']) || empty($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    die("Lỗi trạng thái bảo mật CSRF. Vui lòng thử lại.");
}

// Nếu có mã xác thực trả về
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // 1. Đổi code lấy access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URL,
        'grant_type'    => 'authorization_code',
        'code'          => $code
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    $token_data = json_decode($token_response, true);
    curl_close($ch);

    if (isset($token_data['error'])) {
        die("Lỗi lấy Access Token: " . (isset($token_data['error_description']) ? $token_data['error_description'] : $token_data['error']));
    }

    $access_token = $token_data['access_token'];

    // 2. Lấy thông tin người dùng từ Google bằng access token
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, GOOGLE_USERINFO_URL);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    $userinfo_response = curl_exec($ch2);
    $user_data = json_decode($userinfo_response, true);
    curl_close($ch2);

    if (isset($user_data['error'])) {
        die("Lỗi lấy thông tin người dùng: " . $user_data['error']['message']);
    }

    $google_email = $user_data['email'];
    $google_name = $user_data['name'] ?? 'Người dùng Google';
    $google_picture = $user_data['picture'] ?? null;

    // 3. Kiểm tra người dùng trong CSDL
    $stmt = db_query("SELECT * FROM users WHERE email = ?", "s", [$google_email]);

    if ($stmt && $stmt->num_rows > 0) {
        // Đã tồn tại -> Lấy thông tin và Đăng nhập
        $user = $stmt->fetch_assoc();
        // Cập nhật avatar nếu chưa có
        if (empty($user['avatar']) && $google_picture) {
            db_query("UPDATE users SET avatar = ? WHERE id = ?", "si", [$google_picture, $user['id']]);
            $user['avatar'] = $google_picture;
        }
    } else {
        // Chưa tồn tại -> Đăng ký tự động
        // Generate a random username base on email prefix
        $username_base = explode('@', $google_email)[0];
        $username = $username_base;
        $counter = 1;
        
        // Ensure unique username
        while (db_query("SELECT id FROM users WHERE username = ?", "s", [$username])->num_rows > 0) {
            $username = $username_base . $counter;
            $counter++;
        }

        // Random password for Google users (they won't need it, but the DB requires it)
        $random_password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

        $insert = db_query(
            "INSERT INTO users (username, email, password, full_name, avatar, role) VALUES (?, ?, ?, ?, ?, 'customer')",
            "sssss",
            [$username, $google_email, $hashed_password, $google_name, $google_picture]
        );

        if ($insert) {
            // Lấy lại user vừa thêm
            $stmt = db_query("SELECT * FROM users WHERE email = ?", "s", [$google_email]);
            $user = $stmt->fetch_assoc();
        } else {
            die("Lỗi khi tạo tài khoản mới từ Google.");
        }
    }

    // 4. Thiết lập Session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['avatar'] = $user['avatar'] ?? null;

    // Restore cart from DB or save current guest cart to DB
    auth_restore_cart_from_db($user['id']);

    // 5. Chuyển hướng về trang chủ hoặc trang mong muốn
    header('Location: index.php');
    exit;

} else {
    // Không có code thì quay về login
    header('Location: login.php');
    exit;
}
