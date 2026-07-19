<?php
$page_title = "Trang Chủ";
$page_desc = "Công ty Cổ phần Hóa chất Nhập khẩu Ngọc Ánh Dương chuyên phân phối và nhập khẩu hóa chất công nghiệp, vật tư nông nghiệp, phân bón cao cấp và chế phẩm sinh học vi sinh uy tín.";
$active_page = 'home';
include 'includes/head.php';
include 'includes/header.php';

?>

<!-- Hero Section -->
<section class="hero">
    
    <div class="container  hero-grid">
        <div class="hero-content">
            <span class="hero-tagline">Hóa Chất & Vật Tư Nông Nghiệp Nhập Khẩu</span>
            <h1>Kiến Tạo Giá Trị<br>Nông Nghiệp Bền Vững</h1>
            <p class="hero-description">
                Ngọc Ánh Dương tự hào là nhà nhập khẩu và phân phối hàng đầu các dòng phân bón cao cấp, chế phẩm sinh học vi sinh và hóa chất công nghiệp đạt chuẩn quốc tế tại Cần Thơ và cả nước.
            </p>
            <div class="hero-btns">
                <a href="products.php" class="btn btn-primary">Khám Phá Sản Phẩm <i class="fa-solid fa-arrow-right"></i></a>
                <a href="contact.php" class="btn btn-outline" style="border-color: var(--color-white); color: var(--color-white);">Liên Hệ Ngay</a>
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="hero-slider">
                <img src="images/banner1.jpg" class="slide active" alt="">
                <img src="images/banner2.jpg" class="slide" alt="">
                <img src="images/banner3.jpg" class="slide" alt="">

                <!-- Arrows -->
                <button class="slider-btn prev">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <button class="slider-btn next">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <!-- Popup xem ảnh -->
            <div class="image-popup" id="imagePopup">
                <span class="close-popup">&times;</span>
                <img id="popupImage" src="" alt="">
            </div>
        </div>
</section>

