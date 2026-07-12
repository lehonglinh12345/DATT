<?php
// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Disable error reporting output to avoid corrupted JSON responses
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Include auth helper which starts session and connects to DB
    require_once __DIR__ . '/auth.php';

    // Check authentication
    if (!auth_is_logged_in()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Bạn cần đăng nhập để sử dụng tính năng này.'
        ]);
        exit;
    }

    require_once __DIR__ . '/../frontend/includes/ai_chatbot.php';

    $chatbot = new AIChatbot();

    // Parse input
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'chat';

    if ($method === 'POST') {
        if ($action === 'clear') {
            $chatbot->clearHistory();
            echo json_encode([
                'success' => true,
                'message' => 'Đã làm mới cuộc hội thoại thành công.',
                'suggestions' => [
                    '🌾 Tư vấn bón phân lúa',
                    '🦐 Khử khí độc ao tôm',
                    '🧪 Hóa chất xử lý nước',
                    '📖 Kỹ thuật trị sâu cà phê'
                ]
            ]);
            exit;
        }

        if ($action === 'new_chat') {
            $chatbot->startNewChat();
            echo json_encode([
                'success' => true,
                'message' => 'Đã bắt đầu cuộc trò chuyện mới.',
                'suggestions' => [
                    '🌾 Tư vấn bón phân lúa',
                    '🦐 Khử khí độc ao tôm',
                    '🧪 Hóa chất xử lý nước',
                    '📖 Kỹ thuật trị sâu cà phê'
                ]
            ]);
            exit;
        }

        if ($action === 'delete') {
            $targetSessionId = $_POST['session_id'] ?? '';
            if ($targetSessionId === '') {
                $rawInput = file_get_contents('php://input');
                $input = json_decode($rawInput, true);
                if (is_array($input)) {
                    $targetSessionId = $input['session_id'] ?? '';
                }
            }
            $targetSessionId = trim($targetSessionId);
            if (empty($targetSessionId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Thiếu session_id']);
                exit;
            }
            $deleted = $chatbot->deleteSession($targetSessionId);
            if ($deleted) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Không thể xóa cuộc trò chuyện']);
            }
            exit;
        }

        // Đọc tin nhắn từ POST (urlencoded) hoặc từ raw JSON input (nếu có)
        $userMessage = $_POST['message'] ?? '';
        if ($userMessage === '') {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            if (is_array($input)) {
                $userMessage = $input['message'] ?? '';
            }
        }
        $userMessage = trim($userMessage);

        if (empty($userMessage)) {
            echo json_encode([
                'success' => false,
                'error' => 'Tin nhắn không được để trống.'
            ]);
            exit;
        }

        // Handle message and retrieve reply
        $response = $chatbot->handleMessage($userMessage);

        echo json_encode([
            'success' => true,
            'reply' => $response['message'],
            'action' => $response['action'],
            'suggestions' => $response['suggestions'] ?? []
        ]);
        exit;
    } elseif ($method === 'GET') {
        if ($action === 'history') {
            // Return existing chat history in session
            $history = $chatbot->getHistory();
            $suggestions = [
                '🌾 Tư vấn bón phân lúa',
                '🦐 Khử khí độc ao tôm',
                '🧪 Hóa chất xử lý nước',
                '📖 Kỹ thuật trị sâu cà phê'
            ];
            echo json_encode([
                'success' => true,
                'history' => $history,
                'suggestions' => $suggestions
            ]);
            exit;
        }

        if ($action === 'sessions') {
            $sessions = $chatbot->getSessions();
            echo json_encode([
                'success' => true,
                'sessions' => $sessions
            ]);
            exit;
        }

        if ($action === 'load') {
            $targetSessionId = $_GET['session_id'] ?? '';
            if (empty($targetSessionId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Thiếu session_id']);
                exit;
            }
            $loaded = $chatbot->loadSession($targetSessionId);
            if ($loaded) {
                echo json_encode([
                    'success' => true,
                    'history' => $chatbot->getHistory(),
                    'suggestions' => [
                        '🌾 Tư vấn bón phân lúa',
                        '🦐 Khử khí độc ao tôm',
                        '🧪 Hóa chất xử lý nước',
                        '📖 Kỹ thuật trị sâu cà phê'
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Không thể tải cuộc hội thoại']);
            }
            exit;
        }
    }

    // Default response for unhandled requests
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Yêu cầu không hợp lệ.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi máy chủ: ' . $e->getMessage()
    ]);
}
?>
