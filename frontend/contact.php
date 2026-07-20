<?php
$page_title = "Liên Hệ";
$page_desc = "Liên hệ Công ty Cổ phần Hóa chất Nhập khẩu Ngọc Ánh Dương tại Cần Thơ qua Hotline 0976828171 hoặc điền mẫu gửi yêu cầu báo giá phân bón và hóa chất.";
$active_page = 'contact';
include 'includes/head.php';
include 'includes/header.php';
require_once __DIR__ . '/../backend/db.php';

$form_errors = [];
$form_success = '';
$form_name = '';
$form_phone = '';
$form_email = '';
$form_subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';
$form_message = '';

// Check if quote request mode is enabled
$is_quote = (isset($_GET['action']) && $_GET['action'] === 'quote') || (isset($_POST['action']) && $_POST['action'] === 'quote');
$prod_name = isset($_GET['prod_name']) ? trim($_GET['prod_name']) : (isset($_POST['prod_name']) ? trim($_POST['prod_name']) : '');
$prod_key = isset($_GET['prod_key']) ? trim($_GET['prod_key']) : (isset($_POST['prod_key']) ? trim($_POST['prod_key']) : '');
$prod_link = isset($_GET['prod_link']) ? trim($_GET['prod_link']) : (isset($_POST['prod_link']) ? trim($_POST['prod_link']) : '');

if ($is_quote && !auth_is_logged_in()) {
    $redirect_url = $_SERVER['REQUEST_URI'];
    header('Location: login.php?redirect=' . urlencode($redirect_url));
    exit;
}

if (auth_is_logged_in() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $currentUser = auth_get_user();
    $form_name = $currentUser['full_name'] ?: $currentUser['username'];
    $form_phone = $currentUser['phone'];
    $form_email = $currentUser['email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_name = trim($_POST['name'] ?? '');
    $form_phone = trim($_POST['phone'] ?? '');
    $form_email = trim($_POST['email'] ?? '');
    $form_subject = trim($_POST['subject'] ?? '');
    $form_message = trim($_POST['message'] ?? '');

    // Honeypot spam prevention
    $honeypot = trim($_POST['website_hp'] ?? '');
    $time_lock = isset($_POST['form_time']) ? (int)$_POST['form_time'] : 0;

    if ($honeypot !== '') {
        // Silent pass for spam bots
        $form_success = 'Cảm ơn bạn! Yêu cầu của bạn đã được gửi thành công.';
        $form_name = $form_phone = $form_email = $form_message = '';
    } else {
        // Fast submit lock (less than 2s = bot)
        if (time() - $time_lock < 2) {
            $form_errors[] = 'Thao tác quá nhanh. Vui lòng gửi lại form sau 2 giây.';
        }

        if ($form_name === '') {
            $form_errors[] = 'Vui lòng nhập họ và tên.';
        }
        if ($form_phone === '') {
            $form_errors[] = 'Vui lòng nhập số điện thoại.';
        } elseif (!preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $form_phone)) {
            $form_errors[] = 'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam gồm 10 chữ số (ví dụ: 0976828171).';
        }
        if ($form_email !== '' && !filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
            $form_errors[] = 'Địa chỉ email không hợp lệ.';
        }
        if ($form_message === '') {
            $form_errors[] = 'Vui lòng nhập nội dung yêu cầu.';
        }

        if (empty($form_errors)) {
            if ($is_quote) {
                // Save to quote_requests table
                $saved = db_query(
                    'INSERT INTO quote_requests (product_name, product_key, product_link, name, phone, message) VALUES (?, ?, ?, ?, ?, ?)',
                    'ssssss',
                    [$prod_name, $prod_key, $prod_link, $form_name, $form_phone, $form_message]
                );
            } else {
                // Save to contact_messages table
                $saved = db_query(
                    'INSERT INTO contact_messages (name, phone, email, subject, message) VALUES (?, ?, ?, ?, ?)',
                    'sssss',
                    [$form_name, $form_phone, $form_email, $form_subject, $form_message]
                );
            }

            if ($saved === false) {
                $form_errors[] = 'Không thể lưu yêu cầu liên hệ. Vui lòng thử lại sau.';
            } else {
                $form_success = $is_quote ? 
                    'Cảm ơn bạn! Yêu cầu báo giá sản phẩm "' . h($prod_name) . '" đã được gửi thành công. Chúng tôi sẽ phản hồi lại ngay!' :
                    'Cảm ơn bạn! Yêu cầu liên hệ đã được gửi thành công. Chúng tôi sẽ phản hồi sớm nhất có thể.';
                $form_name = $form_phone = $form_email = $form_message = '';
                $form_subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';
            }
        }
    }
}
?>

<!-- Page Header Banner -->
<section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/about-hero.jpg') center/cover;">
    <div class="container">
        <h1>Liên Hệ</h1>
        <div class="breadcrumbs">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <span>Liên hệ</span>
        </div>
    </div>
</section>

