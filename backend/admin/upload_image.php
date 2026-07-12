<?php
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Phương thức không được phép.']]);
    exit;
}

if (!auth_csrf_verify()) {
    http_response_code(403);
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Yêu cầu không hợp lệ (Lỗi CSRF token).']]);
    exit;
}

if (!isset($_FILES['upload']) || $_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Không có hình ảnh hợp lệ được gửi lên.']]);
    exit;
}

$file = $_FILES['upload'];
$allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$max_size = 5 * 1024 * 1024; // 5MB

if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Kích thước ảnh quá lớn. Giới hạn 5MB.']]);
    exit;
}

if (!in_array($file_ext, $allowed_exts, true)) {
    http_response_code(400);
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG, WEBP, GIF.']]);
    exit;
}

$image_info = @getimagesize($file['tmp_name']);
if ($image_info === false || !in_array($image_info['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
    http_response_code(400);
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Tệp tải lên không phải là ảnh hợp lệ.']]);
    exit;
}

$upload_dir = __DIR__ . '/../../frontend/uploads/news';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        http_response_code(500);
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Không thể tạo thư mục lưu trữ ảnh.']]);
        exit;
    }
}

$safe_file_name = 'news_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
$target_path = $upload_dir . '/' . $safe_file_name;

if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    http_response_code(500);
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Không thể lưu ảnh lên máy chủ.']]);
    exit;
}

$public_url = 'uploads/news/' . $safe_file_name;
echo json_encode(['uploaded' => 1, 'url' => $public_url]);
