<!-- Floating AI Chatbot Widget -->
<div class="ai-chatbot-widget" id="aiChatbotWidget" aria-live="polite">
    
    <!-- Chatbox Welcome Bubble -->
    <div class="chat-welcome-bubble" id="aiChatWelcomeBubble" style="display: none;">
        <span class="welcome-text">Trò chuyện cùng Ngọc Ánh nhé!</span>
        <button type="button" id="aiChatWelcomeClose" class="welcome-close-btn" aria-label="Đóng gợi ý">&times;</button>
    </div>

    <!-- Chatbox Trigger Button -->
    <button type="button" 
            id="aiChatbotToggle" 
            class="ai-chatbot-toggle" 
            aria-expanded="false" 
            aria-controls="aiChatbotWindow" 
            aria-label="Mở trợ lý ảo Ngọc Ánh Dương">
        <span class="chat-toggle-icon icon-closed">
            <i class="fa-solid fa-comment-dots"></i>
        </span>
        <span class="chat-toggle-icon icon-opened">
            <i class="fa-solid fa-xmark"></i>
        </span>
        <span class="chat-toggle-badge animate-ping"></span>
    </button>

    <!-- Chatbox Window -->
    <div class="ai-chatbot-window" id="aiChatbotWindow" aria-hidden="true" inert>
        <!-- History Sidebar Panel (Slide-out) -->
        <div class="chatbot-history-panel" id="aiChatbotHistoryPanel" aria-hidden="true">
            <div class="history-panel-header">
                <h5>Lịch sử hội thoại 📖</h5>
                <button type="button" id="aiChatbotHistoryClose" class="history-close-btn" aria-label="Đóng lịch sử">&times;</button>
            </div>
            <button type="button" id="aiChatbotNewChat" class="btn-new-chat">
                <i class="fa-solid fa-plus"></i> Cuộc trò chuyện mới
            </button>
            <div class="history-sessions-list" id="aiChatbotSessionsList">
                <!-- Dynamically loaded -->
            </div>
        </div>
        <div class="chatbot-history-overlay" id="aiChatbotHistoryOverlay" style="display: none;"></div>

        <!-- Header -->
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <button type="button" id="aiChatbotHistoryToggle" class="chatbot-btn-action" title="Lịch sử hội thoại" aria-label="Mở lịch sử hội thoại">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="chatbot-avatar">
                    <img src="images/logo.jpg" alt="Ngọc Ánh Dương Logo">
                    <span class="avatar-status-dot"></span>
                </div>
                <div class="chatbot-name-wrapper">
                    <h4 class="chatbot-name">Trợ lý Ngọc Ánh Dương</h4>
                    <span class="chatbot-subtitle">Trực tuyến • Tư vấn 24/7</span>
                </div>
            </div>
            <div class="chatbot-header-actions">
                <button type="button" id="aiChatbotClear" class="chatbot-btn-action" title="Xóa lịch sử chat" aria-label="Xóa lịch sử chat" style="display: none;">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
                <button type="button" id="aiChatbotClose" class="chatbot-btn-action" title="Thu nhỏ" aria-label="Thu nhỏ khung chat">
                    <i class="fa-solid fa-minus"></i>
                </button>
            </div>
        </div>

        <!-- Message List -->
        <div class="chatbot-messages" id="aiChatbotMessages">
            <!-- Greeting message -->
            <div class="chatbot-message chatbot-message-assistant">
                <div class="message-avatar">
                    <img src="images/logo.jpg" alt="Trợ lý">
                </div>
                <div class="message-content">
                    <div class="message-bubble">
                        Xin chào! Em là **Trợ lý Ngọc Ánh Dương** 🤖 - tư vấn viên ảo về chế phẩm vi sinh, sinh học, phân bón và giải pháp bảo vệ cây trồng.
                        <br><br>
                        Anh/chị cần em hỗ trợ tư vấn sản phẩm, tìm kiếm thông tin hay kỹ thuật gieo trồng nào hôm nay ạ?
                    </div>
                    <div class="message-time">Vừa xong</div>
                </div>
            </div>
        </div>



        <!-- Suggestions Area -->
        <div class="chatbot-suggestions" id="aiChatbotSuggestions" style="display: none;"></div>

        <!-- Input Box Form -->
        <div class="chatbot-input-area">
            <form id="aiChatbotForm" autocomplete="off" class="d-flex w-100 gap-2">
                <input type="text" 
                       id="aiChatbotInput" 
                       class="form-control chatbot-input" 
                       placeholder="Nhập tin nhắn..." 
                       required
                       aria-label="Nhập nội dung tin nhắn gửi trợ lý">
                <button type="submit" id="aiChatbotSend" class="chatbot-send-btn" aria-label="Gửi tin nhắn">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>
