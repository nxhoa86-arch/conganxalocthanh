<?php
/*
 * BO MAY RENDER TRANG DOC LAP (LEGACY): dung cho cac trang goi site_header()/site_footer().
 * KHONG include file nay trong trang da ke thua Templates.dwt.php, tranh lap Header/Body/Footer.
 */
require_once __DIR__ . '/common.php';

// Nap danh muc mot lan o pham vi chung de index.php va cac trang con deu dung duoc.
// Bien $menuDanhMuc ton tai ngay sau khi require template.php, khong phu thuoc
// vao bien cuc bo ben trong ham site_header().
$menuDanhMuc = [];
try {
    if (isset($conn) && $conn instanceof PDO) {
        $menuStmt = $conn->query(
            "SELECT madanhmuc, tendanhmuc
             FROM danhmuc
             WHERE trang_thai = 1
             ORDER BY madanhmuc ASC"
        );
        $menuDanhMuc = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('Khong the nap danh muc cho template: ' . $e->getMessage());
    $menuDanhMuc = [];
}
$GLOBALS['menuDanhMuc'] = $menuDanhMuc;

if (!function_exists('site_header')) {
    function site_header(string $title = '', string $active = '', string $searchValue = '', string $tickerText = ''): void {
        $days = ['Chủ Nhật','Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy'];
        $today = $days[(int)date('w')] . ', ' . date('d/m/Y');
        $loggedIn = function_exists('current_user_id') && current_user_id();
        $admin = function_exists('is_admin') && is_admin();
        $canbo = function_exists('is_canbo') && is_canbo();
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && function_exists('site_track_visit')) $GLOBALS['site_visit_stats'] = site_track_visit($GLOBALS['conn']);
        echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>' . h($title ?: 'Công an xã Lộc Thành') . ' | Cổng thông tin điện tử</title>';
        echo '<meta name="description" content="Cổng thông tin điện tử Công an xã Lộc Thành, tỉnh Đồng Nai.">';
        // Nap CSS bo cuc truoc, CSS dung chung style.css nap cuoi de tranh bi ghi de.
        $cssVersion = static function (string $file): string { $path = __DIR__ . '/' . $file; return is_file($path) ? (string)filemtime($path) : '1'; };
        echo '<link rel="stylesheet" href="template.css?v=' . $cssVersion('template.css') . '">';
        if ($active === 'home') {
            echo '<link rel="stylesheet" href="home.css?v=' . $cssVersion('home.css') . '">';
            echo '<link rel="stylesheet" href="news-carousel.css?v=' . $cssVersion('news-carousel.css') . '">';
        } else { echo '<link rel="stylesheet" href="admin.css?v=' . $cssVersion('admin.css') . '">'; }
        echo '<link rel="stylesheet" href="style.css?v=' . $cssVersion('style.css') . '">';
        // Lop bao ve de font Times New Roman khong bi CSS trang con ghi de.
        echo '<style>
            html,body,body *,button,input,select,textarea{font-family:"Times New Roman",Times,serif!important}
            body{font-size:14px}
            p,li,td,th,label,input,select,textarea{font-size:14px}
            .article-detail,.article-body,.article-content,.news-content,.post-content{font-family:"Times New Roman",Times,serif!important;font-size:14px!important;line-height:1.7}
            .nav-inner > a{font-size:16px!important}
            .nav-inner > a.active{background:#8f0012}
            .category-page{background:#fff;border:1px solid #e1e6ee;border-radius:10px;padding:24px;margin:10px 0 28px;box-shadow:0 4px 18px rgba(15,23,42,.05)}
            .category-page h1{margin:0 0 8px;color:#a90016;font-size:25px}
            .category-page .category-intro{color:#64748b;margin:0 0 22px}
            .subcategory-block{margin:22px 0 28px}
            .subcategory-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:2px solid #b90016;padding:0 0 10px;margin-bottom:14px}
            .subcategory-heading h2{margin:0;color:#18324f;font-size:20px}
            .subcategory-heading a{color:#a90016;text-decoration:none;font-weight:700}
            .category-news-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
            .category-news-card{min-width:0;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#fff}
            .category-news-card img{display:block;width:100%;height:170px;object-fit:cover;background:#f1f5f9}
            .category-news-card .news-card-body{padding:12px}
            .category-news-card h3{font-size:17px;line-height:1.4;margin:0 0 8px}
            .category-news-card h3 a{color:#18324f;text-decoration:none}
            .category-news-card h3 a:hover{color:#b90016}
            .category-news-card p{font-size:15px;line-height:1.5;color:#64748b;margin:0 0 8px}
            .category-news-card small{font-size:13px;color:#8a94a4}
            .subcategory-empty,.category-empty{padding:18px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;color:#64748b}
            @media(max-width:900px){.category-news-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
            @media(max-width:560px){.category-news-grid{grid-template-columns:1fr}.category-page{padding:15px}}
        </style>';
        echo '</head><body>';
        echo '<header class="site-header"><div class="masthead"><div class="container masthead-inner">';
        echo '<a class="identity" href="index.php"><img src="assets/quoc-huy.png" alt="Quốc huy"><div><span class="agency">CÔNG AN TỈNH ĐỒNG NAI</span><strong>CÔNG AN XÃ LỘC THÀNH</strong><small>VÌ AN NINH TỔ QUỐC - VÌ HẠNH PHÚC NHÂN DÂN</small></div></a>';
        echo '<div class="masthead-tools"><div class="utility"><a href="index.php#sitemap">♟ Sơ đồ site</a><i></i><a href="index.php#lien-he">✉ Liên hệ</a><i></i><a href="PhanAnh.php">♧ Góp ý</a></div>';
        echo '<form class="search" action="index.php" method="get"><input type="search" name="q" value="' . h($searchValue) . '" placeholder="Nhập từ khóa tìm kiếm..." aria-label="Từ khóa tìm kiếm"><button type="submit" aria-label="Tìm kiếm">⌕</button></form><div class="font-tools">';
        if ($loggedIn) echo '<span class="hello">Xin chào, ' . h(current_user_name()) . '</span><br><a class="logout" href="DangXuat.php">Đăng xuất</a>';
        else echo '<a href="DangKy.php">Đăng ký</a> &nbsp;|&nbsp; <a href="DangNhap.php">Đăng nhập</a>';
        echo '</div></div></div></div><nav class="main-nav"><div class="container nav-inner">';
        // Dung cung mang danh muc da nap o dau tep, tranh truy van lap lai.
        $navCategories = $GLOBALS['menuDanhMuc'] ?? [];
        if (!is_array($navCategories)) $navCategories = [];

        echo '<a class="home' . ($active === 'home' ? ' active' : '') . '" href="index.php" aria-label="Trang chủ">⌂</a>';
        foreach ($navCategories as $category) {
            $categoryId = (int)$category['madanhmuc'];
            $categoryName = (string)$category['tendanhmuc'];
            $isCurrentCategory = isset($_GET['danhmuc']) && (int)$_GET['danhmuc'] === $categoryId;
            $navClass = $isCurrentCategory ? ' class="active"' : '';
            echo '<a' . $navClass . ' href="chuyenmuc.php?danhmuc=' . $categoryId . '">' . h($categoryName) . '</a>';
        }
        echo '<a href="PhanAnh.php">GỬI PHẢN ÁNH</a><a href="tracuu_phananh.php">TRA CỨU</a>';
        if ($admin) {
            echo '<span class="nav-admin-label">QUẢN TRỊ HỆ THỐNG</span>';
            echo '<a href="QuanLyNguoiDung.php">NGƯỜI DÙNG</a>';
            echo '<a href="quanlydanhmuc.php">DANH MỤC</a>';
            echo '<a href="quanlytintuc.php">QUẢN LÝ TIN</a>';
            echo '<a href="phananh_kiennghi.php">XỬ LÝ PHẢN ÁNH</a>';
            echo '<a href="baocao.php">BÁO CÁO</a>';
        } elseif ($canbo) {
            echo '<span class="nav-admin-label">KHU VỰC CÁN BỘ</span>';
            echo '<a href="baocao.php">BÁO CÁO CÔNG TÁC</a>';
            echo '<a href="phananh_kiennghi.php">XỬ LÝ PHẢN ÁNH</a>';
        }
        echo '</div></nav><div class="ticker"><div class="container ticker-inner"><span class="date">▦ ' . h($today) . '</span><span class="star">★</span><strong>THÔNG TIN:</strong><span>' . h($tickerText !== '' ? $tickerText : ($title ?: 'Cổng thông tin điện tử Công an xã Lộc Thành')) . '</span></div></div></header>';
        echo '<main class="container page-main">';
    }
    function site_footer(): void {
        echo '</main><footer id="lien-he"><div class="container footer-inner">';
        echo '<div class="footer-brand"><img src="assets/quoc-huy.png" alt="Quốc huy"><div><b>CÔNG AN XÃ LỘC THÀNH</b><small>VÌ AN NINH TỔ QUỐC - VÌ HẠNH PHÚC NHÂN DÂN</small></div></div>';
        echo '<div><h3>THÔNG TIN LIÊN HỆ</h3><p>Địa chỉ: Xã Lộc Thành, tỉnh Đồng Nai</p><p>Cổng thông tin điện tử Công an xã Lộc Thành</p></div>';
        echo '<div><h3>LIÊN KẾT</h3><a href="PhanAnh.php">Gửi phản ánh, kiến nghị</a><a href="tracuu_phananh.php">Tra cứu phản ánh</a></div>';
        echo '<div id="sitemap"><h3>SƠ ĐỒ SITE</h3><a href="index.php">Trang chủ</a><a href="index.php#tin-tuc">Tin tức - sự kiện</a><a href="PhanAnh.php">Phản ánh, kiến nghị</a></div>';
        echo '</div>';
        $stats = $GLOBALS['site_visit_stats'] ?? ['total' => null, 'online' => null];
        if (function_exists('site_stats_html')) echo site_stats_html($stats);
        echo '<div class="copyright">© ' . date('Y') . ' CÔNG AN XÃ LỘC THÀNH - TỈNH ĐỒNG NAI</div></footer></body></html>';
    }
}
