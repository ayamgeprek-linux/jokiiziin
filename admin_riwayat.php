<?php
/**
 * =====================================================
 * FILE: admin_riwayat.php
 * FUNGSI: Riwayat Semua Pengajuan (Admin)
 * VERSION: 1.0
 * =====================================================
 */

require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Cek login dan role admin
requireAdmin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// =====================================================
// AMBIL DATA DARI FIREBASE
// =====================================================

// Ambil SEMUA data permohonan
$allPermohonan = $database->getReference('permohonan')->getValue();
$permohonan = [];

if (is_array($allPermohonan) && !empty($allPermohonan)) {
    foreach ($allPermohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        $izin['_key'] = $key;
        $permohonan[] = $izin;
    }
}

// Urutkan dari yang terbaru
usort($permohonan, function($a, $b) {
    return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
});

// Filter berdasarkan status (jika ada)
$filterStatus = $_GET['status'] ?? 'all';
$filteredData = [];
if ($filterStatus !== 'all') {
    foreach ($permohonan as $izin) {
        if (($izin['status'] ?? '') === $filterStatus) {
            $filteredData[] = $izin;
        }
    }
} else {
    $filteredData = $permohonan;
}

// Statistik
$stats = [
    'total' => count($permohonan),
    'menunggu' => 0,
    'disetujui' => 0,
    'ditolak' => 0,
    'selesai' => 0
];

foreach ($permohonan as $izin) {
    $status = $izin['status'] ?? '';
    switch ($status) {
        case 'Menunggu': $stats['menunggu']++; break;
        case 'Disetujui': $stats['disetujui']++; break;
        case 'Ditolak': $stats['ditolak']++; break;
        case 'Selesai': $stats['selesai']++; break;
    }
}

$currentPage = 'admin-riwayat';
include 'includes/header.php';
?>

<!-- =====================================================
     ADMIN RIWAYAT CONTENT
     ===================================================== -->
