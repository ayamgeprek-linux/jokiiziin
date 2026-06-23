<?php
/**
 * =====================================================
 * FILE: riwayat.php
 * FUNGSI: Riwayat Pengajuan Cuti
 * VERSION: 5.0 - Mobile Fully Fixed
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
// AMBIL DATA DARI FIREBASE
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
     STYLE KHUSUS MOBILE
     ===================================================== -->
<style>
/* 🔥 FIX MOBILE RIWAYAT */
@media (max-width: 768px) {
    /* Container utama */
    .mobile-riwayat-container {
        padding: 0 12px 20px;
        overflow-x: hidden;
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Card riwayat */
    .mobile-hist-card {
        width: 100%;
        box-sizing: border-box;
        margin: 0 0 12px 0;
        padding: 14px 16px;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-lg);
        cursor: pointer;
        transition: all .2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .mobile-hist-card:active {
        transform: scale(0.98);
        background: var(--clr-bg);
    }
    
    /* Header card */
    .mobile-hist-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
        flex-wrap: wrap;
        gap: 4px;
    }
    .mobile-hist-id {
        font-size: 11px;
        font-weight: 700;
        color: var(--clr-muted);
        font-family: monospace;
    }
    .mobile-hist-header .badge {
        font-size: 9px;
        padding: 2px 10px;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    /* Judul */
    .mobile-hist-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 4px;
        word-wrap: break-word;
        color: var(--clr-dark);
    }
    
    /* Meta info */
    .mobile-hist-meta {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
    }
    .mobile-hist-meta span {
        font-size: 11px;
        color: var(--clr-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .mobile-hist-meta span i {
        font-size: 13px;
    }
    
    /* Catatan admin */
    .admin-note-mobile {
        margin-top: 8px;
        font-size: 11px;
        color: var(--clr-muted);
        background: var(--clr-bg);
        padding: 8px 12px;
        border-radius: var(--r-sm);
        border-left: 3px solid var(--clr-primary);
        word-wrap: break-word;
        line-height: 1.5;
    }
    .admin-note-mobile strong {
        color: var(--clr-dark);
    }
    
    /* Link dokumen */
    .doc-link-mobile {
        margin-top: 6px;
        display: inline-block;
        font-size: 12px;
        color: var(--clr-primary);
        text-decoration: none;
        word-wrap: break-word;
        font-weight: 500;
        padding: 4px 0;
    }
    .doc-link-mobile:hover {
        text-decoration: underline;
    }
    
    /* Empty state */
    .empty-state-mobile {
        text-align: center;
        padding: 40px 20px;
        color: var(--clr-muted);
    }
    .empty-state-mobile i {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
        color: var(--clr-border);
    }
    .empty-state-mobile h4 {
        font-size: 16px;
        font-weight: 600;
        color: var(--clr-dark);
        margin-bottom: 4px;
    }
    .empty-state-mobile p {
        font-size: 13px;
        margin-bottom: 16px;
    }
    
    /* Search bar */
    .mobile-search-riwayat {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--clr-bg);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-md);
        padding: 10px 14px;
        margin: 0 12px 16px;
        font-size: 14px;
        color: var(--clr-muted);
    }
    .mobile-search-riwayat input {
        background: none;
        border: none;
        flex: 1;
        font-size: 14px;
        color: var(--clr-dark);
        outline: none;
    }
    .mobile-search-riwayat input::placeholder {
        color: #aaa;
    }
    
    /* Header mobile */
    .mobile-riwayat-header {
        padding: 16px 16px 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .mobile-riwayat-header h2 {
        font-family: var(--font-display);
        font-size: 22px;
        font-weight: 700;
        margin: 0;
    }
    .mobile-riwayat-header .sub-info {
        font-size: 12px;
        color: var(--clr-muted);
        margin-top: 2px;
    }
    .mobile-riwayat-header .avatar-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .mobile-riwayat-header .avatar-wrapper .notif-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--clr-bg);
        border: 1px solid var(--clr-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
    }
    .mobile-riwayat-header .avatar-wrapper .avatar-small {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--clr-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        border: 2px solid rgba(255,255,255,0.2);
    }
    
    /* Fix overflow */
    body {
        overflow-x: hidden;
        width: 100%;
    }
    .page-with-mobile-nav .main-content {
        padding-bottom: 80px;
    }
}
</style>

