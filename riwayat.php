<?php
/**
 * =====================================================
 * FILE: riwayat.php
 * FUNGSI: Riwayat Pengajuan Cuti
 * VERSION: 8.1 - With Date Filter
 * =====================================================
 */

require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

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

// =====================================================
// 🔥 FILTER TANGGAL (TAMBAHAN)
// =====================================================
$filterTanggal = $_GET['tanggal'] ?? '';
$filterBulan = $_GET['bulan'] ?? '';

// Data untuk ditampilkan (dengan filter)
$displayData = [];
foreach ($permohonan as $key => $izin) {
    if (!is_array($izin)) continue;
    
    $created = substr($izin['created_at'] ?? '', 0, 10);
    $bulan = substr($izin['created_at'] ?? '', 0, 7);
    
    $match = true;
    if (!empty($filterTanggal) && $created !== $filterTanggal) {
        $match = false;
    }
    if (!empty($filterBulan) && $bulan !== $filterBulan) {
        $match = false;
    }
    
    if ($match) {
        $displayData[$key] = $izin;
    }
}

// Jika tidak ada filter, tampilkan semua
if (empty($filterTanggal) && empty($filterBulan)) {
    $displayData = $permohonan;
}

$stats = [
    'total'    => 0,
    'menunggu' => 0,
    'disetujui'=> 0,
    'ditolak'  => 0,
    'selesai'  => 0
];

if (is_array($displayData) && !empty($displayData)) {
    foreach ($displayData as $izin) {
        if (!is_array($izin)) continue;
        $stats['total']++;
        $status = $izin['status'] ?? '';
        switch ($status) {
            case 'Menunggu':  $stats['menunggu']++;  break;
            case 'Disetujui': $stats['disetujui']++; break;
            case 'Ditolak':   $stats['ditolak']++;   break;
            case 'Selesai':   $stats['selesai']++;   break;
        }
    }
}

$sisaCuti = getSisaCuti($uid, $database);

$currentPage = 'riwayat';
include 'includes/header.php';
?>

<style>
/* ===== RESPONSIVE SHOW/HIDE ===== */
@media (min-width: 769px) {
    #mobile-history-view { display: none !important; }
}
@media (max-width: 768px) {
    #desktop-history-view { display: none !important; }
}

