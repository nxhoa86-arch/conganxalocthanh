<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ketnoiPath = is_file(__DIR__ . '/ketnoi.php')
    ? __DIR__ . '/ketnoi.php'
    : dirname(__DIR__) . '/ketnoi.php';
if (is_file($ketnoiPath)) {
    require_once $ketnoiPath;
}

if (is_file(__DIR__ . '/common.php')) {
    require_once __DIR__ . '/common.php';
}

$homepageVisitStats = (isset($conn) && $conn instanceof PDO && function_exists('site_track_visit')) ? site_track_visit($conn) : ['total' => null, 'online' => null];

// Dùng đường dẫn tương đối để chạy ổn định cả trên trình duyệt và Dreamweaver Live View
$inTemplatesDir = (strtolower(basename(__DIR__)) === 'templates');
$basePrefix = $inTemplatesDir ? '../' : '';

$dayNames = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
$todayText = $dayNames[(int)date('w')] . ', ' . date('d/m/Y');

$role = strtolower(trim((string)($_SESSION['vaitro'] ?? $_SESSION['PhanQuyen'] ?? '')));
$isAdmin = in_array($role, ['quantri', 'admin', 'administrator'], true);
$isCanBo = ($role === 'canbo');
$isStaff = $isAdmin || $isCanBo;

$rawUserId = $_SESSION['nguoidung_id'] ?? null;
$rawUserName = $_SESSION['UserName'] ?? $_SESSION['taikhoan'] ?? null;
$isLoggedIn = (is_scalar($rawUserId) && trim((string)$rawUserId) !== '')
           || (is_scalar($rawUserName) && trim((string)$rawUserName) !== '');
$displayName = trim((string)($_SESSION['hoten'] ?? $rawUserName ?? 'Người dùng'));
$assetVer = '20261121';

// Bien dung rieng cho trang chu, tranh Notice khi render menu/ticker.
$page_title = 'Cổng thông tin điện tử Công an xã Lộc Thành';
$activeMenu = 'home';
$tickerText = 'Thông tin tuyên truyền và hoạt động của Công an xã Lộc Thành';

// Trang chủ: 06 tin mới mỗi trang, hỗ trợ tìm kiếm và lọc danh mục
$perPage = 9;
$page = function_exists('page_value') ? page_value() : max(1, (int)($_GET['page'] ?? 1));
$keyword = trim($_GET['q'] ?? '');
$categoryId = (isset($_GET['danhmuc']) && ctype_digit((string)$_GET['danhmuc']))
    ? (int)$_GET['danhmuc'] : 0;

$categories = [];
$news = [];                 // Tin hoạt động thuộc các danh mục còn lại
$selectedNews = [];         // Tin thuộc một trong bốn danh mục chọn nhanh
$selectedCategory = null;
$primaryCategories = [];
$primaryCategoryIds = [];
$carouselNews = [];
$featuredNews = [];
$total = 0;
$pages = 1;

// Chuẩn hóa tên danh mục để nhận diện dù có viết hoa, dấu hoặc thêm mô tả.
if (!function_exists('category_key')) {
    function category_key($value): string {
        $value = mb_strtolower(trim((string)$value), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $ascii = $ascii !== false ? $ascii : $value;
        $ascii = preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii));
        return trim($ascii, '-');
    }
}

