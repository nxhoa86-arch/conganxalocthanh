<?php
require_once __DIR__ . '/common.php';

$targetPage = 'Phananhkiennghi.php';
if (!current_user_id()) {
    // Ghi nhớ trang đích để sau khi đăng nhập thành công quay lại đúng chức năng.
    $_SESSION['redirect_after_login'] = $targetPage;
    require_once __DIR__ . '/template.php';
    site_header('ĐĂNG NHẬP ĐỂ GỬI PHẢN ÁNH');
    ?>
    <section class="feedback-page">
      <div class="feedback-card feedback-gate">
        <div class="feedback-icon">🔐</div>
        <h1>VUI LÒNG ĐĂNG KÝ HOẶC ĐĂNG NHẬP</h1>
        <p>Để gửi phản ánh, kiến nghị và theo dõi nội dung trả lời từ Công an xã Lộc Thành, công dân cần có tài khoản trên hệ thống.</p>
        <p class="feedback-note">Sau khi đăng ký và đăng nhập thành công, hệ thống sẽ tự động chuyển đến trang phản ánh, kiến nghị.</p>
        <div class="feedback-actions">
          <a class="feedback-btn primary" href="DangKy.php?next=Phananhkiennghi.php">ĐĂNG KÝ TÀI KHOẢN</a>
          <a class="feedback-btn secondary" href="DangNhap.php?next=Phananhkiennghi.php">ĐĂNG NHẬP</a>
        </div>
      </div>
    </section>
    <?php
    site_footer();
    exit;
}

$userId = current_user_id();
$userStmt = $conn->prepare('SELECT id, hoten, email, sodienthoai, diachi FROM nguoidung WHERE id = ? AND trangthai = 1 LIMIT 1');
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$currentUser) {
    $_SESSION = [];
    session_destroy();
    redirect_to('DangNhap.php?next=Phananhkiennghi.php');
}

$error = '';
$successCode = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $hoten = trim($_POST['hoten'] ?? $currentUser['hoten']);
    $phone = trim($_POST['sodienthoai'] ?? $currentUser['sodienthoai']);
    $email = trim($_POST['email'] ?? $currentUser['email']);
    $address = trim($_POST['diachi'] ?? $currentUser['diachi']);
    $field = trim($_POST['linhvuc'] ?? '');
    $title = trim($_POST['tieude'] ?? '');
    $body = trim($_POST['noidung'] ?? '');
    $privacy = (($_POST['chedo_hienthi'] ?? 'bao_mat') === 'cong_khai') ? 'cong_khai' : 'bao_mat';

    if ($hoten === '' || $title === '' || $body === '') {
        $error = 'Vui lòng nhập họ tên, tiêu đề và nội dung phản ánh.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title)) > 255) {
        $error = 'Tiêu đề không được vượt quá 255 ký tự.';
    } else {
        try {
            $successCode = 'PA' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            $insert = $conn->prepare('INSERT INTO phananh_kiennghi (ma_tiepnhan, nguoidung_id, hoten, sodienthoai, email, diachi, linhvuc, tieude, noidung, chedo_hienthi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$successCode, $userId, $hoten, $phone ?: null, $email ?: null, $address ?: null, $field ?: null, $title, $body, $privacy]);
            flash('Đã gửi phản ánh thành công. Mã tiếp nhận: ' . $successCode . '. Nội dung đã được lưu vào tài khoản của bạn.');
            redirect_to('Phananhkiennghi.php');
        } catch (Throwable $e) {
            $error = 'Không gửi được phản ánh. Vui lòng kiểm tra kết nối cơ sở dữ liệu và thử lại.';
        }
    }
}

$perPage = 5;
$page = page_value();
$countStmt = $conn->prepare('SELECT COUNT(*) FROM phananh_kiennghi WHERE nguoidung_id = ?');
$countStmt->execute([$userId]);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$listStmt = $conn->prepare('SELECT * FROM phananh_kiennghi WHERE nguoidung_id = :uid ORDER BY ngaygui DESC, id DESC LIMIT :lim OFFSET :off');
$listStmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$listStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':off', $offset, PDO::PARAM_INT);
$listStmt->execute();
$myFeedback = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$statusNames = [
    'moi_tiep_nhan' => 'Mới tiếp nhận',
    'dang_xu_ly' => 'Đang xử lý',
    'da_tra_loi' => 'Đã trả lời',
    'tu_choi' => 'Từ chối',
    'dong' => 'Đã đóng'
];

