<?php
require_once __DIR__.'/common.php';require_admin(); require_staff(); if($_SERVER['REQUEST_METHOD']!=='POST') redirect_to('quanlychuyenmuc.php'); verify_csrf();
$action=$_POST['action']??'';$id=(int)($_POST['machuyenmuc']??0);
try{
 if(in_array($action,['create','update'],true)){$name=trim($_POST['tenchuyenmuc']??'');if($name==='')throw new RuntimeException('Tên chuyên mục không được để trống.');$cat=($_POST['madanhmuc']??'')!==''?(int)$_POST['madanhmuc']:null;$type=trim($_POST['theloai']??'')?:null;$desc=trim($_POST['motangan']??'')?:null;$status=(int)($_POST['trang_thai']??1);
 if($action==='create'){$s=$conn->prepare('INSERT INTO chuyenmuc(tenchuyenmuc,madanhmuc,theloai,motangan,trang_thai,ngay,time,nguoidang) VALUES(?,?,?,?,?,CURDATE(),CURTIME(),?)');$s->execute([$name,$cat,$type,$desc,$status,current_user_name()]);flash('Đã thêm chuyên mục.');}
 else{$s=$conn->prepare('UPDATE chuyenmuc SET tenchuyenmuc=?,madanhmuc=?,theloai=?,motangan=?,trang_thai=? WHERE machuyenmuc=?');$s->execute([$name,$cat,$type,$desc,$status,$id]);flash('Đã cập nhật chuyên mục.');}}
 elseif($action==='delete'){$s=$conn->prepare('DELETE FROM chuyenmuc WHERE machuyenmuc=?');$s->execute([$id]);flash('Đã xóa chuyên mục; tin tức liên quan vẫn được giữ.');}
 else throw new RuntimeException('Thao tác không hợp lệ.');
}catch(Throwable $e){flash($e instanceof PDOException?'Không thể lưu dữ liệu. Hãy kiểm tra danh mục cha.':$e->getMessage(),'error');}
redirect_to('quanlychuyenmuc.php');
