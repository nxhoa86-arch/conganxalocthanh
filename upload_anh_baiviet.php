<?php
while (ob_get_level() > 0) {
    ob_end_clean();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function json_err(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['error' => ['message' => $message]], JSON_UNESCAPED_UNICODE);
    exit;
}

$role = strtolower(trim((string)($_SESSION['vaitro'] ?? $_SESSION['PhanQuyen'] ?? '')));
$isLoggedIn = !empty($_SESSION['nguoidung_id']) || isset($_SESSION['UserName']);
if (!$isLoggedIn && !in_array($role, ['quantri', 'canbo', 'admin', 'administrator'], true)) {
    json_err(403, 'Phiên đăng nhập đã hết hoặc bạn không có quyền tải ảnh lên.');
}

if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    json_err(400, 'Không tìm thấy tệp ảnh tải lên.');
}

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

$imgInfo = @getimagesize($_FILES['file']['tmp_name']);
$mime = $imgInfo['mime'] ?? '';
if (!isset($allowed[$mime])) {
    json_err(400, 'Chỉ chấp nhận định dạng ảnh JPG, PNG, WEBP, GIF.');
}

if (filesize($_FILES['file']['tmp_name']) > 5 * 1024 * 1024) {
    json_err(400, 'Dung lượng ảnh vượt quá 5 MB.');
}

$uploadDir = __DIR__ . '/uploads/tintuc/';
if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
    json_err(500, 'Không thể tạo thư mục uploads/tintuc/.');
}

$fileName = 'bv_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
$targetPath = $uploadDir . $fileName;

if (!@move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
    json_err(500, 'Không thể lưu ảnh vào thư mục uploads/tintuc/.');
}

echo json_encode([
    'location' => 'uploads/tintuc/' . $fileName
], JSON_UNESCAPED_UNICODE);
exit;