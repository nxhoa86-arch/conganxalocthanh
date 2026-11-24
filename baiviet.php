<?php
// Nap ham dung chung va ket noi CSDL truoc khi header su dung h().
require_once __DIR__ . '/common.php';
/* ============================================================
   TEMPLATE DUNG CHUNG - CONG THONG TIN DIEN TU CONG AN XA LOC THANH
   Header, menu, ticker va footer dung chung cho tat ca trang con.
   Chi vung TemplateBeginEditable name="body" duoc phep thay doi.
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = $page_title ?? 'Cổng thông tin điện tử Công an xã Lộc Thành';
$days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
$todayText = $days[(int) date('w')] . ', ' . date('d/m/Y');

$role = strtolower(trim((string) ($_SESSION['vaitro'] ?? $_SESSION['PhanQuyen'] ?? '')));
$isAdmin = in_array($role, ['quantri', 'admin', 'administrator'], true);
$isCanBo = ($role === 'canbo');
$rawUserId = $_SESSION['nguoidung_id'] ?? null;
$rawUserName = $_SESSION['UserName'] ?? $_SESSION['taikhoan'] ?? null;
$isLoggedIn = (is_scalar($rawUserId) && trim((string) $rawUserId) !== '')
    || (is_scalar($rawUserName) && trim((string) $rawUserName) !== '');
$displayName = trim((string) ($_SESSION['hoten'] ?? $rawUserName ?? 'Người dùng'));
$tickerText = $tickerText ?? 'Thông tin tuyên truyền và hoạt động của Công an xã Lộc Thành';
$activeMenu = $activeMenu ?? '';

// Lay danh muc de hien thi tren thanh menu chung.
$menuDanhMuc = [];
try {
    if (isset($conn) && $conn instanceof PDO) {
        $menuStmt = $conn->query("SELECT madanhmuc, tendanhmuc FROM danhmuc ORDER BY madanhmuc ASC");
        if ($menuStmt !== false) {
            $menuDanhMuc = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    // Khong lam trang bai viet bi loi neu bang danh muc chua san sang.
    $menuDanhMuc = [];
}
?>
<!DOCTYPE html>
<html lang="vi"><!-- InstanceBegin template="Templates/Templates.dwt.php" codeOutsideHTMLIsLocked="false" -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cổng thông tin điện tử Công an xã Lộc Thành, thành phố Đồng Nai.">
    <!-- InstanceBeginEditable name="doctitle" -->
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
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

    <!-- InstanceBeginEditable name="head" -->
    <!-- Trang con chi them CSS/JS rieng tai vung nay; khong sua header/footer -->
    <!-- InstanceEndEditable -->
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
    <!-- InstanceBeginEditable name="body" -->
    <!-- MOI TRANG CON DAT NOI DUNG RIENG TAI DAY -->
	<?php
$id = max(0, (int)($_GET['id'] ?? 0));
$st = $conn->prepare("SELECT t.*, d.tendanhmuc, c.tenchuyenmuc FROM tintuc t LEFT JOIN danhmuc d ON d.madanhmuc=t.madanhmuc LEFT JOIN chuyenmuc c ON c.machuyenmuc=t.machuyenmuc WHERE t.ma=? AND t.trangthai='da_dang'");
$st->execute([$id]); $t = $st->fetch();
$latest=[];
if ($t) { $side=$conn->prepare("SELECT ma,detai,hinh_dd,ngay FROM tintuc WHERE trangthai='da_dang' AND ma<>? ORDER BY ngay DESC,time DESC,ma DESC LIMIT 7"); $side->execute([$id]); $latest=$side->fetchAll(); }
// Anh mac dinh: su dung banner-ai.png trong thu muc assets cua website.
$defaultNewsImage = 'assets/banner-ai.png';
function render_article_body(string $content): string {
 $trimmed=trim($content); if($trimmed==='') return '';
 if(preg_match('/<\s*(img|figure|figcaption|p|br|div|h[1-6]|ul|ol|li|table|blockquote|strong|em|b|i|u|a)\b/i',$trimmed)) {
  $clean=preg_replace('#<(script|style|iframe|object|embed|form)[^>]*>.*?</\1>#is','',$trimmed);
  $clean=preg_replace('/\son\w+\s*=\s*([\'"]).*?\1/i','',$clean);
  $clean=preg_replace('/\s(href|src)\s*=\s*([\'"])\s*javascript:.*?\2/i',' $1="#"',$clean);
  $clean=preg_replace_callback('/<img\b[^>]*>/i',function($m){$tag=preg_replace('/\s+(width|height)\s*=\s*([\'"]).*?\2/i','',$m[0]);return preg_replace('/\s+style\s*=\s*([\'"]).*?\1/i','',$tag);},$clean);
  return $clean;
 }
 return nl2br(h($trimmed));
}
?>
<div class="article-layout">
 <section class="article-main">
 <?php if(!$t): ?><div class="panel">Không tìm thấy bài viết hoặc bài viết chưa được đăng.</div>
 <?php else: ?>
  <article class="article-detail">
   <p class="article-meta"><a href="index.php#tin-tuc">Trang chủ</a> › <?=h($t['tendanhmuc']??'Tin tức')?> · <?=h($t['ngay']??'')?></p>
   <h1><?=h($t['detai'])?></h1>
   <?php
      $coverImage = trim((string)($t['hinh_dd'] ?? ''));
      if ($coverImage === '') $coverImage = $defaultNewsImage;
    ?>
    <div class="article-cover">
      <img src="<?=h($coverImage)?>" alt="<?=h($t['detai'])?>"
           onerror="this.onerror=null;this.src='<?=h($defaultNewsImage)?>';">
    </div>
   <?php if(!empty($t['mota'])): ?><p class="article-summary"></p><?php endif; ?>
   <div class="article-body"><?=render_article_body((string)($t['motachitiet']??''))?></div>
  </article>
 <?php endif; ?>
 </section>
 <aside class="article-sidebar"><h2 class="article-sidebar-title">TIN TỨC - SỰ KIỆN</h2>
 <?php if($latest): foreach($latest as $item): ?><article class="sidebar-news">
   <?php
     $sideImage = trim((string)($item['hinh_dd'] ?? ''));
     if ($sideImage === '') $sideImage = $defaultNewsImage;
   ?>
   <a class="sidebar-news-image" href="baiviet.php?id=<?=(int)$item['ma']?>">
     <img src="<?=h($sideImage)?>" alt="<?=h($item['detai'])?>" loading="lazy"
          onerror="this.onerror=null;this.src='<?=h($defaultNewsImage)?>';">
   </a>
   <div><a href="baiviet.php?id=<?=(int)$item['ma']?>"><?=h($item['detai'])?></a><small><?=h($item['ngay'])?></small></div>
 </article><?php endforeach; else: ?><p>Chưa có tin tức khác.</p><?php endif; ?>
 </aside>
</div>
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

<!-- InstanceBeginEditable name="foot" -->
<!-- JavaScript rieng cua trang con dat tai day neu can -->
<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
