<?php
// api/index.php - Router untuk Vercel

// Ambil path dari URL
$path = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH);

// Mapping URL ke file di root
$routes = [
    '/login' => __DIR__ . '/../login.php',
    '/login.php' => __DIR__ . '/../login.php',
    '/home' => __DIR__ . '/../home.php',
    '/home.php' => __DIR__ . '/../home.php',
    '/admin' => __DIR__ . '/../admin.php',
    '/admin.php' => __DIR__ . '/../admin.php',
    '/riwayat' => __DIR__ . '/../riwayat.php',
    '/riwayat.php' => __DIR__ . '/../riwayat.php',
    '/' => __DIR__ . '/../login.php',
];

// Cek apakah path ada di routes
if (isset($routes[$path])) {
    require_once $routes[$path];
} else {
    // Default ke login
    require_once __DIR__ . '/../login.php';
}
?>