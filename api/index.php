<?php
// api/index.php - Router untuk Vercel

// Ambil path dari URL
$path = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH);

// Mapping URL ke file di root
$routes = [
    // Login
    '/' => __DIR__ . '/../login.php',
    '/login' => __DIR__ . '/../login.php',
    '/login.php' => __DIR__ . '/../login.php',
    
    // User
    '/home' => __DIR__ . '/../home.php',
    '/home.php' => __DIR__ . '/../home.php',
    '/riwayat' => __DIR__ . '/../riwayat.php',
    '/riwayat.php' => __DIR__ . '/../riwayat.php',
    '/profile' => __DIR__ . '/../profil.php',        // 🔥 TAMBAHKAN INI
    '/profile.php' => __DIR__ . '/../profil.php',    // 🔥 TAMBAHKAN INI

    // Admin
    '/admin' => __DIR__ . '/../admin.php',
    '/admin.php' => __DIR__ . '/../admin.php',
    '/admin_riwayat' => __DIR__ . '/../admin_riwayat.php',
    '/admin_riwayat.php' => __DIR__ . '/../admin_riwayat.php',
    '/admin_laporan' => __DIR__ . '/../admin_laporan.php',
    '/admin_laporan.php' => __DIR__ . '/../admin_laporan.php',
    '/admin_cetak_pdf' => __DIR__ . '/../admin_cetak_pdf.php',
    '/admin_cetak_pdf.php' => __DIR__ . '/../admin_cetak_pdf.php',
    
    // Logout
    '/logout' => __DIR__ . '/../logout.php',
    '/logout.php' => __DIR__ . '/../logout.php',
];

// Cek apakah path ada di routes
if (isset($routes[$path])) {
    require_once $routes[$path];
} else {
    // Default ke login
    require_once __DIR__ . '/../login.php';
}
?>