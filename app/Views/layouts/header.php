<?php
// app/Views/layouts/header.php
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__, 3) . '/config/app.php';
}
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME . ' ' . SCHOOL_NAME : APP_NAME . ' - ' . SCHOOL_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="Sistem Informasi E-Voting Pemilihan Ketua dan Wakil Ketua OSIS SMK Muthia Harapan Cicalengka secara transparan, aman, dan demokratis.">
    <meta name="theme-color" content="#0f172a">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS (Local first, CDN fallback) -->
    <link rel="stylesheet" href="<?= asset('assets/css/bootstrap.min.css') ?>" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS with cache busting -->
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>?v=<?= file_exists(PUBLIC_PATH . '/assets/css/style.css') ? filemtime(PUBLIC_PATH . '/assets/css/style.css') : '1.0.1' ?>">

    <!-- Bootstrap 5.3 JS Bundle (Local first with CDN fallback in HEAD so modals & dropdowns work reliably everywhere) -->
    <script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
    if (typeof bootstrap === 'undefined') {
        document.write('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"><\/script>');
    }
    </script>
</head>
<body>
