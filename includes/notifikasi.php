<?php
/**
 * =====================================================
 * FILE: includes/notifikasi.php
 * FUNGSI: Manajemen Notifikasi
 * VERSION: 2.0 - Fix
 * =====================================================
 */

class NotifikasiManager {
    private $database;
    private $uid;
    private $isAdmin;

    public function __construct($database, $uid, $isAdmin = false) {
        $this->database = $database;
        $this->uid = $uid;
        $this->isAdmin = $isAdmin;
    }

    public function getUnreadCount() {
        $notifikasi = $this->getNotifikasi();
        $count = 0;
        
        if (is_array($notifikasi) && !empty($notifikasi)) {
            foreach ($notifikasi as $notif) {
                if (!is_array($notif)) continue;
                if (($notif['dibaca'] ?? false) === false) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function getNotifikasi() {
        $data = $this->database
            ->getReference('notifikasi/' . $this->uid)
            ->getValue();
        return is_array($data) ? $data : [];
    }

    public function getRecentNotifikasi($limit = 5) {
        $notifikasi = $this->getNotifikasi();
        if (empty($notifikasi)) return [];
        
        usort($notifikasi, function($a, $b) {
            $timeA = strtotime($a['created_at'] ?? '1970-01-01');
            $timeB = strtotime($b['created_at'] ?? '1970-01-01');
            return $timeB - $timeA;
        });
        
        return array_slice($notifikasi, 0, $limit);
    }

    public function markAsRead($notifId) {
        return $this->database
            ->getReference('notifikasi/' . $this->uid . '/' . $notifId . '/dibaca')
            ->set(true);
    }

    public function markAllAsRead() {
        $notifikasi = $this->getNotifikasi();
        if (empty($notifikasi)) return true;
        
        foreach ($notifikasi as $key => $notif) {
            if (!is_array($notif)) continue;
            $this->database
                ->getReference('notifikasi/' . $this->uid . '/' . $key . '/dibaca')
                ->set(true);
        }
        return true;
    }

    // 🔥 PERBAIKAN: Kirim notifikasi dengan catatan
    public static function sendNotifikasi($database, $uid, $judul, $pesan, $link = '', $type = 'info', $catatan = '') {
        $notifData = [
            'judul' => $judul,
            'pesan' => $pesan,
            'link' => $link,
            'type' => $type,
            'dibaca' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'read_at' => null
        ];
        
        // 🔥 Tambahkan catatan jika ada
        if (!empty($catatan)) {
            $notifData['catatan'] = $catatan;
        }
        
        return $database
            ->getReference('notifikasi/' . $uid)
            ->push($notifData);
    }

    public static function sendToAllAdmins($database, $judul, $pesan, $link = '', $type = 'info') {
        $allUsers = $database->getReference('users')->getValue();
        if (!is_array($allUsers) || empty($allUsers)) {
            return false;
        }
        
        foreach ($allUsers as $uid => $user) {
            if (!is_array($user)) continue;
            if (($user['role'] ?? '') === 'admin') {
                self::sendNotifikasi($database, $uid, $judul, $pesan, $link, $type);
            }
        }
        return true;
    }

    // 🔥 PERBAIKAN: Notifikasi pengajuan baru
    public static function notifikasiPengajuanBaru($database, $cutiData) {
        $judul = '📝 Pengajuan Cuti Baru';
        $pesan = $cutiData['user_name'] . ' mengajukan ' . $cutiData['jenis_cuti'] . ' (' . $cutiData['durasi'] . ' hari)';
        $link = 'admin.php';
        $type = 'warning';
        return self::sendToAllAdmins($database, $judul, $pesan, $link, $type);
    }

    // 🔥 PERBAIKAN: Notifikasi status berubah dengan catatan
    public static function notifikasiStatusBerubah($database, $uid, $cutiData, $statusBaru, $catatan = '') {
        $statusText = '';
        $type = 'info';
        switch ($statusBaru) {
            case 'Disetujui':
                $statusText = '✅ disetujui';
                $type = 'success';
                break;
            case 'Ditolak':
                $statusText = '❌ ditolak';
                $type = 'danger';
                break;
            case 'Selesai':
                $statusText = '📋 selesai';
                $type = 'info';
                break;
            default:
                $statusText = '📝 diupdate';
                $type = 'info';
        }
        
        $judul = '📢 Status Cuti Berubah';
        $pesan = 'Pengajuan ' . $cutiData['jenis_cuti'] . ' Anda ' . $statusText;
        
        // 🔥 Tambahkan catatan ke pesan jika ada
        if (!empty($catatan)) {
            $pesan .= '. Catatan: "' . $catatan . '"';
        }
        
        $link = 'riwayat.php';
        return self::sendNotifikasi($database, $uid, $judul, $pesan, $link, $type, $catatan);
    }
}
?>