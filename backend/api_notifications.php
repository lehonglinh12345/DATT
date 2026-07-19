<?php
// backend/api_notifications.php
require_once __DIR__ . '/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        // Fetch notifications for the user
        $stmt = db_query(
            "SELECT n.id, n.title, n.message, n.link, n.created_at, un.is_read 
             FROM notifications n
             JOIN user_notifications un ON n.id = un.notification_id
             WHERE un.user_id = ?
             ORDER BY n.created_at DESC
             LIMIT 50",
            "i",
            [$user_id]
        );
        $notifications = [];
        $unread_count = 0;
        if ($stmt) {
            while ($row = $stmt->fetch_assoc()) {
                $notifications[] = $row;
                if ($row['is_read'] == 0) {
                    $unread_count++;
                }
            }
        }
        echo json_encode([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]
        ]);
        break;

    case 'mark_read':
        $notif_id = (int)($_POST['notification_id'] ?? 0);
        if ($notif_id > 0) {
            $update = db_query(
                "UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND notification_id = ?",
                "ii",
                [$user_id, $notif_id]
            );
            if ($update) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi đánh dấu đã đọc']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        }
        break;

    case 'mark_all_read':
        $update = db_query(
            "UPDATE user_notifications SET is_read = 1 WHERE user_id = ?",
            "i",
            [$user_id]
        );
        if ($update) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi đánh dấu đã đọc']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
