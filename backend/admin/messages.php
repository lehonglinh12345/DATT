<?php
$page_title = "Quản Lý Tin Nhắn Liên Hệ";
$active_admin_tab = "messages";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --------------------------------------------------------------------------
// Update Status
// --------------------------------------------------------------------------
if ($action === 'status' && $id > 0) {
    $status = $_GET['status'] ?? '';
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } elseif (!in_array($status, ['new', 'read', 'closed'])) {
        $error = "Trạng thái không hợp lệ.";
    } else {
        $update = db_query("UPDATE contact_messages SET status = ? WHERE id = ?", "si", [$status, $id]);
        if ($update) {
            $success = "Cập nhật trạng thái tin nhắn thành công!";
        } else {
            $error = "Không thể cập nhật trạng thái.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// Delete Message
// --------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } else {
        $delete = db_query("DELETE FROM contact_messages WHERE id = ?", "i", [$id]);
        if ($delete) {
            $success = "Xóa tin nhắn thành công!";
        } else {
            $error = "Không thể xóa tin nhắn.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// View Details
// --------------------------------------------------------------------------
if ($action === 'view' && $id > 0):
    $res = db_query("SELECT * FROM contact_messages WHERE id = ?", "i", [$id]);
    if ($res && $res->num_rows > 0) {
        $msg = $res->fetch_assoc();
        
        // Auto-mark as read if status was 'new'
        if ($msg['status'] === 'new') {
            db_query("UPDATE contact_messages SET status = 'read' WHERE id = ?", "i", [$id]);
            $msg['status'] = 'read';
        }
    } else {
        $error = "Không tìm thấy tin nhắn.";
        $action = 'list';
    }

    if ($action !== 'list'):
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-envelope-open"></i> Chi Tiết Tin Nhắn Liên Hệ</h3>
            <a href="messages.php" class="btn btn-outline btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
        </div>
        <div class="admin-card-body">
            <div class="message-detail-grid">
                <!-- Main Message Content -->
                <div style="background-color: #f8fafc; border-radius: 12px; padding: 2rem; border: 1px solid var(--color-admin-border);">
                    <div style="border-bottom: 1px solid var(--color-admin-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                        <h4 style="font-size: 1.25rem; color: var(--color-secondary); margin-bottom: 0.5rem; font-weight: 700;">
                            <?= h($msg['subject'] ?: '(Không có chủ đề)') ?>
                        </h4>
                        <span style="font-size: 0.85rem; color: var(--color-admin-text-muted);">
                            <i class="fa-solid fa-clock"></i> Ngày gửi: <?= date('d/m/Y H:i:s', strtotime($msg['created_at'])) ?>
                        </span>
                    </div>
                    
                    <div style="line-height: 1.8; color: var(--color-dark); white-space: pre-wrap; font-size: 0.95rem;">
                        <?= h($msg['message']) ?>
                    </div>
                </div>

                <!-- Sender Details -->
                <div style="background-color: white; border-radius: 12px; padding: 2rem; border: 1px solid var(--color-admin-border); display: flex; flex-direction: column; gap: 1.5rem;">
                    <h4 style="border-bottom: 1px solid var(--color-admin-border); padding-bottom: 0.75rem; color: var(--color-secondary); font-weight: 700; margin: 0;">Thông Tin Người Gửi</h4>
                    
                    <div>
                        <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); text-transform: uppercase;">Họ và tên</div>
                        <div style="font-weight: 600; font-size: 1rem; color: var(--color-dark);"><?= h($msg['name']) ?></div>
                    </div>

                    <div>
                        <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); text-transform: uppercase;">Số điện thoại</div>
                        <div style="font-weight: 600; font-size: 1rem; color: var(--color-dark);">
                            <a href="tel:<?= h($msg['phone']) ?>" style="color: var(--color-primary);"><i class="fa-solid fa-phone"></i> <?= h($msg['phone']) ?></a>
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); text-transform: uppercase;">Địa chỉ Email</div>
                        <div style="font-weight: 600; font-size: 1rem; color: var(--color-dark);">
                            <?php if (!empty($msg['email'])): ?>
                                <a href="mailto:<?= h($msg['email']) ?>" style="color: var(--color-secondary);"><i class="fa-solid fa-envelope"></i> <?= h($msg['email']) ?></a>
                            <?php else: ?>
                                <em>Không cung cấp</em>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Trạng thái xử lý</div>
                        <div>
                            <?php if ($msg['status'] === 'new'): ?>
                                <span class="badge badge-new" style="font-size: 0.85rem; padding: 0.4rem 1rem;">Mới</span>
                            <?php elseif ($msg['status'] === 'read'): ?>
                                <span class="badge badge-read" style="font-size: 0.85rem; padding: 0.4rem 1rem;">Đã xem</span>
                            <?php else: ?>
                                <span class="badge badge-closed" style="font-size: 0.85rem; padding: 0.4rem 1rem;">Đã xử lý</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Reply Section -->
                    <div style="margin-top: 0.5rem; border-top: 1px solid var(--color-admin-border); padding-top: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-admin-text-dark); text-transform: uppercase;">
                            <i class="fa-solid fa-reply"></i> Phản hồi nhanh:
                        </span>
                        
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php if (!empty($msg['phone'])): 
                                $zalo_phone = preg_replace('/[^0-9]/', '', $msg['phone']);
                            ?>
                                <a href="https://zalo.me/<?= h($zalo_phone) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center; border-radius: 6px; background-color: #0068ff; display: inline-flex; align-items: center; justify-content: center; gap: 5px; border: none; font-size: 0.8rem; color: white;">
                                    <i class="fa-solid fa-comments"></i> Nhắn Zalo
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($msg['email'])): 
                                $mail_subject = rawurlencode("Phản hồi liên hệ từ Ngọc Ánh Dương");
                                $mail_body = rawurlencode("Kính chào " . $msg['name'] . ",\n\nNgọc Ánh Dương xin phản hồi tin nhắn liên hệ của anh/chị với nội dung: \n\"" . $msg['subject'] . "\"\n\n...\n\nTrân trọng,\nNgọc Ánh Dương Import Chemical");
                            ?>
                                <a href="mailto:<?= h($msg['email']) ?>?subject=<?= $mail_subject ?>&body=<?= $mail_body ?>" class="btn btn-sm" style="flex: 1; text-align: center; border-radius: 6px; background-color: #eab308; color: white; display: inline-flex; align-items: center; justify-content: center; gap: 5px; border: none; font-size: 0.8rem;">
                                    <i class="fa-solid fa-paper-plane"></i> Gửi Email
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Template Copy Box -->
                        <div style="background-color: #f8fafc; border: 1px dashed var(--color-admin-border); border-radius: 8px; padding: 0.75rem; font-size: 0.8rem; display: flex; flex-direction: column; gap: 6px;">
                            <div style="font-weight: 700; color: var(--color-admin-text-dark); display: flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-copy"></i> Mẫu nhắn SMS/Zalo:
                            </div>
                            <?php 
                                $sms_template = "Chào " . $msg['name'] . ", Ngọc Ánh Dương đã nhận được liên hệ của anh/chị. Chúng tôi xin phản hồi như sau: ";
                            ?>
                            <textarea id="smsTemplateText" readonly style="width: 100%; height: 65px; border-radius: 6px; border: 1px solid var(--color-admin-border); padding: 6px; font-size: 0.75rem; background: white; resize: none; outline: none; font-family: inherit; line-height: 1.4;"><?= h($sms_template) ?></textarea>
                            <button type="button" class="btn btn-sm" onclick="copyTemplate('smsTemplateText', this)" style="background-color: var(--color-primary); color: white; border: none; border-radius: 6px; padding: 5px; font-size: 0.75rem; font-weight: 600; cursor: pointer; width: 100%;">
                                Copy Mẫu Tin Nhắn
                            </button>
                        </div>
                    </div>

                    <div style="margin-top: 0.5rem; border-top: 1px solid var(--color-admin-border); padding-top: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <span style="font-size: 0.8rem; font-weight: 600; color: var(--color-admin-text-dark);">CẬP NHẬT TRẠNG THÁI:</span>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="messages.php?action=status&id=<?= $msg['id'] ?>&status=read&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center; border-radius: 6px;">Đánh dấu: Đã Xem</a>
                            <a href="messages.php?action=status&id=<?= $msg['id'] ?>&status=closed&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-primary btn-sm" style="flex: 1; text-align: center; border-radius: 6px; background-color: #10b981;">Đã xử lý xong</a>
                        </div>
                        <a href="messages.php?action=delete&id=<?= $msg['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-outline btn-sm" style="text-align: center; border-radius: 6px; border-color: #ef4444; color: #ef4444; margin-top: 0.5rem;" onclick="return confirm('Bạn có chắc chắn muốn xóa thư này?');">Xóa thư liên hệ này</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php 
    endif;
