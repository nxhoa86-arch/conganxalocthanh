<?php
require_once __DIR__ . '/common.php';
require_admin();
require_staff();

$edit = null;
if (isset($_GET['edit'])) {
    $s = $conn->prepare('SELECT * FROM tintuc WHERE ma = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$cats = $conn->query('SELECT madanhmuc, tendanhmuc FROM danhmuc ORDER BY tendanhmuc')->fetchAll();
$subs = $conn->query('SELECT machuyenmuc, tenchuyenmuc, madanhmuc FROM chuyenmuc ORDER BY tenchuyenmuc')->fetchAll();

$per = 10;
$page = page_value();
$total = (int)$conn->query('SELECT COUNT(*) FROM tintuc')->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);

$st = $conn->prepare('SELECT t.*, d.tendanhmuc, c.tenchuyenmuc
                      FROM tintuc t
                      LEFT JOIN danhmuc d ON d.madanhmuc = t.madanhmuc
                      LEFT JOIN chuyenmuc c ON c.machuyenmuc = t.machuyenmuc
                      ORDER BY t.ma DESC
                      LIMIT :n OFFSET :o');
$st->bindValue(':n', $per, PDO::PARAM_INT);
$st->bindValue(':o', ($page - 1) * $per, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

admin_header('QUẢN LÝ TIN TỨC');
?>
<section class="panel">
  <h2><?= $edit ? 'Sửa bài viết' : 'Đăng bài viết mới' ?></h2>
  <form method="post" action="xulyquanlytin.php" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <input type="hidden" name="ma" value="<?= h($edit['ma'] ?? '') ?>">

    <div class="field">
      <label>Tiêu đề *</label>
      <input name="detai" required maxlength="255" value="<?= h($edit['detai'] ?? '') ?>">
    </div>

    <div class="grid">
      <div class="field">
        <label>Danh mục</label>
        <select name="madanhmuc" id="madanhmuc">
          <option value="">-- Chọn danh mục --</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['madanhmuc'] ?>" <?= ((int)($edit['madanhmuc'] ?? 0) === (int)$c['madanhmuc'] ? 'selected' : '') ?>>
              <?= h($c['tendanhmuc']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Chuyên mục</label>
        <select name="machuyenmuc" id="machuyenmuc">
          <option value="">-- Chọn chuyên mục --</option>
          <?php foreach ($subs as $c): ?>
            <option data-parent="<?= $c['madanhmuc'] ?>" value="<?= $c['machuyenmuc'] ?>" <?= ((int)($edit['machuyenmuc'] ?? 0) === (int)$c['machuyenmuc'] ? 'selected' : '') ?>>
              <?= h($c['tenchuyenmuc']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Trạng thái bài viết</label>
        <select name="trangthai">
          <?php foreach (['nhap' => 'Bản nháp', 'cho_duyet' => 'Chờ duyệt', 'da_dang' => 'Đăng công khai', 'an' => 'Ẩn'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= ($edit['trangthai'] ?? 'nhap') === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Ảnh đại diện</label>
        <input type="file" name="hinh_dd" accept="image/jpeg,image/png,image/webp,image/gif">
        <small class="muted">JPG, PNG, WEBP, GIF; tối đa 5 MB. Để trống nếu giữ ảnh cũ.</small>
        <?php if (!empty($edit['hinh_dd'])): ?>
          <p class="muted">Ảnh hiện tại: <?= h($edit['hinh_dd']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="field">
      <label>Mô tả ngắn</label>
      <textarea name="mota" rows="3"><?= h($edit['mota'] ?? '') ?></textarea>
    </div>

    <div class="field">
      <label>Nội dung chi tiết *</label>
      <textarea name="motachitiet" id="motachitiet" rows="12"><?= h($edit['motachitiet'] ?? '') ?></textarea>
    </div>

    <button><?= $edit ? 'Lưu bài viết' : 'Thêm bài viết' ?></button>
    <?php if ($edit): ?>
      <a class="btn secondary" href="quanlytintuc.php">Hủy</a>
    <?php endif; ?>
  </form>
</section>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Mã</th>
        <th>Tiêu đề</th>
        <th>Danh mục</th>
        <th>Chuyên mục</th>
        <th>Ngày</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= $r['ma'] ?></td>
          <td><?= h($r['detai']) ?></td>
          <td><?= h($r['tendanhmuc'] ?? '—') ?></td>
          <td><?= h($r['tenchuyenmuc'] ?? '—') ?></td>
          <td><?= h($r['ngay']) ?></td>
          <td><?= h($r['trangthai']) ?></td>
          <td>
            <div class="actions">
              <a class="btn small" href="?edit=<?= $r['ma'] ?>">Sửa</a>
              <form method="post" action="xulyquanlytin.php" onsubmit="return confirm('Xóa bài viết này?');">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="ma" value="<?= $r['ma'] ?>">
                <button class="danger small">Xóa</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= pager($total, $per, $page, 'quanlytintuc.php') ?>

<script>
const cat = document.getElementById('madanhmuc');
const sub = document.getElementById('machuyenmuc');
function filterSubs() {
  [...sub.options].forEach(o => {
    if (!o.value) return;
    o.hidden = !!o.dataset.parent && o.dataset.parent !== cat.value;
    if (o.hidden && o.selected) sub.value = '';
  });
}
cat.addEventListener('change', filterSubs);
filterSubs();
</script>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
tinymce.init({
  selector: '#motachitiet',
  height: 440,
  menubar: false,
  plugins: 'image link lists table code',
  toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | image link | code',
  automatic_uploads: true,
  relative_urls: false,
  remove_script_host: true,
  images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
    const formData = new FormData();
    formData.append('file', blobInfo.blob(), blobInfo.filename());

    fetch('upload_anh_baiviet.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
      .then(async (res) => {
        if (!res.ok) {
          throw new Error('HTTP ' + res.status + ' - Không tìm thấy upload_anh_baiviet.php');
        }
        return res.json();
      })
      .then((data) => {
        if (data && data.location) {
          resolve(data.location);
        } else {
          reject(data?.error?.message || 'Tải ảnh thất bại');
        }
      })
      .catch((err) => reject(err.message));
  })
});
</script>
<?php admin_footer(); ?>