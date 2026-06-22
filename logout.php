<?php
/**
 * =====================================================
 * FILE: logout.php
 * FUNGSI: Proses logout dan menghapus session
 * =====================================================
 * 
 * @package doret-cuti
 * @version 1.0.0
 */

session_start();
session_destroy();
header('Location: login.php');
exit;
?>