require_once __DIR__ . '/template.php';
site_header('PHẢN ÁNH, KIẾN NGHỊ');
?>
<section class="feedback-page">
  <div class="feedback-heading">
    <div><small>CỔNG THÔNG TIN ĐIỆN TỬ CÔNG AN XÃ LỘC THÀNH</small><h1>PHẢN ÁNH, KIẾN NGHỊ</h1><p>Xin chào <strong><?= h($currentUser['hoten']) ?></strong>. Công dân có thể gửi phản ánh và theo dõi kết quả trả lời ngay tại trang này.</p></div>
  </div>
  <?php show_flash(); ?>
  <?php if ($error !== ''): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>

  <section class="feedback-card">
    <h2>GỬI PHẢN ÁNH, KIẾN NGHỊ MỚI</h2>
    <p class="feedback-note">Thông tin được chuyển đến Công an xã để xem xét, xử lý theo quy định. Không gửi mật khẩu, mã OTP hoặc tài liệu bí mật nhà nước qua biểu mẫu này.</p>
    <form method="post" action="Phananhkiennghi.php" class="feedback-form">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="feedback-grid">
        <div class="field"><label>Họ và tên *</label><input name="hoten" value="<?= h($_POST['hoten'] ?? $currentUser['hoten']) ?>" required maxlength="150"></div>
        <div class="field"><label>Số điện thoại</label><input name="sodienthoai" value="<?= h($_POST['sodienthoai'] ?? $currentUser['sodienthoai']) ?>" maxlength="20"></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="<?= h($_POST['email'] ?? $currentUser['email']) ?>" maxlength="150"></div>
        <div class="field"><label>Địa chỉ</label><input name="diachi" value="<?= h($_POST['diachi'] ?? $currentUser['diachi']) ?>" maxlength="255"></div>
        <div class="field"><label>Lĩnh vực</label><select name="linhvuc"><option value="">-- Chọn lĩnh vực --</option><?php foreach (['An ninh trật tự','Thủ tục hành chính','Cư trú, căn cước','Giao thông','Môi trường','Lĩnh vực khác'] as $option): ?><option value="<?= h($option) ?>" <?= (($_POST['linhvuc'] ?? '') === $option) ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Chế độ thông tin</label><select name="chedo_hienthi"><option value="bao_mat">Bảo mật thông tin người gửi</option><option value="cong_khai" <?= (($_POST['chedo_hienthi'] ?? '') === 'cong_khai') ? 'selected' : '' ?>>Cho phép công khai nội dung (không công khai thông tin cá nhân)</option></select></div>
      </div>
      <div class="field"><label>Tiêu đề *</label><input name="tieude" value="<?= h($_POST['tieude'] ?? '') ?>" required maxlength="255"></div>
      <div class="field"><label>Nội dung phản ánh, kiến nghị *</label><textarea name="noidung" rows="6" required><?= h($_POST['noidung'] ?? '') ?></textarea></div>
      <button type="submit" class="feedback-btn primary">GỬI PHẢN ÁNH</button>
    </form>
  </section>

  <section class="feedback-history">
    <div class="feedback-history-title"><div><small>THEO DÕI KẾT QUẢ</small><h2>PHẢN ÁNH ĐÃ GỬI VÀ NỘI DUNG TRẢ LỜI</h2></div><span>Tổng số: <?= $total ?></span></div>
    <?php if (!$myFeedback): ?><div class="feedback-empty">Bạn chưa gửi phản ánh, kiến nghị nào.</div><?php endif; ?>
    <?php foreach ($myFeedback as $item): ?>
      <article class="feedback-item">
        <div class="feedback-item-top"><b>Mã tiếp nhận: <?= h($item['ma_tiepnhan']) ?></b><span class="feedback-status status-<?= h($item['trangthai']) ?>"><?= h($statusNames[$item['trangthai']] ?? $item['trangthai']) ?></span></div>
        <h3><?= h($item['tieude']) ?></h3>
        <p class="feedback-meta">Gửi lúc <?= h($item['ngaygui']) ?> · Lĩnh vực: <?= h($item['linhvuc'] ?: 'Chưa phân loại') ?></p>
        <div class="feedback-body"><?= nl2br(h($item['noidung'])) ?></div>
        <?php
          $replyStmt = $conn->prepare('SELECT noidung_traloi, ngaytraloi FROM traloi_phananh WHERE phananh_id = ? AND cong_khai = 1 ORDER BY ngaytraloi ASC, id ASC');
          $replyStmt->execute([(int)$item['id']]);
          $publicReplies = $replyStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="feedback-replies">
          <h4>NỘI DUNG TRẢ LỜI TỪ CÔNG AN XÃ</h4>
          <?php if (!$publicReplies): ?><p class="feedback-pending">Phản ánh đang được tiếp nhận/xử lý. Chưa có nội dung trả lời công khai.</p><?php else: foreach ($publicReplies as $reply): ?>
            <div class="feedback-reply"><b>Trả lời lúc <?= h($reply['ngaytraloi']) ?>:</b><p><?= nl2br(h($reply['noidung_traloi'])) ?></p></div>
          <?php endforeach; endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if ($pages > 1): ?><nav class="pagination" aria-label="Phân trang phản ánh"><?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">‹</a><?php endif; ?><?php for ($p = 1; $p <= $pages; $p++): ?><a class="<?= $p === $page ? 'current' : '' ?>" href="?page=<?= $p ?>"><?= $p ?></a><?php endfor; ?><?php if ($page < $pages): ?><a href="?page=<?= $page + 1 ?>">›</a><?php endif; ?></nav><?php endif; ?>
  </section>
