
<footer class="footer">

    <!-- Dải lúa trang trí trên cùng -->
    <div class="footer-harvest-bar">
        <div class="harvest-line"></div>
        <div class="harvest-icons">
            <i class="fa-solid fa-seedling"></i>
            <i class="fa-solid fa-leaf"></i>
            <i class="fa-solid fa-flask-vial"></i>
            <i class="fa-solid fa-leaf"></i>
            <i class="fa-solid fa-seedling"></i>
        </div>
        <div class="harvest-line"></div>
    </div>

    <div class="container footer-grid">

        <!-- Col 1: About -->
        <div class="footer-col footer-about">
            <a href="index.php" class="logo footer-logo">
                <div class="logo-icon">
                    <img src="images/logo.jpg" alt="Ngọc Ánh Dương" class="logo-bg">
                    
                </div>
                <div class="logo-text">
                    <span class="brand-name">NGỌC ÁNH DƯƠNG</span>
                    <span class="brand-sub">IMPORT CHEMICAL</span>
                </div>
            </a>

            <p class="company-desc">
                Chuyên nhập khẩu và phân phối hóa chất công nghiệp, vật tư nông nghiệp,
                phân bón cao cấp và chế phẩm sinh học — đồng hành cùng nông dân
                hướng tới nền nông nghiệp bền vững.
            </p>

            <div class="company-tax">
                <div class="tax-row"><i class="fa-solid fa-file-invoice"></i><span><strong>MST:</strong> 1801786436</span></div>
                <div class="tax-row"><i class="fa-solid fa-user-tie"></i><span><strong>Đại diện:</strong> Nguyễn Ngọc Ánh Dương</span></div>
            </div>

            <div class="social-links">
                <a href="#" aria-label="Facebook" class="social-fb"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="Youtube"  class="social-yt"><i class="fa-brands fa-youtube"></i></a>
                <a href="#" aria-label="Zalo"     class="social-zl zalo-icon">Zalo</a>
            </div>
        </div>

        <!-- Col 2: Liên kết nhanh -->
        <div class="footer-col">
            <h3 class="footer-heading">
                <i class="fa-solid fa-compass"></i> Liên Kết Nhanh
            </h3>
            <ul class="footer-links">
                <li><a href="index.php"><span class="fl-dot"></span> Trang chủ</a></li>
                <li><a href="about.php"><span class="fl-dot"></span> Giới thiệu</a></li>
                <li><a href="products.php"><span class="fl-dot"></span> Sản phẩm</a></li>
                <li><a href="news.php"><span class="fl-dot"></span> Tin tức</a></li>
                <li><a href="contact.php"><span class="fl-dot"></span> Liên hệ</a></li>
            </ul>
        </div>

        <!-- Col 3: Danh mục sản phẩm -->
        <div class="footer-col">
            <h3 class="footer-heading">
                <i class="fa-solid fa-layer-group"></i> Danh Mục Sản Phẩm
            </h3>
            <ul class="footer-links">
                <li><a href="products.php?category=che-pham-vi-sinh-sinh-hoc"><span class="fl-dot dot-teal"></span> Chế phẩm vi sinh, sinh học</a></li>
                <li><a href="products.php?category=phan-bon-goc"><span class="fl-dot dot-green"></span> Phân bón gốc</a></li>
                <li><a href="products.php?category=phan-bon-la"><span class="fl-dot dot-orange"></span> Phân bón lá</a></li>
                <li><a href="products.php?category=phong-tru-con-trung-oc-hai"><span class="fl-dot dot-red"></span> Phòng trừ côn trùng, ốc hại</a></li>
                <li><a href="products.php?category=phong-tru-nam-hai"><span class="fl-dot dot-blue"></span> Phòng trừ nấm hại</a></li>
            </ul>
        </div>

        <!-- Col 4: Liên hệ -->
        <div class="footer-col footer-contact">
            <h3 class="footer-heading">
                <i class="fa-solid fa-map-location-dot"></i> Liên Hệ
            </h3>
            <ul class="footer-contact-list">
                <li>
                    <div class="fc-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <span>Số 100 đường A3, KDC Phú An,<br>P. Hưng Phú, Q. Cái Răng, TP. Cần Thơ</span>
                </li>
                <li>
                    <div class="fc-icon"><i class="fa-solid fa-phone"></i></div>
                    <a href="tel:0976828171">0976.828.171</a>
                </li>
                <li>
                    <div class="fc-icon"><i class="fa-solid fa-envelope"></i></div>
                    <a href="mailto:ngocanhduongchemical@gmail.com">ngocanhduongchemical@gmail.com</a>
                </li>
                <li>
                    <div class="fc-icon"><i class="fa-solid fa-clock"></i></div>
                    <span>Thứ 2 – Thứ 7 &nbsp;|&nbsp; 7:30 – 17:00</span>
                </li>
            </ul>

            <a href="contact.php" class="footer-cta-btn">
                <i class="fa-solid fa-phone-volume"></i> Liên Hệ Tư Vấn Ngay
            </a>
        </div>
    </div>

    <!-- Copyright -->
    <div class="footer-bottom">
        <div class="container footer-bottom-container">
            <p>&copy; 2026 <strong>Ngọc Ánh Dương Import Chemical</strong>. All rights reserved.</p>
            <div class="footer-policy">
                <a href="#">Chính sách bảo mật</a>
                <span class="fp-dot">•</span>
                <a href="#">Điều khoản sử dụng</a>
            </div>
        </div>
    </div>