<!-- Company Introduction Section -->
<section class="section section-bg-white">
    <div class="container intro-grid">
        <div class="intro-img-wrapper">
            <img src="images/about-hero.jpg" alt="Ngọc Ánh Dương Lab" class="intro-img" loading="lazy">
            <div class="intro-badge">
                <i class="fa-solid fa-award"></i>
                <div class="intro-badge-text">
                    <h4>100% Uy Tín</h4>
                    <p>Đạt chuẩn kiểm định quốc tế</p>
                </div>
            </div>
        </div>
        
        <div class="intro-text">
            <span style="color: var(--color-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Về Chúng Tôi</span>
            <h2 style="font-size: 2.25rem; margin-top: 0.5rem; margin-bottom: 1.5rem;">Đồng Hành Cùng Nhà Nông Nâng Cao Năng Suất Bền Vững</h2>
            <p>
                <strong>Công ty Cổ phần Hóa chất Nhập khẩu Ngọc Ánh Dương</strong> hoạt động chuyên sâu trong lĩnh vực cung cấp và phân phối hóa chất phục vụ ngành nông nghiệp. Chúng tôi nhập khẩu trực tiếp các dòng phân bón vi lượng, chế phẩm sinh học và các chất điều hòa sinh trưởng chất lượng cao nhằm mang lại giải pháp tổng thể tốt nhất cho cây trồng.
            </p>
            <p>
                Với phương châm <em>“Chất lượng là nền tảng – Hiệu quả là mục tiêu”</em>, chúng tôi cam kết không chỉ cung cấp sản phẩm chuẩn quốc tế mà còn tư vấn kỹ thuật, chuyển giao công nghệ canh tác hiện đại để đồng hành cùng sự phát triển thịnh vượng của nông nghiệp Việt Nam.
            </p>
            
            <div class="intro-features">
                <div class="intro-feat-item"><i class="fa-solid fa-circle-check"></i> Phân bón vi lượng & phân bón lá</div>
                <div class="intro-feat-item"><i class="fa-solid fa-circle-check"></i> Chế phẩm sinh học thân thiện</div>
                <div class="intro-feat-item"><i class="fa-solid fa-circle-check"></i> Chất điều hòa sinh trưởng tối ưu</div>
                <div class="intro-feat-item"><i class="fa-solid fa-circle-check"></i> Tư vấn kỹ thuật & chuyển giao</div>
            </div>
            
            <a href="about.php" class="btn btn-secondary" style="margin-top: 2rem;">Xem Chi Tiết Giới Thiệu</a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="section section-bg-white">
    <div class="container">
        <div class="text-center">
            <h2 class="section-title">Sản Phẩm Tiêu Biểu</h2>
            <p class="section-subtitle">Danh sách các sản phẩm đang được phân phối rộng rãi và nhận được phản hồi tốt nhất từ người tiêu dùng</p>
        </div>
        
        <div class="product-grid home-product-grid">
            <!-- Product 1 -->
            <div class="product-card">
                <div class="prod-img-wrapper">
                    <img src="images/tang-luc-x3.jpg" alt="Phân bón gốc Tăng lực X3" class="prod-img" loading="lazy">
                    <span class="prod-badge">Nông Nghiệp</span>
                </div>
                <div class="prod-body">
                    <span class="prod-cat">Phân bón gốc</span>
                    <h3 class="prod-title"><a href="product-detail.php?id=tang-luc-x3">Phân bón gốc Tăng Lực X3 - Phục hồi đất, bật rễ nhanh</a></h3>
                    <p class="prod-origin">Xuất xứ: <strong>Nhập khẩu</strong></p>
                    <div class="prod-footer">
                        <span class="prod-price">Liên hệ báo giá</span>
                        <a href="product-detail.php?id=tang-luc-x3" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Product 2 -->
            <div class="product-card">
                <div class="prod-img-wrapper">
                    <img src="images/nuoi-dong.jpg" alt="Phân bón lá Nuôi đòng - Trổ thoát" class="prod-img" loading="lazy">
                    <span class="prod-badge">Nông Nghiệp</span>
                </div>
                <div class="prod-body">
                    <span class="prod-cat">Phân bón lá</span>
                    <h3 class="prod-title"><a href="product-detail.php?id=nuoi-dong-tro-thoat">Dưỡng chất lúa Nuôi Đòng - Trổ Thoát cao cấp</a></h3>
                    <p class="prod-origin">Xuất xứ: <strong>Châu Âu</strong></p>
                    <div class="prod-footer">
                        <span class="prod-price">Liên hệ báo giá</span>
                        <a href="product-detail.php?id=nuoi-dong-tro-thoat" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Product 3 -->
            <div class="product-card">
                <div class="prod-img-wrapper">
                    <img src="images/bio-prep.jpg" alt="Chế phẩm vi sinh cải tạo đất" class="prod-img" loading="lazy">
                    <span class="prod-badge badge-bio">Vi Sinh</span>
                </div>
                <div class="prod-body">
                    <span class="prod-cat">CHẾ PHẨM VI SINH, SINH HỌC</span>
                    <h3 class="prod-title"><a href="product-detail.php?id=vi-sinh-bio-active">Chế phẩm sinh học Bio-Active cải tạo đất sâu</a></h3>
                    <p class="prod-origin">Xuất xứ: <strong>Nhật Bản</strong></p>
                    <div class="prod-footer">
                        <span class="prod-price">Liên hệ báo giá</span>
                        <a href="product-detail.php?id=vi-sinh-bio-active" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Product 4 -->
            <div class="product-card">
                <div class="prod-img-wrapper">
                    <img src="images/bio-prep.jpg" alt="Chế phẩm nấm đối kháng Trichoderma" class="prod-img" loading="lazy">
                    <span class="prod-badge badge-bio">Trị Nấm</span>
                </div>
                <div class="prod-body">
                    <span class="prod-cat">PHÒNG TRỪ NẤM HẠI</span>
                    <h3 class="prod-title"><a href="product-detail.php?id=trichoderma-doi-khang-nam">Chế phẩm nấm đối kháng Trichoderma ngăn ngừa bệnh hại</a></h3>
                    <p class="prod-origin">Xuất xứ: <strong>Việt Nam</strong></p>
                    <div class="prod-footer">
                        <span class="prod-price">Liên hệ báo giá</span>
                        <a href="product-detail.php?id=trichoderma-doi-khang-nam" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                    </div>
                </div>
            </div>

            <!-- Product 5 -->
            <div class="product-card">
                <div class="prod-img-wrapper">
                    <img src="images/bio-prep.jpg" alt="Chế phẩm trừ sâu sinh học Neem Oil" class="prod-img" loading="lazy">
                    <span class="prod-badge badge-bio">Côn Trùng</span>
                </div>
                <div class="prod-body">
                    <span class="prod-cat">PHÒNG TRỪ CÔN TRÙNG, ỐC HẠI</span>
                    <h3 class="prod-title"><a href="product-detail.php?id=che-pham-tru-sau-sinh-hoc">Chế phẩm trừ sâu sinh học Neem Oil</a></h3>
                    <p class="prod-origin">Xuất xứ: <strong>Ấn Độ</strong></p>
                    <div class="prod-footer">
                        <span class="prod-price">Liên hệ báo giá</span>
                        <a href="product-detail.php?id=che-pham-tru-sau-sinh-hoc" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                    </div>
                </div>
            </div>

            <!-- Product 6 -->
            <div class="product-card">
                <div class="prod-img-wrapper">
                    <img src="images/bio-prep.jpg" alt="Chế phẩm vi sinh xử lý đáy ao nuôi" class="prod-img" loading="lazy">
                    <span class="prod-badge badge-bio">Vi Sinh</span>
                </div>
                <div class="prod-body">
                    <span class="prod-cat">CHẾ PHẨM VI SINH, SINH HỌC</span>
                    <h3 class="prod-title"><a href="product-detail.php?id=vi-sinh-aquaculture-usa">Chế phẩm vi sinh xử lý đáy ao nuôi (Aquaculture USA)</a></h3>
                    <p class="prod-origin">Xuất xứ: <strong>Hoa Kỳ</strong></p>
                    <div class="prod-footer">
                        <span class="prod-price">Liên hệ báo giá</span>
                        <a href="product-detail.php?id=vi-sinh-aquaculture-usa" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center" style="margin-top: 3.5rem;">
            <a href="products.php" class="btn btn-outline">Xem Tất Cả Sản Phẩm <i class="fa-solid fa-chevron-right"></i></a>
        </div>
    </div>
