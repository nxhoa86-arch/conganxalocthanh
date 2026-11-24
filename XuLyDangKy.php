<?php
require_once __DIR__ . '/common.php';
$next = (($_POST['next'] ?? ($_SESSION['redirect_after_login'] ?? '')) === 'Phananhkiennghi.php') ? 'Phananhkiennghi.php' : '';
if ($next !== '') $_SESSION['redirect_after_login'] = $next;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_to('DangKy.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
if ($next !== '') $_SESSION['redirect_after_login'] = $next;
$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');
$email = trim($_POST['email'] ?? '');
$hoten = trim($_POST['hoten'] ?? '');
$address = trim($_POST['diachi'] ?? '');
if ($username === '' || $hoten === '' || strlen($password) < 8) {
    flash('Tên đăng nhập không được để trống; mật khẩu phải có ít nhất 8 ký tự.', 'error');
    redirect_to('DangKy.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
}
try {
    $check = $conn->prepare('SELECT COUNT(*) FROM nguoidung WHERE taikhoan = ?');
    $check->execute([$username]);
    if ((int)$check->fetchColumn() > 0) {
        flash('Tên đăng nhập đã tồn tại.', 'error');
        redirect_to('DangKy.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    // Dang ky cong dan mac dinh; chi quan tri CSDL moi duoc nang vai tro canbo/quantri.
    $stmt = $conn->prepare('INSERT INTO nguoidung(taikhoan,matkhau,hoten,email,diachi,vaitro,trangthai) VALUES(?,?,?,?,?,?,1)');
    $stmt->execute([$username, $hash, $hoten, $email ?: null, $address ?: null, 'congdan']);
    flash('Đăng ký thành công. Bạn có thể đăng nhập.', 'success');
    redirect_to('DangNhap.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
} catch (Throwable $e) {
    flash('Không thể đăng ký tài khoản. Vui lòng kiểm tra lại thông tin.', 'error');
    redirect_to('DangKy.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
}
