<?php
$page_title = "Quản Lý Người Dùng";
$active_admin_tab = "users";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --------------------------------------------------------------------------
// Toggle Role (Admin <-> Customer)
// --------------------------------------------------------------------------
if ($action === 'change_role' && $id > 0) {
    $new_role = $_GET['role'] ?? '';
    
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } elseif (!in_array($new_role, ['admin', 'customer'])) {
        $error = "Vai trò không hợp lệ.";
    } elseif ($id === (int)$_SESSION['user_id']) {
        // Prevent changing own role
        $error = "Bạn không thể tự hạ cấp hoặc thay đổi vai trò của chính mình để tránh mất quyền truy cập hệ trị.";
    } else {
        $update = db_query("UPDATE users SET role = ? WHERE id = ?", "si", [$new_role, $id]);
        if ($update) {
            $success = "Cập nhật vai trò người dùng thành công!";
        } else {
            $error = "Không thể cập nhật vai trò người dùng.";
        }
    }
    $action = 'list';
}

// --------------------------------------------------------------------------
// Delete User
// --------------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $error = "Yêu cầu không hợp lệ (Lỗi CSRF token).";
    } elseif ($id === (int)$_SESSION['user_id']) {
        $error = "Bạn không thể xóa tài khoản hiện tại bạn đang đăng nhập.";
    } else {
        $delete = db_query("DELETE FROM users WHERE id = ?", "i", [$id]);
        if ($delete) {
            $success = "Đã xóa tài khoản người dùng thành công!";
        } else {
            $error = "Không thể xóa tài khoản người dùng.";
        }
    }
    $action = 'list';
}

// Fetch all users
$users = db_query("SELECT * FROM users ORDER BY role ASC, id DESC");
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-users-gear"></i> Quản Lý Tài Khoản Thành Viên</h3>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        
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
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Họ và tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Vai trò hiện tại</th>
                        <th>Ngày đăng ký</th>
                        <th style="width: 200px; text-align: center;">Thay đổi vai trò / Tác vụ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users && $users->num_rows > 0): ?>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td style="font-weight: 600;"><code><?= h($u['username']) ?></code></td>
                                <td><?= h($u['full_name'] ?: '(Chưa cập nhật)') ?></td>
                                <td><?= h($u['email']) ?></td>
                                <td><?= h($u['phone'] ?: '-') ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge badge-admin"><i class="fa-solid fa-user-shield"></i> Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-customer"><i class="fa-solid fa-user"></i> Khách hàng</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <div class="actions-cell" style="justify-content: center;">
                                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <a href="users.php?action=change_role&id=<?= $u['id'] ?>&role=customer&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-outline btn-sm" style="border-color: var(--color-primary); color: var(--color-primary);" onclick="return confirm('Bạn có chắc muốn hạ quyền tài khoản này xuống Khách hàng?');">
                                                    Hạ cấp Khách hàng
                                                </a>
                                            <?php else: ?>
                                                <a href="users.php?action=change_role&id=<?= $u['id'] ?>&role=admin&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-secondary btn-sm" style="background-color: var(--color-secondary); color: white;" onclick="return confirm('Bạn có chắc muốn cấp quyền Admin cho tài khoản này?');">
                                                    Nâng cấp Admin
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="users.php?action=delete&id=<?= $u['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon-only btn-delete" style="margin-left: 0.5rem;" title="Xóa tài khoản" onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tài khoản này?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: var(--color-admin-text-muted); font-style: italic;">Đang đăng nhập (Bản thân)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem; color: var(--color-admin-text-muted);">
                                Không có người dùng nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
