<?php
require_once __DIR__ . '/../auth.php';

// Enforce admin permission
auth_require_role('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("ID không hợp lệ.");
}

$res = db_query("SELECT * FROM quote_requests WHERE id = ?", "i", [$id]);
if (!$res || $res->num_rows === 0) {
    die("Không tìm thấy yêu cầu báo giá.");
}
$quote = $res->fetch_assoc();

// Default Company Info
$company_name = "CÔNG TY CP HÓA CHẤT NHẬP KHẨU NGỌC ÁNH DƯƠNG";
$company_address = "Số 100 đường A3, KDC Phú An, P. Hưng Phú, Q. Cái Răng, TP. Cần Thơ";
$company_phone = "0976.828.171";
$company_email = "ngocanhduongchemical@gmail.com";
$company_website = "www.ngocanhduong.vn";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa Đơn / Báo Giá - <?= h($quote['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0b6623;
        }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f5;
            color: #18181b;
        }
        .invoice-wrapper {
            max-width: 800px;
            margin: 2rem auto;
            background: #ffffff;
            padding: 3rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .header-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        .company-details {
            font-size: 0.85rem;
            line-height: 1.6;
            color: #52525b;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
            color: #18181b;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .invoice-title p {
            margin: 0;
            font-size: 0.9rem;
            color: #71717a;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .details-box {
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .details-box h3 {
            margin-top: 0;
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 1rem;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 0.5rem;
        }
        .details-row {
            display: flex;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .details-label {
            width: 120px;
            font-weight: 600;
            color: #334155;
        }
        .details-value {
            flex: 1;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .invoice-table th {
            background-color: var(--primary-color);
            color: #ffffff;
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .invoice-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }
        .invoice-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .table-empty-row td {
            color: #94a3b8;
            font-style: italic;
        }
        .message-box {
            margin-bottom: 2rem;
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
            background: #f0fdf4;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .message-box strong {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        .footer-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            text-align: center;
            margin-top: 4rem;
        }
        .signature-box p {
            font-weight: 600;
            margin-bottom: 4rem;
        }
        .signature-line {
            width: 200px;
            margin: 0 auto;
            border-bottom: 1px dashed #cbd5e1;
        }
        .print-btn-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .btn-print {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 24px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print:hover {
            opacity: 0.9;
        }
        @media print {
            body {
                background-color: #ffffff;
            }
            .invoice-wrapper {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .print-btn-container {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="print-btn-container">
    <button class="btn-print" onclick="window.print()">
        🖨️ In Hóa Đơn / Báo Giá
    </button>
    <p style="margin-top: 15px; color: #64748b; font-size: 0.9rem;">
        <i>* Mẹo: Bạn có thể click chuột trực tiếp vào các ô trống (Sản phẩm, Số lượng, Đơn giá...) bên dưới để gõ số tiền trước khi bấm In.</i>
    </p>
</div>

<div class="invoice-wrapper">
    <div class="header">
        <div>
            <div class="header-logo"><?= h($company_name) ?></div>
            <div class="company-details">
                <strong>Địa chỉ:</strong> <?= h($company_address) ?><br>
                <strong>Điện thoại:</strong> <?= h($company_phone) ?><br>
                <strong>Website:</strong> <?= h($company_website) ?><br>
                <strong>Email:</strong> <?= h($company_email) ?>
            </div>
        </div>
        <div class="invoice-title">
            <h1>Báo Giá</h1>
            <p><strong>Mã phiếu:</strong> BG-<?= date('ymd', strtotime($quote['created_at'])) ?>-<?= sprintf('%04d', $quote['id']) ?></p>
            <p><strong>Ngày lập:</strong> <?= date('d/m/Y') ?></p>
        </div>
    </div>

    <div class="details-grid">
        <div class="details-box">
            <h3>Thông Tin Khách Hàng</h3>
            <div class="details-row">
                <div class="details-label">Họ Tên:</div>
                <div class="details-value"><strong><?= h($quote['name']) ?></strong></div>
            </div>
            <div class="details-row">
                <div class="details-label">Điện thoại:</div>
                <div class="details-value"><?= h($quote['phone']) ?></div>
            </div>
        </div>
        <div class="details-box">
            <h3>Thông Tin Yêu Cầu</h3>
            <div class="details-row">
                <div class="details-label">Sản phẩm:</div>
                <div class="details-value"><strong><?= h($quote['product_name']) ?></strong></div>
            </div>
            <div class="details-row">
                <div class="details-label">Mã SP:</div>
                <div class="details-value"><?= h($quote['product_key'] ?: 'N/A') ?></div>
            </div>
            <div class="details-row">
                <div class="details-label">Ngày gửi:</div>
                <div class="details-value"><?= date('d/m/Y H:i', strtotime($quote['created_at'])) ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($quote['message'])): ?>
    <div class="message-box">
        <strong><i class="fa-solid fa-comment"></i> Lời nhắn từ khách hàng:</strong>
        <?= nl2br(h($quote['message'])) ?>
    </div>
    <?php endif; ?>

    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 5%">STT</th>
                <th style="width: 40%">Tên Hàng Hóa / Dịch Vụ</th>
                <th style="width: 15%; text-align: center;">Số lượng</th>
                <th style="width: 20%; text-align: right;">Đơn giá</th>
                <th style="width: 20%; text-align: right;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td contenteditable="true">
                    <strong><?= h($quote['product_name']) ?></strong><br>
                    <small style="color: #64748b;">Mã SP: <?= h($quote['product_key']) ?></small>
                </td>
                <td style="text-align: center;" contenteditable="true">1</td>
                <td style="text-align: right;" contenteditable="true">...</td>
                <td style="text-align: right;" contenteditable="true">...</td>
            </tr>
            <tr class="table-empty-row">
                <td contenteditable="true">2</td>
                <td contenteditable="true"></td>
                <td style="text-align: center;" contenteditable="true"></td>
                <td style="text-align: right;" contenteditable="true"></td>
                <td style="text-align: right;" contenteditable="true"></td>
            </tr>
            <tr class="table-empty-row">
                <td contenteditable="true">3</td>
                <td contenteditable="true"></td>
                <td style="text-align: center;" contenteditable="true"></td>
                <td style="text-align: right;" contenteditable="true"></td>
                <td style="text-align: right;" contenteditable="true"></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: 600;">Tổng cộng:</td>
                <td style="text-align: right; font-weight: 700; color: var(--primary-color);" contenteditable="true">...................</td>
            </tr>
        </tbody>
    </table>

    <div class="footer-signatures">
        <div class="signature-box">
            <p>Khách Hàng</p>
            <div class="signature-line"></div>
            <div style="margin-top: 10px; color: #64748b; font-size: 0.85rem;">(Ký, ghi rõ họ tên)</div>
        </div>
        <div class="signature-box">
            <p>Đại Diện Công Ty</p>
            <div class="signature-line"></div>
            <div style="margin-top: 10px; color: #64748b; font-size: 0.85rem;">(Ký, ghi rõ họ tên)</div>
        </div>
    </div>
</div>

</body>
</html>
