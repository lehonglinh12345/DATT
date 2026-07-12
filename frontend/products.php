<?php
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php';

// Get Filters from URL
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'featured';

// Load product categories for the sidebar
$categories = [];
$categoryResult = db_query('SELECT id, slug, name FROM categories WHERE type = ? ORDER BY name ASC', 's', ['product']);
if ($categoryResult instanceof mysqli_result) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Load category counts
$category_counts = ['all' => 0];
$categoryCountResult = db_query('SELECT c.slug, COUNT(p.id) AS count FROM categories c LEFT JOIN products p ON p.category_id = c.id WHERE c.type = ? GROUP BY c.id', 's', ['product']);
if ($categoryCountResult instanceof mysqli_result) {
    while ($row = $categoryCountResult->fetch_assoc()) {
        $category_counts[$row['slug']] = (int)$row['count'];
        $category_counts['all'] += (int)$row['count'];
    }
}

// Build product query
$whereSql = '';
$params = [];
$types = '';
if ($selected_category !== 'all') {
    $whereSql .= ' AND c.slug = ?';
    $params[] = $selected_category;
    $types .= 's';
}
if ($search_query !== '') {
    $whereSql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $searchLike = '%' . $search_query . '%';
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= 'ss';
}

// Sort options
$orderBy = 'p.id DESC'; // Mặc định: Tất cả (Mới nhất)
if ($selected_sort === 'name_asc') {
    $orderBy = 'p.name COLLATE utf8mb4_vietnamese_ci ASC';
} elseif ($selected_sort === 'sales_desc') {
    $orderBy = 'p.sales_count DESC';
} elseif ($selected_sort === 'views_desc') {
    $orderBy = 'p.views DESC';
}

$sql = 'SELECT p.*, c.slug AS category_slug, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1' . $whereSql . ' ORDER BY ' . $orderBy;
$productResult = db_query($sql, $types === '' ? null : $types, $params);
$filtered_products = [];
if ($productResult instanceof mysqli_result) {
    while ($row = $productResult->fetch_assoc()) {
        $filtered_products[] = $row;
    }
}