</section>
<!-- Customer Reviews Section -->
<section class="section section-bg-white">
    <div class="container">
        <div class="text-center">
            <h2 class="section-title">Đánh Giá Khách Hàng Thực Tế</h2>
            <p class="section-subtitle">Đánh giá trực tiếp từ Google Maps để quý khách hàng có thêm cơ sở tin tưởng khi hợp tác cùng Ngọc Ánh Dương.</p>
        </div>

        <div class="review-grid">
            <div class="review-card">
                <div class="review-header">
                    <span class="review-rating"><i class="fa-solid fa-star"></i> 5.0</span>
                    <span class="review-source">Google Maps</span>
                </div>
                <p class="review-text">CTY bán sỉ mẫu riêng giá cả rất cạnh tranh và đảm bảo chất lượng.</p>
                <div class="review-author">
                    <div class="review-avatar">V</div>
                    <div class="review-author-info">
                        <strong>Vũ Quỳnh</strong>
                        <span>Local Guide · 3 reviews · 48 photos</span>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div class="review-header">
                    <span class="review-rating"><i class="fa-solid fa-star"></i> 5.0</span>
                    <span class="review-source">Google Maps</span>
                </div>
                <p class="review-text">Hàng hóa chất lượng, uy tín. Nhân viên nhiệt tình, chu đáo. Chế độ hậu mãi tuyệt vời!</p>
                <div class="review-author">
                    <div class="review-avatar">L</div>
                    <div class="review-author-info">
                        <strong>lephatbao huy</strong>
                        <span>3 reviews · 11 photos</span>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div class="review-header">
                    <span class="review-rating"><i class="fa-solid fa-star"></i> 5.0</span>
                    <span class="review-source">Google Maps</span>
                </div>
                <p class="review-text">Cty rất uy tín, nhiều sản phẩm, giá rất tốt.</p>
                <div class="review-author">
                    <div class="review-avatar">P</div>
                    <div class="review-author-info">
                        <strong>Phuoctoan Phuoctoan</strong>
                        <span>4 reviews</span>
                    </div>
                </div>
            </div>

            <div class="review-card review-card--more">
                <div class="review-header">
                    <span class="review-rating">Xem thêm đánh giá</span>
                </div>
                <p class="review-text">Phản hồi khách hàng từ trang Google Maps. </p>
                <a href="https://www.google.com/maps/place/C%C3%94NG+TY+C%E1%BB%94+PH%E1%BA%A6N+H%C3%93A+CH%E1%BA%A4T+NH%E1%BA%ACP+KH%E1%BA%A8U+NG%E1%BB%8CC+%C3%81NH+D%C6%AF%C6%A0NG/@9.9956254,105.8002686,860m/data=!3m1!1e3!4m8!3m7!1s0x468bffd5247f096f:0x5b54ecc5de92bda5!8m2!3d9.9956254!4d105.8002686!9m1!1b1!16s%2Fg%2F11xf5bck6f?hl=en-US&entry=ttu&g_ep=EgoyMDI2MDUyMC4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noopener noreferrer" class="btn btn-outline review-link">Xem tất cả review</a>
            </div>
        </div>
    </div>
