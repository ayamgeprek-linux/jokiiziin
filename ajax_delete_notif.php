<?php
/**
 * FILE: ajax_delete_notif.php
 * FUNGSI: Hapus notifikasi via AJAX
 */

session_start();
require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/notifikasi.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$uid = $auth->getCurrentUid();
$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID required']);
    exit;
}

$notifManager = new NotifikasiManager($database, $uid);
$result = $notifManager->deleteNotifikasi($id);

echo json_encode(['success' => $result]);
?>