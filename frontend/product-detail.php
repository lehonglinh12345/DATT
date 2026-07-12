<?php
$product_key = trim($_GET['id'] ?? '');
require_once __DIR__ . '/../backend/db.php';

$product = null;
$product_images = [];

if ($product_key !== '') {
    // Increment actual views count
    db_query('UPDATE products SET views = views + 1 WHERE product_key = ?', 's', [$product_key]);

    $productResult = db_query(
        'SELECT p.*, c.slug AS category_slug, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.product_key = ?',
        's',
        [$product_key]
    );
    if ($productResult instanceof mysqli_result) {
        $product = $productResult->fetch_assoc();
    }
}

if ($product) {
    $product_images[] = $product['image'];
    $addImagesRes = db_query('SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC', 'i', [$product['id']]);
    if ($addImagesRes instanceof mysqli_result) {
        while ($img_row = $addImagesRes->fetch_assoc()) {
            $product_images[] = $img_row['image_path'];
        }
    }
}

$page_title = $product ? $product['name'] : 'Sản phẩm không tìm thấy';
$page_desc = $product ? ($product['description'] ?: 'Chi tiết sản phẩm') : 'Sản phẩm bạn yêu cầu hiện không tồn tại hoặc đã được cập nhật.';
$active_page = 'products';
include 'includes/head.php';
include 'includes/header.php';
$is_logged_in = auth_is_logged_in();

if (!$product) {
    ?>
    <section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/hero-bg.jpg') center/cover;">
        <div class="container">
            <h1>Sản phẩm không tìm thấy</h1>
            <div class="breadcrumbs">
                <a href="index.php">Trang chủ</a>
                <span>/</span>
                <a href="products.php">Sản phẩm</a>
                <span>/</span>
                <span>Không tìm thấy</span>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="container error-product-container">
            <h2>Rất tiếc, sản phẩm bạn tìm kiếm không tồn tại.</h2>
            <p>Vui lòng quay lại trang danh mục hoặc thử lại bằng một sản phẩm khác.</p>
            <a href="products.php" class="btn btn-primary">Quay lại danh sách sản phẩm</a>
        </div>
    </section>
    <?php
    include 'includes/footer.php';
    return;
}

$related_products = [];
$relatedResult = db_query(
    'SELECT p.product_key, p.name, p.price, p.image, p.badge, p.badge_class, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.product_key <> ? ORDER BY p.name ASC LIMIT 4',
    'is',
    [$product['category_id'], $product['product_key']]
);
if ($relatedResult instanceof mysqli_result) {
    while ($row = $relatedResult->fetch_assoc()) {
        $related_products[] = $row;
    }
}

$content_detail = '<p>' . htmlspecialchars($product['description']) . '</p>';
$content_usage = '<p>Để nhận hướng dẫn kỹ thuật sử dụng và liều lượng phù hợp, vui lòng liên hệ Hotline hoặc gửi yêu cầu báo giá.</p>';
$content_technical = '<ul class="tech-info-list"><li><strong>Loại sản phẩm:</strong> ' . htmlspecialchars($product['badge'] ?: 'Nông nghiệp') . '</li><li><strong>Xuất xứ:</strong> ' . htmlspecialchars($product['origin']) . '</li><li><strong>Giá:</strong> ' . htmlspecialchars($product['price']) . '</li></ul><p>Thông tin kỹ thuật chi tiết sẽ được gửi theo yêu cầu khách hàng.</p>';
?>

<section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/hero-bg.jpg') center/cover;">
    <div class="container">
        <h1>Chi Tiết Sản Phẩm</h1>
        <div class="breadcrumbs">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <a href="products.php">Sản phẩm</a>
            <span>/</span>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
    </div>
</section>

