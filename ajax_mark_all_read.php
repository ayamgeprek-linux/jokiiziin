<?php
/**
 * FILE: ajax_mark_all_read.php
 * FUNGSI: Tandai semua notifikasi sebagai sudah dibaca via AJAX
 */

session_start();
require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/notifikasi.php';

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$uid = $auth->getCurrentUid();
$notifManager = new NotifikasiManager($database, $uid);
$result = $notifManager->markAllAsRead();

echo json_encode(['success' => $result]);
?>