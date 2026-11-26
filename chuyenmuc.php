<?php
require_once __DIR__ . '/common.php';

$conn = $GLOBALS['conn'] ?? ($conn ?? null);
if (!$conn instanceof PDO) {
    $connectionFile = __DIR__ . '/ketnoi.php';
    if (is_file($connectionFile)) {
        require_once $connectionFile;
        $conn = $GLOBALS['conn'] ?? ($conn ?? null);
    }
}
if (!$conn instanceof PDO) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu conganxa.');
}

// Khoi tao session va cac bien dung chung cho giao dien.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = function_exists('current_role')
    ? current_role()
    : strtolower(trim((string)($_SESSION['vaitro'] ?? $_SESSION['PhanQuyen'] ?? '')));

$isAdmin = function_exists('is_admin')
    ? (bool)is_admin()
    : in_array($role, ['quantri', 'admin', 'administrator'], true);

$isCanBo = function_exists('is_canbo')
    ? (bool)is_canbo()
    : ($role === 'canbo');

$rawUserId = $_SESSION['nguoidung_id'] ?? $_SESSION['MaNguoiDung'] ?? null;
$rawUserName = $_SESSION['hoten'] ?? $_SESSION['UserName'] ?? $_SESSION['taikhoan'] ?? null;

$isLoggedIn = function_exists('current_user_id')
    ? (bool)current_user_id()
    : ((is_scalar($rawUserId) && trim((string)$rawUserId) !== '')
        || (is_scalar($rawUserName) && trim((string)$rawUserName) !== ''));

$displayName = trim((string)($_SESSION['hoten'] ?? $rawUserName ?? 'Người dùng'));
$basePrefix = $basePrefix ?? '';
$activeMenu = $activeMenu ?? '';