<!-- =====================================================
     DESKTOP HISTORY VIEW
     ===================================================== -->
<div id="desktop-history-view" class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            Magang<span>.usg</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small>
        </div>
        <div class="sidebar-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i> Dashboard</div>
        <div class="sidebar-item" onclick="window.location.href='home.php#ajukan-cuti'"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
        <div class="sidebar-item active"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='profile.php'"><i class="ri-user-line"></i> Profil</div>
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
            <div>
                <div class="history-stat-card">
                    <h4>Statistik Pengajuan</h4>
                    <div class="hist-stat-row"><span class="hist-stat-label">Total Diajukan</span><span class="hist-stat-value"><?= $stats['total'] ?></span></div>
                    <div class="hist-stat-row"><span class="hist-stat-label">Menunggu</span><span class="hist-stat-value text-primary"><?= $stats['menunggu'] ?></span></div>
                    <div class="hist-stat-row"><span class="hist-stat-label">Disetujui</span><span class="hist-stat-value success"><?= $stats['disetujui'] ?></span></div>
                    <div class="hist-stat-row"><span class="hist-stat-label">Ditolak</span><span class="hist-stat-value danger"><?= $stats['ditolak'] ?></span></div>
                </div>
                <button class="btn btn-primary btn-full" style="margin-bottom:20px;" onclick="window.location.href='home.php#ajukan-cuti'">
                    <i class="ri-add-line"></i> Ajukan Cuti Baru
                </button>
            </div>

            <div>
                <div class="section-card">
                    <div class="section-card-header">
                        <div><h3>Daftar Pengajuan</h3></div>
                        <div class="history-filter-tabs">
                            <button class="filter-tab active" data-filter="all">Semua</button>
                            <button class="filter-tab" data-filter="Menunggu">Menunggu</button>
                            <button class="filter-tab" data-filter="Disetujui">Disetujui</button>
                            <button class="filter-tab" data-filter="Ditolak">Ditolak</button>
                        </div>
                    </div>
                    <table class="data-table" id="history-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Jenis Cuti</th>
                                <th>Tanggal</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($permohonan)): ?>
                                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--clr-muted);">Belum ada pengajuan cuti</td></tr>
                            <?php else: ?>
                                <?php foreach (array_reverse($permohonan) as $key => $izin): ?>
                                    <?php if (!is_array($izin)) continue; ?>
                                    <tr class="history-row" data-status="<?= $izin['status'] ?? 'Menunggu' ?>">
                                        <td style="font-weight:600;">#<?= escape($izin['id'] ?? 'CUT-' . substr($key, -4)) ?></td>
                                        <td><?= escape($izin['jenis_cuti'] ?? '-') ?></td>
                                        <td><?= formatTanggal($izin['created_at'] ?? '') ?></td>
                                        <td><?= $izin['durasi'] ?? 0 ?> hari</td>
                                        <td><?= getStatusBadge($izin['status'] ?? 'Menunggu') ?></td>
                                        <td>
                                            <div class="history-action-icon" onclick="showDetail('<?= $key ?>')">
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

        
    </main>
</div>

<!-- =====================================================
     MOBILE HISTORY VIEW - FULLY FIXED
     ===================================================== -->