// AJAX Search Response Endpoint
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $is_logged_in = auth_is_logged_in();
    header('Content-Type: application/json; charset=utf-8');
    
    // 1. Render count HTML
    ob_start();
    ?>
    Hiển thị <span><?php echo count($filtered_products); ?></span> kết quả 
    <?php if (!empty($search_query)): ?>
        cho từ khóa "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
    <?php endif; ?>
    <?php
    $count_html = ob_get_clean();

    // 2. Render grid HTML
    ob_start();
    if (count($filtered_products) > 0) {
        ?>
        <div class="product-grid">
            <?php foreach ($filtered_products as $prod): ?>
                <div class="product-card">
                    <div class="prod-img-wrapper">
                        <img src="<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="prod-img" loading="lazy">
                        <?php if(!empty($prod['badge'])): ?>
                            <span class="prod-badge <?php echo htmlspecialchars($prod['badge_class']); ?>"><?php echo htmlspecialchars($prod['badge']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="prod-body">
                        <span class="prod-cat"><?php echo htmlspecialchars($prod['category_name']); ?></span>
                        <h3 class="prod-title"><a href="product-detail.php?id=<?php echo htmlspecialchars($prod['product_key']); ?>"><?php echo htmlspecialchars($prod['name']); ?></a></h3>
                        <p class="prod-origin">Xuất xứ: <strong><?php echo htmlspecialchars($prod['origin']); ?></strong></p>
                        <div class="prod-stats">
                            <span class="prod-stat-item"><i class="fa-solid fa-eye"></i> <?php echo number_format($prod['views']); ?> xem</span>
                            <span class="prod-stat-item"><i class="fa-solid fa-cart-shopping"></i> Đã bán <?php echo number_format($prod['sales_count']); ?></span>
                        </div>
                        <div class="prod-footer">
                            <span class="prod-price"><?php echo htmlspecialchars($prod['price']); ?></span>
                            <?php
                                $quoteTarget = 'contact.php?action=quote&prod_name=' . urlencode($prod['name']) . '&prod_key=' . urlencode($prod['product_key']) . '&prod_link=' . urlencode('product-detail.php?id=' . $prod['product_key']);
                                $quoteUrl = $is_logged_in ? $quoteTarget : 'login.php?redirect=' . urlencode($quoteTarget);
                            ?>
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo htmlspecialchars($prod['product_key']); ?>" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                                <a href="<?php echo $quoteUrl; ?>" class="btn btn-secondary btn-quote" title="Yêu cầu báo giá">Báo giá</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    } else {
        ?>
        <div class="no-results-box">
            <div class="no-results-icon"><i class="fa-solid fa-folder-open"></i></div>
            <h3>Không tìm thấy sản phẩm phù hợp</h3>
            <p>Vui lòng thử lại với từ khóa tìm kiếm hoặc danh mục khác.</p>
            <a href="products.php" class="btn btn-primary">Xóa bộ lọc</a>
        </div>
        <?php
    }
    $grid_html = ob_get_clean();

    echo json_encode([
        'count' => count($filtered_products),
        'count_html' => $count_html,
        'grid_html' => $grid_html
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$page_title = "Danh Mục Sản Phẩm";
$page_desc = "Danh sách sản phẩm hóa chất nhập khẩu, phân bón gốc lá, chế phẩm sinh học vi sinh do Ngọc Ánh Dương phân phối tại Cần Thơ.";
$active_page = 'products';
include 'includes/head.php';
?>
<link rel="stylesheet" href="css/products.css">
<?php
include 'includes/header.php';
$is_logged_in = auth_is_logged_in();
?>

<!-- Page Header Banner -->
<section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/hero-bg.jpg') center/cover;">
    <div class="container">
        <h1>Sản Phẩm</h1>
        <div class="breadcrumbs">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <span>Sản phẩm</span>
        </div>
    </div>
</section>



<!-- Catalog Main Section -->
<section class="section catalog-section">
    <div class="container catalog-layout">
        
        <!-- Mobile Filter Button Trigger -->
        <div class="mobile-filter-trigger">
            <button id="toggleFilterBtn" class="btn"><i class="fa-solid fa-filter"></i> Bộ lọc & Tìm kiếm</button>
        </div>

        <!-- Sidebar Filters -->
        <aside class="filter-sidebar" id="filterSidebar">
            <div class="filter-widget">
                <h3>Tìm Kiếm Nhanh</h3>
                <p class="search-note">Tìm nhanh theo tên sản phẩm, mã hoặc nhóm. Chọn nhóm nếu muốn thu hẹp kết quả.</p>
                <form action="products.php" method="GET" class="search-form-wrapper" id="searchForm">
                    <div class="search-row search-row-select">
                        <select name="category" class="search-select" id="categorySelect">
                            <option value="all" <?php echo $selected_category === 'all' ? 'selected' : ''; ?>>Tất cả nhóm sản phẩm</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['slug']); ?>" <?php echo $selected_category === $category['slug'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selected_sort !== 'featured'): ?>
                        <input type="hidden" name="sort" id="sortHiddenInput" value="<?php echo htmlspecialchars($selected_sort); ?>">
                    <?php endif; ?>
                    <div class="search-row search-input-row">
                        <input type="text" name="search" class="search-input" id="searchInput" placeholder="Nhập tên sản phẩm, mã hoặc mô tả..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="search-submit-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                    <?php if (!empty($search_query) || $selected_category !== 'all'): ?>
                        <div class="search-actions-row">
                            <a href="products.php<?php echo $selected_sort !== 'featured' ? '?sort='.urlencode($selected_sort) : ''; ?>" class="reset-search-link" id="resetSearchBtn">Xóa bộ lọc</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="filter-widget filter-widget-categories">
                <h3>Danh Mục Sản Phẩm</h3>
                <ul class="category-list" id="sidebarCategoryList">
                    <li>
                        <a href="products.php?category=all<?php echo $search_query !== '' ? '&search='.urlencode($search_query) : ''; ?><?php echo $selected_sort !== 'featured' ? '&sort='.urlencode($selected_sort) : ''; ?>" 
                           class="category-item-link <?php echo $selected_category === 'all' ? 'active' : ''; ?>" 
                           data-slug="all">
                            <span>Tất cả sản phẩm</span>
                            <span class="category-count"><?php echo isset($category_counts['all']) ? $category_counts['all'] : 0; ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="products.php?category=<?php echo htmlspecialchars($category['slug']); ?><?php echo $search_query !== '' ? '&search='.urlencode($search_query) : ''; ?><?php echo $selected_sort !== 'featured' ? '&sort='.urlencode($selected_sort) : ''; ?>" 
                               class="category-item-link <?php echo $selected_category === $category['slug'] ? 'active' : ''; ?>" 
                               data-slug="<?php echo htmlspecialchars($category['slug']); ?>">
                                <span><?php echo htmlspecialchars($category['name']); ?></span>
                                <span class="category-count"><?php echo isset($category_counts[$category['slug']]) ? $category_counts[$category['slug']] : 0; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="filter-widget widget-support">
                <h4><i class="fa-solid fa-phone"></i> Hỗ Trợ Đặt Hàng</h4>
                <p>Liên hệ trực tiếp để nhận bảng báo giá sỉ đại lý chiết khấu cao tốt nhất.</p>
                <a href="tel:0976828171" class="support-phone-btn"><i class="fa-solid fa-phone-volume"></i> 0976.828.171</a>
            </div>
        </aside>
        
        <!-- Main Catalog Results -->
        <main class="catalog-results">
            <!-- Filter Header Bar -->
            <div class="catalog-header">
                <div class="results-count" id="resultsCount">
                    Hiển thị <span><?php echo count($filtered_products); ?></span> kết quả 
                    <?php if (!empty($search_query)): ?>
                        cho từ khóa "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                    <?php endif; ?>
                </div>
                
                <div class="sort-wrapper">
                    <form id="sortForm" action="products.php" method="GET">
                        <?php if ($selected_category !== 'all'): ?>
                            <input type="hidden" name="category" id="sortCategoryHidden" value="<?php echo htmlspecialchars($selected_category); ?>">
                        <?php endif; ?>
                        <?php if ($search_query !== ''): ?>
                            <input type="hidden" name="search" id="sortSearchHidden" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php endif; ?>
                        <label class="sort-label" for="sortSelect">Sắp xếp:</label>
                        <select id="sortSelect" name="sort" class="sort-select">
                            <option value="featured" <?php echo $selected_sort === 'featured' ? 'selected' : ''; ?>>Tất cả</option>
                            <option value="sales_desc" <?php echo $selected_sort === 'sales_desc' ? 'selected' : ''; ?>>Bán chạy nhất</option>
                            <option value="views_desc" <?php echo $selected_sort === 'views_desc' ? 'selected' : ''; ?>>Phổ biến nhất</option>
                            <option value="name_asc" <?php echo $selected_sort === 'name_asc' ? 'selected' : ''; ?>>Tên sản phẩm</option>
                        </select>
                    </form>
                </div>
            </div>
            
            <?php if (!$is_logged_in): ?>
                <div class="auth-info-banner" style="margin-bottom: 1rem; padding: 1rem 1.25rem; border-radius: 12px; background: #f4f9ff; color: #0f4c81; border: 1px solid #cfe1f8;">
                    <strong>Lưu ý:</strong> Mời quý khách đăng nhập để gửi yêu cầu báo giá sản phẩm. <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="color: #0f4c81; text-decoration: underline; font-weight: 600;">Đăng nhập</a>.
                </div>
            <?php endif; ?>

            <!-- AJAX search loader -->
            <div class="search-loading-wrapper" id="searchLoading">
                <div class="loading-spinner"></div>
                <span>Đang tìm kiếm sản phẩm...</span>
            </div>

            <!-- Products Catalog Grid Container -->
            <div id="productGridContainer">
                <?php if (count($filtered_products) > 0): ?>
                    <div class="product-grid">
                        <?php foreach ($filtered_products as $prod): ?>
                            <div class="product-card">
                                <div class="prod-img-wrapper">
                                    <img src="<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="prod-img" loading="lazy">
                                    <?php if(!empty($prod['badge'])): ?>
                                        <span class="prod-badge <?php echo htmlspecialchars($prod['badge_class']); ?>"><?php echo htmlspecialchars($prod['badge']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="prod-body">
                                    <span class="prod-cat"><?php echo htmlspecialchars($prod['category_name']); ?></span>
                                    <h3 class="prod-title"><a href="product-detail.php?id=<?php echo htmlspecialchars($prod['product_key']); ?>"><?php echo htmlspecialchars($prod['name']); ?></a></h3>
                                    <p class="prod-origin">Xuất xứ: <strong><?php echo htmlspecialchars($prod['origin']); ?></strong></p>
                                    <div class="prod-stats">
                                        <span class="prod-stat-item"><i class="fa-solid fa-eye"></i> <?php echo number_format($prod['views']); ?> xem</span>
                                        <span class="prod-stat-item"><i class="fa-solid fa-cart-shopping"></i> Đã bán <?php echo number_format($prod['sales_count']); ?></span>
                                    </div>
                                    <div class="prod-footer">
                                        <span class="prod-price"><?php echo htmlspecialchars($prod['price']); ?></span>
                                        <?php
                                            $quoteTarget = 'contact.php?action=quote&prod_name=' . urlencode($prod['name']) . '&prod_key=' . urlencode($prod['product_key']) . '&prod_link=' . urlencode('product-detail.php?id=' . $prod['product_key']);
                                            $quoteUrl = $is_logged_in ? $quoteTarget : 'login.php?redirect=' . urlencode($quoteTarget);
                                        ?>
                                        <div class="product-actions">
                                            <a href="product-detail.php?id=<?php echo htmlspecialchars($prod['product_key']); ?>" class="btn-detail" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo $quoteUrl; ?>" class="btn btn-secondary btn-quote" title="Yêu cầu báo giá">Báo giá</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- No Results State -->
                    <div class="no-results-box">
                        <div class="no-results-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <h3>Không tìm thấy sản phẩm phù hợp</h3>
                        <p>Vui lòng thử lại với từ khóa tìm kiếm hoặc danh mục khác.</p>
                        <a href="products.php" class="btn btn-primary">Xóa bộ lọc</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
    </div>
</section>

<!-- Script nhỏ xử lý Đóng/Mở bộ lọc mượt mà trên Điện thoại -->

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById("toggleFilterBtn");
    const sidebar = document.getElementById("filterSidebar");
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener("click", function(e) {
            e.preventDefault(); // Ngăn chặn mọi hành vi cuộn mặc định
            sidebar.classList.toggle("show-mobile");
            
            // Thay đổi nội dung nút để người dùng biết trạng thái
            if (sidebar.classList.contains("show-mobile")) {
                toggleBtn.classList.add("is-open");
                toggleBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Đóng bộ lọc';
                toggleBtn.style.backgroundColor = '#e74c3c'; // Đổi sang màu đỏ khi muốn đóng
            } else {
                toggleBtn.classList.remove("is-open");
                toggleBtn.innerHTML = '<i class="fa-solid fa-filter"></i> Bộ lọc & Tìm kiếm';
                toggleBtn.style.backgroundColor = ''; // Về màu chủ đạo mặc định
            }
        });
    }
});

</script>

<script src="js/products-search.js"></script>
<?php
include 'includes/footer.php';
?>