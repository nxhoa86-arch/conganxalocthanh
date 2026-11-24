<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('DangNhap.php');
}
$next = (($_POST['next'] ?? ($_SESSION['redirect_after_login'] ?? '')) === 'Phananhkiennghi.php')
    ? 'Phananhkiennghi.php' : '';
if ($next !== '') $_SESSION['redirect_after_login'] = $next;

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');
if ($username === '' || $password === '') {
    flash('Vui lòng nhập tên đăng nhập và mật khẩu.', 'error');
    redirect_to('DangNhap.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
}
try {
    $stmt = $conn->prepare('SELECT id, taikhoan, matkhau, hoten, vaitro, trangthai
                            FROM nguoidung WHERE taikhoan = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || (int)$user['trangthai'] !== 1 || !password_verify($password, $user['matkhau'])) {
        flash('Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.', 'error');
        redirect_to('DangNhap.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
    }

    session_regenerate_id(true);
    $_SESSION['nguoidung_id'] = (int)$user['id'];
    $_SESSION['taikhoan'] = $user['taikhoan'];
    $_SESSION['hoten'] = $user['hoten'];
    $_SESSION['vaitro'] = strtolower(trim($user['vaitro']));
    $_SESSION['MaNguoiDung'] = (int)$user['id'];
    $_SESSION['UserName'] = $user['taikhoan'];
    $_SESSION['PhanQuyen'] = $_SESSION['vaitro'] === 'quantri' ? 'admin' : $_SESSION['vaitro'];

    $savedNext = $_SESSION['redirect_after_login'] ?? '';
    unset($_SESSION['redirect_after_login']);
    if ($next === 'Phananhkiennghi.php' || $savedNext === 'Phananhkiennghi.php') {
        redirect_to('Phananhkiennghi.php');
    }
    if ($_SESSION['vaitro'] === 'quantri') redirect_to('quanlydanhmuc.php');
    if ($_SESSION['vaitro'] === 'canbo') redirect_to('baocao.php');
    redirect_to('index.php');
} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());
    flash('Lỗi đăng nhập hệ thống. Vui lòng kiểm tra cấu hình cơ sở dữ liệu.', 'error');
    redirect_to('DangNhap.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
}
