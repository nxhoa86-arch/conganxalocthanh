
<?php
// Ket noi Aiven MySQL cho website Cong an xa Loc Thanh

$host = getenv('AIVEN_DB_HOST');
$port = getenv('AIVEN_DB_PORT');
$dbname = getenv('AIVEN_DB_NAME');
$username = getenv('AIVEN_DB_USER');
$password = getenv('AIVEN_DB_PASSWORD');
$caPath = getenv('AIVEN_DB_CA');

try {
    if (!$host || !$port || !$dbname || !$username || !$password) {
        throw new RuntimeException('Thieu cau hinh Aiven');
    }

    if (!$caPath || !is_readable($caPath)) {
        throw new RuntimeException('Khong tim thay file CA certificate');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_CA => $caPath,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
    ]);

} catch (Throwable $e) {
    error_log('Aiven MySQL connection error: ' . $e->getMessage());
    throw new RuntimeException(
        'Khong the ket noi co so du lieu.',
        0,
        $e
    );
}
