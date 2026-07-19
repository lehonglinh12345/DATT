<?php
// frontend/profile.php
$active_page = 'profile';
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

if (!auth_is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = auth_get_user();
$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($full_name)) {
        $error_msg = 'Vui lòng nhập họ và tên.';
    } else {
        $update = db_query(
            "UPDATE users SET full_name = ?, phone = ? WHERE id = ?",
            "ssi",
            [$full_name, $phone, $user['id']]
        );

        if ($update) {
            $success_msg = 'Cập nhật thông tin thành công!';
            $_SESSION['full_name'] = $full_name;
            $_SESSION['phone'] = $phone;
            $user = auth_get_user(); // refresh
        } else {
            $error_msg = 'Đã có lỗi xảy ra, vui lòng thử lại.';
        }
    }
}
?>

<div class="page-banner" style="background-image: url('images/banner1.jpg'); padding: 4rem 0; text-align: center; color: white;">
    <div class="container">
        <h1 style="font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Trang Cá Nhân</h1>
    </div>
</div>

<div class="container" style="padding: 4rem 0; max-width: 600px;">
    <div class="profile-card" style="background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <?php if (empty($user['phone'])): ?>
            <div style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #ffeeba;">
                <strong><i class="fa-solid fa-triangle-exclamation"></i> Chú ý:</strong> 
                Bạn chưa cập nhật Số điện thoại. Xin vui lòng cập nhật để Admin có thể liên hệ báo giá nhanh nhất.
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <?= h($error_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <?= h($success_msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom: 1.5rem;">
                <label for="username" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Tên Đăng Nhập</label>
                <input type="text" id="username" value="<?= h($user['username']) ?>" disabled style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email</label>
                <input type="email" id="email" value="<?= h($user['email']) ?>" disabled style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="full_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Họ và Tên <span style="color: red;">*</span></label>
                <input type="text" id="full_name" name="full_name" value="<?= h($user['full_name']) ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="phone" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Số Điện Thoại</label>
                <input type="tel" id="phone" name="phone" value="<?= h($user['phone']) ?>" placeholder="Nhập số điện thoại của bạn..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem; font-size: 1.1rem; border-radius: 8px;">
                Cập Nhật Thông Tin <i class="fa-solid fa-floppy-disk" style="margin-left: 0.5rem;"></i>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
