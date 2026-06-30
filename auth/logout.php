<?php
/**
 * =====================================================
 * FILE: auth/logout.php
 * FUNGSI: Proses Logout
 * =====================================================
 */

session_start();
session_destroy();

// Redirect ke login
header('Location: ../auth/login.php');
exit;
?>