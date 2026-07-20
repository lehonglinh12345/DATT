<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../backend/db.php';

class AIChatbot {
    // Cấu hình API Key của Gemini. Có thể định nghĩa ở đây hoặc lấy từ biến môi trường.
    const GEMINI_API_KEY = ''; 
    const MODEL_NAME = 'gemini-2.5-flash';

    private $db;
    private $sessionId;

    public function __construct() {
        global $database;
        $this->db = $database;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Tự động kiểm tra và tạo bảng
        $this->ensureTableExists();

        // Tự động nạp session gần đây nhất của user nếu chưa có session active
        if (!isset($_SESSION['active_chat_session_id'])) {
            $this->loadLatestSessionIfExists();
            if (!isset($_SESSION['active_chat_session_id'])) {
                $_SESSION['active_chat_session_id'] = uniqid('chat_', true);
            }
        }
        $this->sessionId = $_SESSION['active_chat_session_id'];

        // Khởi tạo các trạng thái session
        if (!isset($_SESSION['ai_chat_state'])) {
            $_SESSION['ai_chat_state'] = 'idle';
        }
        if (!isset($_SESSION['ai_quote_data'])) {
            $_SESSION['ai_quote_data'] = [];
        }
        if (!isset($_SESSION['ai_contact_data'])) {
            $_SESSION['ai_contact_data'] = [];
        }
        if (!isset($_SESSION['ai_context_topic'])) {
            $_SESSION['ai_context_topic'] = '';
        }
        if (!isset($_SESSION['ai_last_product_id'])) {
            $_SESSION['ai_last_product_id'] = null;
        }
        if (!isset($_SESSION['ai_history'])) {
            $_SESSION['ai_history'] = [];
            $this->loadSession($this->sessionId);
        }
    }

    private function loadLatestSessionIfExists() {
        if (!auth_is_logged_in()) return;
        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) return;

