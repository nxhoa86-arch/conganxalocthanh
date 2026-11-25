
<?php
// ============================================
// KET NOI MYSQL AIVEN - CONG AN XA LOC THANH
// ============================================

// Thong tin ket noi lay tu Environment Variables
$host     = getenv('DB_HOST') ?: '';
$port     = getenv('DB_PORT') ?: '23492';
$dbname   = getenv('DB_NAME') ?: 'defaultdb';
$username = getenv('DB_USER') ?: '';
$password = getenv('DB_PASSWORD') ?: '';

// Duong dan chung chi SSL Aiven
$caFile = __DIR__ . '/certs/ca.pem';

try {

    if (empty($host) || empty($username) || empty($password)) {
        throw new Exception('Missing database environment variables');
    }

    if (!is_readable($caFile)) {
        throw new Exception('Aiven SSL certificate not found');
    }

    $conn = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_CA => $caFile,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
        ]
    );

} catch (Throwable $e) {

    error_log('Aiven MySQL connection error: ' . $e->getMessage());

    http_response_code(500);
    exit('Khong the ket noi co so du lieu. Vui long lien he quan tri vien.');

}
