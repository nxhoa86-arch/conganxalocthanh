<?php
/* ============================================================
   TEMPLATE DUNG CHUNG - CONG THONG TIN DIEN TU CONG AN XA LOC THANH
   Dung lam mau Dreamweaver cho tat ca trang con.
   Trang con chi sua cac vung: doctitle, head, body va foot.
   Khong xoa cac vung TemplateBeginEditable/TemplateEndEditable.
   ============================================================ */
// Tim common.php dung ca khi file nam o thu muc goc Locthanh
// hoac nam trong thu muc con (vi du: Templates).
$commonCandidates = [
    __DIR__ . '/common.php',
    dirname(__DIR__) . '/common.php',
];
$commonPath = null;
foreach ($commonCandidates as $candidate) {
    if (is_file($candidate)) {
        $commonPath = $candidate;
        break;
    }
}
if ($commonPath === null) {
    error_log('Khong tim thay common.php. Da kiem tra: ' . implode(' | ', $commonCandidates));
    http_response_code(500);
    exit('Không tìm thấy common.php. Vui lòng kiểm tra tệp common.php trong thư mục Locthanh.');
}
require_once $commonPath;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cac trang con co the khai bao cac bien nay truoc khi dung template.
$page_title = $page_title ?? 'Cổng thông tin điện tử Công an xã Lộc Thành';
$activeMenu = $activeMenu ?? '';