<div id="mobile-history-view" style="display:none;" class="page-with-mobile-nav">

    <!-- HEADER -->
    <div class="mobile-riwayat-header">
        <div>
            <h2>📋 Riwayat Cuti</h2>
            <div class="sub-info">Sisa cuti: <strong><?= $sisaCuti ?></strong> hari · Total: <strong><?= $stats['total'] ?></strong></div>
        </div>
        <div class="avatar-wrapper">
            <div class="notif-btn" onclick="showToast('Tidak ada notifikasi baru')">
                <i class="ri-notification-3-line"></i>
            </div>
            <div class="avatar-small" onclick="window.location.href='profile.php'">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
            </div>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="mobile-search-riwayat">
        <i class="ri-search-line" style="color:var(--clr-muted);"></i>
        <input type="text" placeholder="Cari pengajuan cuti..." id="mobile-search-history">
    </div>

    <!-- LIST CARD -->
    <div class="mobile-riwayat-container">
        <?php if (empty($permohonan)): ?>
            <div class="empty-state-mobile">
                <i class="ri-inbox-line"></i>
                <h4>Belum Ada Pengajuan</h4>
                <p>Mulai ajukan cuti Anda sekarang</p>
                <button class="btn btn-primary" onclick="window.location.href='home.php#ajukan-cuti-mobile'">
                    Ajukan Cuti
                </button>
            </div>
        <?php else: ?>
            <?php foreach (array_reverse($permohonan) as $key => $izin): ?>
                <?php if (!is_array($izin)) continue; ?>
                <div class="mobile-hist-card" onclick="showDetail('<?= $key ?>')">
                    
                    <!-- Header Card -->
                    <div class="mobile-hist-header">
                        <span class="mobile-hist-id">#<?= escape($izin['id'] ?? 'CUT-' . substr($key, -4)) ?></span>
                        <?= getStatusBadge($izin['status'] ?? 'Menunggu') ?>
                    </div>
                    
                    <!-- Judul -->
                    <div class="mobile-hist-title"><?= escape($izin['jenis_cuti'] ?? 'Cuti') ?></div>
                    
                    <!-- Meta -->
                    <div class="mobile-hist-meta">
                        <span><i class="ri-calendar-line"></i> <?= formatTanggal($izin['tanggal_mulai'] ?? '') ?></span>
                        <span><i class="ri-time-line"></i> <?= $izin['durasi'] ?? 0 ?> hari</span>
                    </div>
                    
                    <!-- Catatan Admin -->
                    <?php if (!empty($izin['catatan_admin'])): ?>
                        <div class="admin-note-mobile">
                            <i class="ri-chat-3-line"></i> <strong>Catatan Admin:</strong> <?= escape($izin['catatan_admin']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Dokumen -->
                    <?php if (!empty($izin['dokumen'])): ?>
                        <a href="<?= escape($izin['dokumen']) ?>" target="_blank" class="doc-link-mobile" onclick="event.stopPropagation();">
                            <i class="ri-file-pdf-line"></i> Lihat Dokumen
                        </a>
                    <?php endif; ?>
                    
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- HELP CARD -->
    <div style="margin:0 12px 20px;background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--r-lg);padding:20px;text-align:center;">
        <div style="width:48px;height:48px;background:var(--clr-dark);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--clr-primary);margin:0 auto 12px;">
            <i class="ri-question-line"></i>
        </div>
        <h4 style="font-weight:700;font-size:15px;margin-bottom:6px;">Butuh bantuan?</h4>
        <p style="font-size:12px;color:var(--clr-muted);margin-bottom:14px;line-height:1.5;">Tim HRD siap membantu Anda</p>
        <button class="btn btn-gold btn-full" style="margin-bottom:8px;font-size:13px;padding:10px;" onclick="showToast('Menghubungi HRD...')">
            <i class="ri-customer-service-2-line"></i> Chat HRD
        </button>
        <button class="btn btn-outline btn-full" style="font-size:13px;padding:10px;" onclick="showToast('Membuka panduan...')">
            <i class="ri-file-text-line"></i> Baca Panduan
        </button>
    </div>

    <!-- FOOTER MOBILE -->
    <div style="text-align:center;padding:12px 16px 30px;font-size:11px;color:var(--clr-muted);">
        Magang.usg<br>© <?= date('Y') ?> Magang.usg
    </div>

</div>

<!-- =====================================================
     MODAL DETAIL
     ===================================================== -->
<div id="detail-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:var(--r-xl);max-width:500px;width:100%;padding:28px;box-shadow:var(--shadow-lg);max-height:80vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-family:var(--font-display);font-size:18px;font-weight:700;">Detail Pengajuan</h3>
            <button onclick="closeDetailModal()" style="background:var(--clr-bg);border:1px solid var(--clr-border);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div id="detail-content"><p style="text-align:center;color:var(--clr-muted);">Loading...</p></div>
    </div>
</div>

<!-- =====================================================
     MOBILE NAV
     ===================================================== -->
<nav class="mobile-nav-bar">
    <button class="mobile-nav-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
    <button class="mobile-nav-item" onclick="window.location.href='home.php#ajukan-cuti-mobile'"><i class="ri-add-circle-line"></i>Ajukan</button>
    <button class="mobile-nav-item active"><i class="ri-history-line"></i>Riwayat</button>
    <button class="mobile-nav-item" onclick="window.location.href='profile.php'"><i class="ri-user-line"></i>Profil</button>
</nav>

<!-- Global Toast -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
const allData = <?= json_encode($permohonan) ?>;

function showDetail(key) {
    const data = allData[key];
    if (!data) {
        showToast('Data tidak ditemukan', 'ri-error-warning-line');
        return;
    }
    
    const html = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;">
            <div><strong>ID</strong><br>${data.id || '-'}</div>
            <div><strong>Status</strong><br>${data.status || '-'}</div>
            <div><strong>Nama</strong><br>${data.user_name || '-'}</div>
            <div><strong>NIP</strong><br>${data.nip || '-'}</div>
            <div><strong>Jabatan</strong><br>${data.jabatan || '-'}</div>
            <div><strong>Departemen</strong><br>${data.departemen || '-'}</div>
            <div><strong>Jenis Cuti</strong><br>${data.jenis_cuti || '-'}</div>
            <div><strong>Durasi</strong><br>${data.durasi || 0} hari</div>
            <div style="grid-column:span 2;"><strong>Tanggal</strong><br>${data.tanggal_mulai || '-'} s/d ${data.tanggal_selesai || '-'}</div>
            <div style="grid-column:span 2;"><strong>Alasan</strong><br>${data.alasan || '-'}</div>
            ${data.catatan_admin ? `
                <div style="grid-column:span 2;background:#f8f5f0;padding:10px 12px;border-radius:var(--r-sm);border-left:3px solid var(--clr-primary);">
                    <strong style="color:var(--clr-muted);">📝 Catatan Admin:</strong>
                    <div style="margin-top:4px;">${data.catatan_admin}</div>
                </div>
            ` : ''}
            ${data.reviewed_by ? `<div><strong>Reviewer</strong><br>${data.reviewed_by}</div>` : ''}
            ${data.reviewed_at ? `<div><strong>Tanggal Review</strong><br>${data.reviewed_at}</div>` : ''}
            <div style="grid-column:span 2;"><strong>Tanggal Pengajuan</strong><br>${data.created_at || '-'}</div>
            ${data.dokumen ? `
                <div style="grid-column:span 2;margin-top:4px;">
                    <a href="${data.dokumen}" target="_blank" class="btn btn-outline btn-sm" style="width:100%;text-align:center;font-size:12px;">
                        <i class="ri-file-pdf-line"></i> Lihat Dokumen
                    </a>
                </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('detail-content').innerHTML = html;
    document.getElementById('detail-modal').style.display = 'flex';
}

function closeDetailModal() {
    document.getElementById('detail-modal').style.display = 'none';
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('detail-modal');
    if (e.target === modal) closeDetailModal();
});

// Filter history
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        document.querySelectorAll('.history-row').forEach(row => {
            row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
        });
    });
});

// Search mobile
document.getElementById('mobile-search-history')?.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.mobile-hist-card').forEach(card => {
        const title = card.querySelector('.mobile-hist-title')?.textContent.toLowerCase() || '';
        const id = card.querySelector('.mobile-hist-id')?.textContent.toLowerCase() || '';
        card.style.display = (title.includes(query) || id.includes(query)) ? '' : 'none';
    });
});

function showToast(msg, icon = 'ri-information-line') {
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = `<i class="${icon}"></i> ${msg}`;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>