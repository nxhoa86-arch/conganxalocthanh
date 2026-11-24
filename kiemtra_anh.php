<?php
$checks = ['assets/banner-ai.png','assets/quoc-huy.png','assets/nen-cong-nghe.png','uploads/tintuc'];
?><!doctype html><html lang="vi"><meta charset="utf-8"><title>Kiểm tra ảnh website</title>
<style>body{font:15px Arial;max-width:850px;margin:30px auto;color:#222}table{border-collapse:collapse;width:100%}td,th{padding:10px;border:1px solid #ddd;text-align:left}.ok{color:green}.bad{color:#b00}</style>
<h2>KIỂM TRA ĐƯỜNG DẪN HÌNH ẢNH</h2><table><tr><th>Đường dẫn</th><th>Kết quả</th></tr>
<?php foreach($checks as $f): $full=__DIR__.'/'.$f; $ok=is_dir($full)||is_file($full); ?>
<tr><td><?=htmlspecialchars($f,ENT_QUOTES,'UTF-8')?></td><td class="<?=$ok?'ok':'bad'?>"><?=$ok?'ĐÃ TỒN TẠI':'KHÔNG TÌM THẤY'?></td></tr>
<?php endforeach; ?></table><p>Đối chiếu tên ảnh trong cột hinh_dd của bảng tintuc với file thực tế trong uploads/tintuc.</p></html>
