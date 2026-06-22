<?php
/**
 * =====================================================
 * FILE: riwayat.php
 * FUNGSI: Riwayat Pengajuan Cuti
 * VERSION: 2.0 - Fixed
 * =====================================================
 */

require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Cek login
requireLogin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// =====================================================
// AMBIL DATA DARI FIREBASE (REST API)
// =====================================================

// 🔥 PERBAIKAN: Ambil SEMUA data, filter manual
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

// 🔥 DEBUG: Log data
error_log('=== RIWAYAT USER ===');
error_log('UID: ' . $uid);
error_log('Jumlah: ' . count($permohonan));
error_log(print_r($permohonan, true));

// Statistik
$stats = [
    'total' => 0,
    'menunggu' => 0,
    'disetujui' => 0,
    'ditolak' => 0,
    'selesai' => 0
];

if (is_array($permohonan) && !empty($permohonan)) {
    foreach ($permohonan as $izin) {
        if (!is_array($izin)) continue;
        $stats['total']++;
        $status = $izin['status'] ?? '';
        switch ($status) {
            case 'Menunggu': $stats['menunggu']++; break;
            case 'Disetujui': $stats['disetujui']++; break;
            case 'Ditolak': $stats['ditolak']++; break;
            case 'Selesai': $stats['selesai']++; break;
        }
    }
}

$sisaCuti = getSisaCuti($uid, $database);

$currentPage = 'riwayat';
include 'includes/header.php';
?>

<!-- =====================================================
     RIWAYAT CONTENT
     ===================================================== -->