</footer>

<?php include __DIR__ . '/contact-widget.php'; ?>
<?php if (auth_is_logged_in()): ?>
    <?php include __DIR__ . '/chatbot-widget.php'; ?>
<?php endif; ?>

<!-- Google Translate System -->
<div id="google_translate_element" style="display:none;"></div>
<style>
    /* Hide the google translate toolbar that sometimes appears at the top */
    .skiptranslate iframe { display: none !important; }
    body { top: 0 !important; }
    /* Hide the google translate hover tooltip */
    #goog-gt-tt { display: none !important; }
</style>
<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'vi', autoDisplay: false}, 'google_translate_element');
}
</script>
<script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<!-- Scroll to Top -->
<button id="scrollToTopBtn" class="scroll-to-top" aria-label="Cuộn lên đầu trang">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<script src="js/main.js"></script>
<script src="js/contact-widget.js"></script>
<?php if (auth_is_logged_in()): ?>
    <script src="js/chatbot.js"></script>
<?php endif; ?>
<script>
(function(){
    // Language Switcher Logic
    var langBtns = document.querySelectorAll('.lang-btn');
    var currentLang = 'vi';
    var match = document.cookie.match(new RegExp('(^| )googtrans=([^;]+)'));
    if (match) {
        if (match[2] === '/vi/en' || match[2] === '/auto/en') {
            currentLang = 'en';
        }
    }
    langBtns.forEach(function(btn) {
        if (btn.getAttribute('data-lang') === currentLang) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
        btn.addEventListener('click', function() {
            var lang = this.getAttribute('data-lang');
            if (lang === 'en') {
                document.cookie = "googtrans=/vi/en; path=/";
                document.cookie = "googtrans=/vi/en; domain=" + window.location.hostname + "; path=/";
            } else {
                document.cookie = "googtrans=/vi/vi; path=/";
                document.cookie = "googtrans=/vi/vi; domain=" + window.location.hostname + "; path=/";
                // Optionally clear it
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=" + window.location.hostname + "; path=/;";
            }
            window.location.reload();
        });
    });

    try {
        // User menu fallback - enhanced version
        var userBtn = document.getElementById('userMenuBtn');
        var userDropdown = document.getElementById('userDropdown');
        if (userBtn && userDropdown) {
            // Toggle on button click
            userBtn.addEventListener('click', function(e){ 
                e.preventDefault();
                e.stopPropagation(); 
                userDropdown.classList.toggle('open'); 
                userBtn.setAttribute('aria-expanded', userDropdown.classList.contains('open') ? 'true' : 'false');
            });
            // Close when clicking outside
            document.addEventListener('click', function(e){ 
                if (!userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('open');
                    userBtn.setAttribute('aria-expanded', 'false');
                }
            });
            // Close on Escape
            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape' && userDropdown.classList.contains('open')) {
                    userDropdown.classList.remove('open');
                    userBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Mobile drawer fallback
        var mobileToggle = document.getElementById('mobileMenuToggle');
        var mobileNav = document.getElementById('mobileNav');
        var mobileClose = document.getElementById('mobileMenuClose');
        var mobileOverlay = document.getElementById('mobileOverlay');

        function openMobile(){
            if (mobileNav) { mobileNav.classList.add('open'); mobileNav.setAttribute('aria-hidden','false'); try{ mobileNav.inert = false;}catch(e){ mobileNav.removeAttribute('inert'); } }
            if (mobileOverlay) mobileOverlay.classList.add('open');
            if (mobileToggle) mobileToggle.setAttribute('aria-expanded','true');
            document.body.style.overflow = 'hidden';
        }

        function closeMobile(){
            if (mobileNav) { mobileNav.classList.remove('open'); mobileNav.setAttribute('aria-hidden','true'); try{ mobileNav.inert = true;}catch(e){ mobileNav.setAttribute('inert',''); } }
            if (mobileOverlay) mobileOverlay.classList.remove('open');
            if (mobileToggle) mobileToggle.setAttribute('aria-expanded','false');
            document.body.style.overflow = '';
        }

        if (mobileToggle) mobileToggle.addEventListener('click', function(e){ e.stopPropagation(); openMobile(); });
        if (mobileClose) mobileClose.addEventListener('click', function(e){ e.stopPropagation(); closeMobile(); });
        if (mobileOverlay) mobileOverlay.addEventListener('click', function(e){ closeMobile(); });

        // Close on Escape key
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { closeMobile(); if (userDropdown) userDropdown.classList.remove('open'); } });
    } catch (err) {
        console && console.error && console.error('UI init error', err);
    }
})();

