<div class="comment-section" id="comment-section">
    <h3>Bình luận</h3>
    
    <div id="comments-list">
        <!-- Comments will be loaded here via JS -->
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="comment-form-wrapper active" id="main-comment-form">
            <form onsubmit="submitComment(event, null)">
                <textarea id="comment" name="comment" placeholder="Viết bình luận của bạn..." required></textarea>
                <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                    <button type="submit" class="btn">
                        Gửi bình luận <i class="fa-solid fa-paper-plane" style="margin-left: 0.4rem;"></i>
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="login-prompt">
            <p>Vui lòng đăng nhập để tham gia bình luận cùng mọi người.</p>
            <a href="login.php" class="btn">Đăng nhập ngay</a>
        </div>
    <?php endif; ?>
</div>

<script>
    const API_URL = '../backend/api_comments.php';
    const ARTICLE_SLUG = '<?php echo isset($selected_article["slug"]) ? addslashes($selected_article["slug"]) : ""; ?>';
    const CURRENT_USER_ID = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;

    function renderComment(comment, isReply = false) {
        const initials = comment.full_name ? comment.full_name.charAt(0).toUpperCase() : (comment.username ? comment.username.charAt(0).toUpperCase() : 'U');
        
        let html = `
            <div class="comment-item ${isReply ? 'reply' : ''}" id="comment-${comment.id}">
                <div class="comment-avatar">${initials}</div>
                <div class="comment-content-box">
                    <div class="comment-header">
                        <span class="comment-author">${escapeHtml(comment.full_name || comment.username)}</span>
                        <span class="comment-time">${new Date(comment.created_at).toLocaleString('vi-VN')}</span>
                    </div>
                    <div class="comment-body" id="comment-body-${comment.id}">${escapeHtml(comment.content)}</div>
                    
                    <div class="comment-actions">
                        <button class="comment-btn like-btn ${comment.user_liked ? 'liked' : ''}" onclick="toggleLike(${comment.id}, this)">
                            <i class="${comment.user_liked ? 'fa-solid' : 'fa-regular'} fa-heart"></i>
                            <span class="likes-count">${comment.likes_count}</span>
                        </button>
        `;
        
        if (CURRENT_USER_ID) {
            html += `<button class="comment-btn" onclick="showReplyForm(${comment.id})"><i class="fa-solid fa-reply"></i> Trả lời</button>`;
            
            if (CURRENT_USER_ID == comment.user_id) {
                html += `
                    <button class="comment-btn" onclick="showEditForm(${comment.id})"><i class="fa-solid fa-pen"></i> Sửa</button>
                    <button class="comment-btn delete-btn" onclick="deleteComment(${comment.id})"><i class="fa-solid fa-trash"></i> Xóa</button>
                `;
            }
        }
        
        html += `
                    </div>
                    
                    <div class="reply-form-wrapper" id="reply-form-${comment.id}">
                        <form onsubmit="submitComment(event, ${comment.id})">
                            <textarea placeholder="Viết câu trả lời..." required></textarea>
                            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem;">
                                <button type="button" class="btn" style="background:#f1f5f9; color:#475569;" onclick="hideReplyForm(${comment.id})">Hủy</button>
                                <button type="submit" class="btn">Gửi trả lời <i class="fa-solid fa-paper-plane" style="margin-left: 0.4rem;"></i></button>
                            </div>
                        </form>
                    </div>

                    <div class="edit-form-wrapper" id="edit-form-${comment.id}">
                        <form onsubmit="submitEdit(event, ${comment.id})">
                            <textarea required>${escapeHtml(comment.content)}</textarea>
                            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem;">
                                <button type="button" class="btn" style="background:#f1f5f9; color:#475569;" onclick="hideEditForm(${comment.id})">Hủy</button>
                                <button type="submit" class="btn">Lưu thay đổi <i class="fa-solid fa-check" style="margin-left: 0.4rem;"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        if (comment.replies && comment.replies.length > 0) {
            comment.replies.forEach(reply => {
                html += renderComment(reply, true);
            });
        }
        
        return html;
    }

    function loadComments() {
        if (!ARTICLE_SLUG) return;
        
        fetch(`${API_URL}?action=get&article_slug=${encodeURIComponent(ARTICLE_SLUG)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const list = document.getElementById('comments-list');
                    list.innerHTML = '';
                    if (data.data.length === 0) {
                        list.innerHTML = '<p style="color: #64748b; font-style: italic;">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>';
                    } else {
                        data.data.forEach(comment => {
                            list.innerHTML += renderComment(comment);
                        });
                    }
                }
            })
            .catch(err => console.error(err));
    }

    function submitComment(e, parentId) {
        e.preventDefault();
        const form = e.target;
        const content = form.querySelector('textarea').value;
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('article_slug', ARTICLE_SLUG);
        formData.append('content', content);
        if (parentId) formData.append('parent_id', parentId);

        fetch(API_URL, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    form.reset();
                    if (parentId) hideReplyForm(parentId);
                    loadComments();
                } else {
                    alert(data.message);
                }
            });
    }

    function toggleLike(commentId, btnEl) {
        if (!CURRENT_USER_ID) {
            alert('Vui lòng đăng nhập để thích bình luận.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'toggle_like');
        formData.append('comment_id', commentId);

        fetch(API_URL, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const icon = btnEl.querySelector('i');
                    const span = btnEl.querySelector('span');
                    let count = parseInt(span.innerText);
                    
                    if (data.data.liked) {
                        btnEl.classList.add('liked');
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                        span.innerText = count + 1;
                    } else {
                        btnEl.classList.remove('liked');
                        icon.classList.remove('fa-solid');
                        icon.classList.add('fa-regular');
                        span.innerText = count - 1;
                    }
                }
            });
    }

    function showReplyForm(commentId) {
        document.querySelectorAll('.reply-form-wrapper').forEach(el => el.classList.remove('active'));
        document.getElementById(`reply-form-${commentId}`).classList.add('active');
    }

    function hideReplyForm(commentId) {
        document.getElementById(`reply-form-${commentId}`).classList.remove('active');
    }

    function showEditForm(commentId) {
        document.getElementById(`comment-body-${commentId}`).style.display = 'none';
        document.getElementById(`edit-form-${commentId}`).classList.add('active');
    }

    function hideEditForm(commentId) {
        document.getElementById(`edit-form-${commentId}`).classList.remove('active');
        document.getElementById(`comment-body-${commentId}`).style.display = 'block';
    }

    function submitEdit(e, commentId) {
        e.preventDefault();
        const form = e.target;
        const content = form.querySelector('textarea').value;
        
        const formData = new FormData();
        formData.append('action', 'edit');
        formData.append('comment_id', commentId);
        formData.append('content', content);

        fetch(API_URL, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadComments();
                } else {
                    alert(data.message);
                }
            });
    }

    function deleteComment(commentId) {
        if (confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('comment_id', commentId);

            fetch(API_URL, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadComments();
                    } else {
                        alert(data.message);
                    }
                });
        }
    }

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // Init
    document.addEventListener('DOMContentLoaded', loadComments);
</script>