/* ===== FILTER TANGGAL ===== */
.filter-date-container {
    background: var(--clr-bg);
    border: 1px solid var(--clr-border);
    border-radius: var(--r-md);
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.filter-date-container label {
    font-size: 12px;
    color: var(--clr-muted);
    font-weight: 600;
}
.filter-date-container input[type="date"],
.filter-date-container input[type="month"] {
    padding: 6px 10px;
    border: 1px solid var(--clr-border);
    border-radius: var(--r-sm);
    font-size: 13px;
    background: #fff;
    color: var(--clr-dark);
}
.filter-date-container .btn-filter {
    padding: 6px 16px;
    background: var(--clr-primary);
    color: #fff;
    border: none;
    border-radius: var(--r-sm);
    font-size: 13px;
    cursor: pointer;
    font-weight: 600;
}
.filter-date-container .btn-filter:hover {
    background: var(--clr-primary-dark);
}
.filter-date-container .btn-reset {
    padding: 6px 16px;
    background: var(--clr-bg);
    color: var(--clr-dark);
    border: 1px solid var(--clr-border);
    border-radius: var(--r-sm);
    font-size: 13px;
    cursor: pointer;
    font-weight: 600;
}
.filter-date-container .btn-reset:hover {
    background: #e0e0e0;
}
.filter-date-container .filter-badge {
    font-size: 12px;
    color: var(--clr-muted);
    background: #fff;
    padding: 3px 10px;
    border-radius: 12px;
    border: 1px solid var(--clr-border);
}
@media (max-width: 768px) {
    .filter-date-container {
        margin: 0 12px 14px;
        padding: 10px 12px;
    }
    .filter-date-container input[type="date"],
    .filter-date-container input[type="month"] {
        font-size: 12px;
        padding: 5px 8px;
        width: 100%;
    }
    .filter-date-container .btn-filter,
    .filter-date-container .btn-reset {
        font-size: 12px;
        padding: 5px 12px;
    }
}

/* ===== MOBILE RIWAYAT ===== */
@media (max-width: 768px) {
    * { box-sizing: border-box; }
    body { overflow-x: hidden; width: 100%; }
    #mobile-history-view {
        display: block !important;
        width: 100%;
        overflow-x: hidden;
        padding-bottom: 80px;
    }
    .mobile-riwayat-header {
        padding: 16px 16px 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .mobile-riwayat-header h2 { font-size: 20px; font-weight: 700; margin: 0; }
    .mobile-riwayat-header .sub-info { font-size: 12px; color: var(--clr-muted); margin-top: 2px; }
    .mobile-riwayat-header .avatar-wrapper { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .notif-btn {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--clr-bg);
        border: 1px solid var(--clr-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        flex-shrink: 0;
    }
    .avatar-small {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--clr-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        flex-shrink: 0;
    }
    .mobile-search-riwayat {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--clr-bg);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-md);
        padding: 10px 14px;
        margin: 0 12px 14px;
        width: calc(100% - 24px);
    }
    .mobile-search-riwayat input {
        background: none;
        border: none;
        flex: 1;
        font-size: 14px;
        color: var(--clr-dark);
        outline: none;
        min-width: 0;
    }
    .mobile-search-riwayat input::placeholder { color: #aaa; }
    .mobile-riwayat-container { padding: 0 12px; width: 100%; }
    .mobile-hist-card {
        width: 100%;
        margin: 0 0 10px 0;
        padding: 14px;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-lg);
        cursor: pointer;
        transition: all .15s;
    }
    .mobile-hist-card:active { transform: scale(0.98); background: var(--clr-bg); }
    .mobile-hist-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
        gap: 6px;
    }
    .mobile-hist-id {
        font-size: 11px;
        font-weight: 700;
        color: var(--clr-muted);
        font-family: monospace;
        flex-shrink: 0;
    }
    .mobile-hist-header .badge {
        font-size: 9px;
        padding: 3px 10px;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        flex-shrink: 0;
    }
    .mobile-hist-title {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 5px;
        word-break: break-word;
        color: var(--clr-dark);
        line-height: 1.3;
    }
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
        gap: 3px;
    }
    .mobile-hist-meta span i { font-size: 13px; }
    .admin-note-mobile {
        margin-top: 8px;
        font-size: 11px;
        color: var(--clr-muted);
        background: var(--clr-bg);
        padding: 8px 10px;
        border-radius: var(--r-sm);
        border-left: 3px solid var(--clr-primary);
        word-break: break-word;
        line-height: 1.5;
    }
    .admin-note-mobile strong { color: var(--clr-dark); }
    .doc-link-mobile {
        margin-top: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: var(--clr-primary);
        text-decoration: none;
        font-weight: 500;
        padding: 4px 0;
    }
    .empty-state-mobile {
        text-align: center;
        padding: 40px 20px;
        color: var(--clr-muted);
    }
    .empty-state-mobile i { font-size: 48px; display: block; margin-bottom: 12px; color: var(--clr-border); }
    .empty-state-mobile h4 { font-size: 16px; font-weight: 600; color: var(--clr-dark); margin-bottom: 4px; }
    .empty-state-mobile p { font-size: 13px; margin-bottom: 16px; }
    .mobile-stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        padding: 0 12px;
        margin-bottom: 14px;
        width: 100%;
    }
    .mobile-stat-card {
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-md);
        padding: 10px 12px;
        text-align: center;
    }
    .mobile-stat-card .stat-val {
        font-size: 22px;
        font-weight: 800;
        line-height: 1.1;
        color: var(--clr-dark);
    }
    .mobile-stat-card .stat-lbl { font-size: 10px; color: var(--clr-muted); margin-top: 2px; }
    .mobile-stat-card.primary .stat-val { color: var(--clr-primary); }
    .mobile-stat-card.success .stat-val { color: var(--clr-success, #22c55e); }
    .mobile-stat-card.danger  .stat-val { color: var(--clr-danger,  #ef4444); }
    .mobile-ajukan-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin: 0 12px 16px;
        padding: 11px 16px;
        width: calc(100% - 24px);
        background: var(--clr-primary);
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: var(--r-md);
        cursor: pointer;
        transition: opacity .15s;
    }
    .mobile-ajukan-btn:active { opacity: 0.85; }
    .mobile-section-title {
        padding: 0 12px;
        font-size: 13px;
        font-weight: 700;
        color: var(--clr-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .mobile-help-card {
        margin: 16px 12px 20px;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-lg);
        padding: 20px 16px;
        text-align: center;
        width: calc(100% - 24px);
    }
    .mobile-footer-txt { text-align: center; padding: 8px 16px 30px; font-size: 11px; color: var(--clr-muted); }
    .page-with-mobile-nav .main-content { padding-bottom: 80px; }
}

/* ===== MODAL MODERN ===== */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-overlay.active {
    display: flex;
}
.modal-box {
    background: #fff;
    border-radius: 16px;
    max-width: 500px;
    width: 100%;
    padding: 0;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-height: 80vh;
    overflow: hidden;
    animation: modalIn 0.3s ease;
}
@keyframes modalIn {
    from { transform: scale(0.9) translateY(20px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid var(--clr-border);
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 10;
}
.modal-header h3 {
    font-family: var(--font-display);
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    color: var(--clr-dark);
}
.modal-close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: var(--clr-bg);
    color: var(--clr-muted);
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.modal-close-btn:hover {
    background: var(--clr-danger);
    color: #fff;
    transform: rotate(90deg);
}
.modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(80vh - 70px);
}
.modal-body .field-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.modal-body .field-full {
    grid-column: span 2;
}
.modal-body .field-label {
    font-size: 11px;
    color: var(--clr-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.modal-body .field-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--clr-dark);
    margin-top: 2px;
    word-break: break-word;
}
.modal-body .field-value a {
    color: var(--clr-primary);
    text-decoration: underline;
}
.modal-body .note-box {
    grid-column: span 2;
    background: #f8f5f0;
    padding: 12px 16px;
    border-radius: 8px;
    border-left: 3px solid var(--clr-primary);
}
.modal-body .note-box .label {
    font-size: 11px;
    color: var(--clr-muted);
    font-weight: 600;
}
.modal-body .note-box .text {
    margin-top: 4px;
    font-size: 13px;
    color: var(--clr-dark);
}

@media (max-width: 480px) {
    .modal-body .field-group {
        grid-template-columns: 1fr;
    }
    .modal-body .field-full {
        grid-column: span 1;
    }
    .modal-box {
        max-width: 100%;
        margin: 10px;
        border-radius: 12px;
    }
    .modal-header {
        padding: 14px 16px;
    }
    .modal-header h3 { font-size: 16px; }
    .modal-body { padding: 16px; }
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
                <?php if (!empty($filterTanggal)): ?>
                    <span style="background:rgba(184,134,11,.15);padding:4px 12px;border-radius:var(--r-sm);font-size:13px;color:var(--clr-primary);">
                        📅 <?= formatTanggal($filterTanggal) ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($filterBulan)): ?>
                    <span style="background:rgba(184,134,11,.15);padding:4px 12px;border-radius:var(--r-sm);font-size:13px;color:var(--clr-primary);">
                        📆 <?= date('F Y', strtotime($filterBulan . '-01')) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===================================================== -->
        <!-- 🔥 FILTER TANGGAL (TAMBAHAN) -->
        <!-- ===================================================== -->
        <div class="filter-date-container">
            <label>Filter:</label>
            <form method="GET" action="" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>" placeholder="Tanggal">
                <span style="color:var(--clr-muted);font-size:13px;">atau</span>
                <input type="month" name="bulan" value="<?= $filterBulan ?>" placeholder="Bulan">
                <button type="submit" class="btn-filter">Terapkan</button>
                <a href="?" class="btn-reset">Reset</a>
            </form>
            <?php if (!empty($filterTanggal) || !empty($filterBulan)): ?>
                <span class="filter-badge">
                    <?php if (!empty($filterTanggal)): ?>
                        <?= $stats['total'] ?> data
                    <?php else: ?>
                        <?= $stats['total'] ?> data
                    <?php endif; ?>
                </span>
            <?php endif; ?>
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
                            <?php if (empty($displayData)): ?>
                                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--clr-muted);">Tidak ada data</td></tr>
                            <?php else: ?>
                                <?php $no = 1; foreach (array_reverse($displayData) as $key => $izin): ?>
                                    <?php if (!is_array($izin)) continue; ?>
                                    <tr class="history-row" data-status="<?= $izin['status'] ?? 'Menunggu' ?>">
                                        <td style="font-weight:600;"><?= $no++ ?></td>
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
                        <span>Menampilkan <?= count($displayData ?? []) ?> dari <?= $stats['total'] ?> riwayat</span>
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
     MOBILE HISTORY VIEW
     ===================================================== -->
<div id="mobile-history-view" class="page-with-mobile-nav">

    <div class="mobile-riwayat-header">
        <div>
            <h2> Riwayat Cuti</h2>
            <div class="sub-info">Sisa cuti: <strong><?= $sisaCuti ?></strong> hari</div>
        </div>
       
        </div>
    </div>

    <!-- 🔥 FILTER TANGGAL MOBILE -->
    <div class="filter-date-container" style="margin:0 12px 14px;">
        <form method="GET" action="" style="display:flex;flex-direction:column;gap:8px;width:100%;">
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>" style="flex:1;min-width:120px;">
                <input type="month" name="bulan" value="<?= $filterBulan ?>" style="flex:1;min-width:120px;">
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn-filter" style="flex:1;">Terapkan</button>
                <a href="?" class="btn-reset" style="flex:1;text-align:center;text-decoration:none;">Reset</a>
            </div>
        </form>
    </div>

    <div class="mobile-stats-grid">
        <div class="mobile-stat-card">
            <div class="stat-val"><?= $stats['total'] ?></div>
            <div class="stat-lbl">Total Diajukan</div>
        </div>
        <div class="mobile-stat-card primary">
            <div class="stat-val"><?= $stats['menunggu'] ?></div>
            <div class="stat-lbl">Menunggu</div>
        </div>
        <div class="mobile-stat-card success">
            <div class="stat-val"><?= $stats['disetujui'] ?></div>
            <div class="stat-lbl">Disetujui</div>
        </div>
        <div class="mobile-stat-card danger">
            <div class="stat-val"><?= $stats['ditolak'] ?></div>
            <div class="stat-lbl">Ditolak</div>
        </div>
    </div>

    <button class="mobile-ajukan-btn" onclick="window.location.href='home.php#ajukan-cuti-mobile'">
        <i class="ri-add-line"></i> Ajukan Cuti Baru
    </button>

    <div class="mobile-search-riwayat">
        <i class="ri-search-line" style="color:var(--clr-muted);flex-shrink:0;"></i>
        <input type="text" placeholder="Cari pengajuan cuti..." id="mobile-search-history">
    </div>

    <div class="mobile-section-title">Daftar Pengajuan</div>
    <div class="mobile-riwayat-container">
        <?php if (empty($displayData)): ?>
            <div class="empty-state-mobile">
                <i class="ri-inbox-line"></i>
                <h4>Belum Ada Pengajuan</h4>
                <p>Mulai ajukan cuti Anda sekarang</p>
                <button class="btn btn-primary" onclick="window.location.href='home.php#ajukan-cuti-mobile'">
                    Ajukan Cuti
                </button>
            </div>
        <?php else: ?>
            <?php foreach (array_reverse($displayData) as $key => $izin): ?>
                <?php if (!is_array($izin)) continue; ?>
                <div class="mobile-hist-card" onclick="showDetail('<?= $key ?>')">
                    <div class="mobile-hist-header">
                        <span class="mobile-hist-id">#<?= escape($izin['id'] ?? 'CUT-' . substr($key, -4)) ?></span>
                        <?= getStatusBadge($izin['status'] ?? 'Menunggu') ?>
                    </div>
                    <div class="mobile-hist-title"><?= escape($izin['jenis_cuti'] ?? 'Cuti') ?></div>
                    <div class="mobile-hist-meta">
                        <span><i class="ri-calendar-line"></i> <?= formatTanggal($izin['tanggal_mulai'] ?? '') ?></span>
                        <span><i class="ri-time-line"></i> <?= $izin['durasi'] ?? 0 ?> hari</span>
                    </div>
                    <?php if (!empty($izin['catatan_admin'])): ?>
                        <div class="admin-note-mobile">
                            <i class="ri-chat-3-line"></i> <strong>Catatan:</strong> <?= escape($izin['catatan_admin']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($izin['dokumen'])): ?>
                        <a href="<?= escape($izin['dokumen']) ?>" target="_blank" class="doc-link-mobile" onclick="event.stopPropagation();">
                            <i class="ri-file-pdf-line"></i> Lihat Dokumen
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    

</div>

<!-- =====================================================
     MODAL DETAIL - MODERN
     ===================================================== -->
<div id="detail-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3> Detail Pengajuan</h3>
            <button class="modal-close-btn" onclick="closeDetailModal()">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="modal-body" id="detail-content">
            <p style="text-align:center;color:var(--clr-muted);">Loading...</p>
        </div>
    </div>
</div>

<!-- =====================================================
     MOBILE NAV
     ===================================================== -->
<nav class="mobile-nav-bar">
    <button class="mobile-nav-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
    <button class="mobile-nav-item active"><i class="ri-history-line"></i>Riwayat</button>
    <button class="mobile-nav-item" onclick="window.location.href='profile.php'"><i class="ri-user-line"></i>Profil</button>
</nav>

<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
const allData = <?= json_encode($permohonan) ?>;

// ===== RESPONSIVE SWITCH =====
function applyView() {
    const isMobile = window.innerWidth <= 768;
    document.getElementById('desktop-history-view').style.display = isMobile ? 'none' : '';
    document.getElementById('mobile-history-view').style.display  = isMobile ? 'block' : 'none';
}
applyView();
window.addEventListener('resize', applyView);

// ===== DETAIL MODAL =====
function showDetail(key) {
    const data = allData[key];
    if (!data) { 
        showToast('Data tidak ditemukan', 'ri-error-warning-line'); 
        return; 
    }

    // Build HTML untuk modal
    let html = `<div class="field-group">`;
    
    const fields = [
        ['ID', data.id || '-'],
        ['Status', data.status || '-'],
        ['Nama', data.user_name || '-'],
        ['NIP', data.nip || '-'],
        ['Jabatan', data.jabatan || '-'],
        ['Departemen', data.departemen || '-'],
        ['Jenis Cuti', data.jenis_cuti || '-'],
        ['Durasi', (data.durasi || 0) + ' hari'],
    ];
    
    fields.forEach(f => {
        html += `
            <div>
                <div class="field-label">${f[0]}</div>
                <div class="field-value">${f[1]}</div>
            </div>
        `;
    });
    
    // Tanggal (full width)
    html += `
        <div class="field-full">
            <div class="field-label">Tanggal</div>
            <div class="field-value">${data.tanggal_mulai || '-'} s/d ${data.tanggal_selesai || '-'}</div>
        </div>
        <div class="field-full">
            <div class="field-label">Alasan</div>
            <div class="field-value">${data.alasan || '-'}</div>
        </div>
    `;
    
    // Catatan Admin
    if (data.catatan_admin) {
        html += `
            <div class="field-full note-box">
                <div class="label"> Catatan Admin</div>
                <div class="text">${data.catatan_admin}</div>
            </div>
        `;
    }
    
    // Reviewer
    if (data.reviewed_by) {
        html += `
            <div>
                <div class="field-label">Reviewer</div>
                <div class="field-value">${data.reviewed_by}</div>
            </div>
        `;
    }
    if (data.reviewed_at) {
        html += `
            <div>
                <div class="field-label">Tgl Review</div>
                <div class="field-value">${data.reviewed_at}</div>
            </div>
        `;
    }
    
    // Tanggal Pengajuan
    html += `
        <div class="field-full">
            <div class="field-label">Tanggal Pengajuan</div>
            <div class="field-value">${data.created_at || '-'}</div>
        </div>
    `;
    
    // Dokumen
    if (data.dokumen) {
        html += `
            <div class="field-full" style="margin-top:8px;">
                <a href="${data.dokumen}" target="_blank" class="btn btn-outline btn-sm" style="width:100%;text-align:center;font-size:12px;">
                    <i class="ri-file-pdf-line"></i> Lihat Dokumen
                </a>
            </div>
        `;
    }
    
    html += `</div>`;
    
    document.getElementById('detail-content').innerHTML = html;
    document.getElementById('detail-modal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('detail-modal').classList.remove('active');
}

// Close on backdrop click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('detail-modal');
    if (modal && e.target === modal) {
        closeDetailModal();
    }
});

// Close on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
    }
});

// ===== FILTER =====
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

// ===== SEARCH =====
document.getElementById('mobile-search-history')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.mobile-hist-card').forEach(card => {
        const title = card.querySelector('.mobile-hist-title')?.textContent.toLowerCase() || '';
        const id    = card.querySelector('.mobile-hist-id')?.textContent.toLowerCase() || '';
        card.style.display = (!q || title.includes(q) || id.includes(q)) ? '' : 'none';
    });
});

// ===== TOAST =====
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