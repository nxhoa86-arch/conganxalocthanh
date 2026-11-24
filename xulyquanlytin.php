<?php
require_once __DIR__ . '/common.php';
require_admin();

require_staff();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect_to('quanlytintuc.php');
}

verify_csrf();

$action = $_POST['action'] ?? '';
$id = (int)($_POST['ma'] ?? 0);
$uploadedRelativePath = null;

/**
 * Chuyen dung luong ini sang byte de hien thi huong dan khi PHP chan upload.
 */
function upload_ini_bytes($value)
{
    $value = trim((string)$value);
    if ($value === '') return 0;

    $unit = strtolower(substr($value, -1));
    $number = (float)$value;

    switch ($unit) {
        case 'g': $number *= 1024;
        case 'm': $number *= 1024;
        case 'k': $number *= 1024;
    }

    return (int)$number;
}

/**
 * Tra ve thong bao ro rang theo ma loi upload cua PHP.
 */
function upload_error_message($error)
{
    switch ((int)$error) {
        case UPLOAD_ERR_INI_SIZE:
            return 'Ảnh vượt giới hạn upload_max_filesize của PHP. Hãy tăng upload_max_filesize và post_max_size trong php.ini, sau đó khởi động lại Apache.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ảnh vượt giới hạn dung lượng cho phép của biểu mẫu.';
        case UPLOAD_ERR_PARTIAL:
            return 'Ảnh chỉ được tải lên một phần. Vui lòng chọn lại ảnh và thử lại.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Máy chủ chưa cấu hình thư mục tạm để tải ảnh. Kiểm tra upload_tmp_dir trong php.ini.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'PHP không ghi được tệp tải lên. Kiểm tra quyền ghi ổ đĩa/thư mục tạm.';
        case UPLOAD_ERR_EXTENSION:
            return 'Một tiện ích mở rộng PHP đã dừng việc tải ảnh lên.';
        default:
            return 'Không nhận được ảnh tải lên. Vui lòng thử lại.';
    }
}

