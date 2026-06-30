<?php
/**
 * =====================================================
 * FILE: api/index.php
 * FUNGSI: Router untuk Vercel
 * VERSION: 2.0 - Fixed Path
 * =====================================================
 */

// Ambil path dari URL
$path = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH);

// 🔥 Mapping URL ke file yang benar (sesuai struktur folder)
$routes = [
    // Root / Auth
    '/' => __DIR__ . '/../auth/login.php',
    '/login' => __DIR__ . '/../auth/login.php',
    '/login.php' => __DIR__ . '/../auth/login.php',
    '/logout' => __DIR__ . '/../auth/logout.php',
    '/logout.php' => __DIR__ . '/../auth/logout.php',
    
    // User
    '/home' => __DIR__ . '/../user/index.php',
    '/home.php' => __DIR__ . '/../user/index.php',
    '/riwayat' => __DIR__ . '/../user/riwayat.php',
    '/riwayat.php' => __DIR__ . '/../user/riwayat.php',
    '/profile' => __DIR__ . '/../user/profile.php',
    '/profile.php' => __DIR__ . '/../user/profile.php',
    
    // Admin
    '/admin' => __DIR__ . '/../admin/index.php',
    '/admin.php' => __DIR__ . '/../admin/index.php',
    '/admin_riwayat' => __DIR__ . '/../admin/riwayat.php',
    '/admin_riwayat.php' => __DIR__ . '/../admin/riwayat.php',
    '/admin_laporan' => __DIR__ . '/../admin/laporan.php',
    '/admin_laporan.php' => __DIR__ . '/../admin/laporan.php',
    '/admin_cetak_pdf' => __DIR__ . '/../admin/cetak_pdf.php',
    '/admin_cetak_pdf.php' => __DIR__ . '/../admin/cetak_pdf.php',
    
    // AJAX
    '/ajax/mark_read' => __DIR__ . '/../ajax/mark_read.php',
    '/ajax/mark_read.php' => __DIR__ . '/../ajax/mark_read.php',
    '/ajax/mark_all_read' => __DIR__ . '/../ajax/mark_all_read.php',
    '/ajax/mark_all_read.php' => __DIR__ . '/../ajax/mark_all_read.php',
    '/ajax/delete_notif' => __DIR__ . '/../ajax/delete_notif.php',
    '/ajax/delete_notif.php' => __DIR__ . '/../ajax/delete_notif.php',
    '/ajax/get_notif_count' => __DIR__ . '/../ajax/get_notif_count.php',
    '/ajax/get_notif_count.php' => __DIR__ . '/../ajax/get_notif_count.php',
];

// 🔥 Jika path ada di routes, load file yang sesuai
if (isset($routes[$path])) {
    require_once $routes[$path];
    exit;
}

// 🔥 Jika path adalah file assets (css, js, images)
$assetPath = __DIR__ . '/../assets' . $path;
if (file_exists($assetPath) && is_file($assetPath)) {
    // Tentukan mime type
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

// 🔥 Jika tidak ada yang cocok, redirect ke login
header('Location: /login.php');
exit;
?>
