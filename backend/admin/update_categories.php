<?php
/**
 * Ngọc Ánh Dương Category Migration Script
 * Run this file once in your browser or command line to update the database categories and align sample products.
 */
require_once __DIR__ . '/../db.php';

echo "<pre>";
echo "Bắt đầu cập nhật cơ sở dữ liệu danh mục...\n";

// 1. Tạm thời tắt ràng buộc khóa ngoại
$database->query("SET FOREIGN_KEY_CHECKS = 0;");

// 2. Xóa các danh mục sản phẩm cũ (giữ lại danh mục news 'tech')
$deleteOldCats = $database->query("DELETE FROM categories WHERE type = 'product'");
if ($deleteOldCats) {
    echo "[Thành công] Đã dọn dẹp các danh mục sản phẩm cũ.\n";
} else {
    echo "[Lỗi] Lỗi khi dọn dẹp danh mục cũ: " . $database->error . "\n";
}

// 3. Định nghĩa danh mục mới
$newCategories = [
    [1, 'che-pham-vi-sinh-sinh-hoc', 'CHẾ PHẨM VI SINH, SINH HỌC', 'product'],
    [2, 'phan-bon-goc', 'PHÂN BÓN GỐC', 'product'],
    [3, 'phan-bon-la', 'PHÂN BÓN LÁ', 'product'],
    [4, 'phong-tru-con-trung-oc-hai', 'PHÒNG TRỪ CÔN TRÙNG, ỐC HẠI', 'product'],
    [5, 'phong-tru-nam-hai', 'PHÒNG TRỪ NẤM HẠI', 'product']
];

// Chèn các danh mục mới
foreach ($newCategories as $cat) {
    $stmt = $database->prepare("INSERT INTO categories (id, slug, name, type) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE slug = ?, name = ?, type = ?");
    if ($stmt) {
        $stmt->bind_param("issssss", $cat[0], $cat[1], $cat[2], $cat[3], $cat[1], $cat[2], $cat[3]);
        if ($stmt->execute()) {
            echo "[Thành công] Đã thêm/cập nhật danh mục: {$cat[2]} (ID: {$cat[0]})\n";
        } else {
            echo "[Lỗi] Không thể thêm danh mục {$cat[2]}: " . $stmt->error . "\n";
        }
        $stmt->close();
    } else {
        echo "[Lỗi] Prepare query lỗi: " . $database->error . "\n";
    }
}

// 4. Cập nhật khóa ngoại của các sản phẩm hiện có để khớp với danh mục mới
$productUpdates = [
    ['vi-sinh-bio-active', 1],
    ['vi-sinh-aquaculture-usa', 1],
    ['tang-luc-x3', 2],
    ['nuoi-dong-tro-thoat', 3],
    ['amino-acid-organic', 3]
];

foreach ($productUpdates as $up) {
    $stmt = $database->prepare("UPDATE products SET category_id = ? WHERE product_key = ?");
    if ($stmt) {
        $stmt->bind_param("is", $up[1], $up[0]);
        if ($stmt->execute()) {
            echo "[Thành công] Cập nhật danh mục cho sản phẩm '{$up[0]}' -> ID: {$up[1]}\n";
        }
        $stmt->close();
    }
}

// 5. Thay thế/Cập nhật các sản phẩm hóa chất công nghiệp cũ thành sản phẩm sinh học/nông nghiệp tương ứng
// Thay thế 'soda-ash-light' thành chế phẩm trừ sâu Neem Oil
$updateSoda = $database->query("UPDATE products SET 
    product_key = 'che-pham-tru-sau-sinh-hoc',
    name = 'Chế phẩm trừ sâu sinh học Neem Oil',
    category_id = 4,
    badge = 'Côn Trùng',
    badge_class = 'badge-bio',
    origin = 'Nhập khẩu Ấn Độ',
    image = 'images/bio-prep.jpg',
    description = 'Dầu neem ép lạnh nguyên chất chứa hàm lượng Azadirachtin cao, đặc trị hiệu quả các loại bọ trĩ, nhện đỏ, rệp sáp và sâu cuốn lá hữu cơ.'
    WHERE product_key = 'soda-ash-light' OR product_key = 'che-pham-tru-sau-sinh-hoc'");
if ($updateSoda) {
    echo "[Thành công] Đã chuyển đổi sản phẩm hóa chất cũ sang Chế phẩm trừ sâu sinh học Neem Oil.\n";
}

// Thay thế 'citric-acid-monohydrate' thành Chế phẩm diệt trừ ốc bươu vàng
$updateCitric = $database->query("UPDATE products SET 
    product_key = 'che-pham-diet-oc-sinh-hoc',
    name = 'Chế phẩm diệt trừ ốc bươu vàng',
    category_id = 4,
    badge = 'Trừ Ốc',
    badge_class = '',
    origin = 'Việt Nam',
    image = 'images/nuoi-dong.jpg',
    description = 'Chế phẩm thảo mộc tự nhiên dạng bả mồi, dẫn dụ và tiêu diệt cực nhanh ốc bươu vàng, ốc sên gây hại mà không ảnh hưởng tới môi trường ao ruộng.'
    WHERE product_key = 'citric-acid-monohydrate' OR product_key = 'che-pham-diet-oc-sinh-hoc'");
if ($updateCitric) {
    echo "[Thành công] Đã chuyển đổi sản phẩm hóa chất cũ sang Chế phẩm diệt trừ ốc bươu vàng.\n";
}

// Thay thế 'chlorine-aquafit' thành Chế phẩm nấm đối kháng Trichoderma
$updateChlorine = $database->query("UPDATE products SET 
    product_key = 'trichoderma-doi-khang-nam',
    name = 'Chế phẩm nấm đối kháng Trichoderma',
    category_id = 5,
    badge = 'Trị Nấm',
    badge_class = 'badge-bio',
    origin = 'Việt Nam',
    image = 'images/bio-prep.jpg',
    description = 'Chứa hàng tỷ bào tử nấm Trichoderma harzianum đối kháng mạnh mẽ, tiêu diệt các nấm bệnh gây thối rễ, lở cổ rễ, vàng lá chín sớm.'
    WHERE product_key = 'chlorine-aquafit' OR product_key = 'trichoderma-doi-khang-nam'");
if ($updateChlorine) {
    echo "[Thành công] Đã chuyển đổi sản phẩm hóa chất cũ sang Chế phẩm nấm đối kháng Trichoderma.\n";
}

// 6. Bật lại ràng buộc khóa ngoại
$database->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "\nHoàn thành tất cả các thay đổi! Vui lòng xóa tệp tin này sau khi chạy để đảm bảo an toàn.\n";
echo "</pre>";
?>
