<?php
/**
 * =====================================================
 * FILE: includes/header.php
 * FUNGSI: Header/Topbar untuk semua halaman
 * =====================================================
 */

// 🔥 CEK SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($user)) {
    $user = $_SESSION['user'] ?? null;
}

if (!isset($currentPage)) {
    $currentPage = 'home';
}

if (!isset($uid)) {
    $uid = $_SESSION['uid'] ?? null;
}

$isAdmin = isset($user['role']) && $user['role'] === 'admin';

// Load notifikasi
if (isset($database) && isset($uid)) {
    require_once __DIR__ . '/notifikasi.php';
    $notifManager = new NotifikasiManager($database, $uid, $isAdmin);
    $unreadCount = $notifManager->getUnreadCount();
    $recentNotif = $notifManager->getRecentNotifikasi(5);
} else {
    $unreadCount = 0;
    $recentNotif = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Magang.usg — Manajemen Cuti Karyawan</title>
    
    <!-- 🔥 CSS - PAKAI ABSOLUTE PATH UNTUK VERCEL -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
    
    <style>
        /* ... style tetap sama ... */
    </style>
</head>
<body>

<!-- ... content ... -->

<!-- 🔥 JAVASCRIPT - PAKAI ABSOLUTE PATH -->
<script src="/assets/js/app.js"></script>
</body>
</html>
