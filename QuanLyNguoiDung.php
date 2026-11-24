<?php
require_once __DIR__ . '/common.php';
require_admin();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ketnoiPath = is_file(__DIR__ . '/ketnoi.php')
    ? __DIR__ . '/ketnoi.php'
    : dirname(__DIR__) . '/ketnoi.php';
if (is_file($ketnoiPath)) {
    require_once $ketnoiPath;
}

$inTemplatesDir = (strtolower(basename(__DIR__)) === 'templates');
$basePrefix = $inTemplatesDir ? '../' : '';

$dayNames = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
$todayText = $dayNames[(int)date('w')] . ', ' . date('d/m/Y');

$role = strtolower(trim((string)($_SESSION['vaitro'] ?? $_SESSION['PhanQuyen'] ?? '')));
$isStaff = in_array($role, ['quantri', 'canbo', 'admin', 'administrator'], true);

$rawUserId = $_SESSION['nguoidung_id'] ?? null;
$rawUserName = $_SESSION['UserName'] ?? $_SESSION['taikhoan'] ?? null;
$isLoggedIn = (is_scalar($rawUserId) && trim((string)$rawUserId) !== '')
           || (is_scalar($rawUserName) && trim((string)$rawUserName) !== '');
$displayName = trim((string)($_SESSION['hoten'] ?? $rawUserName ?? 'Người dùng'));
$assetVer = '20261121';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = $_GET['msg'] ?? '';
$error = '';
$notice = '';

if ($msg === 'deleted') {
    $notice = 'Xóa người dùng thành công.';
} elseif ($msg === 'saved') {
    $notice = 'Lưu thông tin người dùng thành công.';
}

// --- XỬ LÝ XÓA ---
if ($action === 'delete' && $id > 0 && isset($conn)) {
    try {
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare("DELETE FROM nguoidung WHERE id = :id");
            $stmt->execute(['id' => $id]);
        } elseif ($conn instanceof mysqli) {
            $stmt = $conn->prepare("DELETE FROM nguoidung WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: QuanLyNguoiDung.php?msg=deleted");
        exit;
    } catch (Throwable $e) {
        $error = "Lỗi xóa người dùng: " . $e->getMessage();
    }
}

