<?php
/**
 * =====================================================
 * FILE: home.php
 * FUNGSI: Dashboard User
 * VERSION: FINAL - Tanpa HRD
 * =====================================================
 */

require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/notifikasi.php';
require_once 'config/supabase.php';

requireLogin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// =====================================================
// PROSES AJUKAN CUTI
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_cuti'])) {
    $jenis_cuti = $_POST['jenis_cuti'] ?? '';
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $alasan = trim($_POST['alasan'] ?? '');
    $dokumen = $_FILES['dokumen'] ?? null;
    
    $errors = [];
    if (empty($jenis_cuti)) $errors[] = 'Jenis cuti harus dipilih';
    if (empty($tanggal_mulai)) $errors[] = 'Tanggal mulai harus diisi';
    if (empty($tanggal_selesai)) $errors[] = 'Tanggal selesai harus diisi';
    if (strtotime($tanggal_mulai) > strtotime($tanggal_selesai)) {
        $errors[] = 'Tanggal mulai harus sebelum tanggal selesai';
    }
    if (empty($alasan)) $errors[] = 'Alasan cuti harus diisi';
    
    $sisaCuti = getSisaCuti($uid, $database);
    $start = new DateTime($tanggal_mulai);
    $end = new DateTime($tanggal_selesai);
    $durasi = $start->diff($end)->days + 1;
    
    if ($durasi > $sisaCuti) {
        $errors[] = "Sisa cuti Anda $sisaCuti hari, namun Anda mengajukan $durasi hari";
    }
    
    if (empty($errors)) {
        if (empty($user['name']) || empty($user['nip']) || empty($user['jabatan'])) {
            $userData = $database->getReference('users/' . $uid)->getValue();
            if (is_array($userData)) {
                $user = array_merge($user, $userData);
                $_SESSION['user'] = $user;
            }
        }
        
        $cutiData = [
            'id' => generateCutiId(),
            'user_id' => $uid,
            'user_name' => $user['name'] ?? 'User',
            'user_email' => $user['email'] ?? '',
            'nip' => $user['nip'] ?? '',
            'jabatan' => $user['jabatan'] ?? '',
            'departemen' => $user['departemen'] ?? '',
            'jenis_cuti' => $jenis_cuti,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,
            'durasi' => $durasi,
            'alasan' => $alasan,
            'status' => 'Menunggu',
            'dokumen' => '',
            'catatan_admin' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Upload dokumen
        if ($dokumen && $dokumen['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            $maxSize = 5 * 1024 * 1024;
            
            if (!in_array($dokumen['type'], $allowedTypes)) {
                $errors[] = 'Format file tidak didukung. Gunakan PDF, JPG, atau PNG';
            } elseif ($dokumen['size'] > $maxSize) {
                $errors[] = 'Ukuran file maksimal 5MB';
            } else {
                $extension = pathinfo($dokumen['name'], PATHINFO_EXTENSION);
                $fileName = $uid . '_' . date('Ymd_His') . '.' . $extension;
                $destinationPath = 'cuti/' . $fileName;
                
                $fileUrl = SupabaseConfig::uploadFile($dokumen['tmp_name'], $destinationPath);
                if ($fileUrl) {
                    $cutiData['dokumen'] = $fileUrl;
                } else {
                    $errors[] = 'Gagal upload: ' . SupabaseConfig::getLastError();
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $database->getReference('permohonan')->push($cutiData);
                NotifikasiManager::notifikasiPengajuanBaru($database, $cutiData);
                $_SESSION['flash_message'] = 'Pengajuan cuti berhasil dikirim!';
                redirect('home.php');
            } catch (Exception $e) {
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        } else {
            $error = implode(', ', $errors);
        }
    } else {
        $error = implode(', ', $errors);
    }
}

// =====================================================
// AMBIL DATA
// =====================================================

$allPermohonan = $database->getReference('permohonan')->getValue();
$permohonan = [];

if (is_array($allPermohonan) && !empty($allPermohonan)) {
    foreach ($allPermohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        if (($izin['user_id'] ?? '') === $uid) {
            $permohonan[$key] = $izin;
        }
    }
}

$stats = ['total' => 0, 'menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];
$recentActivities = [];

if (is_array($permohonan) && !empty($permohonan)) {
    foreach ($permohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        $izin['_key'] = $key;
        $stats['total']++;
        $status = $izin['status'] ?? 'Menunggu';
        switch ($status) {
            case 'Menunggu': $stats['menunggu']++; break;
            case 'Disetujui': $stats['disetujui']++; break;
            case 'Ditolak': $stats['ditolak']++; break;
            case 'Selesai': $stats['selesai']++; break;
        }
        $recentActivities[] = $izin;
    }
}

if (!empty($recentActivities)) {
    usort($recentActivities, function($a, $b) {
        return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
    });
    $recentActivities = array_slice($recentActivities, 0, 5);
}

$sisaCuti = getSisaCuti($uid, $database);
$jenisCutiList = getJenisCuti();

$currentPage = 'home';
include 'includes/header.php';
?>

<div id="desktop-dashboard-view" class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">Magang<span>.usg</span><br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small></div>
        <div class="sidebar-item active"><i class="ri-dashboard-line"></i> Dashboard</div>
        <div class="sidebar-item" onclick="document.getElementById('ajukan-cuti').scrollIntoView()"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
        <div class="sidebar-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='profile.php'"><i class="ri-user-line"></i> Profil</div>
        <div class="sidebar-bottom">
            
            <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <div class="dashboard-hero">
            <div>
                <h2>Selamat Datang, <?= escape($user['name'] ?? 'User') ?></h2>
                <p>Kelola pengajuan cuti Anda dengan mudah.</p>
                
                <?php if ($stats['menunggu'] > 0): ?>
                    <div style="background:rgba(184,134,11,.2);border:1px solid var(--clr-primary);border-radius:var(--r-md);padding:10px 16px;margin-top:12px;display:flex;align-items:center;gap:10px;">
                        <i class="ri-notification-3-line" style="color:var(--clr-primary);font-size:20px;"></i>
                        <span style="color:rgba(255,255,255,.9);font-size:14px;">
                            Anda memiliki <strong style="color:var(--clr-primary-light);"><?= $stats['menunggu'] ?></strong> pengajuan cuti yang sedang <strong style="color:var(--clr-primary-light);">menunggu persetujuan</strong>
                        </span>
                    </div>
                <?php else: ?>
                    <div style="background:rgba(45,122,79,.15);border:1px solid var(--clr-success);border-radius:var(--r-md);padding:10px 16px;margin-top:12px;display:flex;align-items:center;gap:10px;">
                        <i class="ri-checkbox-circle-line" style="color:var(--clr-success);font-size:20px;"></i>
                        <span style="color:rgba(255,255,255,.8);font-size:14px;">Semua pengajuan cuti Anda sudah diproses ✅</span>
                    </div>
                <?php endif; ?>
                
                <div style="display:flex;gap:16px;margin-top:12px;flex-wrap:wrap;">
                    <div style="background:rgba(255,255,255,.1);padding:6px 16px;border-radius:var(--r-md);color:rgba(255,255,255,.7);font-size:13px;">
                        <i class="ri-user-line"></i> <?= escape($user['jabatan'] ?? '-') ?>
                    </div>
                    <div style="background:rgba(255,255,255,.1);padding:6px 16px;border-radius:var(--r-md);color:rgba(255,255,255,.7);font-size:13px;">
                        <i class="ri-building-line"></i> <?= escape($user['departemen'] ?? '-') ?>
                    </div>
                    <div style="background:rgba(184,134,11,.2);padding:6px 16px;border-radius:var(--r-md);color:var(--clr-primary-light);font-size:13px;font-weight:600;">
                        <i class="ri-calendar-2-line"></i> Sisa Cuti: <?= $sisaCuti ?> Hari
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="text-align:right;color:rgba(255,255,255,.6);font-size:12px;"><div>NIP: <?= escape($user['nip'] ?? '-') ?></div></div>
                <div style="width:64px;height:64px;border-radius:50%;background:var(--clr-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:22px;border:3px solid rgba(255,255,255,.2);flex-shrink:0;">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
                </div>
            </div>
        </div>

        <div class="quick-actions-grid">
            <div class="quick-action-card dark" onclick="document.getElementById('ajukan-cuti').scrollIntoView()">
                <div><div class="quick-action-label primary">Layanan Utama</div><h3>Ajukan Cuti Baru</h3><p>Mulai proses pengajuan cuti dengan mudah dan cepat.</p></div>
                <div class="qa-icon dark-icon"><i class="ri-add-circle-line"></i></div>
            </div>
            <div class="quick-action-card" onclick="window.location.href='riwayat.php'">
                <div><div class="quick-action-label muted">Arsip & Data</div><h3>Lihat Riwayat</h3><p>Pantau perkembangan dan unduh dokumen cuti terdahulu.</p></div>
                <div class="qa-icon"><i class="ri-history-line"></i></div>
            </div>
        </div>

        <div class="section-header">
            <div><h3>Ringkasan Status Cuti</h3><small>Update terakhir: <?= date('d M Y, H:i') ?> WIB</small></div>
            <a href="riwayat.php" class="see-all">Lihat Semua <i class="ri-arrow-right-line"></i></a>
        </div>
        
        <div class="status-summary-grid">
            <div class="status-card warning"><div class="status-card-icon"><i class="ri-time-line"></i></div><div class="status-card-value"><?= $stats['menunggu'] ?></div><div class="status-card-label">Menunggu</div><div class="status-card-sub">Menunggu persetujuan</div></div>
            <div class="status-card success"><div class="status-card-icon"><i class="ri-checkbox-circle-line"></i></div><div class="status-card-value"><?= $stats['disetujui'] ?></div><div class="status-card-label">Disetujui</div><div class="status-card-sub">Cuti disetujui</div></div>
            <div class="status-card danger"><div class="status-card-icon"><i class="ri-close-circle-line"></i></div><div class="status-card-value"><?= $stats['ditolak'] ?></div><div class="status-card-label">Ditolak</div><div class="status-card-sub">Perlu perbaikan</div></div>
        </div>

        <div id="ajukan-cuti" style="margin-top:8px;scroll-margin-top:80px;">
            <div class="section-header">
                <div><h3>Ajukan Cuti Baru</h3><small style="font-size:12px;color:var(--clr-muted);">Sisa cuti Anda: <strong style="color:var(--clr-primary);"><?= $sisaCuti ?> hari</strong></small></div>
            </div>
            
            <?php if (isset($error)): ?>
                <div style="background:#FDECEA;border:1px solid #C0392B;border-radius:var(--r-md);padding:12px 16px;margin-bottom:16px;color:#C0392B;font-size:13px;">
                    <i class="ri-error-warning-line"></i> <?= escape($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div style="background:#E8F5EE;border:1px solid #2D7A4F;border-radius:var(--r-md);padding:12px 16px;margin-bottom:16px;color:#2D7A4F;font-size:13px;">
                    <i class="ri-checkbox-circle-line"></i> <?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="card" style="padding:24px;margin-bottom:24px;">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="ajukan_cuti" value="1">
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label class="form-label">Jenis Cuti <span style="color:red;">*</span></label>
                            <select name="jenis_cuti" class="form-control" required>
                                <option value="">Pilih jenis cuti</option>
                                <?php foreach ($jenisCutiList as $value => $label): ?>
                                    <option value="<?= escape($value) ?>"><?= escape($value) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Durasi Cuti</label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                <input type="date" name="tanggal_mulai" class="form-control" required>
                                <input type="date" name="tanggal_selesai" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top:16px;">
                        <label class="form-label">Alasan Cuti <span style="color:red;">*</span></label>
                        <textarea name="alasan" class="form-control" rows="3" placeholder="Jelaskan alasan pengajuan cuti..." required></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-top:16px;">
                        <label class="form-label">Dokumen Pendukung (opsional)</label>
                        <input type="file" name="dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small style="color:var(--clr-muted);font-size:12px;">Format: PDF, JPG, PNG. Maks 5MB</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top:16px;">
                        <i class="ri-send-plane-line"></i> Kirim Pengajuan
                    </button>
                </form>
            </div>
        </div>

        <div class="dashboard-bottom">
            <div class="card" style="padding:24px;">
                <h3 style="font-family:var(--font-display);font-size:18px;font-weight:700;margin-bottom:16px;">Aktivitas Terbaru</h3>
                <div class="activity-list">
                    <?php if (empty($recentActivities)): ?>
                        <div style="text-align:center;padding:20px;color:var(--clr-muted);">
                            <i class="ri-inbox-line" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                            Belum ada aktivitas cuti
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <?php if (!is_array($activity)) continue; ?>
                            <?php $status = $activity['status'] ?? 'Menunggu'; ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $status === 'Menunggu' ? 'warning' : ($status === 'Disetujui' ? 'success' : 'danger') ?>">
                                    <i class="ri-file-list-3-line"></i>
                                </div>
                                <div style="flex:1;">
                                    <div class="activity-title"><?= escape($activity['jenis_cuti'] ?? 'Cuti') ?></div>
                                    <div class="activity-sub">
                                        Status: <span class="activity-status <?= $status === 'Menunggu' ? 'text-primary' : ($status === 'Disetujui' ? 'text-success' : 'text-danger') ?>"><?= $status ?></span>
                                        <span style="color:var(--clr-muted);font-size:12px;">(<?= $activity['durasi'] ?? 0 ?> hari)</span>
                                    </div>
                                    <?php if (!empty($activity['catatan_admin'])): ?>
                                        <div style="background:var(--clr-bg);padding:8px 12px;border-radius:var(--r-sm);margin-top:6px;font-size:12px;border-left:3px solid var(--clr-primary);">
                                            <strong style="color:var(--clr-muted);"> Catatan Admin:</strong>
                                            <span><?= escape($activity['catatan_admin']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($activity['dokumen'])): ?>
                                        <div style="margin-top:4px;">
                                            <a href="<?= escape($activity['dokumen']) ?>" target="_blank" style="font-size:12px;color:var(--clr-primary);text-decoration:underline;">
                                                <i class="ri-file-pdf-line"></i> Lihat Dokumen
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <div style="font-size:11px;color:var(--clr-muted);margin-top:2px;">
                                        <?= formatTanggalWaktu($activity['created_at'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <div class="card" style="padding:20px;text-align:center;background:var(--clr-bg);border:1px solid var(--clr-border);">
                    <i class="ri-information-line" style="font-size:32px;color:var(--clr-primary);display:block;margin-bottom:8px;"></i>
                    <p style="font-size:13px;color:var(--clr-muted);">Sistem manajemen cuti terintegrasi</p>
                    <div style="margin-top:8px;display:flex;justify-content:center;gap:16px;flex-wrap:wrap;">
                        <span style="font-size:12px;color:var(--clr-muted);"><span class="online-dot"></span> Online</span>
                        <span style="font-size:12px;color:var(--clr-muted);">Versi 2.4.0</span>
                    </div>
                </div>
                <div class="sys-info" style="margin-top:12px;">
                    <div class="sys-info-label">Informasi Sistem</div>
                    <div class="sys-info-item"><span class="online-dot"></span> Server Operasional</div>
                    <div class="sys-info-item" style="color:var(--clr-muted);font-size:12px;padding-left:16px;">Sisa Cuti: <?= $sisaCuti ?> hari</div>
                </div>
            </div>
        </div>

       
    </main>
</div>

<!-- MOBILE VIEW -->
<div id="mobile-dashboard-view" class="page-with-mobile-nav" style="display:none;">
    <div class="mobile-dashboard-header">
        <div class="mobile-greet">
            <small>Selamat Datang,</small>
            <h2><?= escape($user['name'] ?? 'User') ?></h2>
            <div style="font-size:12px;color:var(--clr-muted);"><?= escape($user['jabatan'] ?? '-') ?> · <?= escape($user['departemen'] ?? '-') ?></div>
        </div>
        <div class="mobile-avatar-wrap">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--clr-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
            </div>
            <span class="mobile-online"></span>
        </div>
    </div>
    
    <div class="mobile-search">
        <i class="ri-search-line"></i>
        <input type="text" placeholder="Cari pengajuan cuti...">
    </div>
    
    <div class="mobile-section-label">Aksi Cepat</div>
    <div class="mobile-main-action" onclick="document.getElementById('ajukan-cuti-mobile').scrollIntoView()">
        <div>
            <div class="plus-icon"><i class="ri-add-line"></i></div>
            <h3>Ajukan Cuti Baru</h3>
            <p>Sisa cuti: <?= $sisaCuti ?> hari</p>
        </div>
        <i class="ri-file-text-line mobile-action-bg-icon"></i>
    </div>
    
    <div class="mobile-sub-actions">
        <div class="mobile-sub-action" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i><span>Lihat Riwayat</span></div>
        <div class="mobile-sub-action" onclick="showToast('Panduan sedang diperbarui')"><i class="ri-question-line"></i><span>Panduan</span></div>
    </div>
    
    <div class="mobile-section-label">Status Cuti</div>
    <div class="mobile-status-row">
        <div class="mobile-status-item featured">
            <div class="mobile-status-left">
                <div class="mobile-status-icon amber-dark"><i class="ri-calendar-2-line"></i></div>
                <div>
                    <div class="mobile-status-label light">Sisa Cuti</div>
                    <div class="mobile-status-count light"><?= $sisaCuti ?></div>
                    <div class="mobile-status-sub light">Hari</div>
                </div>
            </div>
        </div>
        <div class="mobile-status-grid">
            <div class="mobile-stat-mini success"><div class="stat-num"><?= $stats['disetujui'] ?></div><div class="stat-label">Disetujui</div><div class="stat-sub">AKTIF</div></div>
            <div class="mobile-stat-mini danger"><div class="stat-num"><?= $stats['ditolak'] ?></div><div class="stat-label">Ditolak</div><div class="stat-sub">REVISI</div></div>
        </div>
    </div>
    
    <div id="ajukan-cuti-mobile" style="padding:0 16px;margin-bottom:20px;scroll-margin-top:20px;">
        <div class="mobile-section-label">Ajukan Cuti</div>
        <div class="card" style="padding:16px;">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="ajukan_cuti" value="1">
                <div class="form-group" style="margin-bottom:12px;">
                    <label class="form-label">Jenis Cuti</label>
                    <select name="jenis_cuti" class="form-control" required>
                        <option value="">Pilih jenis cuti</option>
                        <?php foreach ($jenisCutiList as $value => $label): ?>
                            <option value="<?= escape($value) ?>"><?= escape($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
                    <div class="form-group"><label class="form-label">Mulai</label><input type="date" name="tanggal_mulai" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Selesai</label><input type="date" name="tanggal_selesai" class="form-control" required></div>
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label class="form-label">Alasan</label>
                    <textarea name="alasan" class="form-control" rows="2" placeholder="Jelaskan alasan cuti..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-full"><i class="ri-send-plane-line"></i> Kirim Pengajuan</button>
            </form>
        </div>
    </div>
    
    <div class="mobile-activity-list">
        <div class="mobile-activity-header"><h4>Aktivitas Terakhir</h4><a class="see-all" href="riwayat.php">Lihat Semua</a></div>
        <?php if (empty($recentActivities)): ?>
            <div style="text-align:center;padding:20px;color:var(--clr-muted);background:var(--clr-surface);border-radius:var(--r-lg);margin:0 16px;">
                <i class="ri-inbox-line" style="font-size:32px;display:block;margin-bottom:8px;"></i> Belum ada aktivitas
            </div>
        <?php else: ?>
            <?php foreach ($recentActivities as $activity): ?>
                <?php if (!is_array($activity)) continue; ?>
                <div class="mobile-activity-item">
                    <div class="ma-icon <?= ($activity['status'] ?? 'Menunggu') === 'Menunggu' ? 'dark' : (($activity['status'] ?? '') === 'Disetujui' ? 'green' : 'amber') ?>">
                        <i class="ri-file-copy-2-line"></i>
                    </div>
                    <div>
                        <div class="ma-title"><?= escape($activity['jenis_cuti'] ?? 'Cuti') ?></div>
                        <div class="ma-sub">Status: <span style="font-weight:600;<?= ($activity['status'] ?? 'Menunggu') === 'Menunggu' ? 'color:var(--clr-primary);' : (($activity['status'] ?? '') === 'Disetujui' ? 'color:var(--clr-success);' : 'color:var(--clr-danger);') ?>"><?= $activity['status'] ?? 'Menunggu' ?></span></div>
                        <?php if (!empty($activity['catatan_admin'])): ?>
                            <div style="background:var(--clr-bg);padding:6px 10px;border-radius:var(--r-sm);margin-top:4px;font-size:11px;border-left:2px solid var(--clr-primary);">
                                <strong style="color:var(--clr-muted);">📝 Catatan:</strong> <?= escape($activity['catatan_admin']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($activity['dokumen'])): ?>
                            <a href="<?= escape($activity['dokumen']) ?>" target="_blank" style="font-size:11px;color:var(--clr-primary);text-decoration:underline;display:inline-block;margin-top:2px;">
                                <i class="ri-file-pdf-line"></i> Lihat Dokumen
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="ma-time"><?= formatTanggal($activity['created_at'] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div style="height:70px;"></div>
</div>

<!-- Mobile Nav -->
<nav class="mobile-nav-bar">
    <button class="mobile-nav-item active" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
    <button class="mobile-nav-item" onclick="document.getElementById('ajukan-cuti-mobile').scrollIntoView()"><i class="ri-add-circle-line"></i>Ajukan</button>
    <button class="mobile-nav-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i>Riwayat</button>
    <button class="mobile-nav-item" onclick="window.location.href='profile.php'"><i class="ri-user-line"></i>Profil</button>
</nav>

<?php include 'includes/footer.php'; ?>