<section class="section detail-section">
    <div class="container">
        <div class="prod-detail-grid">
            <div class="detail-img-box">
                <?php if (count($product_images) > 1): ?>
                    <div class="detail-slider-wrapper">
                        <div class="detail-slides">
                            <?php foreach ($product_images as $idx => $img_path): ?>
                                <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="detail-slide <?php echo $idx === 0 ? 'active' : ''; ?>">
                            <?php endforeach; ?>
                        </div>
                        <button class="slider-arrow prev-arrow"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="slider-arrow next-arrow"><i class="fa-solid fa-chevron-right"></i></button>
                        <div class="slider-dots">
                            <?php foreach ($product_images as $idx => $img_path): ?>
                                <span class="slider-dot <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="main-detail-img">
                <?php endif; ?>
                
                <?php if (!empty($product['badge'])): ?>
                    <span class="prod-badge <?php echo htmlspecialchars($product['badge_class']); ?>"><?php echo htmlspecialchars($product['badge']); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="prod-detail-info">
                <span class="detail-meta-cat"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="prod-status-tag" style="margin-bottom: 0.5rem;">
                    Xuất xứ: <strong><?php echo htmlspecialchars($product['origin']); ?></strong>
                </p>
                <div class="detail-stats-row" style="display: flex; gap: 1.25rem; align-items: center; font-size: 0.88rem; color: var(--color-dark-muted); margin-bottom: 1.25rem;">
                    <span><i class="fa-solid fa-eye" style="color: var(--color-secondary);"></i> <?php echo number_format($product['views']); ?> lượt xem</span>
                    <span><i class="fa-solid fa-cart-shopping" style="color: var(--color-secondary);"></i> Đã bán <?php echo number_format($product['sales_count']); ?> sản phẩm</span>
                </div>
                <div class="detail-price"><?php echo htmlspecialchars($product['price']); ?></div>
                
                <div class="detail-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <div class="detail-actions">
                    <a href="tel:0976828171" class="btn btn-primary btn-call-now"><i class="fa-solid fa-phone-volume"></i> Gọi Ngay: 0976.828.171</a>
                    <?php
                        $productQuoteUrl = 'contact.php?action=quote&prod_name=' . urlencode($product['name']) . '&prod_key=' . urlencode($product['product_key']) . '&prod_link=' . urlencode('product-detail.php?id=' . $product['product_key']);
                        $quoteLink = $is_logged_in ? $productQuoteUrl : 'login.php?redirect=' . urlencode($productQuoteUrl);
                    ?>
                    <a href="<?php echo $quoteLink; ?>" class="btn btn-outline btn-quote"><i class="fa-solid fa-envelope"></i> Báo giá</a>
                </div>
            </div>
        </div>
        
        <?php if (!$is_logged_in): ?>
            <div class="product-detail-auth-note" style="margin: 1.5rem 0; padding: 1rem 1.25rem; border-radius: 12px; background: #fff7ed; color: #92400e; border: 1px solid #fcd34d;">
                <strong>Lưu ý:</strong> Mời quý khách đăng nhập để gửi yêu cầu báo giá nhanh và xem lại trạng thái đơn hàng dễ dàng.
                <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="color: #92400e; text-decoration: underline; font-weight: 600;">Đăng nhập ngay</a>.
            </div>
        <?php endif; ?>
        <div class="product-tabs-container">
            <div class="detail-tabs-nav">
                <button class="tab-btn active" data-tab="tab-desc">Mô tả sản phẩm</button>
                <button class="tab-btn" data-tab="tab-usage">Hướng dẫn</button>
                <button class="tab-btn" data-tab="tab-tech">Thông số kỹ thuật</button>
            </div>
            <div class="tab-content">
                <div class="tab-pane active" id="tab-desc">
                    <?php echo $content_detail; ?>
                </div>
                <div class="tab-pane" id="tab-usage">
                    <?php echo $content_usage; ?>
                </div>
                <div class="tab-pane" id="tab-tech">
                    <?php echo $content_technical; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($related_products)): ?>
            <div class="related-products-section">
                <div class="text-center section-title-wrapper">
                    <h2>Sản Phẩm Cùng Loại Khác</h2>
                    <div class="title-line"></div>
                </div>
                
                <div class="product-grid-related">
                    <?php foreach ($related_products as $p_data): ?>
                        <div class="product-card">
                            <div class="prod-img-wrapper">
                                <img src="<?php echo htmlspecialchars($p_data['image']); ?>" alt="<?php echo htmlspecialchars($p_data['name']); ?>" class="prod-img">
                                <?php if (!empty($p_data['badge'])): ?>
                                    <span class="prod-badge <?php echo htmlspecialchars($p_data['badge_class']); ?>"><?php echo htmlspecialchars($p_data['badge']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="prod-body">
                                <span class="prod-cat"><?php echo htmlspecialchars($p_data['category_name']); ?></span>
                                <h3 class="prod-title"><a href="product-detail.php?id=<?php echo htmlspecialchars($p_data['product_key']); ?>"><?php echo htmlspecialchars($p_data['name']); ?></a></h3>
                                <div class="prod-footer">
                                    <span class="prod-price"><?php echo htmlspecialchars($p_data['price']); ?></span>
                                    <a href="product-detail.php?id=<?php echo htmlspecialchars($p_data['product_key']); ?>" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const tabButtons = document.querySelectorAll(".tab-btn");
    const tabPanes = document.querySelectorAll(".tab-pane");

    tabButtons.forEach(button => {
        button.addEventListener("click", function() {
            const targetTab = this.getAttribute("data-tab");

            tabButtons.forEach(btn => btn.classList.remove("active"));
            tabPanes.forEach(pane => pane.classList.remove("active"));

            this.classList.add("active");
            document.getElementById(targetTab).classList.add("active");
        });
    });

    // Slider logic
    const slides = document.querySelectorAll(".detail-slide");
    const prevBtn = document.querySelector(".prev-arrow");
    const nextBtn = document.querySelector(".next-arrow");
    const dots = document.querySelectorAll(".slider-dot");
    let activeIdx = 0;

    if (slides.length > 1) {
        function showSlide(index) {
            slides.forEach(s => s.classList.remove("active"));
            dots.forEach(d => d.classList.remove("active"));
            
            slides[index].classList.add("active");
            dots[index].classList.add("active");
            activeIdx = index;
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", function() {
                let nextIdx = (activeIdx + 1) % slides.length;
                showSlide(nextIdx);
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener("click", function() {
                let prevIdx = (activeIdx - 1 + slides.length) % slides.length;
                showSlide(prevIdx);
            });
        }

        dots.forEach(dot => {
            dot.addEventListener("click", function() {
                let idx = parseInt(this.getAttribute("data-index"));
                showSlide(idx);
            });
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>