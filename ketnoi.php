<?php
// Cau hinh ket noi MySQL cho database conganxa.
$host = 'localhost';
$dbname = 'conganxa';
$username = 'root';       // Thay bang tai khoan MySQL cua may chu neu can.
$password = '';           // Dien mat khau MySQL neu co.
try {
    $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Khong the ket noi co so du lieu. Vui long kiem tra ketnoi.php va database conganxa.');
}
