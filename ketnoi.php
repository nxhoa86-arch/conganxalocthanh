<?php
// Ket noi Aiven MySQL bang bien moi truong tren Render.
// Khong ghi mat khau truc tiep vao ma nguon/GitHub.

$host = getenv('AIVEN_MYSQL_HOST') ?: '';
$port = (int)(getenv('AIVEN_MYSQL_PORT') ?: 23492);
$dbname = getenv('AIVEN_MYSQL_DATABASE') ?: 'defaultdb';
$username = getenv('AIVEN_MYSQL_USER') ?: 'avnadmin';
$password = getenv('AIVEN_MYSQL_PASSWORD') ?: '';
$caPath = __DIR__ . '/ca.pem';

try {
    if ($host === '' || $password === '') {
        throw new RuntimeException('Missing Aiven MySQL environment variables.');
    }

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('PHP extension pdo_mysql is not enabled.');
    }

    if (!is_file($caPath)) {
        throw new RuntimeException('Aiven CA certificate ca.pem was not found.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $dbname
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 15,
        PDO::MYSQL_ATTR_SSL_CA => $caPath,
    ];

    // Verify the server certificate when this PDO build supports the option.
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')] = true;
    }

    $conn = new PDO($dsn, $username, $password, $options);

} catch (Throwable $e) {
    error_log('Aiven MySQL connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra cấu hình máy chủ.');
}
