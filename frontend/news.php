<?php
$section = 'news';
$page_title = "Tin Tức Nhà Nông";
$page_desc = "Tin tức, câu chuyện và định hướng nông nghiệp của nhà nông Việt Nam, cập nhật xu hướng trồng trọt, sản xuất và xuất khẩu.";
$active_page = 'news';
include 'includes/head.php';
include 'includes/header.php';
require_once __DIR__ . '/../backend/db.php';

$article_slug = trim($_GET['article'] ?? '');
$selected_article = null;
$prev_article = null;
$next_article = null;

if ($article_slug !== '') {

    // 1. Lấy bài hiện tại
    $articleResult = db_query(
        'SELECT *, DATE_FORMAT(published_at, "%d/%m/%Y") AS date, image_alt AS alt 
         FROM news_articles 
         WHERE slug = ? AND section = ? AND status = ?',
        'sss',
        [$article_slug, $section, 'published']
    );

    if ($articleResult instanceof mysqli_result) {
        $selected_article = $articleResult->fetch_assoc();
    }

    // 2. Nếu có bài hiện tại thì mới tìm prev/next
    if ($selected_article) {

        // Bài trước (cũ hơn)
        $prevResult = db_query(
            'SELECT slug, title FROM news_articles 
             WHERE section = ? AND status = ? AND published_at < ? 
             ORDER BY published_at DESC LIMIT 1',
            'sss',
            [$section, 'published', $selected_article['published_at']]
        );

        if ($prevResult instanceof mysqli_result) {
            $prev_article = $prevResult->fetch_assoc();
        }

        // Bài sau (mới hơn)
        $nextResult = db_query(
            'SELECT slug, title FROM news_articles 
             WHERE section = ? AND status = ? AND published_at > ? 
             ORDER BY published_at ASC LIMIT 1',
            'sss',
            [$section, 'published', $selected_article['published_at']]
        );

        if ($nextResult instanceof mysqli_result) {
            $next_article = $nextResult->fetch_assoc();
        }
    }
}
$visible_articles = [];
$per_page = 3;
$current_page = max(1, intval($_GET['page'] ?? 1));
$total_pages = 1;

if (!$selected_article) {
    $countResult = db_query('SELECT COUNT(*) AS total FROM news_articles WHERE section = ? AND status = ?', 'ss', [$section, 'published']);
    $totalNews = 0;
    if ($countResult instanceof mysqli_result) {
        $countRow = $countResult->fetch_assoc();
        $totalNews = isset($countRow['total']) ? intval($countRow['total']) : 0;
    }

    $total_pages = max(1, ceil($totalNews / $per_page));
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }

    $offset = ($current_page - 1) * $per_page;
    $listResult = db_query('SELECT *, DATE_FORMAT(published_at, "%d/%m/%Y") AS date, image_alt AS alt FROM news_articles WHERE section = ? AND status = ? ORDER BY published_at DESC LIMIT ? OFFSET ?', 'ssii', [$section, 'published', $per_page, $offset]);
    if ($listResult instanceof mysqli_result) {
        while ($row = $listResult->fetch_assoc()) {
            $visible_articles[] = $row;
        }
    }
} else {
    $sidebarResult = db_query('SELECT slug, title, DATE_FORMAT(published_at, "%d/%m/%Y") AS date FROM news_articles WHERE section = ? AND status = ? ORDER BY published_at DESC', 'ss', [$section, 'published']);
    if ($sidebarResult instanceof mysqli_result) {
        while ($row = $sidebarResult->fetch_assoc()) {
            $visible_articles[] = $row;
        }
    }
}
?>

<!-- Page Header Banner -->
<section class="about-hero" style="background: linear-gradient(rgba(18, 24, 32, 0.75), rgba(18, 24, 32, 0.8)), url('images/hero-bg.jpg') center/cover;">
    <div class="container">
        <h1><?php echo $section === 'tech' ? 'Kỹ Thuật Trồng Cây' : 'Tin Tức Nhà Nông'; ?></h1>
        <div class="breadcrumbs">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <span><?php echo $section === 'tech' ? 'Kỹ thuật trồng cây' : 'Tin tức'; ?></span>
        </div>
    </div>
</section>

<!-- News Main Section -->
<section class="section">
    <div class="container">
        <div class="text-center">
            <h2 class="section-title"><?php echo $section === 'tech' ? 'Kỹ Thuật Trồng Cây' : 'Tin Tức Nhà Nông'; ?></h2>
            <p class="section-subtitle"><?php echo $section === 'tech' ? 'Chia sẻ kỹ thuật trồng trọt và chăm sóc cây trồng phù hợp từng mùa vụ.' : 'Tin tức, câu chuyện và định hướng nông nghiệp của nhà nông Việt Nam.'; ?></p>
        </div>
        
        <?php if ($selected_article): ?>
            <div class="news-detail">
                <article class="news-article">
                    <div class="news-detail-img">
                        <img src="<?php echo htmlspecialchars($selected_article['image']); ?>" alt="<?php echo htmlspecialchars($selected_article['alt']); ?>">
                    </div>
                    <div class="news-article-meta">
                        <span><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($selected_article['date']); ?></span>
                        <span><i class="fa-regular fa-folder"></i> <?php echo htmlspecialchars($selected_article['category']); ?></span>
                        <span><i class="fa-regular fa-user"></i> Ks. Ngọc Ánh Dương</span>
                    </div>
                    <h2 class="news-detail-title"><?php echo htmlspecialchars($selected_article['title']); ?></h2>
                    <div class="news-article-content">
                        <?php echo $selected_article['content']; ?>
                    </div>
                    <!-- Prev / Next Navigation -->
