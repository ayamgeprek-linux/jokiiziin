<?php
/**
 * =====================================================
 * FILE: api/index.php
 * FUNGSI: Router untuk Vercel
 * VERSION: 3.0 - Fix Redirect Loop
 * =====================================================
 */

// 🔥 Ambil path dari URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

// 🔥 Hapus leading dan trailing slash
$path = trim($path, '/');

// 🔥 Jika path kosong atau hanya 'login', arahkan ke login
if (empty($path) || $path === 'login' || $path === 'login.php') {
    require_once __DIR__ . '/../auth/login.php';
    exit;
}

// 🔥 Jika path adalah 'admin' atau 'admin/', arahkan ke admin/index.php
if ($path === 'admin' || $path === 'admin/') {
    require_once __DIR__ . '/../admin/index.php';
    exit;
}

// 🔥 Jika path adalah 'home' atau 'home/', arahkan ke user/index.php
if ($path === 'home' || $path === 'home/') {
    require_once __DIR__ . '/../user/index.php';
    exit;
}

// 🔥 Cek apakah file tersebut ada di folder yang sesuai
$fileMap = [
    'auth/login' => __DIR__ . '/../auth/login.php',
    'auth/login.php' => __DIR__ . '/../auth/login.php',
    'auth/logout' => __DIR__ . '/../auth/logout.php',
    'auth/logout.php' => __DIR__ . '/../auth/logout.php',
    
    'user/index' => __DIR__ . '/../user/index.php',
    'user/index.php' => __DIR__ . '/../user/index.php',
    'user/riwayat' => __DIR__ . '/../user/riwayat.php',
    'user/riwayat.php' => __DIR__ . '/../user/riwayat.php',
    'user/profile' => __DIR__ . '/../user/profile.php',
    'user/profile.php' => __DIR__ . '/../user/profile.php',
    
    'admin/index' => __DIR__ . '/../admin/index.php',
    'admin/index.php' => __DIR__ . '/../admin/index.php',
    'admin/riwayat' => __DIR__ . '/../admin/riwayat.php',
    'admin/riwayat.php' => __DIR__ . '/../admin/riwayat.php',
    'admin/laporan' => __DIR__ . '/../admin/laporan.php',
    'admin/laporan.php' => __DIR__ . '/../admin/laporan.php',
    'admin/cetak_pdf' => __DIR__ . '/../admin/cetak_pdf.php',
    'admin/cetak_pdf.php' => __DIR__ . '/../admin/cetak_pdf.php',
    
    'ajax/mark_read' => __DIR__ . '/../ajax/mark_read.php',
    'ajax/mark_read.php' => __DIR__ . '/../ajax/mark_read.php',
    'ajax/mark_all_read' => __DIR__ . '/../ajax/mark_all_read.php',
    'ajax/mark_all_read.php' => __DIR__ . '/../ajax/mark_all_read.php',
    'ajax/delete_notif' => __DIR__ . '/../ajax/delete_notif.php',
    'ajax/delete_notif.php' => __DIR__ . '/../ajax/delete_notif.php',
    'ajax/get_notif_count' => __DIR__ . '/../ajax/get_notif_count.php',
    'ajax/get_notif_count.php' => __DIR__ . '/../ajax/get_notif_count.php',
];

// 🔥 Cek di fileMap
if (isset($fileMap[$path])) {
    require_once $fileMap[$path];
    exit;
}

// 🔥 Cek apakah ini file PHP langsung (misal: login.php, home.php)
$directFile = __DIR__ . '/../' . $path;
if (file_exists($directFile) && is_file($directFile)) {
    require_once $directFile;
    exit;
}

// 🔥 Cek apakah ini asset (css, js, images)
$assetPath = __DIR__ . '/../assets/' . $path;
if (file_exists($assetPath) && is_file($assetPath)) {
    $ext = pathinfo($assetPath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($assetPath);
    exit;
}

// 🔥 Jika tidak ada yang cocok, redirect ke login (BUKAN /login, tapi langsung file)
header('Location: /auth/login.php');
exit;
?>
