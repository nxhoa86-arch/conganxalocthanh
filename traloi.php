<?php
require_once __DIR__.'/common.php';require_staff();if($_SERVER['REQUEST_METHOD']!=='POST')redirect_to('phananh_kiennghi.php');verify_csrf();
$id=(int)($_POST['phananh_id']??0);$body=trim($_POST['noidung_traloi']??'');$status=$_POST['trangthai']??'dang_xu_ly';$public=(int)($_POST['cong_khai']??0);
$allowed=['dang_xu_ly','da_tra_loi','tu_choi','dong'];
if($id<1||$body===''){flash('Vui lòng nhập đầy đủ nội dung trả lời.','error');redirect_to('phananh_kiennghi.php');}
if(!in_array($status,$allowed,true))$status='dang_xu_ly';
try{$conn->beginTransaction();$s=$conn->prepare('SELECT trangthai FROM phananh_kiennghi WHERE id=? FOR UPDATE');$s->execute([$id]);$old=$s->fetchColumn();if($old===false)throw new RuntimeException('Không tìm thấy phản ánh.');
$ins=$conn->prepare('INSERT INTO traloi_phananh(phananh_id,nguoidung_id,noidung_traloi,cong_khai) VALUES(?,?,?,?)');$ins->execute([$id,current_user_id(),$body,$public]);
$up=$conn->prepare('UPDATE phananh_kiennghi SET trangthai=? WHERE id=?');$up->execute([$status,$id]);
$his=$conn->prepare('INSERT INTO lichsu_phananh(phananh_id,nguoidung_id,trangthai_cu,trangthai_moi,ghichu) VALUES(?,?,?,?,?)');$his->execute([$id,current_user_id(),$old,$status,$body]);$conn->commit();flash('Đã lưu câu trả lời và cập nhật trạng thái phản ánh.');
}catch(Throwable $e){if($conn->inTransaction())$conn->rollBack();flash($e->getMessage(),'error');}
redirect_to('phananh_kiennghi.php');
