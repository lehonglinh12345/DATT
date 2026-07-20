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
    <section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/about-hero.jpg') center/cover;">
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

function formatProductDescription($text) {
    $text = htmlspecialchars($text);
    $lines = explode("\n", $text);
    $html = '';
    $inList = false;
    $inNumList = false;
    
    $expectingList = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // 1. Nhận diện tiêu đề (Heading)
        if (preg_match('/^(Giới thiệu sản phẩm|Giới thiệu|Thành phần|Thành phần chính|Cơ chế hoạt động|Cơ chế tác động|Công dụng|Công dụng chính|Hướng dẫn sử dụng|Lưu ý|Đặc tính|Đặc điểm|Liều lượng)[:]?$/iu', $line)) {
            if ($inList) { $html .= "</ul>"; $inList = false; }
            if ($inNumList) { $html .= "</ol>"; $inNumList = false; }
            $html .= "<h4 class='prod-desc-heading'>" . preg_replace('/\:$/', '', $line) . "</h4>";
            
            if (preg_match('/(Công dụng|Thành phần|Đặc tính|Đặc điểm|Liều lượng|Lưu ý|Hướng dẫn)/iu', $line)) {
                $expectingList = true;
            } else {
                $expectingList = false;
            }
        } 
        // 2. Nhận diện danh sách có dấu chấm/gạch ngang rõ ràng
        elseif (preg_match('/^[\-\+\*]\s+(.*)$/', $line, $matches)) {
            if ($inNumList) { $html .= "</ol>"; $inNumList = false; }
            if (!$inList) { $html .= "<ul class='prod-desc-list'>"; $inList = true; }
            // In đậm phần nhãn nếu có (VD: "- Thành phần: xyz")
            $liText = preg_replace('/^([^:]+:)/u', '<strong>$1</strong>', $matches[1]);
            $html .= "<li>" . $liText . "</li>";
            $expectingList = true;
        } 
        // 3. Nhận diện danh sách đánh số thứ tự (1., 2.)
        elseif (preg_match('/^(\d+)[\.\)]\s+(.*)$/', $line, $matches)) {
            if ($inList) { $html .= "</ul>"; $inList = false; }
            if (!$inNumList) { $html .= "<ol class='prod-desc-list-num'>"; $inNumList = true; }
            $liText = preg_replace('/^([^:]+:)/u', '<strong>$1</strong>', $matches[2]);
            $html .= "<li>" . $liText . "</li>";
            $expectingList = false;
        }
        // 4. Các dòng văn bản bình thường (Có thể là list ẩn)
        else {
            $isListLike = false;
            
            // Heuristic: Nếu đang ở mục Công dụng/Thành phần và dòng ngắn, khả năng cao là list ẩn
            if ($expectingList && mb_strlen($line, 'UTF-8') < 150) {
                $isListLike = true;
            }
            // Heuristic: Nếu dòng rất ngắn không có dấu kết câu, thường là list
            if (mb_strlen($line, 'UTF-8') < 60 && !preg_match('/[.:;!]$/u', $line)) {
                $isListLike = true;
            }
            
            if (preg_match('/\:$/', $line)) {
                $isListLike = false; // Dòng nhãn kết thúc bằng ":" thì là đoạn văn highlight
            }
            
            if ($isListLike) {
                if ($inNumList) { $html .= "</ol>"; $inNumList = false; }
                if (!$inList) { $html .= "<ul class='prod-desc-list'>"; $inList = true; }
                $liText = preg_replace('/^([^:]+:)/u', '<strong>$1</strong>', $line);
                $html .= "<li>" . $liText . "</li>";
            } else {
                if ($inList) { $html .= "</ul>"; $inList = false; }
                if ($inNumList) { $html .= "</ol>"; $inNumList = false; }
                
                if (preg_match('/\:$/', $line)) {
                    $html .= "<p class='desc-highlight'>" . $line . "</p>";
                    $expectingList = true; // Sau dòng nhãn ":" thường là list
                } else {
                    $lineText = preg_replace('/^([^:]+:)/u', '<strong>$1</strong>', $line);
                    $html .= "<p>" . $lineText . "</p>";
                    $expectingList = false;
                }
            }
        }
    }
    if ($inList) { $html .= "</ul>"; }
    if ($inNumList) { $html .= "</ol>"; }
    return $html;
}

$content_detail = '<div class="formatted-description">' . formatProductDescription($product['description']) . '</div>';
$content_usage = '<div class="formatted-description"><p>Để nhận hướng dẫn kỹ thuật sử dụng và liều lượng phù hợp cho từng loại cây trồng, quý khách vui lòng liên hệ trực tiếp <strong>Hotline 0976.828.171</strong> hoặc gửi yêu cầu báo giá để chuyên gia nông nghiệp tư vấn chi tiết.</p></div>';
$content_technical = '<div class="formatted-description"><ul class="prod-desc-list">
    <li><strong>Phân loại:</strong> ' . htmlspecialchars($product['badge'] ?: 'Vật tư nông nghiệp') . '</li>
    <li><strong>Xuất xứ:</strong> ' . htmlspecialchars($product['origin']) . '</li>
    <li><strong>Phân phối:</strong> Ngọc Ánh Dương</li>
</ul><p><em>* Giấy chứng nhận và bảng thành phần kỹ thuật chi tiết sẽ được gửi kèm trong hồ sơ sản phẩm theo yêu cầu.</em></p></div>';

// Xử lý mô tả ngắn cho phần tóm tắt
$descLines = array_values(array_filter(explode("\n", $product['description']), 'trim'));
$short_desc = count($descLines) > 0 ? $descLines[0] : '';
if (preg_match('/^(Giới thiệu sản phẩm|Giới thiệu|Thành phần)/iu', $short_desc) && count($descLines) > 1) {
    $short_desc = $descLines[1];
}
if (mb_strlen($short_desc, 'UTF-8') > 160) {
    $short_desc = mb_substr($short_desc, 0, 160, 'UTF-8') . '...';
}
?>

