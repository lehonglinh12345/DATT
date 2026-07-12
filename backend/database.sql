-- Database schema for Ngọc Ánh Dương website
-- Use this file in XAMPP / phpMyAdmin to create the database and tables.

CREATE DATABASE IF NOT EXISTS `ngoc_anh_duong`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ngoc_anh_duong`;

-- Categories for products and article sections.
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `type` ENUM('product','news') NOT NULL DEFAULT 'product',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table used on products.php and product-detail.php
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_key` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `badge` VARCHAR(80) DEFAULT NULL,
  `badge_class` VARCHAR(80) DEFAULT NULL,
  `origin` VARCHAR(150) DEFAULT NULL,
  `price` VARCHAR(120) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `sales_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_product_key` (`product_key`),
  KEY `idx_products_category_id` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- News / technique articles table used on news.php
CREATE TABLE IF NOT EXISTS `news_articles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(120) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `section` VARCHAR(120) NOT NULL DEFAULT 'tech',
  `category` VARCHAR(150) DEFAULT NULL,
  `excerpt` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `image_alt` VARCHAR(255) DEFAULT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('draft','published') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_news_articles_slug` (`slug`),
  KEY `idx_news_articles_section` (`section`),
  KEY `idx_news_articles_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contact form submissions from contact.php
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `save_info` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('new','read','closed') NOT NULL DEFAULT 'new',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_messages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product Quote Requests submissions
CREATE TABLE IF NOT EXISTS `quote_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(255) NOT NULL,
  `product_key` VARCHAR(100) DEFAULT NULL,
  `product_link` VARCHAR(512) DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('new','read','completed') NOT NULL DEFAULT 'new',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quote_requests_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Optional comments table for news detail pages
