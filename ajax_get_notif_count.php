<?php
/**
 * FILE: ajax_get_notif_count.php
 * FUNGSI: Mendapatkan jumlah notifikasi belum dibaca via AJAX
 */

session_start();
require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/notifikasi.php';

if (!$auth->isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

$uid = $auth->getCurrentUid();
$isAdmin = $auth->isAdmin();
$notifManager = new NotifikasiManager($database, $uid, $isAdmin);

echo json_encode(['count' => $notifManager->getUnreadCount()]);
?>