<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            Magang<span>.usg</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Admin Panel</small>
        </div>
        <div class="sidebar-item" onclick="window.location.href='admin.php'"><i class="ri-file-search-line"></i> Review Cuti</div>
        <div class="sidebar-item active"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='admin_laporan.php'"><i class="ri-file-chart-line"></i> Laporan</div>
        <div class="sidebar-bottom">
            <div class="sidebar-pdf-btn" onclick="window.location.href='admin_cetak_pdf.php'"><i class="ri-printer-line"></i> Cetak Laporan PDF</div>
            <div class="sidebar-item"><i class="ri-settings-3-line"></i> Pengaturan</div>
            <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:var(--font-display);font-size:28px;font-weight:800;">Riwayat Pengajuan</h1>
                <p style="color:var(--clr-muted);font-size:14px;">Semua pengajuan cuti dari semua karyawan</p>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn btn-outline btn-sm" onclick="window.location.href='admin_cetak_pdf.php'">
                    <i class="ri-printer-line"></i> Cetak PDF
                </button>
                <button class="btn btn-primary btn-sm" onclick="window.location.reload()">
                    <i class="ri-refresh-line"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:24px;">
            <div class="card" style="padding:16px;text-align:center;">
                <div style="font-size:24px;font-weight:800;"><?= $stats['total'] ?></div>
                <div style="font-size:12px;color:var(--clr-muted);">Total</div>
            </div>
            <div class="card" style="padding:16px;text-align:center;border-left:3px solid var(--clr-primary);">
                <div style="font-size:24px;font-weight:800;color:var(--clr-primary);"><?= $stats['menunggu'] ?></div>
                <div style="font-size:12px;color:var(--clr-muted);">Menunggu</div>
            </div>
            <div class="card" style="padding:16px;text-align:center;border-left:3px solid var(--clr-success);">
                <div style="font-size:24px;font-weight:800;color:var(--clr-success);"><?= $stats['disetujui'] ?></div>
                <div style="font-size:12px;color:var(--clr-muted);">Disetujui</div>
            </div>
            <div class="card" style="padding:16px;text-align:center;border-left:3px solid var(--clr-danger);">
                <div style="font-size:24px;font-weight:800;color:var(--clr-danger);"><?= $stats['ditolak'] ?></div>
                <div style="font-size:12px;color:var(--clr-muted);">Ditolak</div>
            </div>
            <div class="card" style="padding:16px;text-align:center;border-left:3px solid #666;">
                <div style="font-size:24px;font-weight:800;color:#666;"><?= $stats['selesai'] ?></div>
                <div style="font-size:12px;color:var(--clr-muted);">Selesai</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="section-card">
            <div class="section-card-header">
                <div>
                    <h3>Daftar Pengajuan</h3>
                    <p>Menampilkan <?= count($filteredData) ?> dari <?= $stats['total'] ?> pengajuan</p>
                </div>
                <div class="history-filter-tabs">
                    <a href="?status=all" class="filter-tab <?= $filterStatus === 'all' ? 'active' : '' ?>">Semua</a>
                    <a href="?status=Menunggu" class="filter-tab <?= $filterStatus === 'Menunggu' ? 'active' : '' ?>">Menunggu</a>
                    <a href="?status=Disetujui" class="filter-tab <?= $filterStatus === 'Disetujui' ? 'active' : '' ?>">Disetujui</a>
                    <a href="?status=Ditolak" class="filter-tab <?= $filterStatus === 'Ditolak' ? 'active' : '' ?>">Ditolak</a>
                    <a href="?status=Selesai" class="filter-tab <?= $filterStatus === 'Selesai' ? 'active' : '' ?>">Selesai</a>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pemohon</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Reviewer</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredData)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:40px;color:var(--clr-muted);">
                                <i class="ri-inbox-line" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                Tidak ada pengajuan
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($filteredData as $izin): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div>
                                        <div style="font-weight:600;"><?= escape($izin['user_name'] ?? '-') ?></div>
                                        <div style="font-size:11px;color:var(--clr-muted);"><?= escape($izin['nip'] ?? '-') ?></div>
                                    </div>
                                </td>
                                <td><?= escape($izin['jenis_cuti'] ?? '-') ?></td>
                                <td style="font-size:13px;">
                                    <?= formatTanggal($izin['tanggal_mulai'] ?? '') ?>
                                    <br><span style="font-size:11px;color:var(--clr-muted);">
                                        s/d <?= formatTanggal($izin['tanggal_selesai'] ?? '') ?>
                                    </span>
                                </td>
                                <td><?= $izin['durasi'] ?? 0 ?> hari</td>
                                <td><?= getStatusBadge($izin['status'] ?? 'Menunggu') ?></td>
                                <td style="font-size:13px;">
                                    <?= escape($izin['reviewed_by'] ?? '-') ?>
                                    <?php if (!empty($izin['reviewed_at'])): ?>
                                        <br><span style="font-size:10px;color:var(--clr-muted);">
                                            <?= formatTanggalWaktu($izin['reviewed_at']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick="showDetail('<?= $izin['_key'] ?? '' ?>')">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>
</div>

<script>
const allData = <?= json_encode($permohonan) ?>;

function showDetail(key) {
    const data = allData.find(item => item._key === key);
    if (!data) {
        showToast('Data tidak ditemukan', 'ri-error-warning-line');
        return;
    }
    
    const html = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
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
            ${data.catatan ? `<div style="grid-column:span 2;"><strong>Catatan Review</strong><br>${data.catatan}</div>` : ''}
            ${data.reviewed_by ? `<div><strong>Reviewer</strong><br>${data.reviewed_by}</div>` : ''}
            ${data.reviewed_at ? `<div><strong>Tanggal Review</strong><br>${data.reviewed_at}</div>` : ''}
            <div style="grid-column:span 2;"><strong>Tanggal Pengajuan</strong><br>${data.created_at || '-'}</div>
        </div>
    `;
    
    // Buat modal sederhana
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;display:flex;align-items:center;justify-content:center;padding:20px;';
    modal.innerHTML = `
        <div style="background:#fff;border-radius:var(--r-xl);max-width:500px;width:100%;padding:32px;box-shadow:var(--shadow-lg);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h3 style="font-family:var(--font-display);font-size:20px;font-weight:700;">Detail Pengajuan</h3>
                <button onclick="this.closest('div[style]').remove()" style="background:var(--clr-bg);border:1px solid var(--clr-border);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            ${html}
        </div>
    `;
    document.body.appendChild(modal);
}
</script>

<?php include 'includes/footer.php'; ?>