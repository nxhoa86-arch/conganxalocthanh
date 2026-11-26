
<?php
/**
 * KET NOI AIVEN MYSQL - WEBSITE CONG AN XA LOC THANH
 * Su dung Environment Variables tren Render.
 */

$host     = getenv('DB_HOST');
$dbname   = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$port     = getenv('DB_PORT');

// Kiem tra cau hinh
if (!$host || !$dbname || !$username || !$password || !$port) {
    error_log('Aiven: Thieu bien moi truong ket noi CSDL.');
    http_response_code(500);
    exit('Thieu cau hinh ket noi co so du lieu.');
}

// Duong dan chung chi SSL
$caFile = __DIR__ . '/ca.pem';

if (!file_exists($caFile)) {
    error_log('Aiven: Khong tim thay ca.pem tai ' . $caFile);
    http_response_code(500);
    exit('Khong tim thay chung chi SSL cua may chu.');
}

try {
    // Cau hinh PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 15,
        PDO::MYSQL_ATTR_SSL_CA => $caFile
    ];

    // Xac minh chung chi may chu neu PHP ho tro
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $conn = new PDO($dsn, $username, $password, $options);

} catch (PDOException $e) {
    error_log('Aiven MySQL connection error: ' . $e->getMessage());
    http_response_code(500);
    exit('Khong the ket noi co so du lieu. Vui long kiem tra cau hinh may chu.');
}