</section>


<!-- Product Categories Section -->
<section class="section">
    <div class="container">
        <div class="text-center">
            <h2 class="section-title">Danh Mục Sản Phẩm</h2>
            <p class="section-subtitle">Chúng tôi cung cấp 5 nhóm sản phẩm chính phục vụ tối đa nhu cầu của quý khách hàng</p>
        </div>
        
        <div class="category-grid">
            <!-- Cat 1 -->
            <div class="category-card">
                <div class="cat-img-wrapper">
                    <img src="images/bio-prep.jpg" alt="CHẾ PHẨM VI SINH, SINH HỌC" class="cat-img" loading="lazy">
                    <div class="cat-icon-overlay">
                        <i class="fa-solid fa-dna"></i>
                    </div>
                </div>
                <div class="cat-body">
                    <h3>CHẾ PHẨM VI SINH, SINH HỌC</h3>
                    <p>Các dòng chế phẩm vi sinh học công nghệ cao cải tạo đất hữu cơ, kích thích bộ rễ phát triển và tăng sức đề kháng tự nhiên.</p>
                    <a href="products.php?category=che-pham-vi-sinh-sinh-hoc" class="cat-link">Xem sản phẩm <i class="fa-solid fa-arrow-right-long"></i></a>
                </div>
            </div>
            
            <!-- Cat 2 -->
            <div class="category-card">
                <div class="cat-img-wrapper">
                    <img src="images/tang-luc-x3.jpg" alt="PHÂN BÓN GỐC" class="cat-img" loading="lazy">
                    <div class="cat-icon-overlay">
                        <i class="fa-solid fa-seedling"></i>
                    </div>
                </div>
                <div class="cat-body">
                    <h3>PHÂN BÓN GỐC</h3>
                    <p>Dòng phân bón gốc cao cấp chuyên phục hồi đất bạc màu, kích thích đẻ nhánh và giúp cây sinh trưởng khỏe mạnh từ gốc rễ.</p>
                    <a href="products.php?category=phan-bon-goc" class="cat-link">Xem sản phẩm <i class="fa-solid fa-arrow-right-long"></i></a>
                </div>
            </div>
            
            <!-- Cat 3 -->
            <div class="category-card">
                <div class="cat-img-wrapper">
                    <img src="images/nuoi-dong.jpg" alt="PHÂN BÓN LÁ" class="cat-img" loading="lazy">
                    <div class="cat-icon-overlay">
                        <i class="fa-solid fa-leaf"></i>
                    </div>
                </div>
                <div class="cat-body">
                    <h3>PHÂN BÓN LÁ</h3>
                    <p>Phân bón lá sinh học chuyên dùng trong giai đoạn làm đòng, dưỡng bông, nuôi dưỡng hoa trái trổ thoát cực mạnh, đạt năng suất cao.</p>
                    <a href="products.php?category=phan-bon-la" class="cat-link">Xem sản phẩm <i class="fa-solid fa-arrow-right-long"></i></a>
                </div>
            </div>

            <!-- Cat 4 -->
            <div class="category-card">
                <div class="cat-img-wrapper">
                    <img src="images/news-3.jpg" alt="PHÒNG TRỪ CÔN TRÙNG, ỐC HẠI" class="cat-img" loading="lazy">
                    <div class="cat-icon-overlay">
                        <i class="fa-solid fa-bug-slash"></i>
                    </div>
                </div>
                <div class="cat-body">
                    <h3>PHÒNG TRỪ CÔN TRÙNG, ỐC HẠI</h3>
                    <p>Các giải pháp sinh học an toàn phòng trừ và diệt côn trùng, bọ trĩ, nhện đỏ, rệp sáp và ốc bươu vàng phá hoại nông sản.</p>
                    <a href="products.php?category=phong-tru-con-trung-oc-hai" class="cat-link">Xem sản phẩm <i class="fa-solid fa-arrow-right-long"></i></a>
                </div>
            </div>

            <!-- Cat 5 -->
            <div class="category-card">
                <div class="cat-img-wrapper">
                    <img src="images/news-1.jpg" alt="PHÒNG TRỪ NẤM HẠI" class="cat-img" loading="lazy">
                    <div class="cat-icon-overlay">
                        <i class="fa-solid fa-shield-virus"></i>
                    </div>
                </div>
                <div class="cat-body">
                    <h3>PHÒNG TRỪ NẤM HẠI</h3>
                    <p>Chế phẩm sinh học đối kháng mạnh mẽ ngăn ngừa và tiêu diệt các nấm bệnh thối rễ, lở cổ rễ, vàng lá chín sớm.</p>
                    <a href="products.php?category=phong-tru-nam-hai" class="cat-link">Xem sản phẩm <i class="fa-solid fa-arrow-right-long"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Why Choose Us Section -->
