<?php
/**
 * =====================================================
 * FILE: includes/functions.php
 * FUNGSI: Fungsi-fungsi helper untuk aplikasi
 * VERSION: 2.0 - Fixed for REST API
 * =====================================================
 */

/**
 * Fungsi untuk menampilkan toast notification via JavaScript
 */
function showToast($message, $type = 'info') {
    $icon = 'ri-information-line';
    switch ($type) {
        case 'success':
            $icon = 'ri-checkbox-circle-line';
            break;
        case 'error':
            $icon = 'ri-close-circle-line';
            break;
        case 'warning':
            $icon = 'ri-alert-line';
            break;
    }
    echo "<script>showToast('" . addslashes($message) . "', '" . $icon . "');</script>";
}

/**
 * Fungsi untuk mendapatkan status cuti dengan badge HTML
 */
function getStatusBadge($status) {
    $class = '';
    switch ($status) {
        case 'Menunggu':
            $class = 'badge-warning';
            break;
        case 'Disetujui':
            $class = 'badge-success';
            break;
        case 'Ditolak':
            $class = 'badge-danger';
            break;
        case 'Selesai':
            $class = 'badge-dark';
            break;
        default:
            $class = 'badge-warning';
    }
    return '<span class="badge ' . $class . '">' . $status . '</span>';
}

/**
 * Format tanggal ke format Indonesia
 */
function formatTanggal($datetime) {
    if (empty($datetime)) return '-';
    
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    
    $date = date_create($datetime);
    if (!$date) return $datetime;
    
    return date_format($date, 'd') . ' ' . 
           $bulan[(int)date_format($date, 'n')] . ' ' . 
           date_format($date, 'Y');
}

/**
 * Format tanggal dan waktu ke format Indonesia
 */
function formatTanggalWaktu($datetime) {
    if (empty($datetime)) return '-';
    return formatTanggal($datetime) . ', ' . date('H:i', strtotime($datetime));
}

/**
 * Mendapatkan daftar jenis cuti
 */
function getJenisCuti() {
    return [
        'Cuti Tahunan' => 'Cuti tahunan (12 hari kerja)',
        'Cuti Sakit' => 'Cuti karena sakit (dengan surat dokter)',
        'Cuti Melahirkan' => 'Cuti melahirkan (90 hari)',
        'Cuti Penting' => 'Cuti untuk keperluan penting',
        'Cuti Ibadah' => 'Cuti untuk keperluan ibadah',
        'Cuti Pernikahan' => 'Cuti pernikahan (3 hari)',
        'Cuti Duka' => 'Cuti duka cita (2 hari)'
    ];
}

/**
 * =====================================================
 * 🔥 PERBAIKAN: Menghitung sisa cuti
 * =====================================================
 */
function getSisaCuti($uid, $database) {
    // Ambil data user
    $userData = $database
        ->getReference('users/' . $uid)
        ->getValue();
    
    // 🔥 PERBAIKAN 1: Cek apakah userData adalah array
    $totalCuti = 12; // Default
    if (is_array($userData) && isset($userData['sisa_cuti'])) {
        $totalCuti = (int)$userData['sisa_cuti'];
    }
    
    // Ambil cuti yang sudah disetujui
    $permohonan = $database
        ->getReference('permohonan')
        ->orderByChild('user_id')
        ->equalTo($uid)
        ->getValue();
    
    $used = 0;
    
    // 🔥 PERBAIKAN 2: Cek apakah $permohonan adalah array
    if (is_array($permohonan) && !empty($permohonan)) {
        foreach ($permohonan as $key => $izin) {
            // 🔥 PERBAIKAN 3: Pastikan $izin adalah array
            if (!is_array($izin)) {
                continue;
            }
            
            $status = $izin['status'] ?? '';
            if ($status === 'Disetujui' || $status === 'Selesai') {
                // 🔥 PERBAIKAN 4: Gunakan null coalescing untuk tanggal
                $startDate = $izin['tanggal_mulai'] ?? null;
                $endDate = $izin['tanggal_selesai'] ?? null;
                
                if ($startDate && $endDate) {
                    try {
                        $start = new DateTime($startDate);
                        $end = new DateTime($endDate);
                        $diff = $start->diff($end);
                        $used += $diff->days + 1;
                    } catch (Exception $e) {
                        // Jika format tanggal salah, skip
                        continue;
                    }
                }
            }
        }
    }
    
    return max(0, $totalCuti - $used);
}

/**
 * Generate ID unik untuk permohonan
 */
function generateCutiId() {
    $year = date('Y');
    $random = strtoupper(substr(uniqid(), -4));
    return 'CUT-' . $year . '-' . $random;
}

/**
 * Get user data by UID
 */
function getUserData($uid, $database) {
    $data = $database
        ->getReference('users/' . $uid)
        ->getValue();
    
    return is_array($data) ? $data : null;
}

/**
 * Redirect ke halaman tertentu
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Cek apakah user sudah login
 */
function requireLogin($auth) {
    if (!$auth->isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Cek apakah user adalah admin
 */
function requireAdmin($auth) {
    requireLogin($auth);
    if (!$auth->isAdmin()) {
        redirect('home.php');
    }
}

/**
 * Escape string untuk keamanan XSS
 */
function escape($string) {
    if ($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>