<style>
/* Style đồng bộ cho văn bản mô tả sản phẩm */
.formatted-description {
    font-size: 1rem;
    line-height: 1.7;
    color: var(--color-dark);
}
.formatted-description h4.prod-desc-heading {
    color: var(--color-primary);
    font-weight: 700;
    font-size: 1.15rem;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 6px;
    margin-top: 1.75rem;
    margin-bottom: 1rem;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.formatted-description h4.prod-desc-heading:first-child {
    margin-top: 0;
}
.formatted-description p {
    margin-bottom: 12px;
    text-align: justify;
}
.formatted-description .desc-highlight {
    font-weight: 600;
    margin-bottom: 6px;
    color: #1f2937;
}
.formatted-description ul.prod-desc-list {
    list-style-type: none;
    padding-left: 0;
    margin-bottom: 1.25rem;
}
.formatted-description ul.prod-desc-list li {
    position: relative;
    padding-left: 20px;
    margin-bottom: 8px;
    text-align: justify;
}
.formatted-description ul.prod-desc-list li::before {
    content: "•";
    color: var(--color-primary);
    font-weight: bold;
    font-size: 1.2rem;
    position: absolute;
    left: 4px;
    top: -2px;
}
.formatted-description ol.prod-desc-list-num {
    padding-left: 24px;
    margin-bottom: 1.25rem;
}
.formatted-description ol.prod-desc-list-num li {
    margin-bottom: 8px;
    padding-left: 4px;
    text-align: justify;
}
.formatted-description strong {
    color: #111827;
}
</style>

<section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/about-hero.jpg') center/cover;">
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
                
                <div class="detail-description" style="color: var(--color-dark-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem; border-left: 3px solid var(--color-primary); padding-left: 15px; background: rgba(16, 185, 129, 0.05); padding: 12px 15px; border-radius: 4px;">
                    <?php echo htmlspecialchars($short_desc); ?>
                </div>
                
                <div class="cart-add-container" style="display: flex; gap: 15px; margin-bottom: 1.5rem; align-items: center;">
                    <div class="quantity-selector" style="display: flex; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; height: 45px;">
                        <button type="button" class="qty-btn minus" style="width: 40px; background: #f8fafc; border: none; border-right: 1px solid #e2e8f0; cursor: pointer; color: #64748b; font-weight: bold;">-</button>
                        <input type="number" id="product_quantity" value="1" min="1" style="width: 50px; text-align: center; border: none; outline: none; font-weight: 600; color: #1e293b;">
                        <button type="button" class="qty-btn plus" style="width: 40px; background: #f8fafc; border: none; border-left: 1px solid #e2e8f0; cursor: pointer; color: #64748b; font-weight: bold;">+</button>
                    </div>
                    <button class="btn btn-primary btn-add-cart" data-id="<?php echo $product['id']; ?>" style="flex-grow: 1; height: 45px; border-radius: 8px; font-weight: 600; font-size: 1rem;"><i class="fa-solid fa-cart-plus"></i> Thêm Vào Giỏ Hàng</button>
                </div>

                <div class="detail-actions" style="display: flex; gap: 15px;">
                    <a href="tel:0976828171" class="btn btn-outline btn-call-now" style="flex: 1; border-color: #0b6623; color: #0b6623; justify-content: center;"><i class="fa-solid fa-phone-volume"></i> Gọi Hotline</a>
                    <a href="https://zalo.me/0976828171" target="_blank" class="btn btn-outline btn-quote" style="flex: 1; border-color: #0068ff; color: #0068ff; justify-content: center;"><i class="fa-solid fa-comment-dots"></i> Nhắn Zalo</a>
                </div>
            </div>
        </div>
        

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity Selector Logic
    const qtyInput = document.getElementById('product_quantity');
    const btnMinus = document.querySelector('.qty-btn.minus');
    const btnPlus = document.querySelector('.qty-btn.plus');

    if (qtyInput && btnMinus && btnPlus) {
        btnMinus.addEventListener('click', function() {
            let current = parseInt(qtyInput.value) || 1;
            if (current > 1) {
                qtyInput.value = current - 1;
            }
        });
        
        btnPlus.addEventListener('click', function() {
            let current = parseInt(qtyInput.value) || 1;
            qtyInput.value = current + 1;
        });

        qtyInput.addEventListener('change', function() {
            let current = parseInt(qtyInput.value);
            if (isNaN(current) || current < 1) {
                qtyInput.value = 1;
            }
        });
    }

    // Add to Cart Logic
    const btnAddCart = document.querySelector('.btn-add-cart');
    if (btnAddCart) {
        btnAddCart.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const quantity = qtyInput ? parseInt(qtyInput.value) : 1;
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

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
                    
                    const detailContainer = document.querySelector('.prod-detail-grid');
                    if (detailContainer && window.flyToCart) {
                        const img = detailContainer.querySelector('.main-detail-img') || detailContainer.querySelector('.detail-slide.active');
                        if (img) window.flyToCart(img);
                    }
                    
                    // Show success feedback
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fa-solid fa-check"></i> Đã thêm vào giỏ';
                    this.style.backgroundColor = '#10b981';
                    this.style.borderColor = '#10b981';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.backgroundColor = '';
                        this.style.borderColor = '';
                    }, 2000);
                } else {
                    if (data.redirect) {
                        alert(data.message);
                        window.location.href = data.redirect;
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi, vui lòng thử lại sau.');
            });
        });
    }
});
</script>