<!-- Desktop History View -->
<div id="desktop-history-view" class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            Magang<span>.usg</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small>
        </div>
        <div class="sidebar-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i> Dashboard</div>
        <div class="sidebar-item" onclick="window.location.href='home.php#ajukan-cuti'"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
        <div class="sidebar-item active"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-bottom">
            <div class="sidebar-item"><i class="ri-settings-3-line"></i> Pengaturan</div>
            <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <div style="margin-bottom:24px;">
            <h1 style="font-family:var(--font-display);font-size:32px;font-weight:800;margin-bottom:6px;">Riwayat Cuti</h1>
            <p style="color:var(--clr-muted);font-size:14px;">Pantau status pengajuan cuti dan riwayat aktivitas Anda.</p>
            <div style="display:flex;gap:12px;margin-top:8px;flex-wrap:wrap;">
                <span style="background:var(--clr-bg);padding:4px 12px;border-radius:var(--r-sm);font-size:13px;">
                    Sisa Cuti: <strong><?= $sisaCuti ?> hari</strong>
                </span>
                <span style="background:var(--clr-bg);padding:4px 12px;border-radius:var(--r-sm);font-size:13px;">
                    Total Pengajuan: <strong><?= $stats['total'] ?></strong>
                </span>
            </div>
        </div>

        <div class="history-layout">
            <!-- Left: stats + active detail -->
            <div>
                <div class="history-stat-card">
                    <h4>Statistik Pengajuan</h4>
                    <div class="hist-stat-row">
                        <span class="hist-stat-label">Total Diajukan</span>
                        <span class="hist-stat-value"><?= $stats['total'] ?></span>
                    </div>
                    <div class="hist-stat-row">
                        <span class="hist-stat-label">Menunggu</span>
                        <span class="hist-stat-value text-primary"><?= $stats['menunggu'] ?></span>
                    </div>
                    <div class="hist-stat-row">
                        <span class="hist-stat-label">Disetujui</span>
                        <span class="hist-stat-value success"><?= $stats['disetujui'] ?></span>
                    </div>
                    <div class="hist-stat-row">
                        <span class="hist-stat-label">Ditolak</span>
                        <span class="hist-stat-value danger"><?= $stats['ditolak'] ?></span>
                    </div>
                </div>
                
                <button class="btn btn-primary btn-full" style="margin-bottom:20px;" onclick="window.location.href='home.php#ajukan-cuti'">
                    <i class="ri-add-line"></i> Ajukan Cuti Baru
                </button>

                <!-- Detail Pengajuan Aktif -->
                <?php 
                $activeRequest = null;
                if (is_array($permohonan) && !empty($permohonan)) {
                    foreach ($permohonan as $key => $izin) {
                        if (!is_array($izin)) continue;
                        if (($izin['status'] ?? '') === 'Menunggu') {
                            $izin['_key'] = $key;
                            $activeRequest = $izin;
                            break;
                        }
                    }
                }
                ?>
                
                <?php if ($activeRequest): ?>
                <div class="active-detail-card">
                    <div class="detail-card-header">
                        <div>
                            <span class="badge badge-warning" style="margin-bottom:8px;">Menunggu</span>
                            <h4><?= escape($activeRequest['jenis_cuti'] ?? 'Cuti') ?></h4>
                        </div>
                    </div>
                    <ul class="timeline">
                        <li class="timeline-item">
                            <div class="timeline-indicator">
                                <div class="timeline-dot"></div>
                                <div class="timeline-line"></div>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">Pengajuan Dikirim</div>
                                <div class="timeline-sub"><?= formatTanggalWaktu($activeRequest['created_at'] ?? '') ?></div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-indicator">
                                <div class="timeline-dot inactive"></div>
                                <div class="timeline-line"></div>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title" style="color:rgba(255,255,255,.5);">Review HRD</div>
                                <div class="timeline-sub">Menunggu persetujuan</div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-indicator">
                                <div class="timeline-dot inactive"></div>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title" style="color:rgba(255,255,255,.5);">Keputusan Akhir</div>
                                <div class="timeline-sub">Estimasi 2 hari kerja</div>
                            </div>
                        </li>
                    </ul>
                    <div class="detail-btns">
                        <button class="btn btn-outline" style="border-color:rgba(255,255,255,.2);color:#fff;flex:1;" onclick="showToast('Mengunduh bukti pengajuan...')">
                            Unduh Bukti
                        </button>
                        <button class="btn btn-gold" style="flex:1;" onclick="showToast('Menghubungi HRD...')">
                            Bantuan
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="active-detail-card" style="text-align:center;padding:32px;">
                    <div style="font-size:48px;margin-bottom:12px;">📋</div>
                    <h4 style="color:#fff;font-size:18px;">Tidak Ada Pengajuan Aktif</h4>
                    <p style="color:rgba(255,255,255,.5);font-size:13px;margin-top:4px;">Semua pengajuan cuti Anda sudah selesai diproses</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: table -->
            <div>
                <div class="section-card">
                    <div class="section-card-header">
                        <div>
                            <h3>Daftar Pengajuan</h3>
                        </div>
                        <div class="section-card-actions">
                            <div class="history-filter-tabs">
                                <button class="filter-tab active" data-filter="all">Semua</button>
                                <button class="filter-tab" data-filter="Menunggu">Menunggu</button>
                                <button class="filter-tab" data-filter="Disetujui">Disetujui</button>
                                <button class="filter-tab" data-filter="Ditolak">Ditolak</button>
                            </div>
                        </div>
                    </div>
                    <table class="data-table" id="history-table">
                        <thead>
                            <tr>
                                <th>No. Pengajuan</th>
                                <th>Jenis Cuti</th>
                                <th>Tanggal</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($permohonan)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:40px;color:var(--clr-muted);">
                                        <i class="ri-inbox-line" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                        Belum ada pengajuan cuti
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_reverse($permohonan) as $key => $izin): ?>
                                    <?php if (!is_array($izin)) continue; ?>
                                    <tr class="history-row" data-status="<?= $izin['status'] ?? 'Menunggu' ?>">
                                        <td style="font-weight:600;">#<?= escape($izin['id'] ?? 'CUT-' . substr($key, -4)) ?></td>
                                        <td><i class="ri-calendar-2-line" style="margin-right:6px;color:var(--clr-muted);"></i><?= escape($izin['jenis_cuti'] ?? '-') ?></td>
                                        <td><?= formatTanggal($izin['created_at'] ?? '') ?></td>
                                        <td><?= $izin['durasi'] ?? 0 ?> hari</td>
                                        <td><?= getStatusBadge($izin['status'] ?? 'Menunggu') ?></td>
                                        <td>
                                            <div class="history-action-icon" onclick="showToast('Detail pengajuan #<?= escape($izin['id'] ?? '') ?>')">
                                                <i class="ri-eye-line"></i>
                                            </div>
                                            <?php if (isset($izin['dokumen']) && $izin['dokumen']): ?>
                                            <div class="history-action-icon" onclick="window.open('<?= escape($izin['dokumen']) ?>', '_blank')">
                                                <i class="ri-download-2-line"></i>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-pagination">
                        <span>Menampilkan <?= count($permohonan ?? []) ?> dari <?= $stats['total'] ?> riwayat</span>
                        <div class="pagination-btns">
                            <button class="page-btn"><i class="ri-arrow-left-s-line"></i></button>
                            <button class="page-btn active">1</button>
                            <button class="page-btn"><i class="ri-arrow-right-s-line"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="help-section">
            <div class="help-section-left">
                <div class="help-section-icon"><i class="ri-customer-service-2-line"></i></div>
                <div class="help-section-text">
                    <h4>Butuh bantuan pengajuan?</h4>
                    <p>Layanan HRD kami tersedia setiap hari kerja pukul 08:00 - 16:00 WIB.</p>
                </div>
            </div>
            <div class="help-section-btns">
                <button class="btn btn-outline" onclick="showToast('Membuka panduan...')">Lihat Panduan</button>
                <button class="btn btn-gold" onclick="showToast('Menghubungi HRD...')">Hubungi HRD</button>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>
</div>

<!-- =====================================================
     MOBILE HISTORY VIEW
     ===================================================== -->