<div class="post-navigation">
    <?php if ($prev_article): ?>
        <a class="nav-prev" href="?section=<?php echo urlencode($section); ?>&article=<?php echo urlencode($prev_article['slug']); ?>">
            <div class="nav-label">← Bài trước</div>
            <div class="nav-title">
                <?php echo htmlspecialchars($prev_article['title']); ?>
            </div>
        </a>
    <?php else: ?>
        <div></div>
    <?php endif; ?>

    <?php if ($next_article): ?>
        <a class="nav-next" href="?section=<?php echo urlencode($section); ?>&article=<?php echo urlencode($next_article['slug']); ?>">
            <div class="nav-label">Bài sau →</div>
            <div class="nav-title">
                <?php echo htmlspecialchars($next_article['title']); ?>
            </div>
        </a>
    <?php else: ?>
        <div></div>
    <?php endif; ?>
</div>
                    <div class="news-article-tag">
                        Bài viết này được đăng trong <a href="?section=<?php echo urlencode($section); ?>"><?php echo $section === 'tech' ? 'Kỹ thuật trồng trọt' : 'Tin Tức Nhà Nông'; ?></a>. Đánh dấu liên kết thường trực.
                    </div>
                    <div class="comment-section">
                        <h3>Bình luận</h3>
                        <form class="comment-form" action="#" method="post">
                            <label for="comment">Bình luận *</label>
                            <textarea id="comment" name="comment" placeholder="Viết bình luận..." required></textarea>
                            <label for="name">Tên *</label>
                            <input id="name" type="text" name="name" placeholder="Tên của bạn" required>
                            <label for="email">Email *</label>
                            <input id="email" type="email" name="email" placeholder="Email của bạn" required>
                            <label for="website">Trang web</label>
                            <input id="website" type="url" name="website" placeholder="Địa chỉ trang web">
                            <div>
                                <input id="save-info" type="checkbox" name="save_info">
                                <label for="save-info">Lưu tên, email và trang web cho lần bình luận kế tiếp</label>
                            </div>
                            <button type="submit">Gửi bình luận</button>
                        </form>
                    </div>
                </article>

                <aside class="news-sidebar">
                    <div class="sidebar-widget">
                        <h3>Bài viết mới</h3>
                        <ul class="related-posts">
                            <?php foreach ($visible_articles as $article): ?>
                                <?php if ($article['slug'] !== $selected_article['slug']): ?>
                                    <li>
                                        <a href="?section=<?php echo urlencode($section); ?>&article=<?php echo urlencode($article['slug']); ?>"><?php echo htmlspecialchars($article['title']); ?></a>
                                        <span><?php echo htmlspecialchars($article['date']); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($visible_articles as $article): ?>
                    <div class="news-card">
                        <div class="news-img-wrapper">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['alt']); ?>">
                        </div>
                        <div class="news-body">
                            <div class="news-meta">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($article['date']); ?></span>
                                <span><i class="fa-regular fa-user"></i> Ks. Ngọc Ánh Dương</span>
                            </div>
                            <h3><a href="?section=<?php echo urlencode($section); ?>&article=<?php echo urlencode($article['slug']); ?>"><?php echo htmlspecialchars($article['title']); ?></a></h3>
                            <p class="news-desc">
                                <?php echo htmlspecialchars($article['excerpt']); ?>
                            </p>
                            <a href="?section=<?php echo urlencode($section); ?>&article=<?php echo urlencode($article['slug']); ?>" class="news-link">Đọc tiếp <i class="fa-solid fa-arrow-right-long"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination controls layout -->
            <?php if ($total_pages > 1): ?>
                <div class="text-center" style="margin-top: 4rem; display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php $isActive = $i === $current_page; ?>
                        <a href="?section=<?php echo urlencode($section); ?>&page=<?php echo $i; ?>" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid <?php echo $isActive ? 'var(--color-primary)' : 'var(--color-border)'; ?>; display: flex; align-items: center; justify-content: center; color: <?php echo $isActive ? 'var(--color-white)' : 'var(--color-dark-muted)'; ?>; background-color: <?php echo $isActive ? 'var(--color-primary)' : 'var(--color-white)'; ?>; font-weight: <?php echo $isActive ? '700' : '600'; ?>; text-decoration: none;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
include 'includes/footer.php';
?>