<!-- Contact Info & Form Grid Section -->
<section class="section">
    <div class="container contact-grid">
        
        <!-- Left Column: Contact details card -->
        <div class="contact-info-panel">
            <!-- Mobile toggle header -->
            <button class="contact-info-toggle" id="contactInfoToggle" aria-label="Bấm để xem thông tin liên hệ" style="display: none;">
                <span>Thông Tin Liên Hệ</span>
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            
            <!-- Content wrapper (collapsible on mobile) -->
            <div class="contact-info-content" id="contactInfoContent">
                <h2>Thông Tin Liên Hệ</h2>
                <p>Vui lòng chọn phương thức liên hệ phù hợp nhất bên dưới để kết nối với bộ phận chăm sóc khách hàng và kỹ thuật của Ngọc Ánh Dương.</p>
                
                <div class="contact-info-list">
                <!-- Item 1: Office Address -->
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div class="contact-info-text">
                        <h4>Trụ Sở Chính</h4>
                        <p>Số 100 đường A3, Khu dân cư Phú An, Phường Hưng Phú, Quận Cái Răng, Thành phố Cần Thơ, Việt Nam.</p>
                    </div>
                </div>
                
                <!-- Item 2: Phone Hotline -->
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fa-solid fa-phone-volume"></i>
                    </div>
                    <div class="contact-info-text">
                        <h4>Điện Thoại / Hotline</h4>
                        <p><a href="tel:0976828171" style="font-weight: 700; font-size: 1.15rem; color: var(--color-accent);">0976.828.171</a> (Mr. Dương)</p>
                    </div>
                </div>
                
                <!-- Item 3: Email Support -->
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </div>
                    <div class="contact-info-text">
                        <h4>Hộp Thư Điện Tử</h4>
                        <p><a href="mailto:ngocanhduongchemical@gmail.com">ngocanhduongchemical@gmail.com</a></p>
                    </div>
                </div>

                <!-- Item 4: Legal tax identity -->
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div class="contact-info-text">
                        <h4>Mã Số Thuế Doanh Nghiệp</h4>
                        <p><strong>1801786436</strong> - Công ty Cổ phần Hóa chất Nhập khẩu Ngọc Ánh Dương.</p>
                    </div>
                </div>
            </div>
            </div>
        </div>
        
        <!-- Right Column: Interactive Form -->
        <div class="contact-form-panel">
            <?php if ($is_quote): ?>
                <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem; color: var(--color-primary);"><i class="fa-solid fa-file-invoice-dollar"></i> Yêu Cầu Báo Giá Sản Phẩm</h3>
                <p style="color: var(--color-dark-muted); margin-bottom: 1.5rem; font-size: 0.95rem;">Quý khách vui lòng cung cấp thông tin liên hệ. Chúng tôi sẽ lập bảng báo giá chi tiết và phản hồi ngay.</p>
                
                <!-- Product Information Panel -->
                <div class="product-quote-card" style="background: linear-gradient(135deg, rgba(11, 102, 35, 0.04) 0%, rgba(15, 76, 129, 0.04) 100%); border: 1px dashed var(--color-primary); border-radius: 12px; padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.25rem; box-shadow: var(--shadow-sm);">
                    <div style="font-size: 2.2rem; color: var(--color-primary);"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div>
                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 2px;">Sản phẩm yêu cầu báo giá</span>
                        <h4 style="margin: 0; font-size: 1.2rem; color: var(--color-secondary); font-weight: 700;"><?php echo h($prod_name); ?></h4>
                        <?php if ($prod_key !== ''): ?>
                            <span style="font-size: 0.85rem; color: var(--color-dark-muted);">Mã sản phẩm: <strong style="color: var(--color-dark);"><?php echo h($prod_key); ?></strong></span>
                        <?php endif; ?>
                        <?php if ($prod_link !== ''): ?>
                            <div style="margin-top: 4px;"><a href="<?php echo h($prod_link); ?>" target="_blank" style="font-size: 0.8rem; color: var(--color-secondary); text-decoration: underline;"><i class="fa-solid fa-up-right-from-square"></i> Xem lại chi tiết sản phẩm</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem; color: var(--color-secondary);">Gửi Tin Nhắn Cho Chúng Tôi</h3>
                <p style="color: var(--color-dark-muted); margin-bottom: 2rem; font-size: 0.95rem;">Quý khách vui lòng điền mẫu dưới đây để chúng tôi hỗ trợ nhanh nhất.</p>
            <?php endif; ?>
            
            <?php if (!empty($form_success)): ?>
                <div style="background: #e6f7e8; border: 1px solid #8fcd97; color: #1f5d2b; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease;">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.5rem; color: #10b981;"></i>
                    <div><?php echo h($form_success); ?></div>
                </div>
            <?php elseif (!empty($form_errors)): ?>
                <div style="background: #fff0f0; border: 1px solid #e09b9b; color: #7a1f1f; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; animation: slideDown 0.3s ease;">
                    <div style="font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-exclamation"></i> Có lỗi xảy ra:</div>
                    <ul style="margin:0; padding-left: 1.25rem; font-size: 0.9rem;">
                        <?php foreach ($form_errors as $error): ?>
                            <li><?php echo h($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="contactForm" method="POST" action="contact.php<?php echo isset($_GET['action']) ? '?action='.urlencode($_GET['action']) : ''; ?>">
                <!-- Hidden Quoting fields & spam locks -->
                <input type="hidden" name="action" value="<?php echo $is_quote ? 'quote' : 'contact'; ?>">
                <input type="hidden" name="prod_name" value="<?php echo h($prod_name); ?>">
                <input type="hidden" name="prod_key" value="<?php echo h($prod_key); ?>">
                <input type="hidden" name="prod_link" value="<?php echo h($prod_link); ?>">
                <input type="hidden" name="form_time" value="<?php echo time(); ?>">
                
                <!-- Honeypot field (hidden from users) -->
                <div style="display: none;">
                    <label for="formWebsiteHp">Do not fill this field if you are human</label>
                    <input type="text" id="formWebsiteHp" name="website_hp" value="">
                </div>

                <div class="form-group">
                    <label for="formName">Họ và tên của bạn <span style="color: red;">*</span></label>
                    <input type="text" id="formName" name="name" class="form-control" placeholder="Nguyễn Văn A" required value="<?php echo h($form_name); ?>">
                </div>
                
                <div class="form-group form-grid-2col">
                    <div>
                        <label for="formPhone">Số điện thoại <span style="color: red;">*</span></label>
                        <input type="tel" id="formPhone" name="phone" class="form-control" placeholder="0901xxxxxx" required value="<?php echo h($form_phone); ?>">
                    </div>
                    <div>
                        <label for="formEmail">Hộp thư Email</label>
                        <input type="email" id="formEmail" name="email" class="form-control" placeholder="email@gmail.com" value="<?php echo h($form_email); ?>">
                    </div>
                </div>
                
                <?php if (!$is_quote): ?>
                    <div class="form-group">
                        <label for="formSubject">Tiêu đề yêu cầu</label>
                        <input type="text" id="formSubject" name="subject" class="form-control" placeholder="Ví dụ: Báo giá sỉ phân bón Tăng Lực X3" value="<?php echo h($form_subject); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="formMessage">Nội dung chi tiết <?php echo $is_quote ? '' : '<span style="color: red;">*</span>'; ?></label>
                    <textarea id="formMessage" name="message" class="form-control" placeholder="<?php echo $is_quote ? 'Ví dụ: Cần báo giá số lượng 50 bao giao hàng tại quận Cái Răng...' : 'Quý khách vui lòng cung cấp quy cách đặt hàng, số lượng dự kiến hoặc yêu cầu kỹ thuật cần hỗ trợ...'; ?>" <?php echo $is_quote ? '' : 'required'; ?>><?php echo h($form_message); ?></textarea>
                </div>
                
                <?php if ($is_quote): ?>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; border-radius: 10px; margin-top: 1rem; font-weight: 700; background-color: var(--color-primary); box-shadow: var(--shadow-glow);"><i class="fa-solid fa-file-invoice-dollar"></i> NHẬN BÁO GIÁ NGAY <i class="fa-solid fa-paper-plane" style="margin-left: 5px;"></i></button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; border-radius: 10px; margin-top: 1rem; font-weight: 700;"><i class="fa-solid fa-paper-plane"></i> GỬI YÊU CẦU LIÊN HỆ</button>
                <?php endif; ?>
            </form>
        </div>
        
    </div>
</section>

<!-- Google Maps Section -->
<section class="section map-section">
    <div class="container">
        <div class="map-container">
            <!-- Embedded map pointing to Ngọc Ánh Dương Company -->
            <iframe src="https://maps.google.com/maps?q=C%C3%94NG+TY+C%E1%BB%9ED+PH%E1%BA%A6N+H%C3%93A+CH%E1%BA%A4T+NH%E1%BA%ACP+KH%E1%BA%A8U+NG%E1%BB%8CC+%C3%81NH+D%C6%AF%C6%A0NG%2C+100+Duong+A3+KDC+Phu+An+Hung+Phu+Can+Tho&t=&z=16&ie=UTF8&iwloc=&output=embed" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</section>

<script>
(function(){
    var toggle = document.getElementById('contactInfoToggle');
    var content = document.getElementById('contactInfoContent');
    
    // Show toggle button on mobile, hide it on desktop
    function updateToggleVisibility(){
        if (window.innerWidth <= 768) {
            toggle.style.display = 'flex';
        } else {
            toggle.style.display = 'none';
            content.classList.add('open');
        }
    }
    
    // Initial setup
    updateToggleVisibility();
    
    // Update on window resize
    window.addEventListener('resize', function(){
        updateToggleVisibility();
    });
    
    // Toggle click handler
    if (toggle && content) {
        toggle.addEventListener('click', function(e){
            e.preventDefault();
            toggle.classList.toggle('open');
            content.classList.toggle('open');
        });
    }
})();
</script>

<?php
include 'includes/footer.php';
?>
