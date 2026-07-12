/**
 * PRODUCTS AJAX FILTER & SEARCH SYSTEM
 * Cung cấp chức năng tìm kiếm thời gian thực, đồng bộ hóa bộ lọc Desktop/Mobile,
 * hiệu ứng chuyển cảnh mượt mà và quản lý URL bằng History API.
 */
document.addEventListener('DOMContentLoaded', function() {
    // ── Elements ──────────────────────────────────────────────────────────
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const categorySelect = document.getElementById('categorySelect');
    const sortSelect = document.getElementById('sortSelect');
    const sidebarCategoryLinks = document.querySelectorAll('#sidebarCategoryList a');
    const gridContainer = document.getElementById('productGridContainer');
    const resultsCount = document.getElementById('resultsCount');
    const searchLoading = document.getElementById('searchLoading');
    const resetSearchBtn = document.getElementById('resetSearchBtn');

    if (!gridContainer) return; // Chỉ chạy trên trang danh mục sản phẩm

    let searchDebounceTimeout = null;

    // ── 1. Gửi Yêu Cầu Tìm Kiếm AJAX (Core) ─────────────────────────────────
    function performSearch(shouldPushState = true) {
        // Lấy các tham số lọc hiện tại
        const searchQuery = searchInput ? searchInput.value.trim() : '';
        const selectedCategory = categorySelect ? categorySelect.value : 'all';
        const selectedSort = sortSelect ? sortSelect.value : 'featured';

        // Hiển thị trạng thái Loading
        if (searchLoading) {
            searchLoading.style.display = 'flex';
        }
        if (gridContainer) {
            // Thêm class loading để làm mờ lưới cũ
            gridContainer.classList.add('loading');
        }

        // Xây dựng URL cho AJAX request
        const ajaxUrl = `products.php?category=${encodeURIComponent(selectedCategory)}&search=${encodeURIComponent(searchQuery)}&sort=${encodeURIComponent(selectedSort)}&ajax=1`;

        // Xây dựng URL hiển thị cho người dùng (History API)
        let displayUrl = 'products.php';
        const params = [];
        if (selectedCategory !== 'all') params.push(`category=${encodeURIComponent(selectedCategory)}`);
        if (searchQuery !== '') params.push(`search=${encodeURIComponent(searchQuery)}`);
        if (selectedSort !== 'featured') params.push(`sort=${encodeURIComponent(selectedSort)}`);
        if (params.length > 0) {
            displayUrl += '?' + params.join('&');
        }

        // Thực hiện Fetch dữ liệu ngầm từ máy chủ
        fetch(ajaxUrl)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                // 1. Cập nhật số lượng hiển thị kết quả
                if (resultsCount && data.count_html) {
                    resultsCount.innerHTML = data.count_html;
                }

                // 2. Cập nhật lưới sản phẩm dạng HTML rút gọn
                if (gridContainer && data.grid_html) {
                    gridContainer.innerHTML = data.grid_html;
                }

                // 3. Đẩy URL mới lên thanh địa chỉ (History API)
                if (shouldPushState) {
                    history.pushState({
                        search: searchQuery,
                        category: selectedCategory,
                        sort: selectedSort
                    }, '', displayUrl);
                }

                // 4. Đồng bộ hóa nút "Xóa bộ lọc" nhanh
                updateResetButtonVisibility(searchQuery, selectedCategory);
            })
            .catch(error => {
                console.error('AJAX Search error:', error);
            })
            .finally(() => {
                // Tắt trạng thái Loading
                if (searchLoading) {
                    searchLoading.style.display = 'none';
                }
                if (gridContainer) {
                    gridContainer.classList.remove('loading');
                }
                
                // Kích hoạt hoạt ảnh xuất hiện mượt mà cho các card mới
                const cards = gridContainer.querySelectorAll('.product-card');
                cards.forEach((card, index) => {
                    card.style.animationDelay = `${index * 0.05}s`;
                });
            });
    }

    // ── 2. Trình theo dõi thay đổi phần tử (Event Listeners) ───────────────

    // Ngăn chặn hành vi submit load lại trang của Form
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }

    // Lắng nghe ô tìm kiếm (Debounce 300ms tránh spam request lên database)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounceTimeout);
            searchDebounceTimeout = setTimeout(() => {
                performSearch();
            }, 300);
        });
    }

    // Lắng nghe bộ chọn danh mục di động (Mobile Dropdown Select)
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            // Đồng bộ sang sidebar Desktop
            const selectedVal = this.value;
            syncSidebarActiveLink(selectedVal);
            performSearch();
        });
    }

    // Lắng nghe bộ chọn sắp xếp (Sort Select)
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            performSearch();
        });
    }

    // Lắng nghe click chọn danh mục trên Sidebar Desktop
    sidebarCategoryLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const categorySlug = this.getAttribute('data-slug');

            // 1. Đồng bộ sang dropdown select của di động
            if (categorySelect) {
                categorySelect.value = categorySlug;
            }

            // 2. Cập nhật trạng thái Active trên sidebar
            sidebarCategoryLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            // 3. Tiến hành tìm kiếm AJAX
            performSearch();
        });
    });

    // Lắng nghe nút Xóa bộ lọc (Reset Filter)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'resetSearchBtn') {
            e.preventDefault();
            resetAllFilters();
        }
    });

    // ── 3. Quản lý lịch sử trình duyệt (Browser Back/Forward) ────────────────
    window.addEventListener('popstate', function(e) {
        // Lấy thông tin trạng thái từ lịch sử trình duyệt
        const state = e.state;
        if (state) {
            if (searchInput) searchInput.value = state.search || '';
            if (categorySelect) categorySelect.value = state.category || 'all';
            if (sortSelect) sortSelect.value = state.sort || 'featured';
            syncSidebarActiveLink(state.category || 'all');
            performSearch(false); // Gọi tìm kiếm nhưng KHÔNG được đẩy tiếp state mới
        } else {
            // Khôi phục mặc định nếu state rỗng
            resetAllFilters(false);
        }
    });

    // ── 4. Hàm bổ trợ Helper ───────────────────────────────────────────────

    // Đồng bộ class Active trên Sidebar khi dropdown thay đổi
    function syncSidebarActiveLink(slug) {
        sidebarCategoryLinks.forEach(link => {
            if (link.getAttribute('data-slug') === slug) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Reset toàn bộ bộ lọc về trạng thái mặc định ban đầu
    function resetAllFilters(shouldPush = true) {
        if (searchInput) searchInput.value = '';
        if (categorySelect) categorySelect.value = 'all';
        if (sortSelect) sortSelect.value = 'featured';
        syncSidebarActiveLink('all');
        performSearch(shouldPush);
    }

    // Kiểm tra và hiển thị/ẩn nút xóa bộ lọc nhanh
    function updateResetButtonVisibility(search, category) {
        const resetArea = document.querySelector('.search-actions-row');
        
        if (search !== '' || category !== 'all') {
            if (!resetArea) {
                // Tạo mới nếu chưa có
                const newRow = document.createElement('div');
                newRow.className = 'search-actions-row';
                newRow.innerHTML = `<a href="products.php" class="reset-search-link" id="resetSearchBtn">Xóa bộ lọc</a>`;
                if (searchForm) searchForm.appendChild(newRow);
            }
        } else {
            if (resetArea) {
                resetArea.remove();
            }
        }
    }
});