<section class="section why-section">
    <div class="container">
        <div class="text-center">
            <h2 class="section-title">Tại Sao Chọn Ngọc Ánh Dương?</h2>
            <p class="section-subtitle">Chúng tôi nỗ lực đem lại giá trị vượt trội và niềm tin tuyệt đối cho từng khách hàng và đối tác</p>
        </div>
        
        <div class="why-grid">
            <!-- Box 1 -->
            <div class="why-card">
                <div class="why-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3>Chất Lượng Đạt Chuẩn</h3>
                <p>Mọi lô hàng nhập khẩu đều trải qua quy trình kiểm tra nghiêm ngặt, đảm bảo độ tinh khiết và hiệu quả tối đa.</p>
            </div>
            
            <!-- Box 2 -->
            <div class="why-card">
                <div class="why-icon">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <h3>Đối Tác Toàn Cầu</h3>
                <p>Liên kết trực tiếp với các nhà sản xuất hóa chất và chế phẩm nông nghiệp uy tín trên toàn thế giới.</p>
            </div>
            
            <!-- Box 3 -->
            <div class="why-card">
                <div class="why-icon">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <h3>Giá Cả Cạnh Tranh</h3>
                <p>Nhập khẩu trực tiếp không qua trung gian giúp cung ứng sản phẩm tới tay khách hàng với mức giá tối ưu nhất.</p>
            </div>
            
            <!-- Box 4 -->
            <div class="why-card">
                <div class="why-icon">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h3>Giao Hàng Nhanh Chóng</h3>
                <p>Hệ thống kho bãi rộng rãi tại Cần Thơ cùng đội xe vận tải chuyên dụng đáp ứng kịp thời mọi tiến độ sản xuất.</p>
            </div>
        </div>
    </div>