window.flyToCart = function(imgElement) {
    const cartBtn = document.querySelector('a[href="cart.php"].user-menu-btn');
    if (!cartBtn || !imgElement) return;
    
    const cartIcon = cartBtn.querySelector('i');
    const imgRect = imgElement.getBoundingClientRect();
    const cartRect = cartBtn.getBoundingClientRect();
    
    const flyingImg = imgElement.cloneNode();
    flyingImg.style.position = 'fixed';
    flyingImg.style.left = imgRect.left + 'px';
    flyingImg.style.top = imgRect.top + 'px';
    flyingImg.style.width = imgRect.width + 'px';
    flyingImg.style.height = imgRect.height + 'px';
    flyingImg.style.zIndex = '9999';
    flyingImg.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
    flyingImg.style.borderRadius = '50%';
    flyingImg.style.pointerEvents = 'none';
    flyingImg.style.boxShadow = '0 10px 25px rgba(0,0,0,0.2)';
    
    document.body.appendChild(flyingImg);
    
    setTimeout(() => {
        flyingImg.style.left = (cartRect.left + cartRect.width / 2 - 10) + 'px';
        flyingImg.style.top = (cartRect.top + cartRect.height / 2 - 10) + 'px';
        flyingImg.style.width = '20px';
        flyingImg.style.height = '20px';
        flyingImg.style.opacity = '0';
    }, 50);
    
    setTimeout(() => {
        flyingImg.remove();
        if (cartIcon) {
            cartIcon.style.transform = 'scale(1.4)';
            cartIcon.style.transition = 'transform 0.3s ease';
            setTimeout(() => {
                cartIcon.style.transform = 'scale(1)';
            }, 300);
        }
    }, 850);
};

// Global Add to Cart Logic for list pages
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-add-cart-list')) {
        e.preventDefault();
        const btn = e.target.closest('.btn-add-cart-list');
        const productId = btn.getAttribute('data-id');
        
        const card = btn.closest('.product-card');
        const imgElement = card ? card.querySelector('.prod-img') : null;
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', 1);

        fetch('ajax_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.textContent = data.total_items;
                    badge.style.display = 'block';
                }
                
                if (imgElement) {
                    window.flyToCart(imgElement);
                }
                
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check"></i>';
                btn.style.backgroundColor = '#059669';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.backgroundColor = '#10b981';
                }, 1500);
            } else {
                if (data.redirect) {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    alert('Lỗi: ' + data.message);
                }
            }
        })
        .catch(err => {
            console.error(err);
        });
    }
});
// Global Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.auto-hide-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 500);
        }, 5000);
    });
});
</script>
</body>
</html>