try {
    // Xoa bai viet
    if ($action === 'delete') {
        if ($id <= 0) {
            throw new RuntimeException('Mã bài viết không hợp lệ.');
        }

        $stmt = $conn->prepare('DELETE FROM tintuc WHERE ma = ?');
        $stmt->execute([$id]);

        flash('Đã xóa bài viết.');
        redirect_to('quanlytintuc.php');
    }

    if (!in_array($action, ['create', 'update'], true)) {
        throw new RuntimeException('Thao tác không hợp lệ.');
    }

    if ($action === 'update' && $id <= 0) {
        throw new RuntimeException('Mã bài viết không hợp lệ.');
    }

    $title = trim($_POST['detai'] ?? '');
    $content = trim($_POST['motachitiet'] ?? '');

    if ($title === '' || $content === '') {
        throw new RuntimeException('Tiêu đề và nội dung chi tiết là bắt buộc.');
    }

    // Kiem tra danh muc va chuyen muc
    $categoryId = (($_POST['madanhmuc'] ?? '') !== '')
        ? (int)$_POST['madanhmuc'] : null;

    $subCategoryId = (($_POST['machuyenmuc'] ?? '') !== '')
        ? (int)$_POST['machuyenmuc'] : null;

    $status = $_POST['trangthai'] ?? 'nhap';
    $allowedStatuses = ['nhap', 'cho_duyet', 'da_dang', 'an'];

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'nhap';
    }

    if ($subCategoryId !== null) {
        $stmt = $conn->prepare(
            'SELECT madanhmuc FROM chuyenmuc WHERE machuyenmuc = ?'
        );
        $stmt->execute([$subCategoryId]);
        $parentId = $stmt->fetchColumn();

        if (!$parentId) {
            throw new RuntimeException('Chuyên mục không tồn tại.');
        }

        if ($categoryId !== null && (int)$parentId !== $categoryId) {
            throw new RuntimeException('Chuyên mục không thuộc danh mục đã chọn.');
        }

        if ($categoryId === null) {
            $categoryId = (int)$parentId;
        }
    }

    // Lay anh cu khi cap nhat de giu lai neu khong chon anh moi
    $oldImage = null;

    if ($action === 'update') {
        $stmt = $conn->prepare('SELECT hinh_dd FROM tintuc WHERE ma = ?');
        $stmt->execute([$id]);
        $oldImage = $stmt->fetchColumn();

        if ($oldImage === false) {
            throw new RuntimeException('Không tìm thấy bài viết cần cập nhật.');
        }
    }

    // Xu ly anh moi (khong bat buoc)
    if (isset($_FILES['hinh_dd']) &&
        (int)($_FILES['hinh_dd']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

        $file = $_FILES['hinh_dd'];
        $errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new RuntimeException(upload_error_message($errorCode));
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Tệp ảnh tải lên không hợp lệ. Vui lòng chọn lại ảnh.');
        }

        $maxSize = 5 * 1024 * 1024;

        if ((int)$file['size'] > $maxSize) {
            throw new RuntimeException('Ảnh vượt quá 5 MB. Vui lòng chọn ảnh nhỏ hơn.');
        }

        // Kiem tra MIME thuc te, khong tin vao ten file/duoi file do trinh duyet gui len
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];

        if (!isset($allowedTypes[$mime])) {
            throw new RuntimeException('Chỉ nhận ảnh JPG, PNG, WEBP hoặc GIF.');
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new RuntimeException('Tệp tải lên không phải hình ảnh hợp lệ.');
        }

        $extension = $allowedTypes[$mime];
        $uploadDirectory = __DIR__ . '/uploads/tintuc';

        if (!is_dir($uploadDirectory)) {
            if (!@mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException('Không tạo được thư mục uploads/tintuc. Hãy tạo thư mục này thủ công.');
            }
        }

        if (!is_writable($uploadDirectory)) {
            throw new RuntimeException('Thư mục uploads/tintuc không có quyền ghi. Hãy cấp quyền ghi cho thư mục.');
        }

        $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $uploadDirectory . '/' . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Không lưu được ảnh vào uploads/tintuc. Kiểm tra quyền ghi thư mục.');
        }

        $uploadedRelativePath = 'uploads/tintuc/' . $newFileName;
    }

    // Neu khong chon anh moi khi sua thi giu nguyen anh cu
    $imagePath = $uploadedRelativePath ?? $oldImage;
    $description = trim($_POST['mota'] ?? '');
    $description = ($description !== '') ? $description : null;

    $date = date('Y-m-d');
    $time = date('H:i:s');
    $userName = current_user_name();

    if ($action === 'create') {
        $stmt = $conn->prepare(
            'INSERT INTO tintuc
            (detai, hinh_dd, mota, motachitiet, theloai, id, ngay, time,
             nguoidang, madanhmuc, machuyenmuc, trangthai)
             VALUES (?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $title,
            $imagePath,
            $description,
            $content,
            $date,
            $time,
            $userName,
            $categoryId,
            $subCategoryId,
            $status
        ]);

        flash('Đã thêm bài viết.');
    } else {
        $stmt = $conn->prepare(
            'UPDATE tintuc
             SET detai = ?, hinh_dd = ?, mota = ?, motachitiet = ?,
                 ngay = ?, time = ?, nguoidang = ?, madanhmuc = ?,
                 machuyenmuc = ?, trangthai = ?
             WHERE ma = ?'
        );

        $stmt->execute([
            $title,
            $imagePath,
            $description,
            $content,
            $date,
            $time,
            $userName,
            $categoryId,
            $subCategoryId,
            $status,
            $id
        ]);

        flash('Đã cập nhật bài viết.');
    }

} catch (Throwable $e) {
    // Neu upload thanh cong nhung luu CSDL that bai thi xoa file vua upload
    if ($uploadedRelativePath !== null) {
        $newFile = __DIR__ . '/' . $uploadedRelativePath;
        if (is_file($newFile)) {
            @unlink($newFile);
        }
    }

    $message = ($e instanceof PDOException)
        ? 'Không thể lưu dữ liệu. Hãy kiểm tra cấu trúc bảng tintuc và các khóa liên kết.'
        : $e->getMessage();

    flash($message, 'error');
}

redirect_to('quanlytintuc.php');