// Nap danh muc cho thanh dieu huong; luon dam bao foreach nhan mot mang.
$menuDanhMuc = [];
try {
    $menuStmt = $conn->query(
        "SELECT madanhmuc, tendanhmuc
         FROM danhmuc
         WHERE trang_thai = 1
         ORDER BY madanhmuc ASC"
    );
    if ($menuStmt) {
        $menuDanhMuc = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('Loi nap danh muc tren trang chuyen muc: ' . $e->getMessage());
    $menuDanhMuc = [];
}

$days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
$todayText = $days[(int)date('w')] . ', ' . date('d/m/Y');
$tickerText = $tickerText ?? 'Thông tin tuyên truyền và hoạt động của Công an xã Lộc Thành';

$categoryId = filter_input(INPUT_GET, 'danhmuc', FILTER_VALIDATE_INT) ?: 0;
$subcategoryId = filter_input(INPUT_GET, 'chuyenmuc', FILTER_VALIDATE_INT) ?: 0;
if ($categoryId < 1) {
    http_response_code(400);
    exit('Vui lòng chọn danh mục thông tin.');
}

$categoryStmt = $conn->prepare(
    "SELECT madanhmuc, tendanhmuc, mo_ta
     FROM danhmuc
     WHERE madanhmuc = :id AND trang_thai = 1
     LIMIT 1"
);
$categoryStmt->execute([':id' => $categoryId]);
$category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
if (!$category) {
    http_response_code(404);
    exit('Không tìm thấy danh mục hoặc danh mục đã bị khóa.');
}

$subStmt = $conn->prepare(
    "SELECT machuyenmuc, tenchuyenmuc, motangan
     FROM chuyenmuc
     WHERE madanhmuc = :cat AND trang_thai = 1
     ORDER BY machuyenmuc DESC"
);
$subStmt->execute([':cat' => $categoryId]);
$subcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedSubcategory = null;
if ($subcategoryId > 0) {
    foreach ($subcategories as $sub) {
        if ((int)$sub['machuyenmuc'] === $subcategoryId) {
            $selectedSubcategory = $sub;
            break;
        }
    }
    if (!$selectedSubcategory) {
        http_response_code(404);
        exit('Chuyên mục không thuộc danh mục đã chọn hoặc đã bị khóa.');
    }
}

$sql = "SELECT t.ma, t.detai, t.hinh_dd, t.mota, t.ngay, t.time,
               t.machuyenmuc, c.tenchuyenmuc
        FROM tintuc t
        LEFT JOIN chuyenmuc c ON c.machuyenmuc = t.machuyenmuc
        WHERE (t.madanhmuc = :cat OR c.madanhmuc = :cat_sub)
          AND t.trangthai = 'da_dang'";
$params = [':cat' => $categoryId, ':cat_sub' => $categoryId];
if ($selectedSubcategory) {
    $sql .= " AND t.machuyenmuc = :sub";
    $params[':sub'] = $subcategoryId;
}
$sql .= " ORDER BY t.ngay DESC, t.time DESC, t.ma DESC";
$articleStmt = $conn->prepare($sql);
$articleStmt->execute($params);
$articles = $articleStmt->fetchAll(PDO::FETCH_ASSOC);

$groupedArticles = [];
foreach ($articles as $article) {
    $groupKey = $article['machuyenmuc'] !== null ? (string)$article['machuyenmuc'] : '0';
    if (!isset($groupedArticles[$groupKey])) $groupedArticles[$groupKey] = [];
    $groupedArticles[$groupKey][] = $article;
}

$pageTitle = $category['tendanhmuc'];
if ($selectedSubcategory) $pageTitle .= ' - ' . $selectedSubcategory['tenchuyenmuc'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cổng thông tin điện tử Công an xã Lộc Thành, thành phố Đồng Nai.">
    <!-- TemplateBeginEditable name="doctitle" -->
    <title><?= h($pageTitle ?? ($page_title ?? 'Cổng thông tin điện tử Công an xã Lộc Thành')) ?></title>
    <style>
      .category-breadcrumb{font-size:14px;color:#64748b;margin:0 0 14px}
      .category-breadcrumb a{color:#a90016;text-decoration:none}
      .category-page .category-intro{font-size:16px}
      .subcategory-nav{display:flex;flex-wrap:wrap;gap:9px;margin:0 0 22px}
      .subcategory-nav a{display:inline-block;padding:8px 13px;border:1px solid #e5b9bf;border-radius:20px;color:#a90016;text-decoration:none;background:#fff;font-size:15px}
      .subcategory-nav a.active,.subcategory-nav a:hover{background:#b90016;color:#fff;border-color:#b90016}
    </style>
    <!-- TemplateEndEditable -->

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
      .account-links { display:flex; justify-content:flex-end; align-items:center; flex-wrap:wrap; gap:6px; font-size:13px; }
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

    <style id="category-professional-design">
      :root {
        --cat-red: #a90019;
        --cat-red-dark: #790013;
        --cat-ink: #172b45;
        --cat-muted: #66758a;
        --cat-line: #e6eaf0;
        --cat-soft: #f5f7fa;
      }
      body { background: #f3f5f8; color: var(--cat-ink); }
      .page-main { width: min(1200px, calc(100% - 36px)); margin: 28px auto 42px; }
      .category-page {
        position: relative; overflow: hidden;
        padding: clamp(20px, 3vw, 36px);
        background: #fff; border: 1px solid #e8ebf0;
        border-radius: 16px; box-shadow: 0 10px 32px rgba(20,35,55,.07);
      }
      .category-page:before {
        content: ""; position: absolute; top: 0; left: 0; right: 0; height: 5px;
        background: linear-gradient(90deg, #850014, #c20b2c, #e6a3ad);
      }
      .category-breadcrumb {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
        margin: 0 0 22px !important; color: #8792a2 !important;
        font-size: 13px !important;
      }
      .category-breadcrumb a { color: var(--cat-red) !important; font-weight: 700; }
      .category-page > h1 {
        margin: 0 0 10px !important; color: var(--cat-red-dark);
        font-size: clamp(25px, 3vw, 34px) !important;
        line-height: 1.2; font-weight: 800; letter-spacing: -.35px;
      }
      .category-page .category-intro {
        max-width: 850px; margin: 0 0 24px !important;
        color: var(--cat-muted); font-size: 16px !important; line-height: 1.7;
      }
      .subcategory-nav {
        display: flex; flex-wrap: wrap; gap: 10px;
        margin: 0 0 30px !important; padding: 16px 0 22px;
        border-top: 1px solid var(--cat-line); border-bottom: 1px solid var(--cat-line);
      }
      .subcategory-nav a {
        display: inline-flex; align-items: center; justify-content: center;
        min-height: 38px; padding: 8px 16px !important;
        border: 1px solid #e2c4c9 !important; border-radius: 999px !important;
        background: #fff !important; color: var(--cat-red) !important;
        font-size: 14px !important; font-weight: 700; text-decoration: none;
        transition: .2s ease;
      }
      .subcategory-nav a:hover, .subcategory-nav a.active {
        color: #fff !important; border-color: var(--cat-red) !important;
        background: linear-gradient(135deg, #bd0928, #870015) !important;
        box-shadow: 0 5px 13px rgba(169,0,25,.18); transform: translateY(-1px);
      }
      .subcategory-block { margin: 30px 0 38px !important; }
      .subcategory-heading {
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px; margin: 0 0 18px !important; padding: 0 0 12px !important;
        border-bottom: 2px solid #e8edf3 !important; position: relative;
      }
      .subcategory-heading:after {
        content: ""; position: absolute; left: 0; bottom: -2px;
        width: 78px; height: 2px; background: var(--cat-red);
      }
      .subcategory-heading h2 {
        margin: 0 !important; color: var(--cat-ink) !important;
        font-size: clamp(19px, 2vw, 23px) !important;
        line-height: 1.35; font-weight: 800;
      }
      .subcategory-heading span { color: var(--cat-muted); font-size: 13px; white-space: nowrap; }
      .subcategory-heading > a {
        color: var(--cat-red) !important; font-size: 14px; font-weight: 700;
        text-decoration: none; white-space: nowrap;
      }
      .subcategory-heading > a:hover { color: var(--cat-red-dark) !important; text-decoration: underline; }
      .category-news-grid {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px;
      }
      .category-news-card {
        display: flex; flex-direction: column; min-width: 0; overflow: hidden;
        background: #fff; border: 1px solid var(--cat-line) !important;
        border-radius: 12px !important;
        box-shadow: 0 3px 12px rgba(20,35,55,.045);
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
      }
      .category-news-card:hover {
        transform: translateY(-4px); border-color: #d7aab2 !important;
        box-shadow: 0 12px 26px rgba(20,35,55,.12);
      }
      .category-news-card > a:first-child {
        display: block; overflow: hidden; background: #edf1f5;
        aspect-ratio: 16 / 9;
      }
      .category-news-card img {
        display: block; width: 100%; height: 100% !important;
        min-height: 190px; object-fit: cover;
        transition: transform .35s ease;
      }
      .category-news-card:hover img { transform: scale(1.035); }
      .category-news-card .news-card-body {
        display: flex; flex: 1; flex-direction: column;
        padding: 17px 18px 16px !important;
      }
      .category-news-card h3 {
        margin: 0 0 10px !important; font-size: 17px !important;
        line-height: 1.48 !important; font-weight: 750;
      }
      .category-news-card h3 a {
        color: #20334c !important; text-decoration: none;
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
        overflow: hidden;
      }
      .category-news-card h3 a:hover { color: var(--cat-red) !important; }
      .category-news-card p {
        margin: 0 0 14px !important; color: #68778b !important;
        font-size: 14px !important; line-height: 1.65 !important;
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
        overflow: hidden;
      }
      .category-news-card small {
        display: block; margin-top: auto; padding-top: 10px;
        border-top: 1px solid #f0f2f5; color: #8792a2 !important;
        font-size: 12px !important;
      }
      .category-empty, .subcategory-empty {
        padding: 24px; border: 1px dashed #d8dee8; border-radius: 12px;
        background: #f8fafc; color: #68778b; text-align: center;
      }
      .category-empty:before { content: "ℹ"; display: block; margin-bottom: 8px; color: var(--cat-red); font-size: 24px; }
      @media (max-width: 900px) {
        .category-news-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .page-main { width: min(100% - 24px, 1200px); }
      }
      @media (max-width: 580px) {
        .page-main { width: calc(100% - 20px); margin: 14px auto 24px; }
        .category-page { padding: 20px 15px; border-radius: 12px; }
        .category-news-grid { grid-template-columns: 1fr; gap: 14px; }
        .category-news-card { display: grid; grid-template-columns: 112px minmax(0,1fr); }
        .category-news-card > a:first-child { aspect-ratio: 1 / 1; height: 100%; }
        .category-news-card img { min-height: 112px; }
        .category-news-card .news-card-body { padding: 10px 12px !important; }
        .category-news-card h3 { font-size: 14px !important; margin-bottom: 6px !important; }
        .category-news-card p { display: none; }
        .category-news-card small { font-size: 11px !important; padding-top: 6px; }
        .subcategory-heading { align-items: flex-start; }
      }
      @media (prefers-reduced-motion: reduce) {
        .category-news-card, .category-news-card img, .subcategory-nav a { transition: none; }
      }
    </style>

    <!-- TemplateBeginEditable name="head" -->
    <!-- Trang con them CSS rieng tai day. Khong sua stylesheet dung chung. -->
    <!-- TemplateEndEditable -->
</head>
<body>
<header class="site-header" id="top">
    <div class="masthead">
        <div class="container masthead-inner">
            <a class="identity" href="index.php" aria-label="Trang chủ Công an xã Lộc Thành">
                <img src="assets/quoc-huy.png" alt="Quốc huy">
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
                <form class="search" action="index.php" method="get" role="search">
                    <input type="search" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Nhập từ khóa tìm kiếm..." aria-label="Nhập từ khóa tìm kiếm">
                    <button type="submit" aria-label="Tìm kiếm">⌕</button>
                </form>
                <div class="account-links">
                    <?php if ($isLoggedIn): ?>
                        <span class="hello">Xin chào, <?= h($displayName) ?></span><a class="account-action" href="doimatkhau.php">Đổi mật khẩu</a>
                        <span class="account-separator">|</span><a class="login-link" href="<?= htmlspecialchars($basePrefix . 'DangXuat.php', ENT_QUOTES, 'UTF-8') ?>">ĐĂNG XUẤT</a>
                    <?php else: ?>
                        <a class="account-action" href="DangKy.php">Đăng ký</a>
                        <span class="account-separator">|</span>
                        <a class="account-action" href="DangNhap.php">Đăng nhập</a>
                    <?php endif; ?>
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
    <!-- TemplateBeginEditable name="body" -->
    <div class="category-page">
  <p class="category-breadcrumb"><a href="index.php">Trang chủ</a> › <?= h($category['tendanhmuc']) ?><?= $selectedSubcategory ? ' › ' . h($selectedSubcategory['tenchuyenmuc']) : '' ?></p>
  <h1><?= h($pageTitle) ?></h1>
  <p class="category-intro"><?= h($category['mo_ta'] ?: 'Tin tức, bài viết và thông tin thuộc danh mục ' . $category['tendanhmuc'] . '.') ?></p>

  <?php if ($subcategories): ?>
    <nav class="subcategory-nav" aria-label="Chuyên mục">
      <a class="<?= !$selectedSubcategory ? 'active' : '' ?>" href="chuyenmuc.php?danhmuc=<?= $categoryId ?>">Tất cả chuyên mục</a>
      
    </nav>
  <?php endif; ?>

  <?php if (!$articles): ?>
    <div class="category-empty">Danh mục này hiện chưa có bài viết được đăng.</div>
  <?php elseif ($selectedSubcategory): ?>
    <section class="subcategory-block">
      <div class="subcategory-heading"><h2><?= h($selectedSubcategory['tenchuyenmuc']) ?></h2><span><?= count($articles) ?> bài viết</span></div>
      <div class="category-news-grid">
        <?php foreach ($articles as $article): ?>
          <article class="category-news-card">
            <a href="baiviet.php?id=<?= (int)$article['ma'] ?>">
              <?php if (!empty($article['hinh_dd'])): ?><img src="<?= h($article['hinh_dd']) ?>" alt="<?= h($article['detai']) ?>" loading="lazy"><?php else: ?><div style="height:170px;background:#f1f5f9"></div><?php endif; ?>
            </a>
            <div class="news-card-body">
              <h3><a href="baiviet.php?id=<?= (int)$article['ma'] ?>"><?= h($article['detai']) ?></a></h3>
              <?php if (!empty($article['mota'])): ?><p><?= h(mb_strimwidth(strip_tags((string)$article['mota']), 0, 180, '…', 'UTF-8')) ?></p><?php endif; ?>
              <small><?= h($article['ngay']) ?></small>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php else: ?>
    <?php foreach ($subcategories as $sub): $items = $groupedArticles[(string)$sub['machuyenmuc']] ?? []; ?>
      <section class="subcategory-block">
        <div class="subcategory-heading">
          <h2><?= h($sub['tenchuyenmuc']) ?></h2>
          <a href="chuyenmuc.php?danhmuc=<?= $categoryId ?>&chuyenmuc=<?= (int)$sub['machuyenmuc'] ?>">Xem tất cả »</a>
        </div>
        <?php if ($items): ?>
          <div class="category-news-grid">
            <?php foreach (array_slice($items, 0, 6) as $article): ?>
              <article class="category-news-card">
                <a href="baiviet.php?id=<?= (int)$article['ma'] ?>">
                  <?php if (!empty($article['hinh_dd'])): ?><img src="<?= h($article['hinh_dd']) ?>" alt="<?= h($article['detai']) ?>" loading="lazy"><?php else: ?><div style="height:170px;background:#f1f5f9"></div><?php endif; ?>
                </a>
                <div class="news-card-body">
                  <h3><a href="baiviet.php?id=<?= (int)$article['ma'] ?>"><?= h($article['detai']) ?></a></h3>
                  <?php if (!empty($article['mota'])): ?><p><?= h(mb_strimwidth(strip_tags((string)$article['mota']), 0, 150, '…', 'UTF-8')) ?></p><?php endif; ?>
                  <small><?= h($article['ngay']) ?></small>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="subcategory-empty">Chuyên mục này chưa có bài viết được đăng.</div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <?php $ungrouped = $groupedArticles['0'] ?? []; if ($ungrouped): ?>
      <section class="subcategory-block">
        <div class="subcategory-heading"><h2>Bài viết khác</h2></div>
        <div class="category-news-grid">
          <?php foreach ($ungrouped as $article): ?>
            <article class="category-news-card">
              <a href="baiviet.php?id=<?= (int)$article['ma'] ?>"><?php if (!empty($article['hinh_dd'])): ?><img src="<?= h($article['hinh_dd']) ?>" alt="<?= h($article['detai']) ?>" loading="lazy"><?php endif; ?></a>
              <div class="news-card-body"><h3><a href="baiviet.php?id=<?= (int)$article['ma'] ?>"><?= h($article['detai']) ?></a></h3><small><?= h($article['ngay']) ?></small></div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</div>
    <!-- TemplateEndEditable -->
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
            <p>Email: caxlocthanhf.gov.vn</p>
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
            <p>Thông tin tuyên truyền và hoạt động của đơn vị.</p>
            <a href="#top">Trở về đầu trang ↑</a>
        </div>
    </div>
    <div class="copyright">© <?= date('Y') ?> CÔNG AN XÃ LỘC THÀNH - THÀNH PHỐ ĐỒNG NAI</div>
</footer>

<!-- TemplateBeginEditable name="foot" -->
<!-- Trang con them JavaScript rieng tai day. -->
<!-- TemplateEndEditable -->
</body>
</html>