// Lay danh muc dang hoat dong tu CSDL conganxa.
$menuDanhMuc = [];
try {
    if (isset($conn) && $conn instanceof PDO) {
        $stmtMenu = $conn->query(
            "SELECT madanhmuc, tendanhmuc
             FROM danhmuc
             WHERE trang_thai = 1
             ORDER BY madanhmuc ASC"
        );
        $menuDanhMuc = $stmtMenu->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('Loi truy van danh muc tren menu: ' . $e->getMessage());
    $menuDanhMuc = [];
}
$tickerText = $tickerText ?? 'Thông tin tuyên truyền và hoạt động của Công an xã Lộc Thành';

$days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
$todayText = $days[(int) date('w')] . ', ' . date('d/m/Y');

$role = function_exists('current_role')
    ? current_role()
    : strtolower(trim((string) ($_SESSION['vaitro'] ?? $_SESSION['PhanQuyen'] ?? '')));
$isAdmin = function_exists('is_admin')
    ? is_admin()
    : in_array($role, ['quantri', 'admin', 'administrator'], true);
$isCanBo = function_exists('is_canbo') ? is_canbo() : ($role === 'canbo');
$rawUserId = $_SESSION['nguoidung_id'] ?? $_SESSION['MaNguoiDung'] ?? null;
$rawUserName = $_SESSION['hoten'] ?? $_SESSION['UserName'] ?? $_SESSION['taikhoan'] ?? null;
$isLoggedIn = function_exists('current_user_id')
    ? (bool) current_user_id()
    : ((is_scalar($rawUserId) && trim((string) $rawUserId) !== '')
        || (is_scalar($rawUserName) && trim((string) $rawUserName) !== ''));
$displayName = trim((string) ($rawUserName ?? 'Người dùng'));

?>
<!DOCTYPE html>
<html lang="vi"><!-- InstanceBegin template="Templates/Templates.dwt.php" codeOutsideHTMLIsLocked="false" -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cổng thông tin điện tử Công an xã Lộc Thành, thành phố Đồng Nai.">
    <!-- InstanceBeginEditable name="doctitle" -->
    <title><?= h($page_title) ?></title>
    <!-- InstanceEndEditable -->

    <!-- CSS DUNG CHUNG: cac trang con dat cung cap voi thu muc goc Locthanh -->
    <link rel="stylesheet" href="style.css?v=20261123a">
    <link rel="stylesheet" href="template.css?v=20261123a">

    <style id="template-shared-layout">
      * { box-sizing: border-box; }
      html { scroll-behavior: smooth; }
      body { margin: 0; min-width: 320px; }
      .site-header { width: 100%; position: relative; z-index: 100; }
      .masthead { width: 100%; overflow: hidden; }
      .masthead-inner { display:flex; align-items:center; justify-content:space-between; gap:24px; min-height:124px; }
      .identity { display:flex; align-items:center; gap:18px; flex:1 1 58%; min-width:0; text-decoration:none; }
      .identity img { width:96px; height:96px; object-fit:contain; flex:0 0 96px; }
      .identity-text { display:flex; flex-direction:column; min-width:0; line-height:1.2; }
      .identity-text .agency { display:block; font-size:15px; font-weight:700; white-space:normal; }
      .identity-text strong { display:block; font-size:clamp(22px,2.2vw,34px); line-height:1.15; white-space:normal; }
      .identity-text small { display:block; margin-top:5px; font-size:13px; line-height:1.35; white-space:normal; }
      .masthead-tools { flex:0 1 340px; min-width:260px; display:flex; flex-direction:column; align-items:stretch; gap:9px; }
      .utility { display:flex; justify-content:flex-end; align-items:center; gap:10px; white-space:nowrap; font-size:13px; }
      .search { display:flex; width:100%; min-width:0; }
      .search input { flex:1 1 auto; min-width:0; }
      .search button { flex:0 0 48px; }
      .account-links { display:flex; justify-content:flex-end; align-items:center; flex-wrap:wrap; gap:6px; font-size:13px; }
      .main-nav { width:100%; }
      .nav-inner { display:flex; align-items:stretch; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; white-space:nowrap; scrollbar-width:thin; }
      .nav-inner > a, .nav-inner > span { flex:0 0 auto; }
      .ticker-inner { display:flex; align-items:center; gap:12px; min-height:38px; overflow:hidden; white-space:nowrap; }
      .ticker-message { overflow:hidden; text-overflow:ellipsis; }
      .page-main { min-height:300px; padding-top:24px; padding-bottom:24px; }
      .site-footer { width:100%; margin-top:30px; }
      .footer-inner { display:grid; grid-template-columns:1.25fr 1.1fr .8fr 1fr; gap:28px; }
      .footer-inner > div { min-width:0; }
      .nav-admin-label { display:flex; align-items:center; padding:0 12px; color:#ffe18b; font-weight:700; }
      @media (max-width:900px) {
        .masthead-inner { align-items:flex-start; padding-top:12px; padding-bottom:12px; flex-direction:column; gap:12px; }
        .identity { width:100%; }
        .masthead-tools { width:100%; min-width:0; }
        .utility, .account-links { justify-content:flex-start; }
        .footer-inner { grid-template-columns:repeat(2,minmax(0,1fr)); }
      }
      @media (max-width:560px) {
        .identity img { width:68px; height:68px; flex-basis:68px; }
        .identity-text .agency { font-size:12px; }
        .identity-text strong { font-size:20px; }
        .identity-text small { font-size:10px; }
        .footer-inner { grid-template-columns:1fr; }
      }
    </style>

    <!-- InstanceBeginEditable name="head" -->
    <!-- Trang con them CSS rieng tai day. Khong sua stylesheet dung chung. -->
    <!-- InstanceEndEditable -->
</head>
<body>
<header class="site-header" id="top">
    <div class="masthead">
        <div class="container masthead-inner">
            <a class="identity" href="index.php" aria-label="Trang chủ Công an xã Lộc Thành">
                <img src="assets/quoc-huy.png" alt="Quốc huy">
                <span class="identity-text">
                    <span class="agency">CÔNG AN THÀNH PHỐ ĐỒNG NAI</span>
                    <strong>CÔNG AN XÃ LỘC THÀNH</strong>
                    <small>VÌ AN NINH TỔ QUỐC - VÌ HẠNH PHÚC NHÂN DÂN</small>
                </span>
            </a>

            <div class="masthead-tools">
                <div class="utility">
                    <a href="index.php#sitemap">♟ Sơ đồ site</a><i aria-hidden="true"></i>
                    <a href="index.php#lien-he">✉ Liên hệ</a><i aria-hidden="true"></i>
                    <a href="PhanAnh.php">☑ Góp ý</a>
                </div>
                <form class="search" action="index.php" method="get" role="search">
                    <input type="search" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Nhập từ khóa tìm kiếm..." aria-label="Nhập từ khóa tìm kiếm">
                    <button type="submit" aria-label="Tìm kiếm">⌕</button>
                </form>
                <div class="account-links">
                    <?php if ($isLoggedIn): ?>
                        <span class="hello">Xin chào, <?= h($displayName) ?></span><a class="account-action" href="doimatkhau.php">Đổi mật khẩu</a>
                        <span class="account-separator">|</span><a class="login-link" href="<?= htmlspecialchars($basePrefix . 'DangXuat.php', ENT_QUOTES, 'UTF-8') ?>">ĐĂNG XUẤT</a>
                    <?php else: ?>
                        <a class="account-action" href="DangKy.php">Đăng ký</a>
                        <span class="account-separator">|</span>
                        <a class="account-action" href="DangNhap.php">Đăng nhập</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <nav class="main-nav" aria-label="Điều hướng chính">
        <div class="container nav-inner">
            <a class="home <?= $activeMenu === 'home' ? 'active' : '' ?>" href="index.php" aria-label="Trang chủ" title="Trang chủ"><img src="uploads/home-page_3711265.png" width="50" height="50" alt="Trang chủ" title = "Trang chủ"/></a>
            <?php foreach ($menuDanhMuc as $dm): ?>
                <?php
                    $menuId = (int)($dm['madanhmuc'] ?? 0);
                    $menuName = (string)($dm['tendanhmuc'] ?? '');
                    $isCategoryActive = isset($_GET['danhmuc']) && (int)$_GET['danhmuc'] === $menuId;
                ?>
                <a class="<?= $isCategoryActive ? 'active' : '' ?>"
                   href="chuyenmuc.php?danhmuc=<?= $menuId ?>">
                    <?= h($menuName) ?>
                </a>
            <?php endforeach; ?>
            <a href="PhanAnh.php">GỬI PHẢN ÁNH</a>
            <a href="tracuu_phananh.php">TRA CỨU</a>
            <?php if ($isAdmin): ?>
                <span class="nav-admin-label">QUẢN TRỊ</span>
                <a href="QuanLyNguoiDung.php">NGƯỜI DÙNG</a>
                <a href="quanlydanhmuc.php">DANH MỤC</a>
				<a href="quanlychuyenmuc.php">CHUYÊN MỤC</a>
                <a href="quanlytintuc.php">QUẢN LÝ TIN</a>
                <a href="phananh_kiennghi.php">XỬ LÝ PHẢN ÁNH</a>
                <a href="baocao.php">BÁO CÁO</a>
            <?php elseif ($isCanBo): ?>
                <span class="nav-admin-label">KHU VỰC CÁN BỘ</span>
                <a href="baocao.php">BÁO CÁO CÔNG TÁC</a>
                <a href="phananh_kiennghi.php">XỬ LÝ PHẢN ÁNH</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="ticker">
        <div class="container ticker-inner">
            <span class="date">▦ <?= h($todayText) ?></span>
            <b class="star">★</b>
            <strong>THÔNG TIN:</strong>
            <span class="ticker-message"><?= h($tickerText) ?></span>
        </div>
    </div>
</header>

<main class="container page-main">
    <!-- InstanceBeginEditable name="body" -->
    <!-- MOI TRANG CON DAT NOI DUNG RIENG TAI DAY -->
	<?php
if (!function_exists('report_redirect')) {
    function report_redirect(string $url): void {
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }
        $safeUrl = json_encode($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo '<script>window.location.replace(' . $safeUrl . ');</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        exit;
    }
}
require_staff();

$reportTables = [
    'CSTT' => 'bc_cstt',
    'CSKV' => 'bc_cskv',
    'PCTP' => 'bc_pctp',
];
$reportNames = [
    'CSTT' => 'Cảnh sát trật tự',
    'CSKV' => 'Cảnh sát khu vực',
    'PCTP' => 'Phòng chống tội phạm',
];
$type = strtoupper(trim((string)($_GET['loai'] ?? $_POST['loai_bao_cao'] ?? 'CSTT')));
if (!isset($reportTables[$type])) $type = 'CSTT';
$detailTable = $reportTables[$type];

function report_columns(PDO $db, string $table): array {
    $allowed = ['bc_cstt','bc_cskv','bc_pctp'];
    if (!in_array($table, $allowed, true)) return [];
    return $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
}
function report_label(string $field): string {
    $labels = [
        'tinh_hinh_antt' => 'Tình hình an ninh, trật tự',
        'tinh_hinh_chay_no' => 'Tình hình cháy, nổ',
        'tinh_hinh_khac' => 'Tình hình khác / nội dung bổ sung',
        'tuyen_truyen_noi_dung' => 'Nội dung tuyên truyền',
        'dang_ky_xe_ty_le_dien_tu' => 'Tỷ lệ đăng ký xe điện tử (%)',
        'xlvphc_tien_phat' => 'Tổng tiền xử phạt vi phạm hành chính (đồng)',

        // Tuần tra, kiểm soát an ninh trật tự.
        'tuan_tra_ancs_ca' => 'Tuần tra an ninh, trật tự (ca)',
        'tuan_tra_ancs_gio' => 'Tuần tra an ninh, trật tự (giờ)',
        'tuan_tra_ancs_cbcs' => 'Tuần tra an ninh, trật tự (cán bộ, chiến sĩ)',
        'tuan_tra_ancs_vi_pham' => 'Vi phạm phát hiện qua tuần tra an ninh, trật tự',
        'tuan_tra_ttagt_ca' => 'Tuần tra trật tự, an toàn giao thông (ca)',
        'tuan_tra_ttagt_gio' => 'Tuần tra trật tự, an toàn giao thông (giờ)',
        'tuan_tra_ttagt_cbcs' => 'Tuần tra trật tự, an toàn giao thông (cán bộ, chiến sĩ)',
        'ttagt_so_vu_vi_pham' => 'Số vụ vi phạm trật tự, an toàn giao thông',
        'ttagt_so_mo_to' => 'Số mô tô vi phạm',
        'ttagt_khong_mu_bh' => 'Số trường hợp không đội mũ bảo hiểm',
        'ttagt_tam_giu_cn_dk' => 'Số trường hợp tạm giữ phương tiện, giấy tờ đăng ký',
        'ttagt_xlyphc_so_ho_so' => 'Số hồ sơ xử lý vi phạm hành chính',
        'ttagt_tien_phat' => 'Tổng số tiền xử phạt (đồng)',

        // Tuyên truyền, phương tiện và xử lý vi phạm.
        'tuyen_truyen_so_buoi' => 'Số buổi tuyên truyền',
        'tuyen_truyen_so_phut' => 'Thời lượng tuyên truyền (phút)',
        'xe_may_cap_moi' => 'Số xe máy cấp mới',
        'xe_may_sang_ten' => 'Số xe máy đăng ký sang tên',
        'tinh_hinh_chay_no' => 'Tình hình cháy, nổ',
        'noi_dung_khac' => 'Nội dung khác',
        'nguoi_lap' => 'Người lập báo cáo',
        'ngay_bao_cao' => 'Ngày báo cáo',
        'tu_ngay' => 'Từ ngày',
        'den_ngay' => 'Đến ngày',
        'ma_bao_cao' => 'Mã báo cáo',
        'loai_bao_cao' => 'Loại báo cáo',
        'trang_thai' => 'Trạng thái',
    ];
    if (isset($labels[$field])) return $labels[$field];

    // Dich tu dong cac ten cot CSKV/PCTP chua duoc khai bao rieng.
    // Cach nay giup khong con hien thi ten cot dang tieng Anh/khong dau.
    $tuDien = [
        'cskv'=>'Cảnh sát khu vực', 'pctp'=>'Phòng chống tội phạm', 'cstt'=>'Cảnh sát trật tự',
        'so'=>'Số', 'tong'=>'Tổng', 'ca'=>'ca', 'gio'=>'giờ', 'ngay'=>'ngày',
        'thang'=>'tháng', 'nam'=>'năm', 'buoi'=>'buổi', 'lan'=>'lần',
        'vu'=>'vụ', 'viec'=>'việc', 'doi'=>'đối', 'tuong'=>'tượng',
        'ho'=>'hộ', 'khau'=>'khẩu', 'nhan'=>'nhân', 'tam'=>'tạm',
        'tru'=>'trú', 'vang'=>'vắng', 'cu'=>'cư', 'dia'=>'địa',
        'ban'=>'bàn', 'khu'=>'khu', 'vuc'=>'vực', 'ap'=>'ấp',
        'to'=>'tổ', 'dan'=>'dân', 'pho'=>'phố', 'kiem'=>'kiểm',
        'tra'=>'tra', 'tuan'=>'tuần', 'truyen'=>'truyền', 'tuyen'=>'tuyên',
        'noi'=>'nội', 'dung'=>'dung', 'cap'=>'cấp', 'nhat'=>'nhật',
        'nhap'=>'nhập', 'lieu'=>'liệu', 'thu'=>'thu', 'thap'=>'thập',
        'quan'=>'quản', 'ly'=>'lý', 'giao'=>'giáo', 'duc'=>'dục',
        'xu'=>'xử', 'lyvphc'=>'lý vi phạm hành chính', 'vphc'=>'vi phạm hành chính',
        'vi'=>'vi', 'pham'=>'phạm', 'hanh'=>'hành', 'chinh'=>'chính',
        'an'=>'an', 'ninh'=>'ninh', 'trat'=>'trật', 'tu'=>'tự',
        'toi'=>'tội', 'phạm'=>'phạm', 'toi_pham'=>'tội phạm',
        'ma'=>'ma', 'tuy'=>'túy', 'hinh'=>'hình', 'su'=>'sự',
        'kinh'=>'kinh', 'te'=>'tế', 'moi'=>'môi', 'truong'=>'trường',
        'tin'=>'tin', 'bao'=>'báo', 'to_giac'=>'tố giác', 'to_giac_toi_pham'=>'tố giác tội phạm',
        'bat'=>'bắt', 'giu'=>'giữ', 'truy'=>'truy', 'na'=>'nã',
        'dieu'=>'điều', 'dieu_tra'=>'điều tra', 'khoi'=>'khởi', 'to'=>'tố',
        'ho_so'=>'hồ sơ', 'chuyen'=>'chuyển', 'hoa'=>'hòa', 'giai'=>'giải',
        'tranh'=>'tranh', 'chap'=>'chấp', 'cam'=>'cam', 'ket'=>'kết',
        'xac'=>'xác', 'thuc'=>'thực', 'can'=>'căn', 'cuoc'=>'cước',
        'dinh'=>'định', 'danh'=>'danh', 'dien'=>'điện', 'tu'=>'tử',
        'vneid'=>'VNeID', 'cbcs'=>'cán bộ, chiến sĩ', 'ty_le'=>'tỷ lệ',
        'phan'=>'phản', 'anh'=>'ánh', 'kien'=>'kiến', 'nghi'=>'nghị',
        'phat'=>'phát', 'hien'=>'hiện', 'xay'=>'xảy', 'ra'=>'ra',
        'tai'=>'tai', 'nan'=>'nạn', 'giao_thong'=>'giao thông',
        'phuong'=>'phương', 'tien'=>'tiện', 'xe'=>'xe', 'may'=>'máy',
        'mo_to'=>'mô tô', 'dang'=>'đăng', 'ky'=>'ký', 'moi'=>'mới',
        'cu'=>'cũ', 'di'=>'di', 'bien'=>'biến', 'dong'=>'động',
        'thong'=>'thông', 'ke'=>'kê', 'khai'=>'khai', 'sinh'=>'sinh',
        'tu'=>'tự', 'nguyen'=>'nguyện', 'tham'=>'tham', 'gia'=>'gia',
        'to_chuc'=>'tổ chức', 'ca_nhan'=>'cá nhân', 'dia_chi'=>'địa chỉ',
        'khac'=>'khác', 'bo_sung'=>'bổ sung', 'tinh_hinh'=>'tình hình',
        'ket_qua'=>'kết quả', 'thuc_hien'=>'thực hiện', 'so_vu'=>'số vụ',
        'so_luong'=>'số lượng', 'tong_so'=>'tổng số', 'tien'=>'tiền',
        'phat'=>'phạt', 'dong'=>'đồng', 'ty_le'=>'tỷ lệ'
    ];

    $parts = explode('_', strtolower($field));
    $translated = [];
    foreach ($parts as $part) {
        if ($part === '') continue;
        $translated[] = $tuDien[$part] ?? $part;
    }
    $text = implode(' ', $translated);
    return mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8')
        . mb_substr($text, 1, null, 'UTF-8');
}
function report_status_label(string $status): string {
    $labels = [
        'nhap' => 'Bản nháp',
        'da_gui' => 'Đã gửi / chờ duyệt',
        'da_duyet' => 'Đã duyệt'
    ];
    return $labels[$status] ?? 'Không xác định';
}

// Dinh dang gia tri tong hop theo y nghia cua ten cot.
function report_format_metric(string $field, $value): string {
    if ($value === null || $value === '') return '';

    $field = strtolower($field);
    $number = (float)$value;

    // Cac chi tieu tien te: hien thi so nguyen va don vi Viet Nam dong.
    if (preg_match('/(^|_)(tien|kinh_phi|chi_phi|muc_phat|le_phi)(_|$)/', $field)
        || preg_match('/(^|_)tien_/', $field)) {
        return number_format((int)round($number), 0, ',', '.') . ' VNĐ';
    }

    // Cot gio luu dang so thap phan: doi phan le cua gio sang phut.
    if (preg_match('/(^|_)gio($|_)/', $field) || preg_match('/_gio_/', $field)) {
        $totalMinutes = (int)round($number * 60);
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        if ($hours > 0 && $minutes > 0) return $hours . ' giờ ' . $minutes . ' phút';
        if ($hours > 0) return $hours . ' giờ';
        return $minutes . ' phút';
    }

    // Chi tieu thoi luong da tinh theo phut.
    if (preg_match('/(^|_)phut($|_)/', $field)) {
        return number_format((int)round($number), 0, ',', '.') . ' phút';
    }

    // Cac chi tieu con lai chi hien thi so nguyen.
    return number_format((int)round($number), 0, ',', '.');
}

function report_numeric_kind(string $field, string $columnType): string {
    $field = strtolower($field);
    if (preg_match('/(^|_)(tien|kinh_phi|chi_phi|muc_phat|le_phi)(_|$)/', $field) || strpos($field, 'tien_') !== false) return 'currency';
    if (preg_match('/(^|_)gio($|_)/', $field) || strpos($field, '_gio_') !== false) return 'hours';
    if (preg_match('/(^|_)phut($|_)/', $field)) return 'minutes';
    if (strpos(strtolower($columnType), 'decimal') !== false || strpos(strtolower($columnType), 'float') !== false || strpos(strtolower($columnType), 'double') !== false) return 'decimal';
    return 'integer';
}
function report_normalize_numeric_input($value, string $field, string $columnType): string {
    $raw = trim((string)$value);
    if ($raw === '') return '0';
    $raw = str_ireplace(['VNĐ', 'VND', 'đồng', 'giờ', 'phút', ' '], '', $raw);
    $raw = preg_replace('/[^0-9,\\.\\-]/u', '', $raw);
    // Định dạng Việt Nam: dấu chấm phân cách hàng nghìn, dấu phẩy là thập phân.
    if (strpos($raw, ',') !== false) {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    }
    if (!is_numeric($raw) || (float)$raw < 0) {
        throw new RuntimeException('Số liệu không hợp lệ tại mục: ' . report_label($field));
    }
    $kind = report_numeric_kind($field, $columnType);
    if (in_array($kind, ['integer','currency','minutes'], true)) {
        $raw = str_replace('.', '', $raw);
    }
    if (in_array($kind, ['integer','currency','minutes'], true) && floor((float)$raw) != (float)$raw) {
        throw new RuntimeException('Mục ' . report_label($field) . ' phải nhập số nguyên.');
    }
    return $raw;
}

function report_input(PDO $db, string $table, array $values=[]): void {
    foreach (report_columns($db,$table) as $col) {
        $field=$col['Field'];
        if (in_array($field,['id','baocao_id','created_at','updated_at'],true)) continue;
        $type=strtolower((string)$col['Type']);
        $value=$values[$field] ?? ($col['Default'] ?? '');
        echo '<div class="field">';
        echo '<label for="r_' . h($field) . '">' . h(report_label($field)) . '</label>';
        if (strpos($type,'text')!==false) {
            echo '<textarea id="r_' . h($field) . '" name="detail[' . h($field) . ']" rows="3">' . h($value) . '</textarea>';
        } else {
            $kind = report_numeric_kind($field, $type);
            $step = in_array($kind, ['hours','decimal'], true) ? '0.01' : '1';
            $suffix = ['currency'=>' VNĐ','hours'=>' giờ','minutes'=>' phút','integer'=>'','decimal'=>''][ $kind ];
            $display = (string)$value;
            if ($display !== '' && is_numeric($display)) {
                if ($kind === 'currency' || $kind === 'integer' || $kind === 'minutes') $display = number_format((float)$display, 0, ',', '.');
                elseif ($kind === 'hours' || $kind === 'decimal') $display = str_replace('.', ',', rtrim(rtrim(number_format((float)$display, 2, '.', ''), '0'), '.'));
            }
            echo '<input class="report-number" data-kind="' . h($kind) . '" data-suffix="' . h($suffix) . '" id="r_' . h($field) . '" type="text" inputmode="decimal" autocomplete="off" name="detail[' . h($field) . ']" value="' . h($display . $suffix) . '" data-step="' . $step . '">';
        }
        echo '</div>';
    }
}
function report_insert_detail(PDO $db, string $table, int $reportId, array $input): void {
    $allowed=['bc_cstt','bc_cskv','bc_pctp'];
    if (!in_array($table,$allowed,true)) throw new RuntimeException('Loại báo cáo không hợp lệ.');
    $cols=report_columns($db,$table);
    $data=['baocao_id'=>$reportId];
    foreach ($cols as $col) {
        $field=$col['Field'];
        if (in_array($field,['id','baocao_id','created_at','updated_at'],true)) continue;
        if (!array_key_exists($field,$input)) continue;
        $type=strtolower((string)$col['Type']);
        if (strpos($type,'text')!==false) {
            $data[$field]=trim((string)$input[$field]);
        } else {
            $raw = report_normalize_numeric_input($input[$field], $field, $type);
            $data[$field] = $raw;
        }
    }
    $fields=array_keys($data);
    $sql='INSERT INTO `' . $table . '` (`' . implode('`,`',$fields) . '`) VALUES (' . implode(',',array_fill(0,count($fields),'?')) . ')';
    $db->prepare($sql)->execute(array_values($data));
}


function report_update_detail(PDO $db, string $table, int $reportId, array $input): void {
    $allowed = ['bc_cstt','bc_cskv','bc_pctp'];
    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Loại báo cáo không hợp lệ.');
    }

    $cols = report_columns($db, $table);
    $data = [];
    foreach ($cols as $col) {
        $field = $col['Field'];
        if (in_array($field, ['id','baocao_id','created_at','updated_at'], true)) continue;
        if (!array_key_exists($field, $input)) continue;

        $type = strtolower((string)$col['Type']);
        if (strpos($type, 'text') !== false) {
            $data[$field] = trim((string)$input[$field]);
        } else {
            $raw = report_normalize_numeric_input($input[$field], $field, $type);
            $data[$field] = $raw;
        }
    }

    $check = $db->prepare("SELECT id FROM `$table` WHERE baocao_id=? LIMIT 1");
    $check->execute([$reportId]);
    $detailId = $check->fetchColumn();

    if ($detailId) {
        if ($data) {
            $sets = [];
            foreach (array_keys($data) as $field) $sets[] = "`$field`=?";
            $sql = "UPDATE `$table` SET " . implode(',', $sets) . " WHERE baocao_id=?";
            $db->prepare($sql)->execute(array_merge(array_values($data), [$reportId]));
        }
    } else {
        report_insert_detail($db, $table, $reportId, $input);
    }
}

$error='';
$success='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_report'])) {
    verify_csrf();
    $type=strtoupper(trim((string)($_POST['loai_bao_cao']??'')));
    if (!isset($reportTables[$type])) {
        $error='Vui lòng chọn loại báo cáo hợp lệ.';
    } else {
        $reportDate=$_POST['ngay_bao_cao']??date('Y-m-d');
        $fromDate=$_POST['tu_ngay']??'';
        $toDate=$_POST['den_ngay']??'';
        $periodName=substr(preg_replace('/[^0-9]/','',date('YmdHis')),0,14);
        $code='BC-'.$type.'-'.date('YmdHis').'-'.strtoupper(bin2hex(random_bytes(2)));
        try {
            $conn->beginTransaction();
            $insert=$conn->prepare("INSERT INTO bc_baocao
                (ma_bao_cao,loai_bao_cao,ngay_bao_cao,tu_ngay,den_ngay,don_vi,nguoi_lap,chuc_vu_nguoi_lap,noi_dung_khac,trang_thai)
                VALUES (?,?,?,?,?,?,?,?,?,'da_gui')");
            $insert->execute([
                $code,$type,$reportDate,$fromDate?:null,$toDate?:null,
                'Công an xã Lộc Thành',current_user_name(),
                is_canbo()?'Cán bộ':'Quản trị viên',
                trim((string)($_POST['noi_dung_khac']??'')) ?: null
            ]);
            $reportId=(int)$conn->lastInsertId();
            report_insert_detail($conn,$reportTables[$type],$reportId,$_POST['detail']??[]);
            $conn->commit();
            flash('Đã gửi báo cáo ' . $code . ' thành công. Báo cáo đang chờ quản trị duyệt.');
            report_redirect('baocao.php');
        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log('Save report: '.$e->getMessage());
            $error='Không lưu được báo cáo. Kiểm tra đã import đầy đủ các bảng báo cáo trong CSDL và thử lại.';
        }
    }
}

if (isset($_POST['approve_report']) && is_admin()) {
    verify_csrf();
    $rid=(int)($_POST['report_id']??0);
    $st=$conn->prepare("UPDATE bc_baocao SET trang_thai='da_duyet',nguoi_duyet=?,ngay_duyet=NOW() WHERE id=?");
    $st->execute([current_user_name(),$rid]);
    flash('Đã duyệt báo cáo.');
    report_redirect('baocao.php');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report'])) {
    verify_csrf();
    $rid = (int)($_POST['report_id'] ?? 0);
    $q = $conn->prepare("SELECT * FROM bc_baocao WHERE id=?");
    $q->execute([$rid]);
    $existing = $q->fetch(PDO::FETCH_ASSOC);

    if (!$existing || (!is_admin() && $existing['nguoi_lap'] !== current_user_name()) || (!is_admin() && $existing['trang_thai'] === 'da_duyet')) {
        flash('Bạn không có quyền sửa báo cáo này hoặc báo cáo đã được duyệt.');
        report_redirect('baocao.php');
    }

    try {
        $table = $reportTables[$existing['loai_bao_cao']] ?? '';
        if ($table === '') throw new RuntimeException('Loại báo cáo không hợp lệ.');
        $date = (string)($_POST['ngay_bao_cao'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new RuntimeException('Ngày báo cáo không hợp lệ.');
        $from = (string)($_POST['tu_ngay'] ?? '');
        $to = (string)($_POST['den_ngay'] ?? '');
        if ($from !== '' && $to !== '' && $from > $to) throw new RuntimeException('Từ ngày không được lớn hơn đến ngày.');

        $conn->beginTransaction();
        $upd = $conn->prepare("UPDATE bc_baocao SET ngay_bao_cao=?, tu_ngay=?, den_ngay=?, noi_dung_khac=? WHERE id=?");
        $upd->execute([$date, $from !== '' ? $from : null, $to !== '' ? $to : null,
            trim((string)($_POST['noi_dung_khac'] ?? '')) ?: null, $rid]);
        report_update_detail($conn, $table, $rid, $_POST['detail'] ?? []);
        $conn->commit();
        flash('Đã cập nhật báo cáo thành công.');
        report_redirect('baocao.php?xem=' . $rid);
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Update report: ' . $e->getMessage());
        flash('Không cập nhật được báo cáo. Vui lòng kiểm tra dữ liệu và cấu trúc CSDL.');
        report_redirect('baocao.php?sua=' . $rid);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    verify_csrf();
    $rid = (int)($_POST['report_id'] ?? 0);
    $q = $conn->prepare("SELECT * FROM bc_baocao WHERE id=?");
    $q->execute([$rid]);
    $existing = $q->fetch(PDO::FETCH_ASSOC);

    if (!$existing || (!is_admin() && $existing['nguoi_lap'] !== current_user_name()) || (!is_admin() && $existing['trang_thai'] === 'da_duyet')) {
        flash('Bạn không có quyền xóa báo cáo này.');
        report_redirect('baocao.php');
    }

    try {
        $table = $reportTables[$existing['loai_bao_cao']] ?? '';
        $conn->beginTransaction();
        if ($table !== '') {
            $conn->prepare("DELETE FROM `$table` WHERE baocao_id=?")->execute([$rid]);
        }
        $conn->prepare("DELETE FROM bc_baocao WHERE id=?")->execute([$rid]);
        $conn->commit();
        flash('Đã xóa báo cáo.');
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Delete report: ' . $e->getMessage());
        flash('Không xóa được báo cáo. Có thể báo cáo đang được dữ liệu khác tham chiếu.');
    }
    report_redirect('baocao.php');
}

$view=null;
$viewDetail=null;
$editView = null;
$editDetail = null;
if (isset($_GET['sua'])) {
    $qe = $conn->prepare('SELECT * FROM bc_baocao WHERE id=?');
    $qe->execute([(int)$_GET['sua']]);
    $editView = $qe->fetch(PDO::FETCH_ASSOC);
    if ($editView && ((!is_admin() && $editView['nguoi_lap'] !== current_user_name()) || $editView['trang_thai'] === 'da_duyet')) $editView = null;
    if ($editView) {
        $editTable = $reportTables[$editView['loai_bao_cao']] ?? '';
        if ($editTable !== '') {
            $ed = $conn->prepare("SELECT * FROM `$editTable` WHERE baocao_id=?");
            $ed->execute([(int)$editView['id']]);
            $editDetail = $ed->fetch(PDO::FETCH_ASSOC) ?: [];
        }
    }
}

if (isset($_GET['xem'])) {
    $q=$conn->prepare('SELECT * FROM bc_baocao WHERE id=?');
    $q->execute([(int)$_GET['xem']]);
    $view=$q->fetch(PDO::FETCH_ASSOC);
    if ($view && !is_admin() && $view['nguoi_lap']!==current_user_name()) $view=null;
    if ($view) {
        $table=$reportTables[$view['loai_bao_cao']]??'';
        if ($table!=='') {
            $d=$conn->prepare("SELECT * FROM `$table` WHERE baocao_id=?");
            $d->execute([(int)$view['id']]);
            $viewDetail=$d->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// ---------------- BO LOC VA TONG HOP SO LIEU THEO NGAY ----------------
$filterFrom = trim((string)($_GET['tu_ngay_loc'] ?? ''));
$filterTo = trim((string)($_GET['den_ngay_loc'] ?? ''));
$filterType = strtoupper(trim((string)($_GET['loc_loai'] ?? '')));
$filterStatus = trim((string)($_GET['loc_trangthai'] ?? ''));
$filterOfficer = is_admin() ? trim((string)($_GET['loc_canbo'] ?? '')) : '';

$dateValid = static function(string $value): bool {
    if ($value === '') return true;
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
};
if (!$dateValid($filterFrom) || !$dateValid($filterTo)) {
    $error = 'Ngày lọc không hợp lệ. Vui lòng chọn ngày theo định dạng hợp lệ.';
    $filterFrom = $filterTo = '';
}
if ($filterFrom !== '' && $filterTo !== '' && $filterFrom > $filterTo) {
    $error = 'Từ ngày không được lớn hơn đến ngày.';
}
if (!isset($reportTables[$filterType])) $filterType = '';
if (!in_array($filterStatus, ['nhap','da_gui','da_duyet'], true)) $filterStatus = '';
$officerOptions = [];
if (is_admin()) {
    $officerQuery = $conn->query("SELECT DISTINCT nguoi_lap FROM bc_baocao WHERE nguoi_lap IS NOT NULL AND nguoi_lap <> '' ORDER BY nguoi_lap");
    $officerOptions = $officerQuery->fetchAll(PDO::FETCH_COLUMN);
    if ($filterOfficer !== '' && !in_array($filterOfficer, $officerOptions, true)) $filterOfficer = '';
}

$whereParts = [];
$params = [];
if (!is_admin()) {
    $whereParts[] = 'b.nguoi_lap = ?';
    $params[] = current_user_name();
}
if ($filterFrom !== '') {
    $whereParts[] = 'b.ngay_bao_cao >= ?';
    $params[] = $filterFrom;
}
if ($filterTo !== '') {
    $whereParts[] = 'b.ngay_bao_cao <= ?';
    $params[] = $filterTo;
}
if ($filterType !== '') {
    $whereParts[] = 'b.loai_bao_cao = ?';
    $params[] = $filterType;
}
if ($filterStatus !== '') {
    $whereParts[] = 'b.trang_thai = ?';
    $params[] = $filterStatus;
}
if (is_admin() && $filterOfficer !== '') {
    $whereParts[] = 'b.nguoi_lap = ?';
    $params[] = $filterOfficer;
}
$where = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

$count = $conn->prepare('SELECT COUNT(*) FROM bc_baocao b' . $where);
$count->execute($params);
$total = (int)$count->fetchColumn();

// Tổng hợp số lượng báo cáo theo loại và trạng thái trong khoảng ngày đang lọc.
$summarySql = 'SELECT b.loai_bao_cao, COUNT(*) AS so_bao_cao,
    SUM(CASE WHEN b.trang_thai = \'nhap\' THEN 1 ELSE 0 END) AS so_nhap,
    SUM(CASE WHEN b.trang_thai = \'da_gui\' THEN 1 ELSE 0 END) AS so_da_gui,
    SUM(CASE WHEN b.trang_thai = \'da_duyet\' THEN 1 ELSE 0 END) AS so_da_duyet
    FROM bc_baocao b' . $where . ' GROUP BY b.loai_bao_cao';
$summaryStmt = $conn->prepare($summarySql);
$summaryStmt->execute($params);
$summaryRows = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

$metricRows = [];
$metricTotals = [];
foreach ($reportTables as $code => $table) {
    if ($filterType !== '' && $filterType !== $code) continue;
    $numericColumns = [];
    foreach (report_columns($conn, $table) as $column) {
        $field = $column['Field'];
        $columnType = strtolower((string)$column['Type']);
        if (in_array($field, ['id','baocao_id','created_at','updated_at'], true)) continue;
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double)/', $columnType)) {
            $numericColumns[] = $field;
        }
    }
    if (!$numericColumns) continue;

    $detailWhere = $whereParts;
    $detailParams = $params;
    $detailWhere[] = 'b.loai_bao_cao = ?';
    $detailParams[] = $code;
    $detailSqlWhere = ' WHERE ' . implode(' AND ', $detailWhere);

    $selectParts = [];
    foreach ($numericColumns as $field) {
        // Chi tieu ty le duoc tinh trung binh, cac chi tieu con lai duoc cong don.
        $aggregate = (strpos($field, 'ty_le') !== false) ? 'AVG' : 'SUM';
        $selectParts[] = $aggregate . '(d.`' . str_replace('`','', $field) . '`) AS `' . $field . '`';
    }
    $sql = 'SELECT ' . implode(',', $selectParts) . ' FROM bc_baocao b
            INNER JOIN `' . $table . '` d ON d.baocao_id = b.id' . $detailSqlWhere;
    $st = $conn->prepare($sql);
    $st->execute($detailParams);
    $aggregates = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    foreach ($numericColumns as $field) {
        $value = $aggregates[$field] ?? null;
        if ($value === null || (float)$value == 0.0) continue;
        $method = strpos($field, 'ty_le') !== false ? 'Trung bình' : 'Tổng cộng';
        $metricRows[] = [
            'type' => $code,
            'type_name' => $reportNames[$code],
            'field' => $field,
            'label' => report_label($field),
            'method' => $method,
            'value' => (float)$value
        ];
    }
}

// Xuat CSV mo duoc bang Excel, dung cung bo loc ngay/loai/trang thai.
if (($_GET['export'] ?? '') === 'excel') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    $filename = 'tong-hop-bao-cao-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['TỔNG HỢP BÁO CÁO CÔNG AN XÃ LỘC THÀNH'], ';');
    fputcsv($out, ['Từ ngày', $filterFrom ?: 'Tất cả'], ';');
    fputcsv($out, ['Đến ngày', $filterTo ?: 'Tất cả'], ';');
    fputcsv($out, ['Loại báo cáo', $filterType !== '' ? $reportNames[$filterType] : 'Tất cả'], ';');
    fputcsv($out, ['Trạng thái', $filterStatus !== '' ? report_status_label($filterStatus) : 'Tất cả'], ';');
    fputcsv($out, ['Cán bộ báo cáo', $filterOfficer !== '' ? $filterOfficer : 'Tất cả'], ';');
    fputcsv($out, [], ';');
    fputcsv($out, ['Loại báo cáo','Tổng số báo cáo','Bản nháp','Đã gửi','Đã duyệt'], ';');
    foreach ($summaryRows as $r) {
        fputcsv($out, [$reportNames[$r['loai_bao_cao']] ?? $r['loai_bao_cao'], (int)$r['so_bao_cao'], (int)$r['so_nhap'], (int)$r['so_da_gui'], (int)$r['so_da_duyet']], ';');
    }
    fputcsv($out, [], ';');
    fputcsv($out, ['Loại báo cáo','Chỉ tiêu','Cách tính','Giá trị'], ';');
    foreach ($metricRows as $r) {
        fputcsv($out, [$r['type_name'], $r['label'], $r['method'], report_format_metric($r['field'], $r['value'])], ';');
    }
    fclose($out);
    exit;
}

// Phân trang danh sách báo cáo: tối đa 10 báo cáo mỗi trang.
$per = 10;
$page = isset($_GET['page']) && is_scalar($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$offset = ($page - 1) * $per;

$list = $conn->prepare(
    'SELECT b.* FROM bc_baocao b' . $where .
    ' ORDER BY b.ngay_bao_cao DESC, b.created_at DESC, b.id DESC
      LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset
);
$list->execute($params);
$rows = $list->fetchAll(PDO::FETCH_ASSOC);

$filterParams = array_filter([
    'tu_ngay_loc' => $filterFrom,
    'den_ngay_loc' => $filterTo,
    'loc_loai' => $filterType,
    'loc_trangthai' => $filterStatus
], static function ($v) { return $v !== ''; });

// Chuoi tham so dung chung cho nut xuat Excel.
$filterQuery = http_build_query($filterParams);

$makePageUrl = static function (int $targetPage) use ($filterParams): string {
    return 'baocao.php?' . http_build_query($filterParams + ['page' => $targetPage]);
};

$firstItem = $total > 0 ? $offset + 1 : 0;
$lastItem = min($offset + count($rows), $total);


?>
<?php if ($error!==''): ?><div class="notice error"><?=h($error)?></div><?php endif; ?>
<?php if ($editView): ?>
<section class="panel report-panel report-detail-panel">
  <div class="detail-heading">
    <div><span class="eyebrow">CHỈNH SỬA</span><h2>Cập nhật báo cáo</h2><p>Mã báo cáo: <strong><?=h($editView['ma_bao_cao'])?></strong></p></div>
    <a class="btn secondary" href="baocao.php?xem=<?=(int)$editView['id']?>">Quay lại chi tiết</a>
  </div>
  <form method="post" class="report-form">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="update_report" value="1">
    <input type="hidden" name="report_id" value="<?=(int)$editView['id']?>">
    <div class="detail-meta-grid">
      <div class="detail-meta"><span>Loại báo cáo</span><strong><?=h($reportNames[$editView['loai_bao_cao']] ?? $editView['loai_bao_cao'])?></strong></div>
      <div class="detail-meta"><span>Người lập</span><strong><?=h($editView['nguoi_lap'])?></strong></div>
      <div class="detail-meta"><span>Trạng thái</span><strong><?=h(report_status_label((string)$editView['trang_thai']))?></strong></div>
    </div>
    <div class="grid">
      <div class="field"><label>Ngày báo cáo *</label><input type="date" name="ngay_bao_cao" value="<?=h($editView['ngay_bao_cao'])?>" required></div>
      <div class="field"><label>Từ ngày</label><input type="date" name="tu_ngay" value="<?=h($editView['tu_ngay'] ?? '')?>"></div>
      <div class="field"><label>Đến ngày</label><input type="date" name="den_ngay" value="<?=h($editView['den_ngay'] ?? '')?>"></div>
    </div>
    <h3><?=h($reportNames[$editView['loai_bao_cao']] ?? 'Số liệu báo cáo')?></h3>
    <div class="report-fields"><?php report_input($conn, $reportTables[$editView['loai_bao_cao']], $editDetail ?? []); ?></div>
    <div class="field"><label>Nội dung bổ sung</label><textarea name="noi_dung_khac" rows="4"><?=h($editView['noi_dung_khac'] ?? '')?></textarea></div>
    <div class="actions"><button class="btn btn-primary" type="submit">Lưu thay đổi</button><a class="btn secondary" href="baocao.php?xem=<?=(int)$editView['id']?>">Hủy</a></div>
  </form>
</section>
<?php elseif ($view): ?>
<section class="panel report-panel report-detail-panel">
  <div class="detail-heading">
    <div><span class="eyebrow">HỒ SƠ BÁO CÁO</span><h2>Chi tiết báo cáo</h2><p class="report-code"><?=h($view['ma_bao_cao'])?></p></div>
    <div class="actions">
      <a class="btn secondary" href="baocao.php">Quay lại danh sách</a>
      <?php if ((!is_admin() && $view['nguoi_lap'] === current_user_name() && $view['trang_thai'] !== 'da_duyet') || is_admin()): ?>
        <?php if ($view['trang_thai'] !== 'da_duyet' || is_admin()): ?>
          <a class="btn" href="baocao.php?sua=<?=(int)$view['id']?>">Sửa báo cáo</a>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (is_admin() || ($view['nguoi_lap'] === current_user_name() && $view['trang_thai'] !== 'da_duyet')): ?>
        <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa báo cáo này? Thao tác không thể hoàn tác.');">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="report_id" value="<?=(int)$view['id']?>">
          <button class="btn danger" type="submit" name="delete_report" value="1">Xóa báo cáo</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="detail-meta-grid">
    <div class="detail-meta"><span>Loại báo cáo</span><strong><?=h($reportNames[$view['loai_bao_cao']]??$view['loai_bao_cao'])?></strong></div>
    <div class="detail-meta"><span>Ngày báo cáo</span><strong><?=h($view['ngay_bao_cao'])?></strong></div>
    <div class="detail-meta"><span>Thời gian báo cáo</span><strong><?=h($view['tu_ngay']?:'—')?> đến <?=h($view['den_ngay']?:'—')?></strong></div>
    <div class="detail-meta"><span>Người lập</span><strong><?=h($view['nguoi_lap'])?></strong></div>
    <div class="detail-meta"><span>Trạng thái</span><strong><span class="status-pill status-<?=h($view['trang_thai'])?>"><?=h(report_status_label((string)$view['trang_thai']))?></span></strong></div>
  </div>

  <?php if ($viewDetail): ?>
    <div class="detail-section-title"><span>01</span><div><h3>Số liệu chi tiết</h3><p>Các chỉ tiêu và kết quả được ghi nhận trong báo cáo</p></div></div>
    <div class="detail-data-grid">
      <?php foreach($viewDetail as $k=>$v): if(in_array($k,['id','baocao_id','created_at','updated_at'],true)||$v===null||$v==='') continue; ?>
        <div class="detail-data-item"><span><?=h(report_label($k))?></span><strong><?=nl2br(h($v))?></strong></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if(!empty($view['noi_dung_khac'])): ?>
    <div class="detail-section-title"><span>02</span><div><h3>Nội dung bổ sung</h3><p>Thông tin và ghi chú kèm theo</p></div></div>
    <div class="detail-notes"><?=nl2br(h($view['noi_dung_khac']))?></div>
  <?php endif; ?>
</section>
<?php else: ?>
<section class="panel report-panel report-summary-panel">
  <h2>TỔNG HỢP SỐ LIỆU BÁO CÁO THEO NGÀY</h2>
  <form method="get" class="report-filter">
    <div class="grid">
      <div class="field"><label>Từ ngày</label><input type="date" name="tu_ngay_loc" value="<?=h($filterFrom)?>"></div>
      <div class="field"><label>Đến ngày</label><input type="date" name="den_ngay_loc" value="<?=h($filterTo)?>"></div>
      <div class="field"><label>Loại báo cáo</label><select name="loc_loai">
        <option value="">Tất cả loại báo cáo</option>
        <?php foreach($reportNames as $k=>$v): ?><option value="<?=h($k)?>" <?=$filterType===$k?'selected':''?>><?=h($v)?></option><?php endforeach; ?>
      </select></div>
      <div class="field"><label>Trạng thái</label><select name="loc_trangthai">
        <option value="">Tất cả trạng thái</option>
        <option value="nhap" <?=$filterStatus==='nhap'?'selected':''?>>Bản nháp</option>
        <option value="da_gui" <?=$filterStatus==='da_gui'?'selected':''?>>Đã gửi / chờ duyệt</option>
        <option value="da_duyet" <?=$filterStatus==='da_duyet'?'selected':''?>>Đã duyệt</option>
      </select></div>
      <?php if (is_admin()): ?>
      <div class="field"><label>Cán bộ báo cáo</label><select name="loc_canbo">
        <option value="">Tất cả cán bộ</option>
        <?php foreach ($officerOptions as $officer): ?><option value="<?=h($officer)?>" <?=$filterOfficer===$officer?'selected':''?>><?=h($officer)?></option><?php endforeach; ?>
      </select></div>
      <?php endif; ?>
    </div>
    <div class="actions">
      <button class="btn btn-primary" type="submit">Lọc và tổng hợp</button>
      <a class="btn secondary" href="baocao.php">Xóa bộ lọc</a>
      <a class="btn" href="baocao.php?<?=h($filterQuery)?>&export=excel">Xuất Excel</a>
    </div>
  </form>
  <p class="muted">Phạm vi: <?=is_admin()?'Toàn đơn vị':'Các báo cáo do bạn lập'?> · Số báo cáo phù hợp: <strong><?=number_format($total)?></strong></p>
  <div class="report-summary-cards">
    <?php
      $sumReports=0; $sumDraft=0; $sumSent=0; $sumApproved=0;
      foreach($summaryRows as $sr) {
        $sumReports+=(int)$sr['so_bao_cao'];
        $sumDraft+=(int)$sr['so_nhap'];
        $sumSent+=(int)$sr['so_da_gui'];
        $sumApproved+=(int)$sr['so_da_duyet'];
      }
    ?>
    <div class="summary-card"><span>Tổng báo cáo</span><strong><?=number_format($sumReports)?></strong></div>
    <div class="summary-card"><span>Bản nháp</span><strong><?=number_format($sumDraft)?></strong></div>
    <div class="summary-card"><span>Đã gửi / chờ duyệt</span><strong><?=number_format($sumSent)?></strong></div>
    <div class="summary-card"><span>Đã duyệt</span><strong><?=number_format($sumApproved)?></strong></div>
  </div>
  <h3>Thống kê theo loại báo cáo</h3>
  <div class="table-wrap"><table><thead><tr><th>Loại báo cáo</th><th>Tổng số</th><th>Bản nháp</th><th>Đã gửi</th><th>Đã duyệt</th></tr></thead><tbody>
  <?php if(!$summaryRows): ?><tr><td colspan="5">Không có báo cáo trong khoảng ngày đã chọn.</td></tr><?php else: foreach($summaryRows as $sr): ?>
    <tr><td><?=h($reportNames[$sr['loai_bao_cao']]??$sr['loai_bao_cao'])?></td><td><?=number_format((int)$sr['so_bao_cao'])?></td><td><?=number_format((int)$sr['so_nhap'])?></td><td><?=number_format((int)$sr['so_da_gui'])?></td><td><?=number_format((int)$sr['so_da_duyet'])?></td></tr>
  <?php endforeach; endif; ?>
  </tbody></table></div>
  <h3>Cộng gộp các chỉ tiêu số liệu</h3>
  <p class="muted">Các chỉ tiêu số lượng/kinh phí được cộng theo báo cáo; chỉ tiêu tỷ lệ được tính trung bình. Các nội dung dạng văn bản không cộng gộp.</p>
  <div class="table-wrap"><table><thead><tr><th>Loại báo cáo</th><th>Chỉ tiêu</th><th>Cách tính</th><th>Giá trị tổng hợp</th></tr></thead><tbody>
  <?php if(!$metricRows): ?><tr><td colspan="4">Chưa có số liệu chi tiết để tổng hợp theo bộ lọc này.</td></tr><?php else: foreach($metricRows as $mr): ?>
    <tr><td><?=h($mr['type_name'])?></td><td><?=h($mr['label'])?></td><td><?=h($mr['method'])?></td><td><?=h(report_format_metric($mr['field'], $mr['value']))?></td></tr>
  <?php endforeach; endif; ?>
  </tbody></table></div>
</section>
<section class="panel report-panel report-create-panel">
  <h2>Tạo báo cáo mới</h2>

  <form method="get" class="report-type-picker">
    <div class="field"><label>Chọn loại báo cáo</label>
      <select name="loai" onchange="this.form.submit()">
        <?php foreach($reportNames as $k=>$v): ?><option value="<?=h($k)?>" <?=$type===$k?'selected':''?>><?=h($v)?></option><?php endforeach; ?>
      </select>
    </div>
  </form>
  <form method="post" class="report-form">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="save_report" value="1">
    <input type="hidden" name="loai_bao_cao" value="<?=h($type)?>">
    <div class="grid">
      <div class="field"><label>Ngày báo cáo *</label><input type="date" name="ngay_bao_cao" value="<?=h($_POST['ngay_bao_cao']??date('Y-m-d'))?>" required></div>
      
    </div>
    <h3><?=h($reportNames[$type])?> – số liệu báo cáo</h3>
    <div class="report-fields">
      <?php report_input($conn,$detailTable,$_POST['detail']??[]); ?>
    </div>
    <div class="field"><label>Nội dung bổ sung</label><textarea name="noi_dung_khac" rows="4"><?=h($_POST['noi_dung_khac']??'')?></textarea></div>
    <button class="btn btn-primary" type="submit">Gửi báo cáo</button>
  </form>
</section>
<?php endif; ?>

<section class="panel report-panel report-list-panel">
  <h2><?=is_admin()?'Danh sách báo cáo toàn đơn vị':'Báo cáo của tôi'?></h2>
  <?php if(!$rows): ?><p>Chưa có báo cáo nào.</p><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Mã báo cáo</th><th>Loại</th><th>Ngày báo cáo</th><th>Giờ báo cáo</th><th>Người lập</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
  <?php foreach($rows as $r): ?><tr>
    <td><?=h($r['ma_bao_cao'])?></td><td><?=h($reportNames[$r['loai_bao_cao']]??$r['loai_bao_cao'])?></td>
    <td><?=h($r['ngay_bao_cao'])?></td>
    <td><?=!empty($r['created_at']) ? h(date('H:i:s', strtotime((string)$r['created_at']))) : '—'?></td>
    <td><?=h($r['nguoi_lap'])?></td><td><?=h(report_status_label((string)$r['trang_thai']))?></td>
    <td><div class="actions row-actions"><a class="btn small" href="baocao.php?xem=<?=(int)$r['id']?>">Xem</a>
    <?php if (($r['nguoi_lap'] === current_user_name() && $r['trang_thai'] !== 'da_duyet') || is_admin()): ?>
      <?php if ($r['trang_thai'] !== 'da_duyet' || is_admin()): ?><a class="btn small secondary" href="baocao.php?sua=<?=(int)$r['id']?>">Sửa</a><?php endif; ?>
      <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa báo cáo này?');"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="report_id" value="<?=(int)$r['id']?>"><button class="small danger" name="delete_report" value="1">Xóa</button></form>
    <?php endif; ?>
    <?php if(is_admin() && $r['trang_thai']==='da_gui'): ?><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="report_id" value="<?=(int)$r['id']?>"><button class="small" name="approve_report" value="1">Duyệt</button></form><?php endif; ?>
    </div></td>
  </tr><?php endforeach; ?>
  </tbody></table></div>
  <div class="pagination-info">
    Hiển thị <strong><?=number_format($firstItem)?></strong>–<strong><?=number_format($lastItem)?></strong>
    trong tổng số <strong><?=number_format($total)?></strong> báo cáo
    <span>(10 báo cáo/trang)</span>
  </div>
  <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Phân trang báo cáo">
      <?php if ($page > 1): ?>
        <a href="<?=h($makePageUrl($page - 1))?>" aria-label="Trang trước">« Trước</a>
      <?php else: ?>
        <span class="disabled">« Trước</span>
      <?php endif; ?>

      <?php
        // Hiển thị tối đa 7 số trang quanh trang hiện tại.
        $startPage = max(1, $page - 3);
        $endPage = min($pages, $startPage + 6);
        $startPage = max(1, $endPage - 6);

        if ($startPage > 1) {
            echo '<a href="' . h($makePageUrl(1)) . '">1</a>';
            if ($startPage > 2) echo '<span class="ellipsis">…</span>';
        }

        for ($pg = $startPage; $pg <= $endPage; $pg++):
      ?>
        <?php if ($pg === $page): ?>
          <span class="current" aria-current="page"><?= $pg ?></span>
        <?php else: ?>
          <a href="<?=h($makePageUrl($pg))?>"><?= $pg ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php
        if ($endPage < $pages) {
            if ($endPage < $pages - 1) echo '<span class="ellipsis">…</span>';
            echo '<a href="' . h($makePageUrl($pages)) . '">' . $pages . '</a>';
        }
      ?>

      <?php if ($page < $pages): ?>
        <a href="<?=h($makePageUrl($page + 1))?>" aria-label="Trang sau">Sau »</a>
      <?php else: ?>
        <span class="disabled">Sau »</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>
<style id="baocao-modern-ui">
/* Giao dien rieng trang bao cao - khong lam thay doi cac trang con khac */

/* Chi tiet bao cao: bo cuc the thong tin, luoi chi tieu va thao tac */
.report-detail-panel{padding:28px;max-width:1160px;margin:0 auto 24px}
.detail-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;padding-bottom:20px;border-bottom:1px solid #e8edf3;margin-bottom:22px}
.detail-heading h2{margin:5px 0 6px!important;border:0!important;padding:0!important;font-size:25px!important}
.detail-heading p{margin:0;color:#64748b}
.detail-heading .eyebrow{font-size:11px;letter-spacing:1.3px;font-weight:800;color:#a90016}
.detail-heading .actions{margin:0;justify-content:flex-end}
.report-code{font-weight:700;color:#334155!important;word-break:break-all}
.detail-meta-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:20px 0 28px}
.detail-meta{background:linear-gradient(145deg,#fff,#f7f9fc);border:1px solid #e2e8f0;border-radius:10px;padding:15px;min-width:0}
.detail-meta span{display:block;color:#64748b;font-size:12px;font-weight:700;margin-bottom:7px}
.detail-meta strong{display:block;color:#263449;font-size:14px;line-height:1.5;overflow-wrap:anywhere}
.status-pill{display:inline-flex!important;padding:5px 9px;border-radius:30px;background:#fff7ed;color:#9a3412!important;font-size:12px!important}
.status-da_duyet{background:#ecfdf5;color:#047857!important}
.status-nhap{background:#f1f5f9;color:#475569!important}
.detail-section-title{display:flex;align-items:center;gap:12px;margin:28px 0 14px}
.detail-section-title>span{display:grid;place-items:center;width:34px;height:34px;flex:0 0 34px;border-radius:9px;background:#fff1f2;color:#a90016;font-weight:800}
.detail-section-title h3{margin:0!important;color:#263449!important;font-size:18px!important}
.detail-section-title p{margin:3px 0 0;color:#94a3b8;font-size:12px}
.detail-data-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.detail-data-item{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;padding:13px 15px;border:1px solid #e8edf3;border-radius:9px;background:#fff;min-width:0}
.detail-data-item:nth-child(odd){background:#fbfcfe}
.detail-data-item span{color:#526176;font-size:13px;font-weight:600;line-height:1.5}
.detail-data-item strong{color:#172b4d;font-size:14px;text-align:right;white-space:pre-wrap;overflow-wrap:anywhere}
.detail-notes{padding:17px 18px;border:1px solid #e2e8f0;border-left:4px solid #b90016;border-radius:9px;background:#fbfcfe;line-height:1.75;color:#334155}
.report-detail-panel .btn.danger,.report-detail-panel button.danger,.row-actions button.danger{background:#fff1f2!important;border-color:#fecdd3!important;color:#be123c!important}
.report-detail-panel .btn.danger:hover,.report-detail-panel button.danger:hover,.row-actions button.danger:hover{background:#be123c!important;color:#fff!important}
.row-actions{gap:7px!important}
.row-actions form,.detail-heading form{margin:0}
.report-detail-panel .report-form .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
@media(max-width:900px){.detail-meta-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.detail-heading{flex-direction:column}.detail-heading .actions{justify-content:flex-start}}
@media(max-width:640px){.report-detail-panel{padding:16px 13px}.detail-meta-grid,.detail-data-grid,.report-detail-panel .report-form .grid{grid-template-columns:1fr}.detail-data-item{flex-direction:column;gap:5px}.detail-data-item strong{text-align:left}.detail-heading .actions{width:100%;flex-wrap:wrap}.detail-heading .actions>*{flex:1 1 auto}}

.page-main{width:min(1180px,calc(100% - 32px));padding-top:26px;padding-bottom:30px}
.report-panel{background:#fff;border:1px solid #e1e6ed;border-radius:12px;padding:24px;margin:0 0 22px;box-shadow:0 5px 22px rgba(24,39,75,.07);color:#202b3c}
.report-panel h2{margin:0 0 20px;padding:0 0 13px;border-bottom:2px solid #b90016;color:#a90016;font-size:23px;line-height:1.35;font-weight:800}
.report-panel h3{margin:24px 0 12px;color:#27364b;font-size:17px;font-weight:800}
.report-panel p{line-height:1.65}
.report-filter{background:#f8fafc;border:1px solid #e7edf4;border-radius:10px;padding:18px;margin:0 0 16px}
.report-filter .grid,.report-form>.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.report-form>.grid{grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:18px}
.report-panel .field{min-width:0;margin:0 0 14px}
.report-panel .field label{display:block;margin:0 0 7px;color:#344256;font-weight:700;font-size:14px}
.report-panel input:not([type=hidden]),.report-panel select,.report-panel textarea{display:block;width:100%;min-height:42px;padding:9px 12px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#243247;font-size:14px;outline:none;transition:border-color .18s,box-shadow .18s}
.report-panel textarea{min-height:105px;resize:vertical}
.report-panel input:focus,.report-panel select:focus,.report-panel textarea:focus{border-color:#b90016;box-shadow:0 0 0 3px rgba(185,0,22,.10)}
.report-panel .actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin:12px 0}
.report-panel .btn,.report-panel button,.report-panel a.btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 17px;border:1px solid #a90016;border-radius:7px;background:#b90016;color:#fff!important;font-weight:700;text-decoration:none;cursor:pointer;transition:.18s}
.report-panel .btn:hover,.report-panel button:hover{background:#8e0012;border-color:#8e0012;transform:translateY(-1px)}
.report-panel .btn.secondary,.report-panel a.secondary{background:#fff;color:#475569!important;border-color:#cbd5e1}
.report-panel .btn.secondary:hover{background:#f1f5f9}
.report-panel .btn.small,.report-panel button.small{min-height:32px;padding:6px 12px;font-size:13px}
.report-panel .muted{color:#64748b;font-size:13px}
.report-summary-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:20px 0 24px}
.summary-card{position:relative;overflow:hidden;min-height:104px;border:1px solid #e0e7ef;border-radius:10px;padding:17px 18px;background:linear-gradient(145deg,#fff,#f5f8fc);display:flex;flex-direction:column;justify-content:space-between;gap:9px;box-shadow:0 2px 8px rgba(15,23,42,.035)}
.summary-card:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:#b90016}
.summary-card span{font-size:13px;font-weight:700;color:#526176}
.summary-card strong{font-size:27px;line-height:1.1;color:#a90016;font-weight:800;font-variant-numeric:tabular-nums}
.report-panel .table-wrap{width:100%;overflow-x:auto;border:1px solid #e2e8f0;border-radius:9px;margin:12px 0 22px}
.report-panel table{width:100%;min-width:620px;border-collapse:separate;border-spacing:0;margin:0;background:#fff;font-size:14px}
.report-panel th{background:#f1f4f8;color:#344256;font-weight:800;text-align:left;white-space:nowrap}
.report-panel th,.report-panel td{padding:12px 14px;border-bottom:1px solid #e8edf3;vertical-align:middle}
.report-panel tbody tr:last-child td{border-bottom:0}
.report-panel tbody tr:hover{background:#fff9f0}
.report-panel .report-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 18px}
.report-panel .report-fields .field{margin-bottom:16px}
.report-panel .report-type-picker{max-width:460px}
.report-panel .report-form>h3{margin-top:12px;padding-bottom:10px;border-bottom:1px solid #edf0f4}
.report-panel .pagination{display:flex;gap:7px;flex-wrap:wrap;margin:18px 0}
.report-panel .pagination a{display:inline-flex;min-width:34px;height:34px;align-items:center;justify-content:center;border:1px solid #d7dee8;border-radius:6px;color:#475569;background:#fff}
.report-panel .pagination a.current{background:#b90016;border-color:#b90016;color:#fff}
.report-panel .pagination span{display:inline-flex;min-width:34px;height:34px;align-items:center;justify-content:center;padding:0 9px;border:1px solid #d7dee8;border-radius:6px;background:#fff;color:#475569}
.report-panel .pagination span.current{background:#b90016;border-color:#b90016;color:#fff;font-weight:700}
.report-panel .pagination span.disabled{color:#a0a8b5;background:#f4f6f8}
.report-panel .pagination span.ellipsis{border:0;background:transparent;min-width:20px}
.report-panel .pagination-info{margin:14px 0 4px;color:#64748b;font-size:13px}
.report-panel .pagination-info strong{color:#263449}
.report-panel .pagination-info span{margin-left:6px;color:#94a3b8}
.report-panel .notice{padding:12px 15px;border-radius:8px;margin:0 0 18px}
.report-panel .notice.error{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}
.report-panel .report-form>button{margin-top:6px}
@media(max-width:900px){
 .report-filter .grid{grid-template-columns:repeat(2,minmax(0,1fr))}
 .report-summary-cards{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:640px){
 .page-main{width:calc(100% - 20px);padding-top:14px}
 .report-panel{padding:16px 13px;border-radius:9px}
 .report-panel h2{font-size:19px}
 .report-filter .grid,.report-form>.grid,.report-panel .report-fields,.report-summary-cards{grid-template-columns:1fr}
 .report-panel .actions{align-items:stretch}
 .report-panel .actions .btn,.report-panel .actions button{flex:1 1 auto}
}
@media print{
 .site-header,.site-footer,.report-filter,.report-create-panel,.report-list-panel .actions{display:none!important}
 .report-panel{box-shadow:none;border:0;padding:0}
}
</style>
    <!-- InstanceEndEditable -->
</main>

<footer id="lien-he" class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <img src="assets/quoc-huy.png" alt="Quốc huy">
            <div><b>CÔNG AN XÃ LỘC THÀNH</b><small>THÀNH PHỐ ĐỒNG NAI</small></div>
        </div>
        <div>
            <h3>THÔNG TIN LIÊN HỆ</h3>
            <p>Địa chỉ: Xã Lộc Thành, thành phố Đồng Nai</p>
            <p>Điện thoại: 02713507007</p>
            <p>Email: caxlocthanhf.gov.vn</p>
        </div>
        <div>
            <h3>LIÊN KẾT</h3>
            <a href="index.php#gioi-thieu">Giới thiệu</a>
            <a href="index.php#tin-tuc">Tin tức - sự kiện</a>
            <a href="PhanAnh.php">Gửi phản ánh, kiến nghị</a>
            <a href="tracuu_phananh.php">Tra cứu phản ánh</a>
        </div>
        <div id="sitemap">
            <h3>KẾT NỐI</h3>
            <p>Thông tin tuyên truyền và hoạt động của đơn vị.</p>
            <a href="#top">Trở về đầu trang ↑</a>
        </div>
    </div>
    <div class="copyright">© <?= date('Y') ?> CÔNG AN XÃ LỘC THÀNH - THÀNH PHỐ ĐỒNG NAI</div>
</footer>

<!-- InstanceBeginEditable name="foot" -->
<script>
(function () {
  function cleanNumber(value, kind) {
    value = String(value || '').replace(/VNĐ|VND|đồng|giờ|phút/gi, '').replace(/\s/g, '');
    if (value.indexOf(',') !== -1) value = value.replace(/\./g, '').replace(',', '.');
    else if (['currency', 'integer', 'minutes'].indexOf(kind) !== -1) value = value.replace(/\./g, '');
    return value.replace(/[^0-9.\-]/g, '');
  }
  function formatInput(input) {
    var kind = input.dataset.kind || 'integer';
    var suffix = input.dataset.suffix || '';
    var raw = cleanNumber(input.value, kind);
    if (raw === '' || !isFinite(Number(raw))) { input.value = suffix ? '0' + suffix : '0'; return; }
    var number = Number(raw);
    var text = (kind === 'hours' || kind === 'decimal')
      ? number.toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 2})
      : Math.round(number).toLocaleString('vi-VN');
    input.value = text + suffix;
  }
  document.querySelectorAll('input.report-number').forEach(function (input) {
    input.addEventListener('focus', function () {
      var kind = input.dataset.kind || 'integer';
      var raw = cleanNumber(input.value, kind);
      input.value = (kind === 'hours' || kind === 'decimal') ? raw.replace('.', ',') : raw;
      input.select();
    });
    input.addEventListener('blur', function () { formatInput(input); });
    if (input.value.trim() === '') formatInput(input);
  });
  document.querySelectorAll('form.report-form').forEach(function (form) {
    form.addEventListener('submit', function () {
      form.querySelectorAll('input.report-number').forEach(function (input) {
        input.value = cleanNumber(input.value, input.dataset.kind || 'integer');
      });
    });
  });
})();
</script>
<!-- Trang con them JavaScript rieng tai day. -->
<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