if (isset($conn) && $conn instanceof PDO) {
    $categories = $conn->query(
        "SELECT madanhmuc, tendanhmuc FROM danhmuc WHERE trang_thai = 1 ORDER BY madanhmuc ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Bốn danh mục được đưa lên danh sách chọn nhanh.
    $primaryMatchers = [
        'tin-tuc' => ['tin-tuc', 'tin-tuc-su-kien'],
        'an-ninh-trat-tu' => ['an-ninh-trat-tu', 'antt'],
        'tuyen-truyen' => ['tuyen-truyen'],
        'gioi-thieu' => ['gioi-thieu']
    ];
    foreach ($categories as $cat) {
        $key = category_key($cat['tendanhmuc']);
        foreach ($primaryMatchers as $groupKey => $aliases) {
            foreach ($aliases as $alias) {
                if ($key === $alias || strpos($key, $alias . '-') === 0) {
                    $primaryCategories[$groupKey] = $cat;
                    $primaryCategoryIds[] = (int)$cat['madanhmuc'];
                    break 2;
                }
            }
        }
    }
    $primaryCategoryIds = array_values(array_unique($primaryCategoryIds));

    // Khu vực Hoạt động Công an xã hiển thị các danh mục còn lại,
    // không phụ thuộc tuyệt đối vào cách đặt tên trong CSDL.
    // Bốn danh mục chọn nhanh được loại khỏi khu vực này.
    $activityCategoryIds = [];
    $activityCategoryNames = [];
    foreach ($categories as $cat) {
        $catId = (int)($cat['madanhmuc'] ?? 0);
        if ($catId > 0 && !in_array($catId, $primaryCategoryIds, true)) {
            $activityCategoryIds[] = $catId;
            $activityCategoryNames[] = trim((string)($cat['tendanhmuc'] ?? ''));
        }
    }
    $activityCategoryIds = array_values(array_unique($activityCategoryIds));
    $activityCategoryNames = array_values(array_unique(array_filter($activityCategoryNames, 'strlen')));

    // Cho phép chọn mọi danh mục đang hoạt động trong CSDL.
    if ($categoryId > 0) {
        foreach ($categories as $cat) {
            if ((int)($cat['madanhmuc'] ?? 0) === $categoryId) {
                $selectedCategory = $cat;
                break;
            }
        }
        if (!$selectedCategory) $categoryId = 0;
    }

    // KHU VỰC TIN TỨC VÀ HOẠT ĐỘNG:
    // Lấy tất cả tin đã đăng, không phụ thuộc cách gắn danh mục trên CSDL.
    // Việc chọn lọc danh mục riêng vẫn được xử lý ở phần $selectedCategory bên dưới.
    $where = ["t.trangthai = 'da_dang'"];
    $params = [];

    if ($keyword !== '') {
        $where[] = '(t.detai LIKE :kw1 OR t.mota LIKE :kw2 OR t.motachitiet LIKE :kw3)';
        $like = '%' . $keyword . '%';
        $params[':kw1'] = $like;
        $params[':kw2'] = $like;
        $params[':kw3'] = $like;
    }

    $whereSql = implode(' AND ', $where);

    // Đếm tổng số tin đã đăng phù hợp từ khóa tìm kiếm.
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM tintuc t WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Phân trang: mặc định hiển thị 09 tin trên mỗi trang.
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    // Lấy danh sách tin mới nhất.
    $listSql = "SELECT t.*, d.tendanhmuc, c.tenchuyenmuc
                FROM tintuc t
                LEFT JOIN danhmuc d ON d.madanhmuc = t.madanhmuc
                LEFT JOIN chuyenmuc c ON c.machuyenmuc = t.machuyenmuc
                WHERE $whereSql
                ORDER BY t.ngay DESC, t.time DESC, t.ma DESC
                LIMIT :lim OFFSET :off";

    $listStmt = $conn->prepare($listSql);
    foreach ($params as $key => $value) {
        $listStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $listStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $listStmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $listStmt->execute();
    $news = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedCategory) {
        $selectedSql = "SELECT t.*, d.tendanhmuc, c.tenchuyenmuc
                        FROM tintuc t
                        LEFT JOIN danhmuc d ON d.madanhmuc = t.madanhmuc
                        LEFT JOIN chuyenmuc c ON c.machuyenmuc = t.machuyenmuc
                        WHERE t.trangthai = 'da_dang'
                          AND (t.madanhmuc = :selected_cat
                               OR t.machuyenmuc IN (
                                   SELECT cm.machuyenmuc FROM chuyenmuc cm
                                   WHERE cm.madanhmuc = :selected_cat_sub
                                     AND cm.trang_thai = 1
                               )
                               OR t.theloai = :selected_cat_name
                               OR t.id = :selected_cat_name_legacy)";
        $selectedParams = [':selected_cat' => (int)$selectedCategory['madanhmuc'], ':selected_cat_sub' => (int)$selectedCategory['madanhmuc'], ':selected_cat_name' => (string)$selectedCategory['tendanhmuc'], ':selected_cat_name_legacy' => (string)$selectedCategory['tendanhmuc']];
        if ($keyword !== '') {
            $selectedSql .= " AND (t.detai LIKE :skw1 OR t.mota LIKE :skw2 OR t.motachitiet LIKE :skw3)";
            $selectedLike = '%' . $keyword . '%';
            $selectedParams[':skw1'] = $selectedLike;
            $selectedParams[':skw2'] = $selectedLike;
            $selectedParams[':skw3'] = $selectedLike;
        }
        $selectedSql .= " ORDER BY t.ngay DESC, t.time DESC, t.ma DESC LIMIT 6";
        $selectedStmt = $conn->prepare($selectedSql);
        foreach ($selectedParams as $key => $value) {
            $selectedStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $selectedStmt->execute();
        $selectedNews = $selectedStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $carouselStmt = $conn->query(
        "SELECT t.ma, t.detai, t.hinh_dd, t.mota, t.ngay, t.theloai, d.tendanhmuc
         FROM tintuc t LEFT JOIN danhmuc d ON d.madanhmuc = t.madanhmuc
         WHERE t.trangthai = 'da_dang'
         ORDER BY t.ngay DESC, t.time DESC, t.ma DESC LIMIT 16"
    );
    $carouselNews = $carouselStmt->fetchAll(PDO::FETCH_ASSOC);

    $featureStmt = $conn->query(
        "SELECT ma, detai, hinh_dd, mota, ngay FROM tintuc
         WHERE trangthai = 'da_dang'
         ORDER BY ngay DESC, time DESC, ma DESC LIMIT 4"
    );
    $featuredNews = $featureStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Danh sách danh mục dùng để hiển thị menu điều hướng trong template.
// Luôn khởi tạo để tránh Undefined variable và foreach nhận giá trị null.
$menuDanhMuc = is_array($categories) ? $categories : [];

$latestNews = $featuredNews;
$popularNews = $featuredNews;

function news_image_path($path): string {
    $raw = trim((string)$path);

    if ($raw !== '' && preg_match('~^https?://~i', $raw)) {
        return $raw;
    }

    $raw = str_replace('\\', '/', $raw);
    $raw = preg_replace('~[?#].*$~', '', $raw);
    $raw = ltrim($raw, '/');

    $scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($scriptDir !== '' && strpos($raw, $scriptDir . '/') === 0) {
        $raw = substr($raw, strlen($scriptDir) + 1);
    }

    $candidates = [];
    if ($raw !== '') {
        $candidates[] = $raw;
        if (strpos($raw, '/') === false) {
            $candidates[] = 'uploads/tintuc/' . $raw;
            $candidates[] = 'uploads/' . $raw;
        }
        if (preg_match('~(?:^|/)uploads/(.+)$~i', $raw, $m)) {
            $candidates[] = 'uploads/' . $m[1];
        }
    }

    foreach (array_unique($candidates) as $candidate) {
        $candidate = str_replace(['../', '..\\'], '', $candidate);
        if (is_file(__DIR__ . '/' . $candidate)) {
            return $candidate;
        }
    }

    foreach (['assets/banner-ai.png', 'assets/nen-cong-nghe.png'] as $fallback) {
        if (is_file(__DIR__ . '/' . $fallback)) {
            return $fallback;
        }
    }

    return '';
}

function index_page_url(int $page, string $keyword, int $categoryId): string {
    $query = ['page' => $page];
    if ($keyword !== '') $query['q'] = $keyword;
    if ($categoryId > 0) $query['danhmuc'] = $categoryId;
    return 'index.php?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="vi"><!-- InstanceBegin template="Templates/Templates.dwt.php" codeOutsideHTMLIsLocked="false" -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cổng thông tin điện tử Công an xã Lộc Thành, thành phố Đồng Nai.">
    <!-- InstanceBeginEditable name="doctitle" -->
  <title>Công an xã Lộc Thành</title>
  <!-- InstanceEndEditable -->

    <!-- CSS DUNG CHUNG: cac trang con dat cung cap voi thu muc goc Locthanh -->
    <link rel="stylesheet" href="style.css?v=20261123a">
    <link rel="stylesheet" href="template.css?v=20261123a">

    <style id="template-shared-layout">
      * { box-sizing: border-box; }
      html { scroll-behavior: smooth; }
      body { margin: 0; min-width: 320px; }
      .site-header { width: 100%; position: relative; z-index: 100; }
      .masthead { width: 100%; overflow: hidden; }
      .masthead-inner { display:flex; align-items:center; justify-content:space-between; gap:24px; min-height:124px; }
      .identity { display:flex; align-items:center; gap:18px; flex:1 1 58%; min-width:0; text-decoration:none; }
      .identity img { width:96px; height:96px; object-fit:contain; flex:0 0 96px; }
      .identity-text { display:flex; flex-direction:column; min-width:0; line-height:1.2; }
      .identity-text .agency { display:block; font-size:15px; font-weight:700; white-space:normal; }
      .identity-text strong { display:block; font-size:clamp(22px,2.2vw,34px); line-height:1.15; white-space:normal; }
      .identity-text small { display:block; margin-top:5px; font-size:13px; line-height:1.35; white-space:normal; }
      .masthead-tools { flex:0 1 340px; min-width:260px; display:flex; flex-direction:column; align-items:stretch; gap:9px; }
      .utility { display:flex; justify-content:flex-end; align-items:center; gap:10px; white-space:nowrap; font-size:13px; }
      .search { display:flex; width:100%; min-width:0; }
      .search input { flex:1 1 auto; min-width:0; }
      .search button { flex:0 0 48px; }
      /* Khung tai khoan: luon hien thi dang ky/dang nhap tren cung mot hang */
      .tools-main-row {
        display:flex !important;
        flex-direction:column !important;
        align-items:stretch !important;
        width:100% !important;
        min-width:0 !important;
        gap:7px !important;
      }
      .account-links {
        display:flex !important;
        flex-direction:row !important;
        flex-wrap:wrap !important;
        align-items:center !important;
        justify-content:flex-end !important;
        gap:8px !important;
        width:100% !important;
        min-height:30px !important;
        font-size:13px !important;
        overflow:visible !important;
      }
      .account-main-row {
        display:flex !important;
        flex-wrap:wrap !important;
        justify-content:flex-end !important;
        align-items:center !important;
        gap:8px !important;
        width:100% !important;
        min-width:0 !important;
      }
      .account-links a.account-action,
      .account-links a.logout-link {
        display:inline-block !important;
        visibility:visible !important;
        opacity:1 !important;
        position:relative !important;
        z-index:30 !important;
        color:#c00018 !important;
        font-weight:700 !important;
        white-space:nowrap !important;
        text-decoration:none !important;
        line-height:1.4 !important;
      }
      .account-links a.account-action:hover,
      .account-links a.logout-link:hover {
        color:#8f0012 !important;
        text-decoration:underline !important;
      }
      .account-links .account-separator { display:inline-block !important; visibility:visible !important; }

      .main-nav { width:100%; }
      .nav-inner { display:flex; align-items:stretch; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; white-space:nowrap; scrollbar-width:thin; }
      .nav-inner > a, .nav-inner > span { flex:0 0 auto; }
      .ticker-inner { display:flex; align-items:center; gap:12px; min-height:38px; overflow:hidden; white-space:nowrap; }
      .ticker-message { overflow:hidden; text-overflow:ellipsis; }
      .page-main { min-height:300px; padding-top:24px; padding-bottom:24px; }
      .site-footer { width:100%; margin-top:30px; }
      .footer-inner { display:grid; grid-template-columns:1.25fr 1.1fr .8fr 1fr; gap:28px; }
      .footer-inner > div { min-width:0; }
      .nav-admin-label { display:flex; align-items:center; padding:0 12px; color:#ffe18b; font-weight:700; }
      @media (max-width:900px) {
        .masthead-inner { align-items:flex-start; padding-top:12px; padding-bottom:12px; flex-direction:column; gap:12px; }
        .account-links, .account-main-row { justify-content:flex-start !important; }
        .identity { width:100%; }
        .masthead-tools { width:100%; min-width:0; }
        .utility, .account-links { justify-content:flex-start; }
        .footer-inner { grid-template-columns:repeat(2,minmax(0,1fr)); }
      }
      @media (max-width:560px) {
        .identity img { width:68px; height:68px; flex-basis:68px; }
        .identity-text .agency { font-size:12px; }
        .identity-text strong { font-size:20px; }
        .identity-text small { font-size:10px; }
        .footer-inner { grid-template-columns:1fr; }
      }
    </style>

    <!-- InstanceBeginEditable name="head" -->
  <style>
    .category-select-bar {
      display:flex; align-items:center; justify-content:space-between; gap:18px;
      padding:14px 18px; margin:12px 0 22px; background:#fff;
      border:1px solid #ead7d9; border-left:4px solid #b90016;
      border-radius:9px; box-shadow:0 3px 14px rgba(30,40,60,.05);
    }
    .category-select-bar label {
      color:#a90016; font-size:16px; font-weight:700;
    }
    .category-select-form { display:flex; align-items:center; gap:10px; }
    .category-select-form select {
      min-width:225px; min-height:42px; padding:8px 12px;
      border:1px solid #cbd5e1; border-radius:7px; background:#fff;
      color:#243247; font-size:16px; font-family:"Times New Roman",serif;
    }
    .category-reset { color:#a90016; font-size:14px; font-weight:700; text-decoration:none; }
    .section-title {
      display:flex; align-items:flex-end; justify-content:space-between; gap:15px;
      padding-bottom:12px; margin:22px 0 16px; border-bottom:1px solid #b90016;
    }
    .section-title small { display:block; color:#b90016; font-size:14px; font-weight:700; margin-bottom:5px; }
    .section-title h2 { margin:0; color:#18324f; font-size:22px; line-height:1.3; }
    .section-title .count { color:#64748b; font-size:14px; white-space:nowrap; }
    .article-grid {
      display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px;
    }
    .article-grid article {
      display:flex; flex-direction:column; min-width:0; overflow:hidden;
      background:#fff; border:1px solid #e2e8f0; border-radius:8px;
      box-shadow:0 2px 8px rgba(15,23,42,.04);
    }
    .article-photo { display:block; width:100%; height:190px; overflow:hidden; background:#f1f5f9; }
    .article-photo img { display:block; width:100%; height:100%; object-fit:cover; }
    .article-grid article > small { padding:10px 14px 0; color:#a90016; font-size:14px; font-weight:700; }
    .article-grid h3 { margin:7px 14px; font-size:16px; line-height:1.45; }
    .article-grid h3 a { color:#18324f; text-decoration:none; }
    .article-grid h3 a:hover { color:#b90016; }
    .article-grid p { margin:0 14px 15px; color:#475569; font-size:16px; line-height:1.5; }
    .empty-news { padding:22px; border:1px dashed #e3b9bf; border-radius:8px; background:#fff; color:#64748b; font-size:16px; text-align:center; }
    .selected-category-title { margin-top:4px; }
    .pagination { display:flex; justify-content:center; gap:7px; margin:22px 0; flex-wrap:wrap; }
    .pagination a { min-width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center;
      padding:0 10px; border:1px solid #d7dee8; border-radius:6px; background:#fff; color:#26364b; text-decoration:none; font-size:15px; }
    .pagination a.current,.pagination a:hover { background:#b90016; color:#fff; border-color:#b90016; }
    @media(max-width:900px) { .article-grid{grid-template-columns:repeat(2,minmax(0,1fr));} }
    @media(max-width:640px) {
      .category-select-bar { align-items:stretch; flex-direction:column; }
      .category-select-form,.category-select-form select { width:100%; }
      .section-title { align-items:flex-start; flex-direction:column; }
      .article-grid { grid-template-columns:1fr; }
    }
  </style>
  <!-- InstanceEndEditable -->
</head>
<body>
<header class="site-header" id="top">
    <div class="masthead">
        <div class="container masthead-inner">
            <a class="identity" href="index.php" aria-label="Trang chủ Công an xã Lộc Thành">
                <img src="<?= h($basePrefix . 'assets/quoc-huy.png?v=20261126') ?>" alt="Quốc huy">
                <span class="identity-text">
                    <span class="agency">CÔNG AN THÀNH PHỐ ĐỒNG NAI</span>
                    <strong>CÔNG AN XÃ LỘC THÀNH</strong>
                    <small>VÌ AN NINH TỔ QUỐC - VÌ HẠNH PHÚC NHÂN DÂN</small>
                </span>
            </a>

            <div class="masthead-tools">
                <div class="utility">
                    <a href="index.php#sitemap">♟ Sơ đồ site</a><i aria-hidden="true"></i>
                    <a href="index.php#lien-he">✉ Liên hệ</a><i aria-hidden="true"></i>
                    <a href="PhanAnh.php">☑ Góp ý</a>
                </div>
                <div class="tools-main-row">
                    <form class="search" action="index.php" method="get" role="search">
                        <input type="search" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Nhập từ khóa tìm kiếm..." aria-label="Nhập từ khóa tìm kiếm">
                        <button type="submit" aria-label="Tìm kiếm">⌕</button>
                    </form>
                    <div class="account-links">
                    <?php if ($isLoggedIn): ?>
                        <div class="account-main-row">
                            <span class="hello">Xin chào, <?= h($displayName) ?></span>
                            <a class="account-action" href="doimatkhau.php">Đổi mật khẩu</a>
                           <span class="account-separator">|</span>                  
                            <a class="logout-link" href="DangXuat.php"><font color="#0835D0">Đăng xuất</font></a>
                        </div>
                    <?php else: ?>
                        <div class="account-main-row guest-account-row">
                            <a class="account-action" href="DangKy.php"><font color="#0835D0"> Đăng ký</font></a>
                            <span class="account-separator" aria-hidden="true">|</span>
                            <a class="account-action" href="DangNhap.php"><font color="#0835D0">Đăng nhập</font></a>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <nav class="main-nav" aria-label="Điều hướng chính">
        <div class="container nav-inner">
            <a class="home <?= $activeMenu === 'home' ? 'active' : '' ?>" href="index.php" aria-label="Trang chủ" title="Trang chủ"><img src="uploads/home-page_3711265.png" width="50" height="50" alt="Trang chủ" title = "Trang chủ"/></a>
            <?php foreach ($menuDanhMuc as $dm): ?>
                <?php
                    $menuId = (int)($dm['madanhmuc'] ?? 0);
                    $menuName = (string)($dm['tendanhmuc'] ?? '');
                    $isCategoryActive = isset($_GET['danhmuc']) && (int)$_GET['danhmuc'] === $menuId;
                ?>
                <a class="<?= $isCategoryActive ? 'active' : '' ?>"
                   href="chuyenmuc.php?danhmuc=<?= $menuId ?>">
                    <?= h($menuName) ?>
                </a>
            <?php endforeach; ?>
            <a href="tracuu_phananh.php">TRA CỨU</a>
            <?php if ($isAdmin): ?>
                <span class="nav-admin-label">QUẢN TRỊ</span>
                <a href="QuanLyNguoiDung.php">NGƯỜI DÙNG</a>
                <a href="quanlydanhmuc.php">DANH MỤC</a>
				<a href="quanlychuyenmuc.php">CHUYÊN MỤC</a>
                <a href="quanlytintuc.php">QUẢN LÝ TIN</a>
                <a href="phananh_kiennghi.php">XỬ LÝ PHẢN ÁNH</a>
                <a href="baocao.php">BÁO CÁO</a>
            <?php elseif ($isCanBo): ?>
                <span class="nav-admin-label">KHU VỰC CÁN BỘ</span>
                <a href="baocao.php">BÁO CÁO CÔNG TÁC</a>
                <a href="phananh_kiennghi.php">XỬ LÝ PHẢN ÁNH</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="ticker">
        <div class="container ticker-inner">
            <span class="date">▦ <?= h($todayText) ?></span>
            <b class="star">★</b>
            <strong>THÔNG TIN:</strong>
            <span class="ticker-message"><?= h($tickerText) ?></span>
        </div>
    </div>
</header>

<main class="container page-main">
    <!-- InstanceBeginEditable name="body" -->
  <?php if (function_exists('show_flash')) { show_flash(); } ?>
  <section class="lead-layout" aria-label="Tin nổi bật">
    <div class="lead-news" id="news-slider">
      <div class="slides">
        <div class="slide active">
          <?php $heroImage = news_image_path('assets/banner-ai.png'); ?>
          <?php if ($heroImage !== ''): ?>
            <img src="<?= h($heroImage) ?>" alt="Công an xã Lộc Thành - chuyển đổi số và phục vụ nhân dân">
          <?php endif; ?>
        </div>
        <?php foreach (array_slice($featuredNews, 0, 3) as $slide): ?>
          <a class="slide" href="baiviet.php?id=<?= (int)$slide['ma'] ?>" aria-label="<?= h($slide['detai']) ?>">
            <?php $slideImage = news_image_path($slide['hinh_dd']); ?>
            <?php if ($slideImage !== ''): ?><img src="<?= h($slideImage) ?>" alt="<?= h($slide['detai']) ?>"><?php endif; ?>
            <span class="image-caption"><?= h($slide['detai']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <button class="slider-arrow prev" type="button" aria-label="Tin trước">‹</button>
      <button class="slider-arrow next" type="button" aria-label="Tin tiếp">›</button>
      <div class="slider-dots" aria-label="Chọn tin nổi bật">
        <?php for ($i = 0; $i < 1 + min(5, count($featuredNews)); $i++): ?>
          <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>" aria-label="Tin <?= $i + 1 ?>"></button>
        <?php endfor; ?>
      </div>
    </div>

    <aside class="news-panel">
      <div class="panel-tabs">
        <button class="btn btn-danger tab-button active" type="button" data-tab="latest"><b>TIN MỚI NHẤT</b></button>
      </div>
      <div class="tab-content" id="tab-latest">
        <?php if ($latestNews): foreach ($latestNews as $item): ?>
          <a class="news-row" href="baiviet.php?id=<?= (int)$item['ma'] ?>">
            <span class="thumb"><?php $thumbImage = news_image_path($item['hinh_dd']); if ($thumbImage !== ''): ?><img src="<?= h($thumbImage) ?>" alt=""><?php endif; ?></span>
            <span><strong><?= h($item['detai']) ?></strong><small><?= h($item['ngay']) ?></small></span>
          </a>
        <?php endforeach; else: ?>
          <div class="empty-news">Chưa có tin mới được đăng.</div>
        <?php endif; ?>
      </div>
      <div class="tab-content" id="tab-featured" hidden>
        <?php if ($popularNews): foreach ($popularNews as $item): ?>
          <b><a class="news-row" href="baiviet.php?id=<?= (int)$item['ma'] ?>">
            <span class="thumb"><?php $thumbImage = news_image_path($item['hinh_dd']); if ($thumbImage !== ''): ?><img src="<?= h($thumbImage) ?>" alt=""><?php endif; ?></span>
            <span><strong><?= h($item['detai']) ?></strong><small><?= h($item['ngay']) ?></small></span>
			  </a></b>
        <?php endforeach; else: ?>
          <div class="empty-news">Chưa có tin nổi bật.</div>
        <?php endif; ?>
      </div>
    </aside>
  </section>

  <section class="quick-links" aria-label="Liên kết nhanh">
    <a href="#thu-tuc"><span>▤</span><b>THỦ TỤC HÀNH CHÍNH</b></a>
    <a href="https://vpcp.dichvucong.gov.vn/p/home/dvc-trang-chu.html"><span>▣</span><b>DỊCH VỤ CÔNG<br>TRỰC TUYẾN</b></a>
    <a href="https://thuvienphapluat.vn/van-ban/Cong-nghe-thong-tin/Quyet-dinh-1762-QD-BKHCN-2025-Danh-muc-Co-so-du-lieu-chuyen-nganh-cua-Bo-Khoa-hoc-665329.aspx"><span>▤</span><b>CƠ SỞ DỮ LIỆU<br>CHUYÊN NGÀNH</b></a>
    <a href="https://nxhoa86-arch.github.io/conganxalocthanh.dongnai/"><span>AI</span><b>ỨNG DỤNG AI<br>CHUYỂN ĐỔI SỐ</b></a>
    <a href="PhanAnh.php"><span>☏</span><b>TIẾP NHẬN<br>KIẾN NGHỊ, PHẢN ÁNH</b></a>
    <a href="tel:0271"><span>☎</span><b>ĐƯỜNG DÂY NÓNG<br><em>02713507007</em></b></a>
  </section>

  <section class="banner-grid" aria-label="Chuyên đề">
    <a class="promo promo-red" href="#nghi-quyet-57">
      <span>★</span><b>THỰC HIỆN NGHỊ QUYẾT SỐ 57-NQ/TW<small>VỀ ĐỘT PHÁ PHÁT TRIỂN KHOA HỌC, CÔNG NGHỆ,<br>ĐỔI MỚI SÁNG TẠO VÀ CHUYỂN ĐỔI SỐ QUỐC GIA</small></b>
    </a>
    <a class="promo promo-cream" href="https://caicachhanhchinh.dongnai.gov.vn/Pages/home.aspx/"><span>▥</span><b>CẢI CÁCH<br>HÀNH CHÍNH</b></a>
    <a class="promo promo-cream" href="https://pbgdpl.moj.gov.vn/"><span>⚖</span><b>PHỔ BIẾN,<br>GIÁO DỤC PHÁP LUẬT</b></a>
  </section>

  <section class="content-section" id="tin-tuc">
    <div class="category-select-bar">
      <label for="chonDanhMuc">CHỌN DANH MỤC THÔNG TIN</label>
      <form method="get" action="index.php" class="category-select-form">
        <?php if ($keyword !== ''): ?><input type="hidden" name="q" value="<?= h($keyword) ?>"><?php endif; ?>
        <select id="chonDanhMuc" name="danhmuc" onchange="this.form.submit()">
          <option value="">-- Chọn danh mục --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['madanhmuc'] ?>" <?= $categoryId === (int)$cat['madanhmuc'] ? 'selected' : '' ?>><?= h($cat['tendanhmuc'] ?? 'Danh mục') ?></option>
          <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="category-submit">Lọc danh mục</button></noscript>
        <?php if ($categoryId > 0 || $keyword !== ''): ?>
          <a class="category-reset" href="index.php<?= $keyword !== '' ? '?q=' . urlencode($keyword) : '' ?>">Xóa lọc</a>
        <?php endif; ?>
      </form>
    </div>

    <?php if ($selectedCategory): ?>
      <div class="section-title selected-category-title">
        <div><small>DANH MỤC ĐANG CHỌN</small><h2><?= h($selectedCategory['tendanhmuc']) ?></h2></div>
        <span class="count">Hiển thị tối đa 6 tin</span>
      </div>
      <?php if ($selectedNews): ?>
        <div class="article-grid selected-category-grid">
          <?php foreach ($selectedNews as $item): ?>
            <article>
              <a class="article-photo" href="baiviet.php?id=<?= (int)$item['ma'] ?>">
                <img src="<?= h(news_image_path($item['hinh_dd'])) ?>" alt="<?= h($item['detai']) ?>" loading="lazy">
              </a>
              <small><?= h($item['tendanhmuc'] ?? 'Tin tức') ?> · <?= h($item['ngay']) ?></small>
              <h3><a href="baiviet.php?id=<?= (int)$item['ma'] ?>"><?= h($item['detai']) ?></a></h3>
              <p><?= h(mb_strimwidth(strip_tags((string)($item['mota'] ?? '')), 0, 155, '...', 'UTF-8')) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-news">Danh mục này hiện chưa có tin được đăng<?= $keyword !== '' ? ' phù hợp với từ khóa tìm kiếm' : '' ?>.</div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="section-title activity-section-title">
      <div><small>HOẠT ĐỘNG CÔNG AN XÃ</small><h2><?= $keyword !== '' ? 'KẾT QUẢ TÌM KIẾM HOẠT ĐỘNG' : 'TIN TỨC VÀ HOẠT ĐỘNG' ?></h2></div>
      <span class="count">Tổng số: <?= $total ?> tin</span>
    </div>

    <?php if ($news): ?>
      <div class="article-grid">
        <?php foreach ($news as $item): ?>
          <article>
            <a class="article-photo" href="baiviet.php?id=<?= (int)$item['ma'] ?>">
              <img src="<?= h(news_image_path($item['hinh_dd'])) ?>" alt="<?= h($item['detai']) ?>" loading="lazy">
            </a>
            <small><?= h($item['tendanhmuc'] ?? $item['theloai'] ?? 'Tin tức') ?> · <?= h($item['ngay']) ?></small>
            <h3><a href="baiviet.php?id=<?= (int)$item['ma'] ?>"><?= h($item['detai']) ?></a></h3>
            <p><?= h(mb_strimwidth(strip_tags((string)($item['mota'] ?? '')), 0, 155, '...', 'UTF-8')) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-news">Chưa có tin hoạt động thuộc các danh mục còn lại<?= $keyword !== '' ? ' phù hợp với từ khóa tìm kiếm' : '' ?>.</div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
      <nav class="pagination" aria-label="Phân trang tin tức">
        <?php if ($page > 1): ?><a href="<?= h(index_page_url($page - 1, $keyword, $categoryId)) ?>">‹</a><?php endif; ?>
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a class="<?= $p === $page ? 'current' : '' ?>" href="<?= h(index_page_url($p, $keyword, $categoryId)) ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= h(index_page_url($page + 1, $keyword, $categoryId)) ?>">›</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  </section>

  <section class="about-strip" id="gioi-thieu">
    <div><small>CÔNG AN XÃ LỘC THÀNH - TỈNH ĐỒNG NAI</small><h2>VÌ AN NINH TỔ QUỐC - VÌ HẠNH PHÚC NHÂN DÂN</h2><p>Thông tin, hướng dẫn và tiếp nhận phản ánh, kiến nghị của người dân.</p></div>
    <img src="assets/quoc-huy.png" alt="Quốc huy">
  </section>

  <script>
  (function () {
    const slider = document.getElementById('news-slider');
    if (slider) {
      const slides = Array.from(slider.querySelectorAll('.slide'));
      const dots = Array.from(slider.querySelectorAll('.slider-dots button'));
      let current = 0;
      let timer = null;
      function showSlide(index) {
        if (!slides.length) return;
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('active', i === current));
        dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
      }
      function startTimer() {
        if (timer) clearInterval(timer);
        if (slides.length > 1) timer = setInterval(() => showSlide(current + 1), 5000);
      }
      slider.querySelector('.prev')?.addEventListener('click', () => { showSlide(current - 1); startTimer(); });
      slider.querySelector('.next')?.addEventListener('click', () => { showSlide(current + 1); startTimer(); });
      dots.forEach(dot => dot.addEventListener('click', () => { showSlide(Number(dot.dataset.slide)); startTimer(); }));
      startTimer();
    }

    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.addEventListener('click', () => {
      tabButtons.forEach(item => item.classList.toggle('active', item === button));
      document.querySelectorAll('.tab-content').forEach(panel => {
        panel.hidden = panel.id !== 'tab-' + button.dataset.tab;
      });
    }));
  })();
  </script>
  <!-- InstanceEndEditable -->
</main>

<footer id="lien-he" class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <img src="assets/quoc-huy.png" alt="Quốc huy">
            <div><b>CÔNG AN XÃ LỘC THÀNH</b><small>THÀNH PHỐ ĐỒNG NAI</small></div>
        </div>
        <div>
            <h3>THÔNG TIN LIÊN HỆ</h3>
            <p>Địa chỉ: Xã Lộc Thành, thành phố Đồng Nai</p>
            <p>Điện thoại: 02713507007</p>
            <p>Email: caxlocthanh.gov.vn</p>
        </div>
        <div>
            <h3>LIÊN KẾT</h3>
            <a href="index.php#gioi-thieu">Giới thiệu</a>
            <a href="index.php#tin-tuc">Tin tức - sự kiện</a>
            <a href="PhanAnh.php">Gửi phản ánh, kiến nghị</a>
            <a href="tracuu_phananh.php">Tra cứu phản ánh</a>
        </div>
        <div id="sitemap">
            <h3>KẾT NỐI</h3>
            <p>Thông tin tuyên truyền và hoạt động của đơn vị</p>
            <a href="#top">Trở về đầu trang ↑</a>
        </div>
    </div>
    <div class="copyright">© <?= date('Y') ?> CÔNG AN XÃ LỘC THÀNH - THÀNH PHỐ ĐỒNG NAI</div>
</footer>

<!-- InstanceBeginEditable name="foot" -->
<!-- JavaScript rieng cua trang con dat tai day neu can -->
<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