<div id="mobile-history-view" style="display:none;" class="page-with-mobile-nav">
    <div style="padding:20px 16px 8px;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h2 style="font-family:var(--font-display);font-size:22px;font-weight:700;">Riwayat Cuti</h2>
            <div style="font-size:12px;color:var(--clr-muted);">Sisa cuti: <?= $sisaCuti ?> hari</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <div class="notif-btn" onclick="showToast('Tidak ada notifikasi baru')">
                <i class="ri-notification-3-line"></i>
            </div>
            <div class="topbar-avatar" style="background:var(--clr-primary);width:36px;height:36px;">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
            </div>
        </div>
    </div>
    
    <div class="mobile-search" style="margin-bottom:16px;">
        <i class="ri-search-line"></i>
        <input type="text" placeholder="Cari pengajuan cuti..." id="mobile-search-history">
    </div>

    <?php if (empty($permohonan)): ?>
        <div style="text-align:center;padding:40px 16px;color:var(--clr-muted);">
            <i class="ri-inbox-line" style="font-size:48px;display:block;margin-bottom:12px;"></i>
            <h4 style="font-size:16px;font-weight:600;">Belum Ada Pengajuan</h4>
            <p style="font-size:13px;">Mulai ajukan cuti Anda sekarang</p>
            <button class="btn btn-primary" style="margin-top:12px;" onclick="window.location.href='home.php#ajukan-cuti-mobile'">
                Ajukan Cuti
            </button>
        </div>
    <?php else: ?>
        <?php foreach (array_reverse($permohonan) as $key => $izin): ?>
            <?php if (!is_array($izin)) continue; ?>
            <div class="mobile-hist-card">
                <div class="mobile-hist-header">
                    <span class="mobile-hist-id">#<?= escape($izin['id'] ?? 'CUT-' . substr($key, -4)) ?></span>
                    <?= getStatusBadge($izin['status'] ?? 'Menunggu') ?>
                </div>
                <div class="mobile-hist-title"><?= escape($izin['jenis_cuti'] ?? 'Cuti') ?></div>
                <div class="mobile-hist-meta">
                    <span><i class="ri-calendar-line"></i> <?= formatTanggal($izin['created_at'] ?? '') ?></span>
                    <span><i class="ri-time-line"></i> <?= $izin['durasi'] ?? 0 ?> hari</span>
                </div>
                <?php if (isset($izin['catatan']) && $izin['catatan']): ?>
                    <div style="margin-top:8px;font-size:12px;color:var(--clr-muted);background:var(--clr-bg);padding:8px;border-radius:var(--r-sm);">
                        <i class="ri-chat-3-line"></i> <?= escape($izin['catatan']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Help Card Mobile -->
    <div style="margin:16px;background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--r-lg);padding:24px;text-align:center;">
        <div style="width:48px;height:48px;background:var(--clr-dark);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--clr-primary);margin:0 auto 12px;">
            <i class="ri-question-line"></i>
        </div>
        <h4 style="font-weight:700;margin-bottom:8px;">Butuh bantuan?</h4>
        <p style="font-size:13px;color:var(--clr-muted);margin-bottom:16px;line-height:1.6;">Mengalami kendala dalam pengecekan status cuti? Tim HRD siap membantu Anda.</p>
        <button class="btn btn-gold btn-full" style="margin-bottom:10px;" onclick="showToast('Menghubungi HRD...')">
            <i class="ri-customer-service-2-line"></i> Chat HRD
        </button>
        <button class="btn btn-outline btn-full" onclick="showToast('Membuka panduan...')">
            <i class="ri-file-text-line"></i> Baca Panduan
        </button>
    </div>

    <div style="text-align:center;padding:16px;font-size:12px;color:var(--clr-muted);">
        Magang.usg<br>© <?= date('Y') ?> Magang.usg - Manajemen Cuti Karyawan
    </div>
    <div style="height:70px;"></div>
</div>

<!-- Mobile Nav -->
<nav class="mobile-nav-bar">
    <button class="mobile-nav-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
    <button class="mobile-nav-item" onclick="window.location.href='home.php#ajukan-cuti-mobile'"><i class="ri-add-circle-line"></i>Ajukan</button>
    <button class="mobile-nav-item active"><i class="ri-history-line"></i>Riwayat</button>
    <button class="mobile-nav-item"><i class="ri-settings-3-line"></i>Akun</button>
</nav>

<!-- Global Toast -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
// Filter history
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        const rows = document.querySelectorAll('.history-row');
        
        rows.forEach(row => {
            if (filter === 'all' || row.dataset.status === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// Search mobile history
document.getElementById('mobile-search-history')?.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.mobile-hist-card').forEach(card => {
        const title = card.querySelector('.mobile-hist-title')?.textContent.toLowerCase() || '';
        const id = card.querySelector('.mobile-hist-id')?.textContent.toLowerCase() || '';
        card.style.display = (title.includes(query) || id.includes(query)) ? '' : 'none';
    });
});

// Show toast function
function showToast(msg, icon) {
    if (typeof icon === 'undefined') icon = 'ri-information-line';
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = `<i class="${icon}"></i> ${msg}`;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>