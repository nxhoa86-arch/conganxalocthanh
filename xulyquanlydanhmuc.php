<?php
require_once __DIR__.'/common.php';require_admin(); require_staff(); if($_SERVER['REQUEST_METHOD']!=='POST') redirect_to('quanlydanhmuc.php'); verify_csrf();
$action=$_POST['action']??''; $id=(int)($_POST['madanhmuc']??0);
try {
 if($action==='create'||$action==='update'){
  $name=trim($_POST['tendanhmuc']??''); if($name==='') throw new RuntimeException('Tên danh mục không được để trống.');
  $code=trim($_POST['ma_danh_muc']??'') ?: null; $type=trim($_POST['theloai']??'') ?: null; $desc=trim($_POST['mo_ta']??'') ?: null; $status=(int)($_POST['trang_thai']??1);
  if($action==='create'){$s=$conn->prepare('INSERT INTO danhmuc(ma_danh_muc,tendanhmuc,theloai,mo_ta,trang_thai) VALUES(?,?,?,?,?)');$s->execute([$code,$name,$type,$desc,$status]);flash('Đã thêm danh mục.');}
  else{$s=$conn->prepare('UPDATE danhmuc SET ma_danh_muc=?,tendanhmuc=?,theloai=?,mo_ta=?,trang_thai=? WHERE madanhmuc=?');$s->execute([$code,$name,$type,$desc,$status,$id]);flash('Đã cập nhật danh mục.');}
 } elseif($action==='delete') { $s=$conn->prepare('DELETE FROM danhmuc WHERE madanhmuc=?');$s->execute([$id]);flash('Đã xóa danh mục. Các liên kết liên quan được đặt NULL.'); }
 else throw new RuntimeException('Thao tác không hợp lệ.');
} catch(Throwable $e){flash($e instanceof PDOException?'Không thể lưu. Mã danh mục có thể đã tồn tại.':$e->getMessage(),'error');}
redirect_to('quanlydanhmuc.php');