endif;

// --------------------------------------------------------------------------
// Messages List View
// --------------------------------------------------------------------------
if ($action === 'list'):
    $status_filter = $_GET['status'] ?? '';
    
    $sql = "SELECT * FROM contact_messages WHERE 1=1";
    $types = "";
    $params = [];
    
    if (in_array($status_filter, ['new', 'read', 'closed'])) {
        $sql .= " AND status = ?";
        $types .= "s";
        $params[] = $status_filter;
    }
    
    $sql .= " ORDER BY id DESC";
    
    $messages = db_query($sql, !empty($types) ? $types : null, !empty($params) ? $params : null);
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-envelope-open-text"></i> Danh Sách Thư Liên Hệ</h3>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button onclick="exportTableToCSV('messagesTable', 'tin-nhan-lien-he.csv')" class="btn btn-outline btn-sm" style="border-radius: 6px; border-color: var(--color-admin-border); color: var(--color-admin-text-dark); background-color: var(--color-white);"><i class="fa-solid fa-file-export"></i> Xuất Excel (CSV)</button>
                <a href="messages.php" class="btn btn-sm <?= empty($status_filter) ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px;">Tất cả</a>
                <a href="messages.php?status=new" class="btn btn-sm <?= $status_filter === 'new' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px;">Mới</a>
                <a href="messages.php?status=read" class="btn btn-sm <?= $status_filter === 'read' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px;">Đã đọc</a>
                <a href="messages.php?status=closed" class="btn btn-sm <?= $status_filter === 'closed' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px;">Đã xử lý</a>
            </div>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <!-- Quick search -->
            <div style="padding: 1.25rem 2rem; border-bottom: 1px solid var(--color-admin-border); background-color: #f8fafc;">
                <input type="text" id="messagesSearchInput" class="form-control" placeholder="Tìm kiếm nhanh người gửi, SĐT, tiêu đề... (gõ để lọc tức thì)">
            </div>
            
            <?php if (!empty($success)): ?>
                <div style="padding: 1rem 2rem 0 2rem;">
                    <div class="admin-alert admin-alert-success"><?= h($success) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div style="padding: 1rem 2rem 0 2rem;">
                    <div class="admin-alert admin-alert-danger"><?= h($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table id="messagesTable" class="admin-table responsive-cards-mobile">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
                            <th>Chủ đề / Lý do</th>
                            <th>Nội dung tóm tắt</th>
                            <th>Ngày gửi</th>
                            <th>Trạng thái</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($messages && $messages->num_rows > 0): ?>
                            <?php while ($msg = $messages->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="ID"><?= $msg['id'] ?></td>
                                    <td data-label="Khách hàng" style="font-weight: 600;">
                                        <a href="messages.php?action=view&id=<?= $msg['id'] ?>" style="color: var(--color-secondary); text-decoration: underline;"><?= h($msg['name']) ?></a>
                                    </td>
                                    <td data-label="Liên hệ">
                                        <div>
                                            <a href="tel:<?= h($msg['phone']) ?>" style="color: var(--color-primary); font-weight: 600;"><i class="fa-solid fa-phone" style="font-size:0.8rem"></i> <?= h($msg['phone']) ?></a>
                                            <?php if(!empty($msg['phone'])): 
                                                $zalo_phone = preg_replace('/[^0-9]/', '', $msg['phone']);
                                            ?>
                                                <a href="https://zalo.me/<?= h($zalo_phone) ?>" target="_blank" rel="noopener noreferrer" style="color: #0068ff; margin-left: 6px;" title="Nhắn tin Zalo"><i class="fa-solid fa-comments"></i></a>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--color-admin-text-muted);"><?= h($msg['email'] ?: '-') ?></div>
                                    </td>
                                    <td data-label="Chủ đề" style="font-weight: 500;"><?= h($msg['subject'] ?: '(Không có chủ đề)') ?></td>
                                    <td data-label="Nội dung tóm tắt">
                                        <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--color-admin-text-muted);">
                                            <?= h($msg['message']) ?>
                                        </div>
                                    </td>
                                    <td data-label="Ngày gửi"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></td>
                                    <td data-label="Trạng thái">
                                        <?php if ($msg['status'] === 'new'): ?>
                                            <span class="badge badge-new">Mới</span>
                                        <?php elseif ($msg['status'] === 'read'): ?>
                                            <span class="badge badge-read">Đã đọc</span>
                                        <?php else: ?>
                                            <span class="badge badge-closed">Đã xử lý</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Thao tác">
                                        <div class="actions-cell" style="justify-content: center;">
                                            <a href="messages.php?action=view&id=<?= $msg['id'] ?>" class="btn-icon-only btn-view" title="Xem chi tiết">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="messages.php?action=delete&id=<?= $msg['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon-only btn-delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa tin nhắn này?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem; color: var(--color-admin-text-muted);">
                                    Không có tin nhắn nào trong danh mục này.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function copyTemplate(elementId, btn) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function() {
        var originalText = btn.innerHTML;
        btn.innerHTML = "<i class='fa-solid fa-check'></i> Đã Copy!";
        btn.style.backgroundColor = "#10b981";
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.style.backgroundColor = "";
        }, 2000);
    }).catch(function(err) {
        console.error('Không thể copy', err);
    });
}
</script>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