</section>
<!-- News & Updates Section -->
<section class="section section-bg-white" style="border-top: 1px solid var(--color-border);">
    <div class="container">
        <div class="text-center">
            <h2 class="section-title">Tin Tức & Cập Nhật</h2>
            <p class="section-subtitle">Cập nhật những xu hướng kỹ thuật, câu chuyện và tin tức nông nghiệp mới nhất cùng Ngọc Ánh Dương</p>
        </div>
        
        <?php
        require_once __DIR__ . '/../backend/db.php';
        $news_home_res = db_query("SELECT *, DATE_FORMAT(published_at, '%d/%m/%Y') AS date, image_alt AS alt FROM news_articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 3");
        $news_home_articles = [];
        if ($news_home_res instanceof mysqli_result) {
            while ($row = $news_home_res->fetch_assoc()) {
                $news_home_articles[] = $row;
            }
        }
        ?>
        
        <?php if (!empty($news_home_articles)): ?>
            <div class="news-grid home-news-grid">
                <?php foreach ($news_home_articles as $article): ?>
                    <div class="news-card">
                        <div class="news-img-wrapper">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['alt']); ?>" loading="lazy">
                        </div>
                        <div class="news-body">
                            <div class="news-meta">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($article['date']); ?></span>
                                <span><i class="fa-regular fa-user"></i> Ks. Ngọc Ánh Dương</span>
                            </div>
                            <h3>
                                <a href="news.php?section=<?php echo urlencode($article['section']); ?>&article=<?php echo urlencode($article['slug']); ?>">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </h3>
                            <p class="news-desc">
                                <?php echo htmlspecialchars($article['excerpt']); ?>
                            </p>
                            <a href="news.php?section=<?php echo urlencode($article['section']); ?>&article=<?php echo urlencode($article['slug']); ?>" class="news-link">
                                Xem chi tiết <i class="fa-solid fa-arrow-right-long"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center" style="color: var(--color-dark-muted);">Chưa có bài viết mới nào.</p>
        <?php endif; ?>
        
        <div class="text-center" style="margin-top: 3.5rem;">
            <a href="news.php" class="btn btn-outline">Xem Tất Cả Tin Tức <i class="fa-solid fa-chevron-right"></i></a>
        </div>
    </div>
</section>


<!-- Call to Action Banner Section -->
<section class="section" style="padding-bottom: 0;">
    <div class="container">
        <div class="cta-banner">
            <div class="cta-grid">
                <div class="cta-content">
                    <h2>Hợp Tác Cùng Ngọc Ánh Dương</h2>
                    <p>Quý khách hàng cần tư vấn kỹ thuật nông nghiệp, thông số hóa chất dệt nhuộm, xử lý nước hoặc nhận bảng báo giá đại lý tốt nhất?</p>
                </div>
                <div class="cta-actions">
                    <div class="cta-phone">
                        <i class="fa-solid fa-phone-volume"></i>
                        <a href="tel:0976828171">0976.828.171</a>
                    </div>
                    <a href="contact.php" class="btn btn-primary" style="background-color: var(--color-accent); color: var(--color-dark); box-shadow: none;">Gửi Yêu Cầu Báo Giá <i class="fa-solid fa-envelope"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>


<script>
    const slides = document.querySelectorAll(".hero-slider .slide");
    const nextBtn = document.querySelector(".next");
    const prevBtn = document.querySelector(".prev");

    const popup = document.getElementById("imagePopup");
    const popupImg = document.getElementById("popupImage");
    const closePopup = document.querySelector(".close-popup");

    let currentSlide = 0;

    // Hiện slide
    function showSlide(index) {
        slides.forEach(slide => {
            slide.classList.remove("active");
        });

        slides[index].classList.add("active");
    }

    // Next
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    // Prev
    function prevSlide() {
        currentSlide =
            (currentSlide - 1 + slides.length) % slides.length;

        showSlide(currentSlide);
    }

    // Nút bấm
    nextBtn.addEventListener("click", nextSlide);
    prevBtn.addEventListener("click", prevSlide);

    // Auto chạy
    setInterval(nextSlide, 4000);

    // Click mở ảnh lớn
    slides.forEach(slide => {
        slide.addEventListener("click", () => {
            popup.classList.add("active");
            popupImg.src = slide.src;
        });
    });

    // Đóng popup
    closePopup.addEventListener("click", () => {
        popup.classList.remove("active");
    });

    // Click ngoài để đóng
    popup.addEventListener("click", (e) => {
        if (e.target === popup) {
            popup.classList.remove("active");
        }
    });
</script>
<div style="height: 5rem;"></div>

<?php
include 'includes/footer.php';
?>
