<?php
$page_title = "Quản Lý Yêu Cầu Báo Giá";
$active_admin_tab = "quotes";
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
    } elseif (!in_array($status, ['new', 'read', 'completed'])) {
        $error = "Trạng thái không hợp lệ.";
    } else {
        $update = db_query("UPDATE quote_requests SET status = ? WHERE id = ?", "si", [$status, $id]);
        if ($update) {
            $success = "Cập nhật trạng thái yêu cầu thành công!";
        } else {
            $error = "Không thể cập nhật trạng thái.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// Delete Request
// --------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } else {
        $delete = db_query("DELETE FROM quote_requests WHERE id = ?", "i", [$id]);
        if ($delete) {
            $success = "Xóa yêu cầu báo giá thành công!";
        } else {
            $error = "Không thể xóa yêu cầu.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// View Details
// --------------------------------------------------------------------------
if ($action === 'view' && $id > 0):
    $res = db_query("SELECT * FROM quote_requests WHERE id = ?", "i", [$id]);
    if ($res && $res->num_rows > 0) {
        $quote = $res->fetch_assoc();
        
        // Auto-mark as read if status was 'new'
        if ($quote['status'] === 'new') {
            db_query("UPDATE quote_requests SET status = 'read' WHERE id = ?", "i", [$id]);
            $quote['status'] = 'read';
        }
    } else {
        $error = "Không tìm thấy yêu cầu báo giá.";
        $action = 'list';
    }

    if ($action !== 'list'):
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-file-invoice-dollar"></i> Chi Tiết Yêu Cầu Báo Giá</h3>
            <div style="display: flex; gap: 0.5rem;">
                <a href="print_quote.php?id=<?= $quote['id'] ?>" target="_blank" class="btn btn-primary btn-sm" style="border-radius: 8px; background-color: #d98a2b;"><i class="fa-solid fa-print"></i> Xuất / In Hóa Đơn</a>
                <a href="quotes.php" class="btn btn-outline btn-sm" style="border-radius: 8px;"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="message-detail-grid">
                <!-- Main Content -->
                <div style="background-color: #f8fafc; border-radius: 12px; padding: 2rem; border: 1px solid var(--color-admin-border);">
                    <div style="border-bottom: 1px solid var(--color-admin-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                        <h4 style="font-size: 1.25rem; color: var(--color-primary); margin-bottom: 0.5rem; font-weight: 700;">
                            Sản phẩm: <?= h($quote['product_name']) ?>
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.85rem; color: var(--color-admin-text-muted);">
                            <span><i class="fa-solid fa-barcode"></i> Mã sản phẩm: <strong><?= h($quote['product_key'] ?: 'Không có') ?></strong></span>
                            <?php if (!empty($quote['product_link'])): ?>
                                <span><i class="fa-solid fa-link"></i> Đường dẫn: <a href="../../frontend/<?= h($quote['product_link']) ?>" target="_blank" style="color: var(--color-secondary); text-decoration: underline;"><?= h($quote['product_link']) ?></a></span>
                            <?php endif; ?>
                            <span><i class="fa-solid fa-clock"></i> Ngày gửi: <?= date('d/m/Y H:i:s', strtotime($quote['created_at'])) ?></span>
                        </div>
                    </div>
                    
                    <div style="font-size: 0.85rem; color: var(--color-admin-text-muted); text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 600;">Nội dung yêu cầu / Lời nhắn thêm:</div>
                    <div style="line-height: 1.8; color: var(--color-admin-text-dark); white-space: pre-wrap; font-size: 0.95rem; background: #fff; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--color-admin-border);">
                        <?= h($quote['message'] ?: '(Không có tin nhắn thêm)') ?>
                    </div>
                </div>

                <!-- Sender Details -->
                <div style="background-color: white; border-radius: 12px; padding: 2rem; border: 1px solid var(--color-admin-border); display: flex; flex-direction: column; gap: 1.5rem;">
                    <h4 style="border-bottom: 1px solid var(--color-admin-border); padding-bottom: 0.75rem; color: var(--color-secondary); font-weight: 700; margin: 0;">Thông Tin Người Gửi</h4>
                    
                    <div>
                        <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); text-transform: uppercase;">Họ và tên khách hàng</div>
                        <div style="font-weight: 600; font-size: 1rem; color: var(--color-admin-text-dark);"><?= h($quote['name']) ?></div>
                    </div>

                    <div>
                        <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); text-transform: uppercase;">Số điện thoại</div>
                        <div style="font-weight: 600; font-size: 1rem; color: var(--color-admin-text-dark);">
                            <a href="tel:<?= h($quote['phone']) ?>" style="color: var(--color-primary);"><i class="fa-solid fa-phone"></i> <?= h($quote['phone']) ?></a>
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 0.8rem; color: var(--color-admin-text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Trạng thái xử lý</div>
                        <div>
                            <?php if ($quote['status'] === 'new'): ?>
                                <span class="badge badge-new" style="font-size: 0.85rem; padding: 0.4rem 1rem;">Mới</span>
                            <?php elseif ($quote['status'] === 'read'): ?>
                                <span class="badge badge-read" style="font-size: 0.85rem; padding: 0.4rem 1rem;">Đã xem</span>
                            <?php else: ?>
                                <span class="badge badge-closed" style="font-size: 0.85rem; padding: 0.4rem 1rem; background-color: #10b981;">Đã báo giá</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Reply Section -->
                    <div style="margin-top: 0.5rem; border-top: 1px solid var(--color-admin-border); padding-top: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-admin-text-dark); text-transform: uppercase;">
                            <i class="fa-solid fa-reply"></i> Phản hồi nhanh:
                        </span>
                        
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php if (!empty($quote['phone'])): 
                                $zalo_phone = preg_replace('/[^0-9]/', '', $quote['phone']);
                            ?>
                                <a href="https://zalo.me/<?= h($zalo_phone) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center; border-radius: 6px; background-color: #0068ff; display: inline-flex; align-items: center; justify-content: center; gap: 5px; border: none; font-size: 0.8rem; color: white;">
                                    <i class="fa-solid fa-comments"></i> Nhắn Zalo
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Template Copy Box -->
                        <div style="background-color: #f8fafc; border: 1px dashed var(--color-admin-border); border-radius: 8px; padding: 0.75rem; font-size: 0.8rem; display: flex; flex-direction: column; gap: 6px;">
                            <div style="font-weight: 700; color: var(--color-admin-text-dark); display: flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-copy"></i> Mẫu nhắn báo giá SMS/Zalo:
                            </div>
                            <?php 
                                $sms_template = "Chào " . $quote['name'] . ", Ngọc Ánh Dương đã nhận được yêu cầu báo giá sản phẩm " . $quote['product_name'] . " của anh/chị. Chúng tôi xin báo giá chi tiết như sau: ";
                            ?>
                            <textarea id="quoteTemplateText" readonly style="width: 100%; height: 65px; border-radius: 6px; border: 1px solid var(--color-admin-border); padding: 6px; font-size: 0.75rem; background: white; resize: none; outline: none; font-family: inherit; line-height: 1.4;"><?= h($sms_template) ?></textarea>
                            <button type="button" class="btn btn-sm" onclick="copyTemplate('quoteTemplateText', this)" style="background-color: var(--color-primary); color: white; border: none; border-radius: 6px; padding: 5px; font-size: 0.75rem; font-weight: 600; cursor: pointer; width: 100%;">
                                Copy Mẫu Báo Giá
                            </button>
                        </div>
                    </div>

                    <div style="margin-top: 0.5rem; border-top: 1px solid var(--color-admin-border); padding-top: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <span style="font-size: 0.8rem; font-weight: 600; color: var(--color-admin-text-dark);">CẬP NHẬT TRẠNG THÁI:</span>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="quotes.php?action=status&id=<?= $quote['id'] ?>&status=read&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center; border-radius: 6px;">Đánh dấu: Đã Xem</a>
                            <a href="quotes.php?action=status&id=<?= $quote['id'] ?>&status=completed&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-primary btn-sm" style="flex: 1; text-align: center; border-radius: 6px; background-color: #10b981;">Đã Báo Giá Xong</a>
                        </div>
                        <a href="quotes.php?action=delete&id=<?= $quote['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-outline btn-sm" style="text-align: center; border-radius: 6px; border-color: #ef4444; color: #ef4444; margin-top: 0.5rem;" onclick="return confirm('Bạn có chắc chắn muốn xóa yêu cầu báo giá này?');">Xóa yêu cầu này</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php 
    endif;
endif;

// --------------------------------------------------------------------------
// Quotes List View
// --------------------------------------------------------------------------
if ($action === 'list'):
    $status_filter = $_GET['status'] ?? '';
    
    $sql = "SELECT * FROM quote_requests WHERE 1=1";
    $types = "";
    $params = [];
    
    if (in_array($status_filter, ['new', 'read', 'completed'])) {
        $sql .= " AND status = ?";
        $types .= "s";
        $params[] = $status_filter;
    }
    
    $sql .= " ORDER BY id DESC";
    
    $quotes = db_query($sql, !empty($types) ? $types : null, !empty($params) ? $params : null);
?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-file-invoice-dollar"></i> Yêu Cầu Báo Giá Từ Khách Hàng</h3>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <button onclick="exportTableToCSV('quotesTable', 'yeu-cau-bao-gia.csv')" class="btn btn-outline btn-sm" style="border-radius: 6px; border-color: var(--color-admin-border); color: var(--color-admin-text-dark); background-color: var(--color-white);"><i class="fa-solid fa-file-export"></i> Xuất Excel (CSV)</button>
                <a href="quotes.php" class="btn btn-sm <?= empty($status_filter) ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px;">Tất cả</a>
                <a href="quotes.php?status=new" class="btn btn-sm <?= $status_filter === 'new' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px;">Mới</a>
                <a href="quotes.php?status=read" class="btn btn-sm <?= $status_filter === 'read' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px;">Đã đọc</a>
                <a href="quotes.php?status=completed" class="btn btn-sm <?= $status_filter === 'completed' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 6px;">Đã báo giá</a>
            </div>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <!-- Quick search -->
            <div style="padding: 1.25rem 2rem; border-bottom: 1px solid var(--color-admin-border); background-color: #f8fafc;">
                <input type="text" id="quotesSearchInput" class="form-control" placeholder="Tìm kiếm nhanh khách hàng, SĐT, sản phẩm... (gõ để lọc tức thì)">
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
                <table id="quotesTable" class="admin-table responsive-cards-mobile">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Sản phẩm yêu cầu</th>
                            <th>Lời nhắn</th>
                            <th>Ngày gửi</th>
                            <th>Trạng thái</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($quotes && $quotes->num_rows > 0): ?>
                            <?php while ($q = $quotes->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="ID"><?= $q['id'] ?></td>
                                    <td data-label="Khách hàng" style="font-weight: 600;">
                                        <a href="quotes.php?action=view&id=<?= $q['id'] ?>" style="color: var(--color-secondary); text-decoration: underline;"><?= h($q['name']) ?></a>
                                    </td>
                                    <td data-label="Số điện thoại">
                                        <a href="tel:<?= h($q['phone']) ?>" style="color: var(--color-primary); font-weight: 600;"><i class="fa-solid fa-phone" style="font-size:0.8rem"></i> <?= h($q['phone']) ?></a>
                                        <?php if (!empty($q['phone'])): 
                                            $zalo_phone = preg_replace('/[^0-9]/', '', $q['phone']);
                                        ?>
                                            <a href="https://zalo.me/<?= h($zalo_phone) ?>" target="_blank" rel="noopener noreferrer" style="color: #0068ff; margin-left: 6px;" title="Nhắn tin Zalo"><i class="fa-solid fa-comments"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Sản phẩm">
                                        <div style="font-weight: 600; color: var(--color-secondary);"><?= h($q['product_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--color-admin-text-muted);">Mã: <?= h($q['product_key'] ?: '-') ?></div>
                                    </td>
                                    <td data-label="Lời nhắn">
                                        <div style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--color-admin-text-muted);">
                                            <?= h($q['message'] ?: '(Không có lời nhắn)') ?>
                                        </div>
                                    </td>
                                    <td data-label="Ngày gửi"><?= date('d/m/Y H:i', strtotime($q['created_at'])) ?></td>
                                    <td data-label="Trạng thái">
                                        <?php if ($q['status'] === 'new'): ?>
                                            <span class="badge badge-new">Mới</span>
                                        <?php elseif ($q['status'] === 'read'): ?>
                                            <span class="badge badge-read">Đã đọc</span>
                                        <?php else: ?>
                                            <span class="badge badge-closed" style="background-color: #10b981;">Đã báo giá</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Thao tác">
                                        <div class="actions-cell" style="justify-content: center;">
                                            <a href="quotes.php?action=view&id=<?= $q['id'] ?>" class="btn-icon-only btn-view" title="Xem chi tiết">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="quotes.php?action=delete&id=<?= $q['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon-only btn-delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa yêu cầu báo giá này?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem; color: var(--color-admin-text-muted);">
                                    Không có yêu cầu báo giá nào trong danh mục này.
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
