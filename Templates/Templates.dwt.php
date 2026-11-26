<?php
/* ============================================================
   TEMPLATE DUNG CHUNG - CONG THONG TIN DIEN TU CONG AN XA LOC THANH
   Dung lam mau Dreamweaver cho tat ca trang con.
   Trang con chi sua cac vung: doctitle, head, body va foot.
   Khong xoa cac vung TemplateBeginEditable/TemplateEndEditable.
   ============================================================ */
require_once dirname(__DIR__) . '/common.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dam bao duong dan dang xuat dung khi template duoc dung o trang goc hoac thu muc Templates.
if (!isset($basePrefix)) {
    $scriptPath = str_replace('\\\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePrefix = (basename(dirname($scriptPath)) === 'Templates') ? '../' : '';
}

$page_title = $page_title ?? ($pageTitle ?? 'Cổng thông tin điện tử Công an xã Lộc Thành');
$activeMenu = $activeMenu ?? ($active_menu ?? '');

// Danh muc: uu tien bien trang con da truy van; neu chua co thi template tu truy van CSDL.
// Luon dam bao $menuDanhMuc la mang de foreach khong phat sinh Warning.
if (isset($menuDanhMuc) && is_array($menuDanhMuc)) {
    // Giu nguyen danh sach da duoc trang con chuan bi.
} elseif (isset($categories) && is_array($categories)) {
    $menuDanhMuc = $categories;
} else {
    $menuDanhMuc = [];
    try {
        // Template nam trong thu muc Templates, ketnoi.php nam o thu muc goc.
        $ketnoiFile = dirname(__DIR__) . '/ketnoi.php';
        if ((!isset($conn) || !($conn instanceof PDO)) && is_file($ketnoiFile)) {
            require_once $ketnoiFile;
        }
        if (isset($conn) && $conn instanceof PDO) {
            $stmtMenu = $conn->query(
                "SELECT madanhmuc, tendanhmuc
                 FROM danhmuc
                 WHERE trang_thai = 1
                 ORDER BY madanhmuc ASC"
            );
            $menuDanhMuc = $stmtMenu ? $stmtMenu->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    } catch (Throwable $e) {
        error_log('Loi truy van danh muc tren menu: ' . $e->getMessage());
        $menuDanhMuc = [];
    }
}
if (!is_array($menuDanhMuc)) {
    $menuDanhMuc = [];
}

// Dong bo nguoc lai de cac trang con/template cu cung dung duoc mot ten bien.
$categories = $categories ?? $menuDanhMuc;

$tickerText = $tickerText ?? 'Thông tin tuyên truyền và hoạt động của Công an xã Lộc Thành';

$days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
$todayText = $days[(int) date('w')] . ', ' . date('d/m/Y');

$role = function_exists('current_role')
    ? current_role()
    : strtolower(trim((string) ($_SESSION['vaitro'] ?? $_SESSION['PhanQuyen'] ?? '')));
$isAdmin = function_exists('is_admin')
    ? is_admin()
    : in_array($role, ['quantri', 'admin', 'administrator'], true);
$isCanBo = function_exists('is_canbo') ? is_canbo() : ($role === 'canbo');
$rawUserId = $_SESSION['nguoidung_id'] ?? $_SESSION['MaNguoiDung'] ?? null;
$rawUserName = $_SESSION['hoten'] ?? $_SESSION['UserName'] ?? $_SESSION['taikhoan'] ?? null;
$sessionHasUserId = is_scalar($rawUserId) && trim((string)$rawUserId) !== '';
$sessionHasUserName = is_scalar($rawUserName) && trim((string)$rawUserName) !== '';
$helperHasUser = function_exists('current_user_id') && (bool) current_user_id();
// Không để helper trả về false làm mất trạng thái đăng nhập đang có trong SESSION.
$isLoggedIn = $helperHasUser || $sessionHasUserId || $sessionHasUserName;
$displayName = trim((string) ($_SESSION['hoten'] ?? $rawUserName ?? 'Người dùng'));

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cổng thông tin điện tử Công an xã Lộc Thành, thành phố Đồng Nai.">
    <!-- TemplateBeginEditable name="doctitle" -->
    <title><?= h($page_title) ?></title>
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
      .account-links { display:flex !important; flex-direction:column; align-items:flex-end; justify-content:center; gap:4px; width:100%; min-height:30px; font-size:13px; }
       .account-main-row { display:flex; flex-wrap:wrap; justify-content:flex-end; align-items:center; gap:8px; width:100%; }
       .account-logout-row { display:block !important; width:100%; text-align:right; line-height:1.25; }
       .account-links .logout-link {
         display:inline-block !important; visibility:visible !important; opacity:1 !important;
         color:#c00018 !important; font-weight:700 !important; white-space:nowrap !important;
         text-decoration:none !important; position:relative; z-index:20;
       }
       .account-links .logout-link:hover { color:#8f0012 !important; text-decoration:underline !important; }

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
                            <a class="logout-link" href="DangXuat.php">Đăng xuất</a>
                        </div>
                    <?php else: ?>
                        <a class="account-action" href="DangKy.php">Đăng ký</a>
                        <span class="account-separator">|</span>
                        <a class="account-action" href="DangNhap.php">Đăng nhập</a>
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
            <a href="PhanAnh.php">GỬI PHẢN ÁNH</a>
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
    <!-- TemplateBeginEditable name="body" -->
    <!-- MOI TRANG CON DAT NOI DUNG RIENG TAI DAY -->
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

<!-- TemplateBeginEditable name="foot" -->
<!-- Trang con them JavaScript rieng tai day. -->
<!-- TemplateEndEditable -->
</body>
</html>
