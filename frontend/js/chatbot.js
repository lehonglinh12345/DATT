/**
 * AI Chatbot Frontend Controller - Ngọc Ánh Dương
 */
document.addEventListener("DOMContentLoaded", function () {
    const chatbotWidget = document.getElementById("aiChatbotWidget");
    const chatbotToggle = document.getElementById("aiChatbotToggle");
    const chatbotWindow = document.getElementById("aiChatbotWindow");
    const chatbotClose = document.getElementById("aiChatbotClose");
    const chatbotClear = document.getElementById("aiChatbotClear");
    const chatbotMessages = document.getElementById("aiChatbotMessages");
    const chatbotForm = document.getElementById("aiChatbotForm");
    const chatbotInput = document.getElementById("aiChatbotInput");
    const chatbotSend = document.getElementById("aiChatbotSend");
    const chatbotSuggestions = document.getElementById("aiChatbotSuggestions");
    const welcomeBubble = document.getElementById("aiChatWelcomeBubble");
    const welcomeClose = document.getElementById("aiChatWelcomeClose");

    // Elements for History Sidebar
    const historyToggle = document.getElementById("aiChatbotHistoryToggle");
    const historyPanel = document.getElementById("aiChatbotHistoryPanel");
    const historyClose = document.getElementById("aiChatbotHistoryClose");
    const historyOverlay = document.getElementById("aiChatbotHistoryOverlay");
    const historySessionsList = document.getElementById("aiChatbotSessionsList");
    const newChatBtn = document.getElementById("aiChatbotNewChat");

    if (!chatbotWidget || !chatbotToggle) return;

    // Kéo thả (Drag to scroll) cho vùng gợi ý trên PC
    let isDown = false;
    let startX;
    let scrollLeft;
    let isDragging = false;

    if (chatbotSuggestions) {
        chatbotSuggestions.style.cursor = 'grab';
        
        chatbotSuggestions.addEventListener('mousedown', (e) => {
            isDown = true;
            isDragging = false;
            chatbotSuggestions.style.cursor = 'grabbing';
            startX = e.pageX - chatbotSuggestions.offsetLeft;
            scrollLeft = chatbotSuggestions.scrollLeft;
        });
        chatbotSuggestions.addEventListener('mouseleave', () => {
            isDown = false;
            chatbotSuggestions.style.cursor = 'grab';
        });
        chatbotSuggestions.addEventListener('mouseup', () => {
            isDown = false;
            chatbotSuggestions.style.cursor = 'grab';
        });
        chatbotSuggestions.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            const x = e.pageX - chatbotSuggestions.offsetLeft;
            const walk = (x - startX) * 2; // Tốc độ cuộn
            if (Math.abs(x - startX) > 5) {
                isDragging = true;
            }
            chatbotSuggestions.scrollLeft = scrollLeft - walk;
        });
        
        chatbotSuggestions.addEventListener('click', (e) => {
            if (isDragging) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true); // Use capture phase to intercept before chip click
    }

    // Trạng thái ban đầu
    let isHistoryLoaded = false;
    let activeSessionId = '';

    // Hiển thị bong bóng chào mừng sau 3 giây
    if (welcomeBubble) {
        if (!chatbotWidget.classList.contains("open") && !sessionStorage.getItem("hideChatWelcomeBubble")) {
            setTimeout(() => {
                if (!chatbotWidget.classList.contains("open")) {
                    welcomeBubble.style.display = "block";
                }
            }, 3000);
        }

        if (welcomeClose) {
            welcomeClose.addEventListener("click", function (e) {
                e.stopPropagation(); // Ngăn sự kiện click lan tới nút toggle
                welcomeBubble.style.display = "none";
                sessionStorage.setItem("hideChatWelcomeBubble", "true");
            });
        }
    }

    // Toggle History Sidebar Panel
    function toggleHistoryPanel(show) {
        if (!historyPanel) return;
        const isOpen = show !== undefined ? show : !historyPanel.classList.contains("open");
        historyPanel.classList.toggle("open", isOpen);
        historyPanel.setAttribute("aria-hidden", !isOpen);
        if (historyOverlay) {
            historyOverlay.style.display = isOpen ? "block" : "none";
        }
        
        if (isOpen) {
            loadSessionsList();
        }
    }

    if (historyToggle) {
        historyToggle.addEventListener("click", () => toggleHistoryPanel(true));
    }
    if (historyClose) {
        historyClose.addEventListener("click", () => toggleHistoryPanel(false));
    }
    if (historyOverlay) {
        historyOverlay.addEventListener("click", () => toggleHistoryPanel(false));
    }

    // Fetch session list from API
    function loadSessionsList() {
        fetch("../backend/chatbot_api.php?action=sessions")
            .then(res => res.json())
            .then(data => {
                if (data.success && data.sessions) {
                    renderSessionsList(data.sessions);
                }
            })
            .catch(err => console.error("Lỗi lấy danh sách lịch sử:", err));
    }

    // Render session list in Sidebar
    function renderSessionsList(sessions) {
        if (!historySessionsList) return;
        historySessionsList.innerHTML = '';

        if (sessions.length === 0) {
            historySessionsList.innerHTML = `<div style="text-align: center; font-size: 0.8rem; color: #94a3b8; margin-top: 30px;">Chưa có lịch sử trò chuyện.</div>`;
            return;
        }

        sessions.forEach(session => {
            const item = document.createElement("div");
            const isActive = session.session_id === activeSessionId;
            item.className = `history-session-item ${isActive ? 'active' : ''}`;
            
            item.innerHTML = `
                <div class="session-item-content">
                    <i class="fa-regular fa-message"></i>
                    <span class="session-item-title" title="${session.title}">${session.title}</span>
                </div>
                <button type="button" class="session-item-delete" title="Xóa hội thoại" aria-label="Xóa cuộc trò chuyện">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            `;

            // Click item to load session messages
            item.querySelector(".session-item-content").addEventListener("click", () => {
                selectSession(session.session_id);
            });

            // Click trash to delete session
            item.querySelector(".session-item-delete").addEventListener("click", (e) => {
                e.stopPropagation();
                if (confirm("Bạn có muốn xóa cuộc hội thoại này không?")) {
                    deleteSession(session.session_id);
                }
            });

            historySessionsList.appendChild(item);
        });
    }

    // Load selected session
    function selectSession(sessionId) {
        fetch(`../backend/chatbot_api.php?action=load&session_id=${encodeURIComponent(sessionId)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    activeSessionId = sessionId;
                    chatbotMessages.innerHTML = '';
                    
                    if (data.history && data.history.length > 0) {
                        data.history.forEach(item => {
                            appendMessage(item.role, item.message);
                        });
                    } else {
                        // Trạng thái chào mặc định
                        appendGreetingMessage();
                    }
                    renderSuggestions(data.suggestions);
                    toggleHistoryPanel(false);
                    scrollToBottom();
                } else {
                    alert("Không thể tải cuộc trò chuyện này.");
                }
            })
            .catch(err => console.error("Lỗi tải cuộc hội thoại:", err));
    }

    // Delete session
    function deleteSession(sessionId) {
        fetch("../backend/chatbot_api.php?action=delete", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({ session_id: sessionId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (activeSessionId === sessionId) {
                    // Nếu là session đang active, bắt đầu chat mới
                    startNewChatLogic(false);
                } else {
                    loadSessionsList();
                }
            } else {
                alert("Lỗi khi xóa cuộc trò chuyện.");
            }
        })
        .catch(err => console.error("Lỗi xóa hội thoại:", err));
    }

    // Start a new chat
    function startNewChatLogic(closePanel = true) {
        fetch("../backend/chatbot_api.php?action=new_chat", {
            method: "POST"
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                activeSessionId = ''; // Reset activeSessionId so next messages creates a new session title
                chatbotMessages.innerHTML = '';
                
                const welcomeText = "Cuộc trò chuyện mới đã bắt đầu! Em là **Trợ lý Ngọc Ánh Dương** 🤖. Em có thể giúp gì cho anh/chị hôm nay về nông nghiệp và hóa chất công nghiệp ạ?";
                appendMessage("assistant", welcomeText, "Vừa xong", true);
                renderSuggestions(data.suggestions);
                
                // Fetch the newly generated session ID
                fetch("../backend/chatbot_api.php?action=history")
                    .then(r => r.json())
                    .then(d => {
                        if (d.success && d.session_id) {
                            activeSessionId = d.session_id;
                        }
                    });

                if (closePanel) {
                    toggleHistoryPanel(false);
                } else {
                    loadSessionsList();
                }
                scrollToBottom();
            }
        })
        .catch(err => console.error("Lỗi bắt đầu chat mới:", err));
    }

    if (newChatBtn) {
        newChatBtn.addEventListener("click", () => startNewChatLogic(true));
    }

    function appendGreetingMessage() {
        const welcomeText = "Xin chào! Em là **Trợ lý Ngọc Ánh Dương** 🤖 - tư vấn viên ảo về chế phẩm vi sinh, sinh học, phân bón và giải pháp bảo vệ cây trồng.\n\nAnh/chị cần em hỗ trợ tư vấn sản phẩm, tìm kiếm thông tin hay kỹ thuật gieo trồng nào hôm nay ạ?";
        appendMessage("assistant", welcomeText, "Vừa xong", true);
    }

    // Định dạng Text từ Markdown đơn giản (Bôi đậm, Liên kết)
    function formatMarkdown(text) {
        if (!text) return "";

        // Mã hóa ký tự HTML đặc biệt để tránh XSS (ngoại trừ các thẻ chúng ta tự tạo)
        let html = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Format chữ đậm **text** thành <strong>text</strong>
        html = html.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");

        // Format chữ nghiêng *text* thành <em>text</em>
        html = html.replace(/\*(.*?)\*/g, "<em>$1</em>");

        // Format liên kết [text](url) thành <a href="url">text</a>
        html = html.replace(/\[(.*?)\]\((.*?)\)/g, function(match, label, url) {
            return `<a href="${url}" target="_blank">${label}</a>`;
        });

        // Đổi các ký tự xuống dòng thành thẻ <br>
        html = html.replace(/\n/g, "<br>");

        return html;
    }

    // Cuộn hộp thoại xuống dưới cùng
    function scrollToBottom() {
        setTimeout(() => {
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }, 50);
    }

    // Thêm tin nhắn mới vào khung chat
    function appendMessage(role, messageText, timestamp = "Vừa xong", animate = false) {
        const messageDiv = document.createElement("div");
        messageDiv.className = `chatbot-message chatbot-message-${role === "user" ? "user" : "assistant"}`;
        
        const formattedText = formatMarkdown(messageText);

        if (role === "user") {
            messageDiv.innerHTML = `
                <div class="message-content">
                    <div class="message-bubble">${formattedText}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            `;
            chatbotMessages.appendChild(messageDiv);
            scrollToBottom();
        } else {
            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <img src="images/logo.jpg" alt="Trợ lý">
                </div>
                <div class="message-content">
                    <div class="message-bubble"></div>
                    <div class="message-time">${timestamp}</div>
                </div>
            `;
            chatbotMessages.appendChild(messageDiv);
            
            const bubble = messageDiv.querySelector('.message-bubble');
            
            if (animate) {
                let i = 0;
                let isTag = false;
                function type() {
                    if (i < formattedText.length) {
                        let char = formattedText.charAt(i);
                        if (char === '<') isTag = true;
                        if (char === '>') isTag = false;
                        i++;
                        if (isTag) {
                            type(); // skip delay for html tags
                        } else {
                            bubble.innerHTML = formattedText.substring(0, i);
                            scrollToBottom();
                            // Increase typing speed for better UX
                            setTimeout(type, 15);
                        }
                    } else {
                        bubble.innerHTML = formattedText;
                        scrollToBottom();
                    }
                }
                type();
            } else {
                bubble.innerHTML = formattedText;
                scrollToBottom();
            }
        }
    }

    // Hiển thị các nút gợi ý hỏi nhanh (chips)
    function renderSuggestions(suggestions) {
        if (!chatbotSuggestions) return;
        chatbotSuggestions.innerHTML = "";

        if (suggestions && suggestions.length > 0) {
            suggestions.forEach(text => {
                const chip = document.createElement("button");
                chip.type = "button";
                chip.className = "chatbot-suggestion-chip";
                chip.textContent = text;
                chip.addEventListener("click", function () {
                    chatbotInput.value = text;
                    chatbotForm.dispatchEvent(new Event("submit"));
                });
                chatbotSuggestions.appendChild(chip);
            });
            chatbotSuggestions.style.display = "flex";
        } else {
            chatbotSuggestions.style.display = "none";
        }
        scrollToBottom();
    }

    // Tải lịch sử cuộc hội thoại từ Session Backend
    function loadChatHistory() {
        if (isHistoryLoaded) return;

        fetch("../backend/chatbot_api.php?action=history")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.session_id) {
                        activeSessionId = data.session_id;
                    }
                    if (data.history && data.history.length > 0) {
                        // Xóa lời chào mặc định nếu có lịch sử lưu trữ
                        chatbotMessages.innerHTML = "";
                        data.history.forEach(item => {
                            appendMessage(item.role, item.message);
                        });
                    }
                    // Hiển thị các gợi ý
                    renderSuggestions(data.suggestions);
                    isHistoryLoaded = true;
                }
            })
            .catch(error => {
                console.error("Lỗi tải lịch sử chat:", error);
            });
    }

    // Bật/tắt trạng thái mở rộng chatbox
    function toggleChatbox() {
        const isOpen = chatbotWidget.classList.toggle("open");
        chatbotToggle.setAttribute("aria-expanded", isOpen);
        chatbotWindow.setAttribute("aria-hidden", !isOpen);

        if (isOpen) {
            chatbotWindow.removeAttribute("inert");
            
            // Ẩn welcome bubble khi mở chat
            if (welcomeBubble) {
                welcomeBubble.style.display = "none";
                sessionStorage.setItem("hideChatWelcomeBubble", "true");
            }

            // Tải lịch sử chat trong lần đầu tiên mở
            loadChatHistory();
            scrollToBottom();
            
            // Focus vào ô nhập liệu (trên PC)
            if (window.innerWidth > 575) {
                setTimeout(() => chatbotInput.focus(), 300);
            }
        } else {
            chatbotWindow.setAttribute("inert", "");
            // Đóng Panel lịch sử nếu đang mở
            toggleHistoryPanel(false);
            // Giải phóng focus của các phần tử bên trong khung chat
            if (document.activeElement && chatbotWindow.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        }
    }

    // Sự kiện Click nút Toggle tròn và nút Đóng (Minus)
    chatbotToggle.addEventListener("click", toggleChatbox);
    chatbotClose.addEventListener("click", toggleChatbox);

    // Xử lý gửi tin nhắn
    chatbotForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const messageText = chatbotInput.value.trim();
        if (messageText === "") return;

        // Ẩn vùng gợi ý khi đang gửi
        if (chatbotSuggestions) {
            chatbotSuggestions.style.display = "none";
        }

        // Hiển thị tin nhắn người dùng lên UI
        appendMessage("user", messageText);
        chatbotInput.value = "";

        // Hiển thị hiệu ứng gõ phím
        const typingId = "typing-" + Date.now();
        const typingDiv = document.createElement("div");
        typingDiv.id = typingId;
        typingDiv.className = "chatbot-message chatbot-message-assistant";
        typingDiv.innerHTML = `
            <div class="message-avatar">
                <img src="images/logo.jpg" alt="Trợ lý">
            </div>
            <div class="message-content">
                <div class="message-bubble typing-bubble">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </div>
        `;
        chatbotMessages.appendChild(typingDiv);
        scrollToBottom();

        // Khóa input trong lúc chờ phản hồi
        chatbotInput.disabled = true;
        chatbotSend.disabled = true;

        // Gửi AJAX POST lên backend dạng Form Urlencoded để tránh ModSecurity 403 trên InfinityFree
        fetch("../backend/chatbot_api.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({ message: messageText })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("Lỗi mạng phản hồi không tốt.");
            }
            return response.json();
        })
        .then(data => {
            // Xóa hiệu ứng gõ phím
            const typingIndicator = document.getElementById(typingId);
            if (typingIndicator) {
                typingIndicator.remove();
            }

            if (data.success && data.reply) {
                appendMessage("assistant", data.reply, "Vừa xong", true);
                // Hiển thị mảng gợi ý tiếp theo do AI sinh ra
                renderSuggestions(data.suggestions);
            } else {
                appendMessage("assistant", "Dạ, em gặp trục trặc khi xử lý yêu cầu. Bạn vui lòng thử lại hoặc gọi Hotline **0976.828.171** nhé!", "Vừa xong", true);
            }
        })
        .catch(error => {
            console.error("Lỗi gửi tin nhắn:", error);
            const typingIndicator = document.getElementById(typingId);
            if (typingIndicator) {
                typingIndicator.remove();
            }
            appendMessage("assistant", "Kết nối mạng bị gián đoạn. Bạn vui lòng tải lại trang hoặc liên hệ Zalo **0976828171**.", "Vừa xong", true);
        })
        .finally(() => {
            // Mở khóa input
            chatbotInput.disabled = false;
            chatbotSend.disabled = false;
            if (window.innerWidth > 575) {
                chatbotInput.focus();
            }
            scrollToBottom();
        });
    });
});
