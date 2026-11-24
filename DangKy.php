<?php
require_once __DIR__ . '/common.php';
$next = $_GET['next'] ?? ($_SESSION['redirect_after_login'] ?? '');
if ($next !== 'Phananhkiennghi.php') $next = '';
if ($next !== '') $_SESSION['redirect_after_login'] = $next;
if (current_user_id()) redirect_to($next ?: 'index.php');
?>
<?php require_once __DIR__ . '/template.php'; site_header('ĐĂNG KÝ TÀI KHOẢN'); ?>
<div class="auth-wrap"><section class="auth-card"><a class="auth-home" href="index.php">← Trở về trang chủ</a><div class="auth-heading"><img src="assets/quoc-huy.png" alt="Quốc huy"><h1>ĐĂNG KÝ TÀI KHOẢN</h1><p>Tài khoản công dân để gửi và theo dõi phản ánh, kiến nghị</p></div><?php show_flash(); ?><form method="post" action="XuLyDangKy.php" class="auth-form"><input type="hidden" name="next" value="<?= h($next) ?>"><label>Tên đăng nhập</label><input name="username" autocomplete="username" required maxlength="50"><label>Họ và tên</label><input name="hoten" required maxlength="150"><label>Email</label><input type="email" name="email" maxlength="150"><label>Địa chỉ</label><input name="diachi" maxlength="255"><label>Mật khẩu (ít nhất 8 ký tự)</label><input type="password" name="password" autocomplete="new-password" minlength="8" required><button class="btn" type="submit">Tạo tài khoản</button></form><p class="auth-switch">Đã có tài khoản? <a href="DangNhap.php<?= $next !== '' ? '?next=' . urlencode($next) : '' ?>">Đăng nhập</a></p></section></div>
<?php site_footer(); ?>
