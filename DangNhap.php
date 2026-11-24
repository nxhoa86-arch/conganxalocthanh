<?php
require_once __DIR__ . '/common.php';
$next = $_GET['next'] ?? ($_SESSION['redirect_after_login'] ?? '');
$allowedNext = ['Phananhkiennghi.php'];
if (!in_array($next, $allowedNext, true)) $next = '';
if ($next !== '') $_SESSION['redirect_after_login'] = $next;
if (current_user_id()) {
    if ($next !== '') redirect_to($next);
    if (is_admin()) redirect_to('quanlydanhmuc.php');
    if (is_canbo()) redirect_to('baocao.php');
    redirect_to('index.php');
}
?>
<?php require_once __DIR__ . '/template.php'; site_header('ĐĂNG NHẬP'); ?>
<div class="auth-wrap"><section class="auth-card"><a class="auth-home" href="index.php">← Trở về trang chủ</a><div class="auth-heading"><img src="assets/quoc-huy.png" alt="Quốc huy"><h1>ĐĂNG NHẬP</h1><p>Cổng thông tin điện tử Công an xã Lộc Thành</p></div><?php show_flash(); ?><form method="post" action="XyLyDangNhap.php" class="auth-form"><input type="hidden" name="next" value="<?= h($next) ?>"><label>Tên đăng nhập</label><input name="username" autocomplete="username" required maxlength="50"><label>Mật khẩu</label><input type="password" name="password" autocomplete="current-password" required><button class="btn" type="submit">Đăng nhập</button></form><p class="auth-switch">Chưa có tài khoản? <a href="DangKy.php<?= $next !== '' ? '?next=' . urlencode($next) : '' ?>">Đăng ký ngay</a></p></section></div>
<?php site_footer(); ?>