// --- XỬ LÝ THÊM / SỬA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($conn)) {
    $taikhoan = trim($_POST['taikhoan'] ?? '');
    $matkhau = trim($_POST['matkhau'] ?? '');
    $hoten = trim($_POST['hoten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sodienthoai = trim($_POST['sodienthoai'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');
    $vaitro = trim($_POST['vaitro'] ?? 'congdan');
    $trangthai = isset($_POST['trangthai']) ? (int)$_POST['trangthai'] : 1;
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    if ($taikhoan === '') {
        $error = "Tên tài khoản không được để trống!";
    } elseif ($hoten === '') {
        $error = "Họ và tên không được để trống!";
    } else {
        try {
            $emailVal = $email !== '' ? $email : null;
            $sdtVal = $sodienthoai !== '' ? $sodienthoai : null;
            $diachiVal = $diachi !== '' ? $diachi : null;

            if ($edit_id > 0) {
                if ($matkhau !== '') {
                    $hashedPassword = password_hash($matkhau, PASSWORD_DEFAULT);
                    if ($conn instanceof PDO) {
                        $stmt = $conn->prepare("UPDATE nguoidung
                            SET taikhoan = :taikhoan, matkhau = :matkhau, hoten = :hoten,
                                email = :email, sodienthoai = :sodienthoai, diachi = :diachi,
                                vaitro = :vaitro, trangthai = :trangthai, capnhat = NOW()
                            WHERE id = :id");
                        $stmt->execute([
                            'taikhoan' => $taikhoan,
                            'matkhau' => $hashedPassword,
                            'hoten' => $hoten,
                            'email' => $emailVal,
                            'sodienthoai' => $sdtVal,
                            'diachi' => $diachiVal,
                            'vaitro' => $vaitro,
                            'trangthai' => $trangthai,
                            'id' => $edit_id
                        ]);
                    } elseif ($conn instanceof mysqli) {
                        $stmt = $conn->prepare("UPDATE nguoidung
                            SET taikhoan = ?, matkhau = ?, hoten = ?,
                                email = ?, sodienthoai = ?, diachi = ?,
                                vaitro = ?, trangthai = ?, capnhat = NOW()
                            WHERE id = ?");
                        $stmt->bind_param("sssssssii", $taikhoan, $hashedPassword, $hoten, $emailVal, $sdtVal, $diachiVal, $vaitro, $trangthai, $edit_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    if ($conn instanceof PDO) {
                        $stmt = $conn->prepare("UPDATE nguoidung
                            SET taikhoan = :taikhoan, hoten = :hoten,
                                email = :email, sodienthoai = :sodienthoai, diachi = :diachi,
                                vaitro = :vaitro, trangthai = :trangthai, capnhat = NOW()
                            WHERE id = :id");
                        $stmt->execute([
                            'taikhoan' => $taikhoan,
                            'hoten' => $hoten,
                            'email' => $emailVal,
                            'sodienthoai' => $sdtVal,
                            'diachi' => $diachiVal,
                            'vaitro' => $vaitro,
                            'trangthai' => $trangthai,
                            'id' => $edit_id
                        ]);
                    } elseif ($conn instanceof mysqli) {
                        $stmt = $conn->prepare("UPDATE nguoidung
                            SET taikhoan = ?, hoten = ?,
                                email = ?, sodienthoai = ?, diachi = ?,
                                vaitro = ?, trangthai = ?, capnhat = NOW()
                            WHERE id = ?");
                        $stmt->bind_param("ssssssii", $taikhoan, $hoten, $emailVal, $sdtVal, $diachiVal, $vaitro, $trangthai, $edit_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                header("Location: QuanLyNguoiDung.php?msg=saved");
                exit;
            } else {
                if ($matkhau === '') {
                    $error = "Vui lòng nhập mật khẩu cho tài khoản mới!";
                } else {
                    $hashedPassword = password_hash($matkhau, PASSWORD_DEFAULT);
                    if ($conn instanceof PDO) {
                        $stmt = $conn->prepare("INSERT INTO nguoidung
                            (taikhoan, matkhau, hoten, email, sodienthoai, diachi, vaitro, trangthai, ngaytao)
                            VALUES (:taikhoan, :matkhau, :hoten, :email, :sodienthoai, :diachi, :vaitro, :trangthai, NOW())");
                        $stmt->execute([
                            'taikhoan' => $taikhoan,
                            'matkhau' => $hashedPassword,
                            'hoten' => $hoten,
                            'email' => $emailVal,
                            'sodienthoai' => $sdtVal,
                            'diachi' => $diachiVal,
                            'vaitro' => $vaitro,
                            'trangthai' => $trangthai
                        ]);
                    } elseif ($conn instanceof mysqli) {
                        $stmt = $conn->prepare("INSERT INTO nguoidung
                            (taikhoan, matkhau, hoten, email, sodienthoai, diachi, vaitro, trangthai, ngaytao)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("sssssssi", $taikhoan, $hashedPassword, $hoten, $emailVal, $sdtVal, $diachiVal, $vaitro, $trangthai);
                        $stmt->execute();
                        $stmt->close();
                    }
                    header("Location: QuanLyNguoiDung.php?msg=saved");
                    exit;
                }
            }
        } catch (Throwable $e) {
            $error = "Lỗi lưu dữ liệu: " . $e->getMessage();
        }
    }
}

// --- LẤY THÔNG TIN KHI SỬA ---
$row_edit = null;
if ($action === 'edit' && $id > 0 && isset($conn)) {
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row_edit = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }
}

// --- LẤY DANH SÁCH NGƯỜI DÙNG ---
$users = [];
if (isset($conn)) {
    if ($conn instanceof PDO) {
        $stmt = $conn->query("SELECT id, taikhoan, hoten, email, sodienthoai, diachi, vaitro, trangthai, ngaytao, capnhat FROM nguoidung ORDER BY id DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $res = $conn->query("SELECT id, taikhoan, hoten, email, sodienthoai, diachi, vaitro, trangthai, ngaytao, capnhat FROM nguoidung ORDER BY id DESC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $users[] = $r;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Trang thông tin điện tử Công an xã Lộc Thành, thành phố Đồng Nai.">
  <title>Quản lý người dùng - Công an xã Lộc Thành</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($basePrefix . 'style.css?v=' . $assetVer, ENT_QUOTES, 'UTF-8') ?>">
  <style>
    html, body, body *, button, input, select, textarea {
      font-family: "Times New Roman", Times, serif !important;
    }
    body, p, li, td, th, label, input, select, textarea,
    .news-row strong, .news-row small,
    .footer-inner p, .footer-inner a {
      font-size: 14px;
    }
    .auth-links {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 9px;
      margin: 0 0 9px;
      flex-wrap: wrap;
    }
    .auth-links a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 12px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 700;
      transition: background .2s, color .2s;
    }
    .auth-links .login-link {
      color: #8b171b;
      border: 1px solid #8b171b;
      background: #fff;
    }
    .auth-links .register-link {
      color: #fff;
      border: 1px solid #8b171b;
      background: #8b171b;
    }
    .auth-links .login-link:hover {
      background: #f7eeee;
    }
    .auth-links .register-link:hover {
      background: #681014;
    }
    .admin-container { padding: 20px 0; }
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
    .admin-table th, .admin-table td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; font-size: 14px; }
    .admin-table th { background-color: #8b171b; color: white; }
    .admin-table tr:nth-child(even) { background-color: #f9f9f9; }
    .btn-action { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: #fff; font-size: 13px; margin-right: 4px; display: inline-block; }
    .btn-edit { background-color: #007bff; }
    .btn-delete { background-color: #dc3545; }
    .btn-add { background-color: #28a745; padding: 8px 15px; display: inline-block; color: #fff; border-radius: 4px; text-decoration: none; font-weight: bold; margin-bottom: 12px; }
    .form-box { background: #fdfdfd; border: 1px solid #ccc; padding: 20px; border-radius: 6px; margin-bottom: 20px; max-width: 650px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; }
    .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .btn-submit { background-color: #8b171b; color: white; border: none; padding: 9px 18px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .alert-error { color: #b91c1c; background: #fee2e2; border: 1px solid #fca5a5; padding: 10px; border-radius: 4px; margin-bottom: 12px; font-weight: bold; }
    .alert-notice { color: #166534; background: #dcfce7; border: 1px solid #86efac; padding: 10px; border-radius: 4px; margin-bottom: 12px; font-weight: bold; }
  </style>
</head>
<body>
<header class="site-header">
  <div class="masthead">
    <div class="container masthead-inner">
      <a class="identity" href="<?= htmlspecialchars($basePrefix . 'index.php', ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= htmlspecialchars($basePrefix . 'assets/quoc-huy.png', ENT_QUOTES, 'UTF-8') ?>" alt="Quốc huy Việt Nam">
        <div>
          <span class="agency">CÔNG AN THÀNH PHỐ ĐỒNG NAI</span>
          <strong>CÔNG AN XÃ LỘC THÀNH</strong>
          <small>VÌ AN NINH TỔ QUỐC - VÌ HẠNH PHÚC NHÂN DÂN</small>
        </div>
      </a>
      <div class="masthead-tools">
        <div class="utility">
          <a href="<?= htmlspecialchars($basePrefix . 'index.php#gioi-thieu', ENT_QUOTES, 'UTF-8') ?>">♙ Sơ đồ site</a>
          <i></i>
          <a href="#lien-he">✉ Liên hệ</a>
          <i></i>
          <a href="<?= htmlspecialchars($basePrefix . 'PhanAnh.php', ENT_QUOTES, 'UTF-8') ?>">☑ Góp ý</a>
        </div>
        <form class="search" action="<?= htmlspecialchars($basePrefix . 'index.php', ENT_QUOTES, 'UTF-8') ?>" method="get">
          <input type="search" name="q" placeholder="Nhập từ khóa tìm kiếm...">
          <button type="submit" aria-label="Tìm kiếm">⌕</button>
        </form>
        <?php if ($isLoggedIn): ?>
          <div class="auth-links logged-in">
            <span>Xin chào, <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong></span>
            <a class="login-link" href="<?= htmlspecialchars($basePrefix . 'DangXuat.php', ENT_QUOTES, 'UTF-8') ?>">ĐĂNG XUẤT</a>
          </div>
        <?php else: ?>
          <div class="auth-links" aria-label="Tài khoản">
            <a class="register-link" href="<?= htmlspecialchars($basePrefix . 'DangKy.php', ENT_QUOTES, 'UTF-8') ?>">✚ ĐĂNG KÝ</a>
            <a class="login-link" href="<?= htmlspecialchars($basePrefix . 'DangNhap.php', ENT_QUOTES, 'UTF-8') ?>">♙ ĐĂNG NHẬP</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <nav class="main-nav" aria-label="Điều hướng chính">
    <div class="container nav-inner">
      <a class="home" href="<?= htmlspecialchars($basePrefix . 'index.php', ENT_QUOTES, 'UTF-8') ?>" title="Trang chủ">⌂</a>
      <a href="<?= htmlspecialchars($basePrefix . 'GioiThieu.php', ENT_QUOTES, 'UTF-8') ?>">GIỚI THIỆU</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#tin-tuc', ENT_QUOTES, 'UTF-8') ?>">TIN TỨC - SỰ KIỆN</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#van-ban', ENT_QUOTES, 'UTF-8') ?>">VĂN BẢN</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#thu-tuc', ENT_QUOTES, 'UTF-8') ?>">THỦ TỤC HÀNH CHÍNH</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#dich-vu', ENT_QUOTES, 'UTF-8') ?>">DỊCH VỤ CÔNG</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#chuyen-doi-so', ENT_QUOTES, 'UTF-8') ?>">CHUYỂN ĐỔI SỐ</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#tuyen-truyen', ENT_QUOTES, 'UTF-8') ?>">PHỔ BIẾN PHÁP LUẬT</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#thu-vien', ENT_QUOTES, 'UTF-8') ?>">HÌNH ẢNH - VIDEO</a>
      <a href="<?= htmlspecialchars($basePrefix . 'PhanAnh.php', ENT_QUOTES, 'UTF-8') ?>">PHẢN ÁNH, KIẾN NGHỊ</a>
      <a href="<?= htmlspecialchars($basePrefix . 'tracuu_phananh.php', ENT_QUOTES, 'UTF-8') ?>">TRA CỨU MÃ</a>
      <?php if ($isStaff): ?>
        <a href="<?= htmlspecialchars($basePrefix . 'quanlydanhmuc.php', ENT_QUOTES, 'UTF-8') ?>">QUẢN LÝ DANH MỤC</a>
        <a href="<?= htmlspecialchars($basePrefix . 'quanlychuyenmuc.php', ENT_QUOTES, 'UTF-8') ?>">CHUYÊN MỤC</a>
        <a href="<?= htmlspecialchars($basePrefix . 'quanlytintuc.php', ENT_QUOTES, 'UTF-8') ?>">QUẢN LÝ TIN</a>
        <a href="<?= htmlspecialchars($basePrefix . 'phananh_kiennghi.php', ENT_QUOTES, 'UTF-8') ?>">XỬ LÝ PHẢN ÁNH</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="ticker">
    <div class="container ticker-inner">
      <span class="date">▦ <?= htmlspecialchars($todayText, ENT_QUOTES, 'UTF-8') ?></span>
      <b class="star">★</b>
      <strong>DÒNG SỰ KIỆN:</strong>
      <span>Triển khai thực hiện Nghị quyết số 57-NQ/TW về đột phá phát triển khoa học, công nghệ, đổi mới sáng tạo và chuyển đổi số quốc gia</span>
      <span class="ticker-controls">Ⅱ ▶ ›</span>
    </div>
  </div>
</header>

<main class="container main-wrap">
  <div class="admin-container">
    <h2>QUẢN LÝ DANH SÁCH NGƯỜI DÙNG</h2>

    <?php if ($error !== ''): ?>
      <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($notice !== ''): ?>
      <div class="alert-notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
      <div class="form-box">
        <h3><?= ($action === 'edit') ? 'Cập Nhật Thông Tin Người Dùng' : 'Thêm Người Dùng Mới' ?></h3>
        <form action="QuanLyNguoiDung.php<?= $action === 'edit' ? '?action=edit&id=' . $id : '' ?>" method="POST">
          <input type="hidden" name="edit_id" value="<?= $row_edit ? (int)$row_edit['id'] : 0 ?>">

          <div class="form-group">
            <label>Tên tài khoản (taikhoan):</label>
            <input type="text" name="taikhoan" value="<?= $row_edit ? htmlspecialchars((string)$row_edit['taikhoan'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
          </div>

          <div class="form-group">
            <label>Mật khẩu (matkhau) <?= ($action === 'edit') ? '(Để trống nếu giữ nguyên mật khẩu cũ)' : '*' ?>:</label>
            <input type="password" name="matkhau" <?= ($action === 'add') ? 'required' : '' ?>>
          </div>

          <div class="form-group">
            <label>Họ và tên (hoten):</label>
            <input type="text" name="hoten" value="<?= $row_edit ? htmlspecialchars((string)$row_edit['hoten'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
          </div>

          <div class="form-group">
            <label>Email (email):</label>
            <input type="email" name="email" value="<?= $row_edit ? htmlspecialchars((string)($row_edit['email'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
          </div>

          <div class="form-group">
            <label>Số điện thoại (sodienthoai):</label>
            <input type="text" name="sodienthoai" value="<?= $row_edit ? htmlspecialchars((string)($row_edit['sodienthoai'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
          </div>

          <div class="form-group">
            <label>Địa chỉ (diachi):</label>
            <input type="text" name="diachi" value="<?= $row_edit ? htmlspecialchars((string)($row_edit['diachi'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
          </div>

          <div class="form-group">
            <label>Vai trò (vaitro):</label>
            <select name="vaitro">
              <?php $curRole = $row_edit['vaitro'] ?? 'congdan'; ?>
              <option value="congdan" <?= $curRole === 'congdan' ? 'selected' : '' ?>>congdan</option>
              <option value="quantri" <?= $curRole === 'quantri' ? 'selected' : '' ?>>quantri</option>
              <option value="canbo" <?= $curRole === 'canbo' ? 'selected' : '' ?>>canbo</option>
            </select>
          </div>

          <div class="form-group">
            <label>Trạng thái (trangthai):</label>
            <select name="trangthai">
              <?php $curStatus = isset($row_edit['trangthai']) ? (int)$row_edit['trangthai'] : 1; ?>
              <option value="1" <?= $curStatus === 1 ? 'selected' : '' ?>>1 - Hoạt động</option>
              <option value="0" <?= $curStatus === 0 ? 'selected' : '' ?>>0 - Tạm khóa</option>
            </select>
          </div>

          <button type="submit" class="btn-submit">Lưu Lại</button>
          <a href="QuanLyNguoiDung.php" style="margin-left: 12px; text-decoration: none; color: #444;">Hủy bỏ</a>
        </form>
      </div>
    <?php else: ?>
      <a href="QuanLyNguoiDung.php?action=add" class="btn-add">✚ Thêm người dùng mới</a>

      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tài khoản</th>
            <th>Họ tên</th>
            <th>Email</th>
            <th>SĐT</th>
            <th>Địa chỉ</th>
            <th>Vai trò</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="10" style="text-align:center;">Chưa có dữ liệu người dùng.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $row): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= htmlspecialchars((string)$row['taikhoan'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$row['hoten'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($row['sodienthoai'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($row['diachi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$row['vaitro'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= ((int)$row['trangthai'] === 1) ? 'Hoạt động' : 'Khóa' ?></td>
                <td><?= htmlspecialchars((string)($row['ngaytao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <a href="QuanLyNguoiDung.php?action=edit&id=<?= (int)$row['id'] ?>" class="btn-action btn-edit">Sửa</a>
                  <a href="QuanLyNguoiDung.php?action=delete&id=<?= (int)$row['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này không?');">Xóa</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>

<footer class="site-footer" id="lien-he">
  <div class="container footer-inner">
    <div class="footer-brand">
      <img src="<?= htmlspecialchars($basePrefix . 'assets/quoc-huy.png', ENT_QUOTES, 'UTF-8') ?>" alt="Quốc huy Việt Nam">
      <div><b>CÔNG AN XÃ LỘC THÀNH</b><br><small>THÀNH PHỐ ĐỒNG NAI</small></div>
    </div>
    <div>
      <h3>THÔNG TIN LIÊN HỆ</h3>
      <p>Địa chỉ: Xã Lộc Thành, thành phố Đồng Nai</p>
      <p>Điện thoại: 0271.xxx.xxxx</p>
      <p>Email: cap.xa@example.gov.vn</p>
    </div>
    <div>
      <h3>LIÊN KẾT</h3>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#gioi-thieu', ENT_QUOTES, 'UTF-8') ?>">Giới thiệu</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#tin-tuc', ENT_QUOTES, 'UTF-8') ?>">Tin tức - sự kiện</a>
      <a href="<?= htmlspecialchars($basePrefix . 'index.php#thu-tuc', ENT_QUOTES, 'UTF-8') ?>">Thủ tục hành chính</a>
    </div>
    <div>
      <h3>KẾT NỐI</h3>
      <p>Thông tin tuyên truyền và hoạt động của đơn vị.</p>
      <a href="<?= htmlspecialchars($basePrefix . 'PhanAnh.php', ENT_QUOTES, 'UTF-8') ?>">Gửi phản ánh, kiến nghị</a>
    </div>
  </div>
  <div class="copyright">© <?= date('Y') ?> CÔNG AN XÃ LỘC THÀNH - thành phố ĐỒNG NAI.</div>
</footer>
</body>
</html>