CREATE TABLE IF NOT EXISTS `news_comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `comment` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_news_comments_article` (`article_id`),
  CONSTRAINT `fk_news_comments_article` FOREIGN KEY (`article_id`) REFERENCES `news_articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional homepage customer reviews table
CREATE TABLE IF NOT EXISTS `customer_reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author` VARCHAR(150) NOT NULL,
  `position` VARCHAR(150) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `rating` TINYINT(1) DEFAULT NULL,
  `source` VARCHAR(100) DEFAULT 'google_maps',
  `review_date` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample categories
INSERT INTO `categories` (`id`, `slug`, `name`, `type`) VALUES
  (1, 'che-pham-vi-sinh-sinh-hoc', 'CHẾ PHẨM VI SINH, SINH HỌC', 'product'),
  (2, 'phan-bon-goc', 'PHÂN BÓN GỐC', 'product'),
  (3, 'phan-bon-la', 'PHÂN BÓN LÁ', 'product'),
  (4, 'phong-tru-con-trung-oc-hai', 'PHÒNG TRỪ CÔN TRÙNG, ỐC HẠI', 'product'),
  (5, 'phong-tru-nam-hai', 'PHÒNG TRỪ NẤM HẠI', 'product'),
  (6, 'tech', 'Kỹ thuật trồng cây', 'news');

-- Sample products
INSERT INTO `products` (`product_key`, `name`, `category_id`, `badge`, `badge_class`, `origin`, `price`, `image`, `views`, `sales_count`, `description`) VALUES
  ('tang-luc-x3', 'Phân bón gốc Tăng Lực X3', 2, 'Nông Nghiệp', '', 'Nhập khẩu Hàn Quốc', 'Liên hệ báo giá', 'images/tang-luc-x3.jpg', 250, 120, 'Dòng phân bón gốc cao cấp chuyên phục hồi đất bạc màu, kích thích bộ rễ phát triển cực mạnh, tăng khả năng đẻ nhánh cho lúa.'),
  ('nuoi-dong-tro-thoat', 'Dưỡng chất lúa Nuôi Đòng - Trổ Thoát', 3, 'Nông Nghiệp', '', 'Nhập khẩu Châu Âu', 'Liên hệ báo giá', 'images/nuoi-dong.jpg', 180, 95, 'Dòng phân bón lá chuyên dùng trong giai đoạn làm đòng giúp đòng to mập, trổ bông đồng loạt, hạn chế nghẹt đòng hiệu quả.'),
  ('vi-sinh-bio-active', 'Chế phẩm sinh học vi sinh Bio-Active', 1, 'Vi Sinh', 'badge-bio', 'Nhật Bản', 'Liên hệ báo giá', 'images/bio-prep.jpg', 320, 150, 'Chứa tổ hợp các chủng vi sinh vật hữu ích có tác dụng phân hủy mùn hữu cơ rơm rạ, khử độc phèn, độc hữu cơ và tăng đề kháng đất.'),
  ('che-pham-tru-sau-sinh-hoc', 'Chế phẩm trừ sâu sinh học Neem Oil', 4, 'Côn Trùng', 'badge-bio', 'Nhập khẩu Ấn Độ', 'Liên hệ báo giá', 'images/bio-prep.jpg', 110, 45, 'Dầu neem ép lạnh nguyên chất chứa hàm lượng Azadirachtin cao, đặc trị hiệu quả các loại bọ trĩ, nhện đỏ, rệp sáp và sâu cuốn lá hữu cơ.'),
  ('che-pham-diet-oc-sinh-hoc', 'Chế phẩm diệt trừ ốc bươu vàng', 4, 'Trừ Ốc', '', 'Việt Nam', 'Liên hệ báo giá', 'images/nuoi-dong.jpg', 90, 30, 'Chế phẩm thảo mộc tự nhiên dạng bả mồi, dẫn dụ và tiêu diệt cực nhanh ốc bươu vàng, ốc sên gây hại mà không ảnh hưởng tới môi trường ao ruộng.'),
  ('trichoderma-doi-khang-nam', 'Chế phẩm nấm đối kháng Trichoderma', 5, 'Trị Nấm', 'badge-bio', 'Việt Nam', 'Liên hệ báo giá', 'images/bio-prep.jpg', 210, 80, 'Chứa hàng tỷ bào tử nấm Trichoderma harzianum đối kháng mạnh mẽ, tiêu diệt các nấm bệnh gây thối rễ, lở cổ rễ, vàng lá chín sớm.'),
  ('vi-sinh-aquaculture-usa', 'Chế phẩm vi sinh xử lý đáy ao nuôi', 1, 'Vi Sinh', 'badge-bio', 'Hoa Kỳ', 'Liên hệ báo giá', 'images/bio-prep.jpg', 150, 60, 'Chế phẩm chuyên biệt để xử lý khí độc NH3, H2S, làm sạch đáy ao nuôi tôm cá thâm canh mật độ cao.'),
  ('amino-acid-organic', 'Phân bón lá Amino Acid Organic Soluble', 3, 'Nông Nghiệp', '', 'Tây Ban Nha', 'Liên hệ báo giá', 'images/nuoi-dong.jpg', 130, 55, 'Cung cấp trực tiếp axit amin thiết yếu cho cây trồng giúp tăng hấp thụ dinh dưỡng nhanh chóng, phục hồi cây sau ngập úng hoặc sâu bệnh.');

-- Sample news articles
INSERT INTO `news_articles` (`slug`, `title`, `section`, `category`, `excerpt`, `image`, `image_alt`, `content`, `published_at`) VALUES
  ('rau-vu-dong', 'Một số biện pháp kỹ thuật chăm sóc cây rau vụ đông', 'tech', 'Kỹ thuật trồng trọt', 'Hiện nay cây vụ đông ở các tỉnh miền Bắc đang trong giai đoạn sinh trưởng phát triển mạnh, một số đang cho thu hoạch những lứa đầu. Thời tiết vụ đông năm nay có nhiều biến động...', 'images/news-1.jpg', 'Kỹ thuật chăm sóc cây rau vụ đông', '<p>Vào vụ đông, điều kiện khí hậu thay đổi thường xuyên nên người trồng cần chủ động điều chỉnh lịch tưới, bón phân và phòng bệnh. Cây rau cần đủ ẩm để sinh trưởng tốt, nhưng đồng thời phải tránh đọng nước gây thối gốc.</p><p>Các biện pháp cơ bản bao gồm bón phân cân đối N-P-K, bổ sung phân hữu cơ và tưới nước đều vào buổi sáng để rễ hấp thụ dưỡng chất. Khi trời rét, cần che phủ thân đất bằng màng nông nghiệp và sử dụng phân kali để tăng sức đề kháng cho cây.</p><h3>Chăm sóc cụ thể</h3><p>Bón phân theo giai đoạn: lần 1 ngay sau gieo, lần 2 khi cây ra lá thật và lần 3 trước khi thu hoạch. Vệ sinh đồng ruộng sạch sẽ, cắt tỉa lá già và loại bỏ cây bệnh để tránh lây lan.</p>', '2026-01-16 08:00:00'),
  ('mat-gau', 'Kỹ thuật trồng, nhân giống và cách sử dụng cây Mật gấu', 'tech', 'Kỹ thuật trồng trọt', 'Một trong những kỹ thuật trồng nhân giống cây mật gấu được phổ biến rộng rãi nhất hiện nay là kỹ thuật giâm cành. Nhiều người đã thử nghiệm kỹ thuật này và thu lại những hiệu quả khả quan...', 'images/news-2.jpg', 'Kỹ thuật trồng cây mật gấu', '<p>Cây mật gấu phát triển tốt khi trồng bằng giâm cành. Chọn cành bánh tẻ, dài 15-20cm, có 3-4 mắt và ngâm thuốc kích thích ra rễ trước khi trồng. Đất trồng cần tơi xốp, thoát nước và giàu mùn.</p><p>Định kỳ bón phân hữu cơ, bổ sung phân lân và kali để cây ra hoa đều và cho lá xanh. Chăm sóc giữ ẩm đều và tránh ngập úng, đồng thời che bóng nhẹ vào mùa nắng gắt.</p><h3>Cách sử dụng</h3><p>Mật gấu thường được dùng trong chế phẩm trộn phân, bón gốc hoặc làm cây che phủ. Cần thu hoạch lá khi độ ẩm vừa phải, phơi khô nhẹ hoặc dùng tươi theo hướng dẫn kỹ thuật để đảm bảo hoạt tính.</p>', '2026-01-16 08:15:00'),
  ('sau-duc-than-cafe', 'Sâu đục thân hại cây cà phê và biện pháp phòng trừ', 'tech', 'Kỹ thuật trồng trọt', 'Sâu đục thân là đối tượng gây hại chủ yếu trên cà phê, làm giảm khả năng hấp thụ dinh dưỡng và suy yếu bộ khung. Vệ sinh đồng ruộng và áp dụng biện pháp sinh học giúp phòng tránh hiệu quả...', 'images/news-3.jpg', 'Sâu đục thân hại cây cà phê', '<p>Có 2 loại sâu đục thân hại cây cà phê là sâu đục thân mình trắng (Xylotrechus quadripes Chevrolat) và sâu đục thân mình hồng (Zeuze coffea Nietner). Chúng hoạt động quanh năm và phát triển mạnh ở những khu vực nhiệt độ cao và nhiều ánh sáng.</p><h3>1. Sâu đục thân mình trắng (Xylotrechus quadripes Chevrolat)</h3><p>Trưởng thành là một loại xén tóc nhỏ có màu xanh đen. Con trưởng thành đẻ trứng vào vết nứt của đoạn cành hoặc thân rải rác hoặc thành từng cụm. Sau khi nở, sâu non đục vào gỗ, rồi đục ngoằn nghèo quanh vòng cây, tiện ngang các mạch gỗ. Sâu đục tới đâu, đùn phân và mạt cưa bịt kín đến đó. Đến tuổi 5, tuổi 6 sâu đục ra phía gần vỏ tạo một khoảng rộng trong phần gỗ của cây và hóa nhộng tại đó.</p><p>Vòng đời từ trứng – sâu non – trưởng thành – đẻ trứng là 200 – 211 ngày trong vụ đông và 126 – 176 ngày đối với vụ hè.</p><p>Sâu phát triển quanh năm và thường gây hại nặng vào tháng 4, 5 và 10, 11. Trưởng thành ưa đẻ trứng vào những cây ít cành, thưa lá. Chúng hoạt động mạnh khi nhiệt độ cao, ánh sáng nhiều. Ruộng cà phê càng dãi nằng càng bị hại nặng.</p><p>Cây cà phê bị sâu đục thân mình trắng gây hại có các biểu hiện sau:</p><ul><li>Toàn bộ lá phía trên ngọn bị vàng héo, các lá phía dưới còn xanh tốt, cây mọc thêm nhiều chồi thân.</li><li>Trên thân có những đường lằn nổi lên theo vòng, vỏ bị nứt nẻ, có những lỗ đục đường kính 2-3 mm.</li><li>Cây dễ bị gãy gục tại chỗ bị sâu đục.</li><li>Chẻ dọc thân cây thấy có đường rãnh sâu đục, phát hiện có sâu non màu trắng ngà, không có chân, toàn thân gồm nhiều đốt.</li></ul><h3>2. Sâu đục thân mình hồng (Zeuze coffea Nietner)</h3><p>Trưởng thành là loài bướm trắng với nhiều chấm nhỏ màu xanh biếc hoặc màu xanh đen, thân dài 20-30mm, màu đỏ và được phủ bằng lớp lông trắng. Sâu non đẫy sức dài 30-50mm màu hồng. Nhộng dài 15-34mm.</p><p>Bướm cái đẻ trứng vào vỏ cây, sâu non đục vào giữa thân cây và đùn mạt gỗ ra ngoài. Cây bị hại dễ bị gãy ngang.</p><p>Sâu thường phá hại thân, hoặc cành cấp 1, cấp 2. Sâu có thể phá hại từ cây này sang cây khác hoặc cành này sang cành khác, gây ảnh hưởng đến sinh trưởng và phát triển của cây thậm chí gây chết cây.</p><p>Suốt vòng đời của sâu đục vào thân và sống bên trong đó, đến khi trưởng thành bay ra ngoài tìm những nơi cành lá xanh tốt xum xuê để đẻ trứng, trứng được đẻ thành từng ổ ở vỏ cây.</p><p>Sâu phát triển thích hợp ở nhiệt độ 20-28oC, dưới 18oC sâu phát triển chậm, sâu thường gây hại ở cây có tán không cân đối, những vườn không có cây che bóng.</p><h3>3. Biện pháp phòng trừ</h3><p>Đối với vườn cà phê đang bị sâu đục thân phá hại, cần tiến hành cưa bỏ những đoạn cành, thân cây có sâu đục thân hại để tiêu diệt bằng cách đốt hoặc chẻ thân cây ra, thu sâu non để diệt.</p><p>Con trưởng thành (bướm, xén tóc) thường bị kích thích và thu hút bởi ánh sáng vì thế có thể dùng bẫy đèn để bắt các con trưởng thành và tiêu diệt vào đầu mùa mưa. Thời điểm này chúng thường ghép đôi và sinh sản.</p><p>Sử dụng một số loại thuốc BVTV sau để phun trừ: hoạt chất Diazinon (Diazol 10G, liều lượng 15g/gốc; Diazan 50EC, liều lượng 2,5 lít/ha); hoạt chất Chlorpyrifos Ethyl + Cypermethrin (Tungcydan 55EC, liều lượng 1,0 lít/ha)… Lượng nước phun 800 lít/ha, phun lên thân cây 2-3 lần để diệt sâu non ngay từ khi mới nở. Chú ý phun ướt đều toàn bộ cây, đặc biệt phun kỹ thân cây, và phun vào sáng sớm hoặc chiều mát.</p><p>Trồng cây che bóng làm giảm cường độ ánh sáng. Cắt tỉa cành để cây có được bộ tán lá cân đối và thân cây được che phủ từ trên xuống dưới. Bón phân cân đối, đầy đủ để cây phát triển tốt, tăng sức đề kháng cho cây.</p><p>Bảo vệ thiên địch, loài ong Apenesia sahyadrica Azevedo & Waichert ký sinh trên giai đoạn sâu non của sâu đục thân mình trắng.</p>', '2026-01-16 08:30:00');

INSERT INTO `news_articles` (`slug`, `title`, `section`, `category`, `excerpt`, `image`, `image_alt`, `content`, `published_at`) VALUES
  ('gao-xanh-phat-thai-thap', 'Từ ‘gạo xanh’ đến nông sản phát thải thấp', 'news', 'Tin nhà nông', 'Không chỉ sản xuất và xuất khẩu thành công gạo phát thải thấp, ngành nông nghiệp đang tiến thêm một bước khi nhân rộng mô hình này sang các loại cây trồng khác.', 'images/news-4.jpg', 'Từ gạo xanh đến nông sản phát thải thấp', '<p>Không chỉ sản xuất và xuất khẩu thành công gạo phát thải thấp, ngành nông nghiệp đang tiến thêm một bước khi nhân rộng mô hình này sang các loại cây trồng khác.</p><p>Sản phẩm “Gạo xanh, phát thải thấp” xuất khẩu đi Nhật vào ngày 5.6 vừa qua là thành quả từ các mô hình thí điểm “Đề án phát triển bền vững 1 triệu ha chuyên canh lúa chất lượng cao và phát thải thấp gắn với tăng trưởng xanh vùng ĐBSCL đến năm 2030” của Thủ tướng.</p><p>Từ gạo đến cà phê, chuối, mía, sắn… đều xanh. Hiện nay, các bộ ngành, địa phương và doanh nghiệp đang tiếp tục mở rộng đề án theo lộ trình.</p><h3>Giảm phát thải trong trồng trọt</h3><p>Đề án đặt mục tiêu giảm ít nhất 10% phát thải khí nhà kính trong lĩnh vực trồng trọt đến năm 2035. Để thực hiện, ngành nông nghiệp triển khai ít nhất 15 mô hình sản xuất giảm phát thải tại các vùng sinh thái có khả năng nhân rộng.</p><p>Đối với lúa, các giải pháp gồm: chuyển đổi sang mô hình cạn ở vùng kém hiệu quả, áp dụng lúa – tôm, tưới ngập – khô xen kẽ và tận dụng phụ phẩm rơm rạ.</p><p>Bộ NN-MT đang lên kế hoạch mở rộng sang nhiều loại cây trồng khác và xây dựng các gói kỹ thuật sản xuất giảm phát thải áp dụng cho lúa, sắn, cà phê, chuối và mía.</p>', '2026-01-15 09:00:00'),
  ('sau-rieng-vai-png', 'Sầu riêng, quả vải Việt Nam tăng vọt ở quốc đảo Papua New Guinea, vì sao?', 'news', 'Tin nhà nông', 'Trong số các thị trường xuất khẩu chủ lực của sầu riêng Việt Nam, quốc đảo Papua New Guinea đang ghi nhận mức tăng đột biến.', 'images/news-5.jpg', 'Sầu riêng quả vải xuất khẩu Papua New Guinea', '<p>Trong số các thị trường xuất khẩu chủ lực của sầu riêng Việt Nam, quốc đảo Papua New Guinea đang có bước tăng trưởng đột phá. Lý do chính là cầu nội địa mạnh, nguồn cung hạn chế và nhu cầu tiêu dùng hàng hóa an toàn.</p><p>Việt Nam tận dụng lợi thế xuất khẩu trái cây tươi với tiêu chuẩn chất lượng cao, trong đó sầu riêng và quả vải được chú ý bởi hương vị đặc trưng và chất lượng ổn định.</p><h3>Thách thức và cơ hội</h3><p>Thị trường PNG yêu cầu truy xuất nguồn gốc rõ ràng và quy trình sản xuất sạch. Các doanh nghiệp Việt Nam cần đẩy mạnh ghi nhãn, đóng gói và chuỗi lạnh để đảm bảo trái cây đến tay người tiêu dùng trong tình trạng tốt nhất.</p><p>Đây là cơ hội lớn cho nông dân vùng Nam Bộ, nhất là các tỉnh trồng sầu riêng và vải nhiều vùng, khi giá xuất khẩu tăng cao và quy trình sản xuất xanh ngày càng được ưu tiên.</p>', '2026-01-15 09:15:00'),
  ('dai-nong-trai-sau-rieng-cong-nghe', 'Đại nông trại sầu riêng dùng công nghệ', 'news', 'Tin nhà nông', 'Đại nông trại sầu riêng áp dụng công nghệ hiện đại để quản lý đất, nước và năng suất, mở rộng diện tích trồng chuyên canh.', 'images/news-6.jpg', 'Đại nông trại sầu riêng dùng công nghệ', '<p>Đại nông trại sầu riêng tại một tỉnh miền Nam đã ứng dụng công nghệ cảm biến độ ẩm, hệ thống tưới nhỏ giọt và quản lý dữ liệu để theo dõi tình trạng đất trồng. Nhờ đó, vườn cây được chăm sóc chính xác và tiết kiệm nước.</p><p>Công nghệ giúp giảm chi phí phân bón, tăng trưởng đồng đều và giảm thiểu sâu bệnh. Những nông trại chuyên canh sầu riêng hiện nay chú trọng áp dụng quản lý thông minh để đáp ứng tiêu chuẩn xuất khẩu.</p><h3>Chuỗi giá trị xanh</h3><p>Việc sử dụng công nghệ còn nâng cao năng lực truy xuất nguồn gốc và cung cấp chứng nhận chất lượng. Điều này tạo niềm tin cho đối tác Nhật Bản và châu Âu khi nhập khẩu sầu riêng Việt Nam.</p>', '2026-01-15 09:30:00'),
  ('nong-dan-dong-nai-trong-tre-lay-mang', 'Nông dân Đồng Nai trồng tre lấy măng kiếm 150 triệu đồng mỗi vụ', 'news', 'Tin nhà nông', 'Nông dân Đồng Nai chuyển sang trồng tre lấy măng và thu nhập đến 150 triệu đồng mỗi vụ nhờ giá măng ổn định.', 'images/news-7.jpg', 'Nông dân Đồng Nai trồng tre lấy măng', '<p>Nhiều nông dân Đồng Nai đang chuyển đổi một phần vườn sang trồng tre lấy măng. Với giá măng ổn định trên thị trường và đầu ra rộng, hộ dân có thể đạt doanh thu 150 triệu đồng mỗi vụ.</p><p>Tre được chăm sóc theo quy trình nông nghiệp sạch, dùng phân hữu cơ và hệ thống tưới nhỏ giọt để đảm bảo măng phát triển đều, không bị sâu bệnh.</p><h3>Hiệu quả canh tác</h3><p>Trồng tre lấy măng không đòi hỏi đất quá tốt nhưng cần quản lý độ ẩm và cắt tỉa thường xuyên. Thu hoạch măng được thực hiện vào buổi sáng sớm để sản phẩm đạt độ tươi cao.</p>', '2026-01-15 09:45:00'),
  ('gia-cay-giong-lam-dong-giam-manh', 'Giá cây giống ở Lâm Đồng đang cảnh cháy hàng, giờ đột nhiên giảm mạnh', 'news', 'Tin nhà nông', 'Giá cây giống ở Lâm Đồng tăng nóng rồi giảm mạnh, khiến nhiều người trồng phải điều chỉnh kế hoạch sản xuất.', 'images/news-8.jpg', 'Giá cây giống Lâm Đồng giảm mạnh', '<p>Hai tuần trước, giá cây giống ở Lâm Đồng ở mức cao kỷ lục do nhu cầu phục hồi diện tích trồng trọt sau hạn. Nay giá cây giống đã giảm mạnh khi nguồn cung tăng trở lại và thương lái cân đối nguồn hàng.</p><p>Việc giảm giá giúp các hộ trồng rau và hoa có cơ hội tái đầu tư, nhưng cũng đặt ra thách thức với nhà vườn vừa trồng phải điều chỉnh lịch sinh trưởng để tránh lỗ.</p><h3>Diễn biến thị trường</h3><p>Cây giống ươm nội địa đang được quan tâm nhiều hơn, vì chi phí thấp hơn nhập khẩu. Nói chung, sự biến động này phản ánh nhu cầu tìm nguồn giống chất lượng, giá hợp lý cho mùa vụ tới.</p>', '2026-01-14 10:00:00'),
  ('dua-hau-duoc-mua-gia-giam', 'Dưa hấu được mùa, giá giảm nhưng nông dân vẫn lãi khá', 'news', 'Tin nhà nông', 'Dù giá giảm khi dưa hấu vào mùa, nhiều nông dân vẫn thu lãi tốt nhờ năng suất cao và chi phí giảm.', 'images/news-9.jpg', 'Dưa hấu được mùa giá giảm', '<p>Dưa hấu đang được mùa, nên giá bán tại ruộng giảm nhẹ. Tuy nhiên, nhờ năng suất tốt và chi phí sản xuất hợp lý, nông dân vẫn giữ được lợi nhuận khá.</p><p>Người trồng chú trọng tưới nước đều, bón phân cân đối và thu hoạch đúng thời điểm để đảm bảo chất lượng trái. Mặc dù giá sụt, sản lượng cao giúp bù đắp doanh thu.</p><h3>Chi phí và lợi nhuận</h3><p>Cuối vụ, nhiều hộ tiết kiệm được chi phí nhờ sử dụng phân hữu cơ và quản lý sâu bệnh hiệu quả. Đây là minh chứng cho cách làm canh tác phù hợp với mùa vụ và thị trường.</p>', '2026-01-14 10:15:00');

-- Product images table for detail image slider
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product_id` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample product additional images
INSERT INTO `product_images` (`product_id`, `image_path`, `sort_order`) VALUES
  (1, 'images/nuoi-dong.jpg', 0),
  (1, 'images/bio-prep.jpg', 1);

