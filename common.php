<?php
// Buffer output so redirects can still be sent if an included PHP file has stray whitespace/BOM.
if (!ob_get_level()) { ob_start(); }
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/ketnoi.php';

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Tuong thich voi cac trang con duoc tao tu Dreamweaver template cu.
if (!function_exists('template_h')) {
    function template_h($value): string {
        return h($value);
    }
}
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function verify_csrf(): void {
    $sent = $_POST['_csrf'] ?? '';
    if (!$sent || !hash_equals($_SESSION['_csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ (CSRF). Vui lòng tải lại trang và thử lại.');
    }
}
function current_role(): string {
    $role = strtolower(trim((string)($_SESSION['vaitro'] ?? '')));
    if ($role === '') {
        $legacy = strtolower(trim((string)($_SESSION['PhanQuyen'] ?? '')));
        $role = in_array($legacy, ['admin','administrator'], true) ? 'quantri' : $legacy;
    }
    return $role;
}
function is_admin(): bool {
    return in_array(current_role(), ['quantri','admin','administrator'], true);
}
function is_canbo(): bool {
    return current_role() === 'canbo';
}
function is_staff(): bool {
    return is_admin() || is_canbo();
}
function require_login(): void {
    if (!current_user_id()) {
        $_SESSION['redirect_after_login'] = basename($_SERVER['PHP_SELF'] ?? 'index.php');
        redirect_to('DangNhap.php');
    }
}
function require_staff(): void {
    if (!is_staff() || !current_user_id()) {
        http_response_code(403);
        exit('Bạn cần đăng nhập bằng tài khoản cán bộ hoặc quản trị để sử dụng chức năng này.');
    }
}
function require_admin(): void {
    if (!is_admin() || !current_user_id()) {
        http_response_code(403);
        exit('Chức năng này chỉ dành cho tài khoản quản trị viên.');
    }
}
function current_user_id(): ?int {
    $id = $_SESSION['nguoidung_id'] ?? $_SESSION['MaNguoiDung'] ?? null;
    return is_numeric($id) && (int)$id > 0 ? (int)$id : null;
}
function current_user_name(): string {
    return (string)($_SESSION['hoten'] ?? $_SESSION['UserName'] ?? $_SESSION['taikhoan'] ?? 'Cán bộ');
}
function redirect_to(string $url): void {
    header('Location: ' . $url);
    exit;
}
function page_value(string $key='page'): int {
    return max(1, (int)($_GET[$key] ?? 1));
}
function pager(int $total, int $perPage, int $page, string $baseUrl): string {
    $pages = max(1, (int)ceil($total / $perPage));
    if ($pages <= 1) return '';
    $html = '<nav class="pagination" aria-label="Phân trang">';
    for ($i=1; $i<=$pages; $i++) {
        $url = h($baseUrl . (strpos($baseUrl,'?') !== false ? '&' : '?') . 'page=' . $i);
        $html .= '<a ' . ($i === $page ? 'class="current"' : '') . ' href="' . $url . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}
function flash(string $message, string $type='success'): void {
    $_SESSION['_flash'] = ['message'=>$message, 'type'=>$type];
}
function show_flash(): void {
    if (!empty($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        echo '<div class="notice ' . h($f['type']) . '">' . h($f['message']) . '</div>';
    }
}
function admin_header(string $title): void {
    require_once __DIR__ . '/template.php';
    site_header($title, 'admin');
    echo '<h1 class="page-title">' . h($title) . '</h1>';
    show_flash();
}
function admin_footer(): void {
    require_once __DIR__ . '/template.php';
    site_footer();
}
function site_track_visit(PDO $db): array {
    $stats = ['total'=>null, 'online'=>null];
    try {
        $key = hash('sha256', session_id() . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $db->prepare("INSERT INTO site_online (session_key,first_seen,last_seen)
            VALUES (?,NOW(),NOW()) ON DUPLICATE KEY UPDATE last_seen=NOW()")->execute([$key]);
        $db->exec("DELETE FROM site_online WHERE last_seen < (NOW() - INTERVAL 5 MINUTE)");
        $db->exec("INSERT INTO site_stats (id,total_views) VALUES (1,1)
            ON DUPLICATE KEY UPDATE total_views=total_views+1");
        $stats['total'] = (int)$db->query("SELECT total_views FROM site_stats WHERE id=1")->fetchColumn();
        $stats['online'] = (int)$db->query("SELECT COUNT(*) FROM site_online
            WHERE last_seen >= (NOW() - INTERVAL 5 MINUTE)")->fetchColumn();
    } catch (Throwable $e) {
        error_log('Site statistics: ' . $e->getMessage());
    }
    return $stats;
}
function site_stats_html(array $stats): string {
    $total = $stats['total'] === null ? 'Chưa thiết lập' : number_format((int)$stats['total']);
    $online = $stats['online'] === null ? 'Chưa thiết lập' : number_format((int)$stats['online']);
    return '<div class="site-stats"><span><b>Đang truy cập:</b> ' . h($online)
        . '</span><span><b>Tổng lượt xem:</b> ' . h($total) . '</span></div>';
}