</section>
<style>
.feedback-page{max-width:1000px;margin:28px auto 48px;color:#243247}.feedback-heading{margin-bottom:18px;border-bottom:2px solid #b90016;padding-bottom:14px}.feedback-heading small,.feedback-history-title small{color:#b90016;font-size:11px;font-weight:800;letter-spacing:1px}.feedback-heading h1{margin:5px 0;color:#970013;font-size:25px}.feedback-heading p{margin:6px 0;color:#687386}.feedback-card,.feedback-item{background:#fff;border:1px solid #dfe4eb;border-radius:9px;box-shadow:0 3px 14px #182b460b;padding:22px;margin-bottom:20px}.feedback-card h2,.feedback-history-title h2{margin:0 0 10px;color:#970013;font-size:19px}.feedback-note{font-size:13px;color:#667085;line-height:1.6}.feedback-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px 18px}.feedback-form .field{margin:13px 0}.feedback-form label{display:block;font-weight:700;font-size:13px;margin-bottom:6px}.feedback-form input,.feedback-form select,.feedback-form textarea{width:100%;box-sizing:border-box;border:1px solid #cbd3df;border-radius:5px;padding:10px 11px;font:14px Arial;background:#fff}.feedback-form textarea{resize:vertical}.feedback-btn{display:inline-flex;justify-content:center;align-items:center;border:0;border-radius:5px;padding:11px 17px;text-decoration:none;font-weight:700;cursor:pointer}.feedback-btn.primary{background:#970013;color:#fff}.feedback-btn.secondary{background:#fff;color:#970013;border:1px solid #970013}.feedback-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:22px}.feedback-gate{text-align:center;padding:42px 28px}.feedback-icon{font-size:42px}.feedback-gate h1{color:#970013;font-size:22px}.feedback-gate p{max-width:700px;margin:12px auto;line-height:1.65}.feedback-history{margin-top:32px}.feedback-history-title{display:flex;align-items:end;justify-content:space-between;gap:12px;border-bottom:2px solid #b90016;padding-bottom:12px;margin-bottom:18px}.feedback-history-title h2{margin:5px 0 0}.feedback-history-title>span{color:#667085;font-size:13px}.feedback-item-top{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;color:#183b69}.feedback-status{font-size:12px;border-radius:20px;padding:5px 10px;background:#edf2f7;color:#344054}.status-da_tra_loi{background:#e5f7ec;color:#16713b}.status-dang_xu_ly{background:#fff4d6;color:#8a5b00}.status-tu_choi{background:#fde8e8;color:#a31515}.feedback-item h3{margin:13px 0 8px;font-size:19px}.feedback-meta{font-size:12px;color:#758195}.feedback-body{line-height:1.65;white-space:normal}.feedback-replies{margin-top:18px;padding:15px;background:#f4f7fa;border-radius:7px}.feedback-replies h4{margin:0 0 10px;color:#17416e;font-size:13px}.feedback-reply{padding:12px;background:#fff;border-left:3px solid #27834a;border-radius:4px;margin-top:8px}.feedback-reply p{margin:7px 0 0;line-height:1.6}.feedback-pending,.feedback-empty{color:#687386;font-size:13px}.feedback-empty{padding:25px;background:#fff;border:1px solid #e1e5eb;border-radius:8px;text-align:center}@media(max-width:650px){.feedback-grid{grid-template-columns:1fr}.feedback-card,.feedback-item{padding:15px}.feedback-history-title{align-items:flex-start;flex-direction:column}.feedback-heading h1{font-size:21px}}
</style>
<?php site_footer(); ?>