        $res = db_query("SELECT session_id FROM chat_sessions WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1", "i", [$userId]);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $_SESSION['active_chat_session_id'] = $row['session_id'];
        }
    }

    private function ensureTableExists() {
        try {
            // Kiểm tra bảng chat_conversations
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'chat_conversations'");
            if ($tableCheck && $tableCheck->num_rows === 0) {
                $sql = "CREATE TABLE IF NOT EXISTS `chat_conversations` (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `session_id` VARCHAR(150) NOT NULL,
                  `role` ENUM('user', 'assistant') NOT NULL,
                  `message` TEXT NOT NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_chat_conversations_session_id` (`session_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                $this->db->query($sql);
            }

            // Kiểm tra bảng chat_sessions
            $sessionTableCheck = $this->db->query("SHOW TABLES LIKE 'chat_sessions'");
            if ($sessionTableCheck && $sessionTableCheck->num_rows === 0) {
                $sql = "CREATE TABLE IF NOT EXISTS `chat_sessions` (
                  `session_id` VARCHAR(150) NOT NULL,
                  `user_id` INT UNSIGNED NOT NULL,
                  `title` VARCHAR(255) NOT NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`session_id`),
                  KEY `idx_chat_sessions_user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                $this->db->query($sql);
            }
        } catch (Exception $e) {
            // Bỏ qua
        }
    }

    public function handleMessage($message) {
        $message = trim($message);
        if ($message === '') {
            return [
                'message' => 'Xin chào! Bạn muốn tôi tư vấn về sản phẩm hay kỹ thuật nông nghiệp nào?',
                'role' => 'assistant',
                'suggestions' => $this->getDefaultSuggestions()
            ];
        }

        // 1. Lưu tin nhắn người dùng
        $this->saveMessage('user', $message);

        $reply = '';
        $actionTaken = 'none';
        $suggestions = [];

        // 2. Kiểm tra State Machine (Quote & Contact)
        $stateResult = $this->processStateMachine($message);
        if ($stateResult !== null) {
            $reply = $stateResult['message'];
            $actionTaken = $stateResult['action'];
            $suggestions = $stateResult['suggestions'] ?? [];
        } else {
            // 3. Chạy Gemini LLM nếu có API Key
            $apiKey = self::GEMINI_API_KEY ?: (getenv('GEMINI_API_KEY') ?: '');
            if (!empty($apiKey)) {
                $replyData = $this->callGeminiAPI($message, $apiKey);
                if ($replyData && isset($replyData['message'])) {
                    $reply = $replyData['message'];
                    
                    // Ghi nhớ sản phẩm được nhắc tới bởi Gemini (nếu có hành động báo giá)
                    if (isset($replyData['action_data']['product']) && !empty($replyData['action_data']['product'])) {
                        $this->rememberProductByName($replyData['action_data']['product']);
                    }

                    if (isset($replyData['action'])) {
                        $action = $replyData['action'];
                        $actionData = $replyData['action_data'] ?? [];

                        if ($action === 'create_quote') {
                            $prodName = $actionData['product'] ?? '';
                            $reply .= "\n\n👉 Để đặt mua trực tiếp **{$prodName}**, anh/chị vui lòng gọi ngay Hotline **0976.828.171** hoặc nhắn tin qua Zalo để được hỗ trợ chốt đơn nhanh nhất ạ!";
                            $actionTaken = 'contact_success';
                        } elseif ($action === 'create_contact') {
                            if (auth_is_logged_in()) {
                                $curr_user = auth_get_user();
                                $name = $curr_user['full_name'] ?: $curr_user['username'];
                                $phone = $curr_user['phone'];
                                $email = $curr_user['email'];

                                $saved = db_query(
                                    "INSERT INTO contact_messages (name, phone, email, subject, message) VALUES (?, ?, ?, ?, ?)",
                                    "sssss",
                                    ['Khách hàng ' . $name . ' từ AI Chatbox', $phone ?: 'Không rõ', $email ?: 'Không rõ', 'Yêu cầu liên hệ từ AI Chatbox', 'Khách hàng yêu cầu hỗ trợ trực tiếp. Tin nhắn: ' . $message]
                                );

                                if ($saved) {
                                    $reply .= "\n\n✅ **Yêu cầu hỗ trợ trực tiếp đã được gửi thành công!**\n\nEm đã chuyển thông tin tài khoản đăng nhập của anh/chị (**{$name}** - SĐT: " . ($phone ?: 'Chưa cập nhật') . " - Email: {$email}) cho bộ phận kinh doanh và kỹ thuật hỗ trợ trực tiếp. Bộ phận hỗ trợ sẽ liên hệ với anh/chị sớm nhất ạ!";
                                    $actionTaken = 'contact_success';
                                } else {
                                    $reply .= "\n\nHệ thống bận, quý khách vui lòng gọi Hotline **0976.828.171** để được các kỹ sư hỗ trợ trực tiếp ngay lập tức ạ.";
                                    $actionTaken = 'contact_error';
                                }
                            } else {
                                $_SESSION['ai_chat_state'] = 'waiting_for_contact_info';
                                $reply .= "\n\n👉 *Bạn vui lòng nhập Tên và Số điện thoại hoặc Email hỗ trợ bên dưới để em gửi thông tin cho kỹ sư nhé.*";
                                $actionTaken = 'start_contact';
                            }
                        }
                    }
                }
            }

            // 4. Chạy bộ xử lý Local NLP thông minh nếu chưa có câu trả lời
            if (empty($reply)) {
                $localResult = $this->processLocalNLP($message);
                $reply = $localResult['message'];
                $actionTaken = $localResult['action'];
                $suggestions = $localResult['suggestions'] ?? [];
            }
        }

        // 5. Lưu phản hồi AI
        $this->saveMessage('assistant', $reply);

        return [
            'message' => $reply,
            'role' => 'assistant',
            'action' => $actionTaken,
            'suggestions' => $suggestions
        ];
    }

    /**
     * Ghi nhớ ID sản phẩm dựa trên tên
     */
    private function rememberProductByName($name) {
        $res = db_query("SELECT id FROM products WHERE name LIKE ? LIMIT 1", "s", ["%" . $name . "%"]);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $_SESSION['ai_last_product_id'] = $row['id'];
        }
    }

    private function processStateMachine($message) {
        $state = $_SESSION['ai_chat_state'];


        if ($state === 'waiting_for_contact_info') {
            $contactInfo = $message;
            $phone = '';
            $email = '';
            
            preg_match('/[0-9]{9,11}/', $contactInfo, $phoneMatches);
            if (!empty($phoneMatches)) {
                $phone = $phoneMatches[0];
            }
            
            preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $contactInfo, $emailMatches);
            if (!empty($emailMatches)) {
                $email = $emailMatches[0];
            }

            $saved = db_query(
                "INSERT INTO contact_messages (name, phone, email, subject, message) VALUES (?, ?, ?, ?, ?)",
                "sssss",
                ['Khách hàng từ AI Chatbox', $phone ?: 'Không rõ', $email ?: 'Không rõ', 'Yêu cầu liên hệ từ AI Chatbox', $contactInfo]
            );

            $_SESSION['ai_chat_state'] = 'idle';

            if ($saved) {
                return [
                    'message' => "✅ **Thông tin của bạn đã được chuyển tiếp thành công!**\n\nNhân viên của chúng tôi sẽ gọi điện hoặc email hỗ trợ bạn trong thời gian sớm nhất. Cảm ơn bạn đã quan tâm đến sản phẩm của Ngọc Ánh Dương!",
                    'action' => 'contact_success',
                    'suggestions' => $this->getDefaultSuggestions()
                ];
            } else {
                return [
                    'message' => "Dạ, em đã lưu thông tin liên hệ. Bạn có thể nhấn trực tiếp vào liên kết Zalo hoặc gọi **0976.828.171** để được các kỹ sư hỗ trợ kỹ thuật trực tiếp ngay lập tức ạ.",
                    'action' => 'contact_error',
                    'suggestions' => ['Gọi ngay 0976.828.171', 'Trang chủ']
                ];
            }
        }

        return null;
    }

    private function processLocalNLP($message) {
        $msgLower = mb_strtolower($message, 'UTF-8');
        
        // --- 1. TÌM KIẾM ĐỘC LẬP THEO NGỮ CẢNH HỎI ĐÁP (Follow-up) ---
        // Nếu câu hỏi ngắn chứa các từ hỏi thông tin về sản phẩm trước đó (xuất xứ, giá, công dụng...)
        if (!empty($_SESSION['ai_last_product_id']) && preg_match('/(ở đâu|xuất xứ|nước nào|giá|bao nhiêu|tiền|tác dụng|công dụng|chức năng|dùng để làm gì|dùng thế nào|hướng dẫn)/u', $msgLower)) {
            $lastProdId = $_SESSION['ai_last_product_id'];
            $pRes = db_query("SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?", "i", [$lastProdId]);
            if ($pRes && $pRes->num_rows > 0) {
                $prod = $pRes->fetch_assoc();
                
                // Trả lời về xuất xứ
                if (preg_match('/(ở đâu|xuất xứ|nước nào|nhập khẩu)/u', $msgLower)) {
                    $origin = $prod['origin'] ?: 'Đang cập nhật';
                    return [
                        'message' => "Dạ, sản phẩm **{$prod['name']}** có xuất xứ từ: **{$origin}** ạ.\n\nSản phẩm được Ngọc Ánh Dương nhập khẩu chính ngạch, có đầy đủ hóa đơn chứng từ kiểm định.",
                        'action' => 'product_info_origin',
                        'suggestions' => ["Báo giá {$prod['name']}", "Công dụng sản phẩm này", "Sản phẩm khác"]
                    ];
                }
                
                // Trả lời về giá cả
                if (preg_match('/(giá|bao nhiêu|tiền|chi phí)/u', $msgLower)) {
                    $price = $prod['price'] ?: 'Liên hệ báo giá sỉ';
                    $link = "product-detail.php?id=" . urlencode($prod['product_key']);
                    return [
                        'message' => "Dạ, sản phẩm **[{$prod['name']}]({$link})** hiện đang có giá: **{$price}**.\n\nNếu anh/chị có nhu cầu mua số lượng lớn làm đại lý, hãy để lại thông tin để nhận chính sách chiết khấu tốt nhất nhé!",
                        'action' => 'product_info_price',
                        'suggestions' => ["Mua {$prod['name']}", "Công dụng sản phẩm này", "Trang chủ"]
                    ];
                }
                
                // Trả lời về tác dụng/công dụng
                if (preg_match('/(tác dụng|công dụng|chức năng|làm gì|hiệu quả|sử dụng)/u', $msgLower)) {
                    $desc = $prod['description'] ?: 'Chi tiết công dụng chưa được mô tả.';
                    return [
                        'message' => "Dạ, công dụng chính của **{$prod['name']}** là:\n\n👉_{$desc}_\n\nBạn có muốn xem hướng dẫn chi tiết hoặc nhận bảng giá của sản phẩm này không ạ?",
                        'action' => 'product_info_desc',
                        'suggestions' => ["Mua {$prod['name']}", "Xuất xứ ở đâu", "Tư vấn cây trồng"]
                    ];
                }
            }
        }

        // --- 2. NHẬN DIỆN Ý ĐỊNH MUA HÀNG / BÁO GIÁ ---
        if (preg_match('/(mua|báo giá|đặt hàng|lấy thêm|cần|lấy)/u', $msgLower)) {
            $matchedProduct = null;
            $allProducts = [];
            $pRes = db_query("SELECT id, name, product_key FROM products");
            if ($pRes && $pRes->num_rows > 0) {
                while ($pRow = $pRes->fetch_assoc()) {
                    $allProducts[] = $pRow;
                }
            }

            foreach ($allProducts as $prod) {
                $cleanProdName = preg_replace('/(phân bón gốc|phân bón lá|chế phẩm vi sinh, sinh học|phòng trừ côn trùng, ốc hại|phòng trừ nấm hại|chế phẩm sinh học vi sinh|chế phẩm sinh học|chế phẩm vi sinh|hóa chất công nghiệp)/ui', '', $prod['name']);
                $cleanProdLower = trim(mb_strtolower($cleanProdName, 'UTF-8'));
                $prodFullNameLower = mb_strtolower($prod['name'], 'UTF-8');

                if (strpos($msgLower, $cleanProdLower) !== false || strpos($msgLower, $prodFullNameLower) !== false) {
                    $matchedProduct = $prod;
                    break;
                }
            }

            if ($matchedProduct) {
                $_SESSION['ai_last_product_id'] = $matchedProduct['id']; // Ghi nhớ sản phẩm vừa nhắc tới
                
                $price = $matchedProduct['price'] ?: 'Liên hệ';
                
                return [
                    'message' => "Dạ, sản phẩm **{$matchedProduct['name']}** hiện đang có giá: **{$price}**.\n\nĐể đặt mua trực tiếp hoặc cần hỗ trợ sỉ số lượng lớn, anh/chị vui lòng nhắn tin qua [Zalo: 0976.828.171](https://zalo.me/0976828171) hoặc gọi ngay Hotline **0976.828.171** để được hỗ trợ chốt đơn nhanh nhất ạ!",
                    'action' => 'contact_success',
                    'suggestions' => ['Gọi điện 0976.828.171', 'Xem sản phẩm khác']
                ];
            }
        }

        // --- 3. Ý ĐỊNH CHUYỂN TIẾP NHÂN VIÊN ---
        if (preg_match('/(nhân viên|gặp người|hỗ trợ trực tiếp|gặp tổng đài|gọi điện|để lại số điện thoại|liên hệ|hotline)/u', $msgLower)) {
            if (auth_is_logged_in()) {
                $curr_user = auth_get_user();
                $name = $curr_user['full_name'] ?: $curr_user['username'];
                $phone = $curr_user['phone'];
                $email = $curr_user['email'];

                $saved = db_query(
                    "INSERT INTO contact_messages (name, phone, email, subject, message) VALUES (?, ?, ?, ?, ?)",
                    "sssss",
                    ['Khách hàng ' . $name . ' từ AI Chatbox', $phone ?: 'Không rõ', $email ?: 'Không rõ', 'Yêu cầu liên hệ từ AI Chatbox', 'Khách hàng yêu cầu hỗ trợ trực tiếp. Tin nhắn: ' . $message]
                );

                if ($saved) {
                    return [
                        'message' => "✅ **Yêu cầu hỗ trợ trực tiếp đã được gửi thành công!**\n\nEm đã chuyển thông tin tài khoản đăng nhập của anh/chị (**{$name}** - SĐT: " . ($phone ?: 'Chưa cập nhật') . " - Email: {$email}) cho kỹ sư và bộ phận chăm sóc khách hàng. Bộ phận hỗ trợ sẽ liên hệ với anh/chị sớm nhất ạ!",
                        'action' => 'contact_success',
                        'suggestions' => $this->getDefaultSuggestions()
                    ];
                }
            }

            $_SESSION['ai_chat_state'] = 'waiting_for_contact_info';
            return [
                'message' => "Dạ, em sẽ chuyển thông tin ngay cho bộ phận kinh doanh và kỹ thuật hỗ trợ trực tiếp. \n\n👉 Bạn vui lòng để lại **Số điện thoại** hoặc **Email** tại đây nhé.",
                'action' => 'start_contact',
                'suggestions' => []
            ];
        }

        // --- 4. TỪ KHÓA ĐỒNG NGHĨA & BẢN ĐỒ TÌM KIẾM SẢN PHẨM THÔNG MINH ---
        // Bản đồ từ khóa đồng nghĩa dẫn tới các sản phẩm tương thích
        $synonymMap = [
            'lúa' => ['Tăng Lực X3', 'Nuôi Đòng Trổ Thoát'],
            'phục hồi' => ['Tăng Lực X3', 'Amino Acid Organic Soluble'],
            'rễ' => ['Tăng Lực X3', 'Amino Acid Organic Soluble'],
            'đất' => ['Tăng Lực X3', 'Bio-Active'],
            'khí độc' => ['Chế phẩm vi sinh xử lý đáy ao nuôi'],
            'tôm' => ['Chế phẩm vi sinh xử lý đáy ao nuôi', 'Soda Ash Light Na2CO3 99%'],
            'xử lý nước' => ['Chlorine Aquafit Ấn Độ 70%', 'Citric Acid Monohydrate', 'Soda Ash Light Na2CO3 99%'],
            'axit' => ['Citric Acid Monohydrate'],
            'ph' => ['Citric Acid Monohydrate', 'Soda Ash Light Na2CO3 99%'],
            'sát trùng' => ['Chlorine Aquafit Ấn Độ 70%'],
            'độc phèn' => ['Bio-Active'],
            'rơm rạ' => ['Bio-Active'],
            'amino' => ['Amino Acid Organic Soluble']
        ];

        $matchedProductsFromMap = [];
        foreach ($synonymMap as $key => $prodsList) {
            if (strpos($msgLower, $key) !== false) {
                $matchedProductsFromMap = array_merge($matchedProductsFromMap, $prodsList);
            }
        }
        $matchedProductsFromMap = array_unique($matchedProductsFromMap);

        // Nếu khớp từ khóa đồng nghĩa, tải sản phẩm từ database lên
        if (!empty($matchedProductsFromMap)) {
            $placeholders = implode(',', array_fill(0, count($matchedProductsFromMap), '?'));
            $sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.name IN ($placeholders)";
            $res = db_query($sql, str_repeat('s', count($matchedProductsFromMap)), array_values($matchedProductsFromMap));
            
            if ($res && $res->num_rows > 0) {
                $reply = "Dạ, với nhu cầu tư vấn liên quan đến **" . h($message) . "**, em xin giới thiệu các dòng sản phẩm tối ưu nhất:\n\n";
                $i = 0;
                $suggestions = [];
                while ($prod = $res->fetch_assoc()) {
                    if ($i == 0) {
                        $_SESSION['ai_last_product_id'] = $prod['id']; // Lưu trữ ID sản phẩm đầu tiên làm ngữ cảnh
                    }
                    $price = $prod['price'] ?: 'Liên hệ báo giá';
                    $link = "product-detail.php?id=" . urlencode($prod['product_key']);
                    
                    $reply .= "🔹 **[{$prod['name']}]({$link})**\n";
                    $reply .= "   *Xuất xứ: {$prod['origin']} | Giá: {$price}*\n";
                    $reply .= "   _{$prod['description']}_\n\n";
                    
                    $suggestions[] = "Báo giá {$prod['name']}";
                    $i++;
                }
                $reply .= "👉 Quý khách có thể nhấn trực tiếp vào liên kết sản phẩm ở trên để xem chi tiết, hoặc nhập số lượng cần báo giá (Ví dụ: *'Mua 20 bao Tăng Lực X3'*) để em lập biểu giá nhanh nhé.";
                
                return [
                    'message' => $reply,
                    'action' => 'product_recommend',
                    'suggestions' => array_slice($suggestions, 0, 3)
                ];
            }
        }

        // --- 5. TƯ VẤN KỸ THUẬT (news_articles section = 'tech') ---
        if (preg_match('/(kỹ thuật|cách trồng|cách trị|sâu đục thân|mật gấu|rau vụ đông|chăm sóc|trồng trọt|phòng trừ)/u', $msgLower)) {
            $techRes = db_query("SELECT slug, title, content, excerpt FROM news_articles WHERE section = 'tech' AND status = 'published'");
            $matchedArticle = null;
            if ($techRes && $techRes->num_rows > 0) {
                while ($article = $techRes->fetch_assoc()) {
                    $titleLower = mb_strtolower($article['title'], 'UTF-8');
                    
                    if ((strpos($msgLower, 'cà phê') !== false || strpos($msgLower, 'cafe') !== false) && strpos($titleLower, 'cà phê') !== false) {
                        $matchedArticle = $article; break;
                    }
                    if (strpos($msgLower, 'mật gấu') !== false && strpos($titleLower, 'mật gấu') !== false) {
                        $matchedArticle = $article; break;
                    }
                    if (strpos($msgLower, 'rau') !== false && strpos($titleLower, 'rau vụ đông') !== false) {
                        $matchedArticle = $article; break;
                    }
                }
            }

            if ($matchedArticle) {
                $summary = strip_tags($matchedArticle['excerpt']);
                if (empty($summary)) {
                    $summary = strip_tags(mb_substr($matchedArticle['content'], 0, 300, 'UTF-8')) . '...';
                }
                
                // Trích xuất cấu trúc H3 từ bài viết nếu có để làm câu trả lời trông cực kì chuyên nghiệp
                $steps = [];
                preg_match_all('/<h3>(.*?)<\/h3>/ui', $matchedArticle['content'], $headerMatches);
                if (!empty($headerMatches[1])) {
                    $steps = array_slice($headerMatches[1], 0, 3);
                }

                $reply = "Dạ, về **{$matchedArticle['title']}**, em xin tóm tắt kỹ thuật nông nghiệp cốt lõi như sau:\n\n";
                $reply .= "📖 *Tóm tắt*: {$summary}\n\n";
                
                if (!empty($steps)) {
                    $reply .= "📌 *Các bước kỹ thuật quan trọng*:\n";
                    foreach ($steps as $idx => $step) {
                        $reply .= ($idx + 1) . ". **" . strip_tags($step) . "**\n";
                    }
                    $reply .= "\n";
                }

                $link = "planting-techniques.php?article=" . urlencode($matchedArticle['slug']);
                $reply .= "👉 Quý khách vui lòng đọc bài viết chi tiết tại đây để áp dụng chính xác nhất: [Xem kỹ thuật chi tiết]({$link})";
                
                return [
                    'message' => $reply,
                    'action' => 'tech_advice',
                    'suggestions' => ["Tư vấn sản phẩm lúa", "Chất lượng vi sinh", "Liên hệ kỹ sư"]
                ];
            }
        }

        // --- 6. TÌM KIẾM THEO XUẤT XỨ (Ví dụ: "Hàn Quốc", "Tây Ban Nha", "Nhật Bản") ---
        // Lấy danh sách xuất xứ thực tế trong Database để tìm kiếm
        $originsList = [];
        $oRes = $this->db->query("SELECT DISTINCT origin FROM products WHERE origin IS NOT NULL AND origin <> ''");
        if ($oRes) {
            while ($oRow = $oRes->fetch_assoc()) {
                $originsList[] = $oRow['origin'];
            }
        }

        $matchedOrigin = '';
        foreach ($originsList as $origin) {
            // Loại bỏ chữ "Nhập khẩu" để lấy lõi quốc gia (Ví dụ "Hàn Quốc", "Tây Ban Nha")
            $cleanOrigin = preg_replace('/(nhập khẩu|sản xuất tại)/ui', '', $origin);
            $cleanOrigin = trim(mb_strtolower($cleanOrigin, 'UTF-8'));
            if (!empty($cleanOrigin) && strpos($msgLower, $cleanOrigin) !== false) {
                $matchedOrigin = $origin;
                break;
            }
        }

        if (!empty($matchedOrigin)) {
            $pRes = db_query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.origin = ?", "s", [$matchedOrigin]);
            if ($pRes && $pRes->num_rows > 0) {
                $reply = "Dạ, đây là các sản phẩm **nhập khẩu từ " . h($matchedOrigin) . "** hiện đang được Ngọc Ánh Dương phân phối chính hãng:\n\n";
                $i = 0;
                $suggestions = [];
                while ($prod = $pRes->fetch_assoc()) {
                    if ($i == 0) {
                        $_SESSION['ai_last_product_id'] = $prod['id'];
                    }
                    $price = $prod['price'] ?: 'Liên hệ báo giá';
                    $link = "product-detail.php?id=" . urlencode($prod['product_key']);
                    $reply .= "🔹 **[{$prod['name']}]({$link})** - Xuất xứ: {$prod['origin']} ({$price})\n";
                    $reply .= "   _{$prod['description']}_\n\n";
                    $suggestions[] = "Báo giá {$prod['name']}";
                    $i++;
                }
                $reply .= "Bạn có cần em gửi báo giá sỉ cho sản phẩm nào ở trên không ạ?";
                return [
                    'message' => $reply,
                    'action' => 'product_origin_search',
                    'suggestions' => array_slice($suggestions, 0, 3)
                ];
            }
        }

        // --- 7. TÌM KIẾM SẢN PHẨM KHỚP TỪ KHÓA BẰNG SQL RÀ SOÁT ---
        // Nếu khách hỏi "Tôi trồng lúa", "Tôi nuôi tôm" để ghi nhớ ngữ cảnh
        if (preg_match('/(trồng lúa|lúa nước|lúa đông xuân|cây lúa)/u', $msgLower)) {
            $_SESSION['ai_context_topic'] = 'lúa';
        } elseif (preg_match('/(nuôi tôm|tôm sú|tôm thẻ|ao tôm|ao nuôi)/u', $msgLower)) {
            $_SESSION['ai_context_topic'] = 'tôm';
        } elseif (preg_match('/(hóa chất|xử lý nước|sát trùng)/u', $msgLower)) {
            $_SESSION['ai_context_topic'] = 'hóa chất';
        }

        $searchQuery = $msgLower;
        if (preg_match('/(dùng loại nào|loại nào tốt|sản phẩm nào tốt|tư vấn đi|có loại nào|dùng gì tốt)/u', $msgLower) && !empty($_SESSION['ai_context_topic'])) {
            $searchQuery = $_SESSION['ai_context_topic'];
        }

        $matchedProducts = $this->searchProductsInDB($searchQuery);

        if (!empty($matchedProducts)) {
            $_SESSION['ai_last_product_id'] = $matchedProducts[0]['id']; // Ghi nhớ sản phẩm đầu tiên tìm được
            
            $reply = "Dạ, dựa trên thông tin bạn chia sẻ, em đề xuất các sản phẩm chất lượng cao phù hợp nhất từ Ngọc Ánh Dương:\n\n";
            $suggestions = [];
            foreach ($matchedProducts as $prod) {
                $price = $prod['price'] ?: 'Liên hệ báo giá';
                $link = "product-detail.php?id=" . urlencode($prod['product_key']);
                
                $reply .= "🔹 **[{$prod['name']}]({$link})** - {$price}\n";
                $reply .= "   *Xuất xứ: {$prod['origin']}*\n";
                $reply .= "   _{$prod['description']}_\n\n";
                $suggestions[] = "Báo giá {$prod['name']}";
            }
            $reply .= "👉 Hãy nhắn số lượng (Ví dụ: *'Tôi muốn mua 10 bao {$matchedProducts[0]['name']}'*) để em lập báo giá nhanh cho anh/chị nhé.";

            return [
                'message' => $reply,
                'action' => 'product_recommend',
                'suggestions' => array_slice($suggestions, 0, 3)
            ];
        }

        // --- 8. CHÀO HỎI / GIỚI THIỆU ---
        if (preg_match('/(xin chào|hello|hi|chào|bạn là ai|tên gì|ngọc ánh dương|trợ lý)/u', $msgLower)) {
            return [
                'message' => "Xin chào! Em là **Trợ lý Ngọc Ánh Dương** 🤖 - tư vấn viên ảo về chế phẩm vi sinh, sinh học, phân bón và giải pháp bảo vệ cây trồng.\n\nEm có thể hỗ trợ anh/chị:\n1. Tư vấn sản phẩm phù hợp cây trồng (Phân bón gốc, phân bón lá, vi sinh, trừ sâu bệnh...)\n2. Tìm sản phẩm theo xuất xứ, công dụng\n3. Hướng dẫn kỹ thuật nông nghiệp (Bài viết chuyên gia)\n4. Tạo yêu cầu báo giá nhanh\n\nAnh/chị cần em hỗ trợ thông tin gì ạ?",
                'action' => 'greeting',
                'suggestions' => $this->getDefaultSuggestions()
            ];
        }

        // --- 9. FALLBACK TẠO LIÊN HỆ NHÂN VIÊN ---
        if (auth_is_logged_in()) {
            $curr_user = auth_get_user();
            $name = $curr_user['full_name'] ?: $curr_user['username'];
            $phone = $curr_user['phone'];
            $email = $curr_user['email'];

            $saved = db_query(
                "INSERT INTO contact_messages (name, phone, email, subject, message) VALUES (?, ?, ?, ?, ?)",
                "sssss",
                ['Khách hàng ' . $name . ' từ AI Chatbox', $phone ?: 'Không rõ', $email ?: 'Không rõ', 'Yêu cầu liên hệ từ AI Chatbox', 'Câu hỏi chưa xử lý được: ' . $message]
            );

            if ($saved) {
                return [
                    'message' => "Dạ, câu hỏi này nằm ngoài phạm vi hỗ trợ tự động của em.\n\nEm đã gửi yêu cầu hỗ trợ trực tiếp kèm câu hỏi của anh/chị với thông tin tài khoản (**{$name}** - SĐT: " . ($phone ?: 'Chưa cập nhật') . " - Email: {$email}). Các kỹ sư nông nghiệp hoặc bộ phận kinh doanh sẽ liên hệ hỗ trợ anh/chị trực tiếp ngay ạ!",
                    'action' => 'contact_success',
                    'suggestions' => $this->getDefaultSuggestions()
                ];
            }
        }

        $_SESSION['ai_chat_state'] = 'waiting_for_contact_info';
        return [
            'message' => "Dạ, câu hỏi này nằm ngoài phạm vi hỗ trợ tự động của em. \n\nĐể được phục vụ tốt nhất, bạn vui lòng nhập **Số điện thoại** hoặc **Email** dưới đây nhé. Các kỹ sư nông nghiệp hoặc bộ phận kinh doanh sẽ liên hệ hỗ trợ bạn trực tiếp ngay ạ!",
            'action' => 'ask_contact_fallback',
            'suggestions' => []
        ];
    }

    private function searchProductsInDB($query) {
        $sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id";
        $res = $this->db->query($sql);
        
        if (!$res || $res->num_rows === 0) {
            return [];
        }

        $products = [];
        while ($row = $res->fetch_assoc()) {
            $products[] = $row;
        }

        $stopWords = ['tôi', 'cần', 'muốn', 'tìm', 'bán', 'có', 'không', 'cho', 'được', 'dùng', 'sử', 'dụng', 'nào', 'loại', 'hàng', 'cây', 'phân', 'bón', 'chế', 'phẩm', 'vật', 'tư', 'hóa', 'chất', 'bị', 'bệnh', 'trị', 'tốt', 'nhất'];
        $words = preg_split('/[\s,\.\?\!]+/u', mb_strtolower($query, 'UTF-8'));
        $keywords = [];
        foreach ($words as $w) {
            $w = trim($w);
            if (strlen($w) > 1 && !in_array($w, $stopWords)) {
                $keywords[] = $w;
            }
        }

        if (empty($keywords)) {
            $keywords[] = trim($query);
        }

        $scoredProducts = [];
        foreach ($products as $prod) {
            $score = 0;
            $nameLower = mb_strtolower($prod['name'], 'UTF-8');
            $descLower = mb_strtolower($prod['description'], 'UTF-8');
            $originLower = mb_strtolower($prod['origin'], 'UTF-8');
            $catLower = mb_strtolower($prod['category_name'], 'UTF-8');

            foreach ($keywords as $kw) {
                if (empty($kw)) continue;

                if (strpos($nameLower, $kw) !== false) {
                    $score += 10;
                }
                if (strpos($originLower, $kw) !== false) {
                    $score += 8;
                }
                if (strpos($catLower, $kw) !== false) {
                    $score += 5;
                }
                if (strpos($descLower, $kw) !== false) {
                    $score += 3;
                }
            }

            if ($score > 0) {
                $prod['search_score'] = $score;
                $scoredProducts[] = $prod;
            }
        }

        usort($scoredProducts, function($a, $b) {
            return $b['search_score'] <=> $a['search_score'];
        });

        return array_slice($scoredProducts, 0, 3);
    }

    private function getDefaultSuggestions() {
        return [
            '🌾 Tư vấn bón phân lúa',
            '🦐 Khử khí độc ao tôm',
            '🧪 Hóa chất xử lý nước',
            '📖 Kỹ thuật trị sâu cà phê'
        ];
    }

    private function callGeminiAPI($message, $apiKey) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . self::MODEL_NAME . ":generateContent?key=" . $apiKey;
        $context = $this->buildSystemContext();
        $history = $this->getConversationsForLLM();

        $contents = [];
        foreach ($history as $h) {
            $contents[] = [
                'role' => $h['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $h['message']]]
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [
                    ['text' => $context]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.4
            ]
        ];

        $jsonData = json_encode($payload);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resData = json_decode($response, true);
            if (isset($resData['candidates'][0]['content']['parts'][0]['text'])) {
                $aiText = $resData['candidates'][0]['content']['parts'][0]['text'];
                $decodedText = json_decode(trim($aiText), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decodedText;
                }
            }
        }

        return null;
    }

    private function buildSystemContext() {
        $cats = [];
        $cRes = $this->db->query("SELECT id, name, slug FROM categories");
        if ($cRes) {
            while ($cRow = $cRes->fetch_assoc()) {
                $cats[] = "ID: {$cRow['id']} - Name: {$cRow['name']} (Slug: {$cRow['slug']})";
            }
        }

        $prods = [];
        $pRes = $this->db->query("SELECT p.id, p.name, p.product_key, p.origin, p.price, p.description, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id");
        if ($pRes) {
            while ($pRow = $pRes->fetch_assoc()) {
                $prods[] = "- Tên sản phẩm: {$pRow['name']} | Mã khóa: {$pRow['product_key']} | Xuất xứ: {$pRow['origin']} | Giá: {$pRow['price']} | Danh mục: {$pRow['cat_name']} | Mô tả: {$pRow['description']}";
            }
        }

        $articles = [];
        $aRes = $this->db->query("SELECT title, slug, excerpt, content FROM news_articles WHERE section = 'tech' AND status = 'published'");
        if ($aRes) {
            while ($aRow = $aRes->fetch_assoc()) {
                $articles[] = "- Tiêu đề: {$aRow['title']} | Đường dẫn: planting-techniques.php?article={$aRow['slug']} | Tóm tắt: {$aRow['excerpt']} | Nội dung cốt lõi: " . strip_tags(mb_substr($aRow['content'], 0, 500, 'UTF-8')) . "...";
            }
        }

        $userInfoStr = "Khách hàng chưa đăng nhập (Khách vãng lai).";
        if (auth_is_logged_in()) {
            $curr_user = auth_get_user();
            $userInfoStr = "Khách hàng đã đăng nhập:\n";
            $userInfoStr .= "- Tên/Họ tên: " . ($curr_user['full_name'] ?: $curr_user['username']) . "\n";
            $userInfoStr .= "- Email: " . $curr_user['email'] . "\n";
            $userInfoStr .= "- Số điện thoại: " . ($curr_user['phone'] ?: 'Chưa cập nhật') . "\n";
            $userInfoStr .= "- Vai trò: " . $curr_user['role'] . "\n";
        }

        $context = "Bạn là Trợ lý Ngọc Ánh Dương (🤖 Trợ lý Ngọc Ánh Dương) của Công ty Cổ phần Hóa chất Nhập khẩu Ngọc Ánh Dương tại Cần Thơ.
Nhiệm vụ của bạn là tư vấn cho khách hàng về phân bón gốc, phân bón lá, chế phẩm vi sinh, sinh học và các giải pháp phòng trừ côn trùng, ốc hại, nấm hại.

THÔNG TIN KHÁCH HÀNG ĐANG TRÒ CHUYỆN:
{$userInfoStr}

Dưới đây là thông tin thực tế từ cơ sở dữ liệu của công ty (Bạn KHÔNG được trả lời sai lệch thông tin này và KHÔNG tự bịa ra sản phẩm khác):

DANH MỤC SẢN PHẨM:
" . implode("\n", $cats) . "

DANH SÁCH SẢN PHẨM THỰC TẾ:
" . implode("\n", $prods) . "

BÀI VIẾT KỸ THUẬT NÔNG NGHIỆP:
" . implode("\n", $articles) . "

QUY TẮC PHẢN HỒI:
1. Hãy trả lời cực kỳ thân thiện, lễ phép và chuyên nghiệp bằng tiếng Việt. Xưng hô là 'Em' hoặc 'Trợ lý Ngọc Ánh Dương' và gọi khách hàng là 'Anh/Chị' hoặc 'Bạn'.
2. Khi giới thiệu sản phẩm hoặc bài viết kỹ thuật, hãy đính kèm liên kết tương ứng theo định dạng markdown. Link sản phẩm là `product-detail.php?id=[Mã khóa]`. Link bài viết là `planting-techniques.php?article=[Đường dẫn]`. Chỉ đề xuất tối đa 3 sản phẩm liên quan nhất.
3. Luôn phân tích câu hỏi để trả lời chính xác dựa trên danh sách dữ liệu.
4. Đảm bảo phản hồi của bạn tuân thủ định dạng JSON chính xác sau:
{
  \"message\": \"Nội dung câu trả lời thân thiện của trợ lý (viết bằng markdown đẹp mắt, liệt kê rõ ràng, thân thiện)\",
  \"action\": \"create_quote\" | \"create_contact\" | \"none\",
  \"action_data\": {
      \"product\": \"Tên sản phẩm khách muốn báo giá (để trống nếu không phải)\",
      \"quantity\": \"Số lượng kèm đơn vị, ví dụ: 20 bao (để trống nếu không phải)\"
  }
}

LƯU Ý Ý ĐỊNH HÀNH ĐỘNG (ACTION):
- Nếu khách bày tỏ muốn mua hoặc báo giá một sản phẩm cụ thể: hãy đặt \"action\" là \"create_quote\", điền tên sản phẩm chính xác vào \"action_data\".
- Nếu khách muốn gặp nhân viên hoặc hỏi những thông tin ngoài chuyên môn nông nghiệp/hóa chất mà bạn không biết trả lời: hãy đặt \"action\" là \"create_contact\", trong phần \"message\" hướng dẫn khách hàng gọi Hotline 0976.828.171.";

        return $context;
    }

    private function getConversationsForLLM() {
        return array_slice($_SESSION['ai_history'], -6);
    }

    private function saveMessage($role, $message) {
        $_SESSION['ai_history'][] = [
            'role' => $role,
            'message' => $message
        ];

        try {
            db_query(
                "INSERT INTO chat_conversations (session_id, role, message) VALUES (?, ?, ?)",
                "sss",
                [$this->sessionId, $role === 'user' ? 'user' : 'assistant', $message]
            );

            // Cập nhật hoặc tạo mới tiêu đề cho phiên trò chuyện
            if ($role === 'user') {
                $this->updateSessionTitle($message);
            }
        } catch (Exception $e) {
            // Bỏ qua
        }
    }

    private function updateSessionTitle($userMessage) {
        if (!auth_is_logged_in()) return;
        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) return;

        $stmt = db_query("SELECT 1 FROM chat_sessions WHERE session_id = ? LIMIT 1", "s", [$this->sessionId]);
        if ($stmt && $stmt->num_rows > 0) {
            db_query("UPDATE chat_sessions SET updated_at = NOW() WHERE session_id = ?", "s", [$this->sessionId]);
        } else {
            $title = mb_substr(strip_tags($userMessage), 0, 40, 'UTF-8');
            if (mb_strlen($userMessage, 'UTF-8') > 40) {
                $title .= '...';
            }
            if (empty($title)) {
                $title = "Cuộc trò chuyện mới";
            }
            db_query(
                "INSERT INTO chat_sessions (session_id, user_id, title, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                "sis",
                [$this->sessionId, $userId, $title]
            );
        }
    }

    public function getHistory() {
        return $_SESSION['ai_history'];
    }

    public function clearHistory() {
        $_SESSION['ai_history'] = [];
        $_SESSION['ai_chat_state'] = 'idle';
        $_SESSION['ai_quote_data'] = [];
        $_SESSION['ai_context_topic'] = '';
        $_SESSION['ai_last_product_id'] = null;
    }

    public function startNewChat() {
        $this->clearHistory();
        $_SESSION['active_chat_session_id'] = uniqid('chat_', true);
        $this->sessionId = $_SESSION['active_chat_session_id'];
    }

    public function getSessions() {
        if (!auth_is_logged_in()) return [];
        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) return [];

        $res = db_query("SELECT session_id, title, updated_at FROM chat_sessions WHERE user_id = ? ORDER BY updated_at DESC", "i", [$userId]);
        $sessions = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $sessions[] = $row;
            }
        }
        return $sessions;
    }

    public function loadSession($sessionId) {
        if (!auth_is_logged_in()) return false;
        $userId = $_SESSION['user_id'] ?? 0;

        $check = db_query("SELECT 1 FROM chat_sessions WHERE session_id = ? AND user_id = ? LIMIT 1", "si", [$sessionId, $userId]);
        if (!$check || $check->num_rows === 0) {
            return false;
        }

        $_SESSION['active_chat_session_id'] = $sessionId;
        $this->sessionId = $sessionId;

        $_SESSION['ai_chat_state'] = 'idle';
        $_SESSION['ai_quote_data'] = [];
        $_SESSION['ai_context_topic'] = '';
        $_SESSION['ai_last_product_id'] = null;

        $res = db_query("SELECT role, message FROM chat_conversations WHERE session_id = ? ORDER BY id ASC", "s", [$sessionId]);
        $_SESSION['ai_history'] = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $_SESSION['ai_history'][] = [
                    'role' => $row['role'],
                    'message' => $row['message']
                ];
            }
        }
        return true;
    }

    public function deleteSession($sessionId) {
        if (!auth_is_logged_in()) return false;
        $userId = $_SESSION['user_id'] ?? 0;

        $check = db_query("SELECT 1 FROM chat_sessions WHERE session_id = ? AND user_id = ? LIMIT 1", "si", [$sessionId, $userId]);
        if (!$check || $check->num_rows === 0) {
            return false;
        }

        db_query("DELETE FROM chat_sessions WHERE session_id = ?", "s", [$sessionId]);
        db_query("DELETE FROM chat_conversations WHERE session_id = ?", "s", [$sessionId]);

        if (isset($_SESSION['active_chat_session_id']) && $_SESSION['active_chat_session_id'] === $sessionId) {
            $this->startNewChat();
        }
        return true;
    }

    public function getActiveSessionId() {
        return $this->sessionId;
    }
}
?>
