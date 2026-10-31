<?php
// app/Views/layouts/header.php
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__, 3) . '/config/app.php';
}
$appTitleSetting = function_exists('app_setting') ? (app_setting('app_title', APP_NAME) ?: APP_NAME) : APP_NAME;
$schoolNameSetting = function_exists('app_setting') ? (app_setting('school_name', SCHOOL_NAME) ?: SCHOOL_NAME) : SCHOOL_NAME;
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . $appTitleSetting . ' ' . $schoolNameSetting : $appTitleSetting . ' - ' . $schoolNameSetting;

$faviconData = class_exists('SettingHelper') && method_exists('SettingHelper', 'getFaviconData')
    ? SettingHelper::getFaviconData()
    : [
        'ico'   => asset('assets/images/logo-placeholder.svg'),
        'png16' => asset('assets/images/logo-placeholder.svg'),
        'png32' => asset('assets/images/logo-placeholder.svg'),
        'png64' => asset('assets/images/logo-placeholder.svg'),
        'type'  => 'image/svg+xml',
    ];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- Favicon / Tab Logo Prioritas Tinggi (Square 16x16, 32x32, 64x64 + Multi-frame ICO) agar konsisten tanpa kedipan saat refresh -->
    <link rel="preload" as="image" href="<?= htmlspecialchars($faviconData['png32'], ENT_QUOTES, 'UTF-8') ?>" type="image/png">
    <link rel="icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="<?= htmlspecialchars($faviconData['ico'], ENT_QUOTES, 'UTF-8') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= htmlspecialchars($faviconData['ico'], ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($faviconData['png32'], ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($faviconData['png16'], ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" type="image/png" sizes="64x64" href="<?= htmlspecialchars($faviconData['png64'], ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" sizes="64x64" href="<?= htmlspecialchars($faviconData['png64'], ENT_QUOTES, 'UTF-8') ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="Sistem Informasi E-Voting Pemilihan Ketua dan Wakil Ketua OSIS <?= htmlspecialchars($schoolNameSetting, ENT_QUOTES, 'UTF-8') ?> secara transparan, aman, dan demokratis.">
    <meta name="theme-color" content="#0f172a">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS (Local first, CDN fallback) -->
    <link rel="stylesheet" href="<?= asset('assets/css/bootstrap.min.css') ?>" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- SweetAlert2 CSS (Local first, CDN fallback) -->
    <link rel="stylesheet" href="<?= asset('assets/css/sweetalert2.min.css') ?>" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css';">

    <!-- Custom CSS with cache busting -->
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>?v=<?= file_exists(PUBLIC_PATH . '/assets/css/style.css') ? filemtime(PUBLIC_PATH . '/assets/css/style.css') : '1.0.1' ?>">

    <!-- Bootstrap 5.3 JS Bundle (Local first with CDN fallback in HEAD so modals & dropdowns work reliably everywhere) -->
    <script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
    if (typeof bootstrap === 'undefined') {
        document.write('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"><\/script>');
    }
    </script>

    <!-- SweetAlert2 JS (Local first with CDN fallback in HEAD so alerts & confirmations work reliably everywhere) -->
    <script src="<?= asset('assets/js/sweetalert2.all.min.js') ?>"></script>
    <script>
    if (typeof Swal === 'undefined') {
        document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"><\/script>');
    }
    </script>
</head>
<body>
