<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$action = $_REQUEST['action'] ?? '';

// Helpers
function jsonResponse($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if ($action === 'get') {
    $article_slug = $_GET['article_slug'] ?? '';
    if (!$article_slug) jsonResponse(false, 'Missing article slug');

    $user_id = $_SESSION['user_id'] ?? 0;

    // Fetch all comments for the article
    $query = "SELECT c.*, u.full_name, u.username, 
              (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) as likes_count,
              (SELECT COUNT(*) FROM comment_likes cl2 WHERE cl2.comment_id = c.id AND cl2.user_id = ?) as user_liked
              FROM comments c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.article_slug = ? 
              ORDER BY c.created_at ASC";
              
    $result = db_query($query, "is", [$user_id, $article_slug]);
    
    $comments = [];
    $allComments = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['user_liked'] = (bool)$row['user_liked'];
            $allComments[$row['id']] = $row;
        }
    }

    // Build tree
    foreach ($allComments as $id => &$comment) {
        $comment['replies'] = [];
        if (empty($comment['parent_id'])) {
            $comments[$id] = &$comment;
        } else {
            if (isset($allComments[$comment['parent_id']])) {
                $allComments[$comment['parent_id']]['replies'][] = &$comment;
            }
        }
    }

    // Convert associative array to indexed array to maintain clean JSON
    $commentsIndexed = array_values($comments);
    
    jsonResponse(true, 'Success', $commentsIndexed);
}

// All other actions require login
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Bạn phải đăng nhập để thực hiện chức năng này');
}
$user_id = (int)$_SESSION['user_id'];

if ($action === 'add') {
    $article_slug = $_POST['article_slug'] ?? '';
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $content = trim($_POST['content'] ?? '');

    if (!$article_slug || !$content) {
        jsonResponse(false, 'Nội dung không hợp lệ');
    }

    $insert = db_query(
        "INSERT INTO comments (article_slug, user_id, parent_id, content) VALUES (?, ?, ?, ?)",
        "siis",
        [$article_slug, $user_id, $parent_id, $content]
    );

    if ($insert) {
        jsonResponse(true, 'Đã gửi bình luận thành công');
    } else {
        global $database;
        jsonResponse(false, 'Lỗi cơ sở dữ liệu: ' . $database->error);
    }
}

if ($action === 'edit') {
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if (!$comment_id || !$content) jsonResponse(false, 'Nội dung không hợp lệ');

    // Check ownership
    $check = db_query("SELECT id FROM comments WHERE id = ? AND user_id = ?", "ii", [$comment_id, $user_id]);
    if ($check->num_rows === 0) jsonResponse(false, 'Bạn không có quyền sửa bình luận này');

    $update = db_query("UPDATE comments SET content = ? WHERE id = ?", "si", [$content, $comment_id]);
    if ($update) jsonResponse(true, 'Sửa thành công');
    else jsonResponse(false, 'Đã xảy ra lỗi');
}

if ($action === 'delete') {
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    if (!$comment_id) jsonResponse(false, 'ID không hợp lệ');

    // Check ownership
    $check = db_query("SELECT id FROM comments WHERE id = ? AND user_id = ?", "ii", [$comment_id, $user_id]);
    if ($check->num_rows === 0) jsonResponse(false, 'Bạn không có quyền xóa bình luận này');

    // Deleting the comment will also delete replies
    db_query("DELETE FROM comments WHERE parent_id = ?", "i", [$comment_id]);
    $delete = db_query("DELETE FROM comments WHERE id = ?", "i", [$comment_id]);

    if ($delete) jsonResponse(true, 'Đã xóa bình luận');
    else jsonResponse(false, 'Đã xảy ra lỗi');
}

if ($action === 'toggle_like') {
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    if (!$comment_id) jsonResponse(false, 'ID không hợp lệ');

    $check = db_query("SELECT * FROM comment_likes WHERE user_id = ? AND comment_id = ?", "ii", [$user_id, $comment_id]);
    if ($check->num_rows > 0) {
        // Unlike
        db_query("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?", "ii", [$user_id, $comment_id]);
        jsonResponse(true, 'Unliked', ['liked' => false]);
    } else {
        // Like
        db_query("INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)", "ii", [$user_id, $comment_id]);
        jsonResponse(true, 'Liked', ['liked' => true]);
    }
}

jsonResponse(false, 'Action not found');
