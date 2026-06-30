<?php
/**
 * =====================================================
 * FILE: user/riwayat.php
 * FUNGSI: Riwayat Cuti User
 * VERSION: FINAL - Full Fix
 * =====================================================
 */

// 🔥 CEK SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// 🔥 Cek login
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

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
// FILTER TANGGAL
// =====================================================
$filterTanggal = $_GET['tanggal'] ?? '';
$filterBulan = $_GET['bulan'] ?? '';

$displayData = [];
foreach ($permohonan as $key => $izin) {
    if (!is_array($izin)) continue;
    
    $created = substr($izin['created_at'] ?? '', 0, 10);
    $bulan = substr($izin['created_at'] ?? '', 0, 7);
    
    $match = true;
    if (!empty($filterTanggal) && $created !== $filterTanggal) $match = false;
    if (!empty($filterBulan) && $bulan !== $filterBulan) $match = false;
    
    if ($match) $displayData[$key] = $izin;
}

if (empty($filterTanggal) && empty($filterBulan)) {
    $displayData = $permohonan;
}

$stats = [
    'total' => 0,
    'menunggu' => 0,
    'disetujui' => 0,
    'ditolak' => 0,
    'selesai' => 0
];

if (is_array($displayData) && !empty($displayData)) {
    foreach ($displayData as $izin) {
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
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ===== RESPONSIVE ===== */
@media (min-width: 769px) {
    #mobile-history-view { display: none !important; }
}
@media (max-width: 768px) {
    #desktop-history-view { display: none !important; }
}

.filter-date-container {
    background: #f5f5f0;
    border: 1px solid #e0e0dc;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.filter-date-container input[type="date"],
.filter-date-container input[type="month"] {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    background: #fff;
}
.filter-date-container .btn-filter {
    padding: 6px 16px;
    background: #B8860B;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}
.filter-date-container .btn-filter:hover { background: #8B6508; }
.filter-date-container .btn-reset {
    padding: 6px 16px;
    background: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}
.filter-date-container .btn-reset:hover { background: #e0e0e0; }

@media (max-width: 768px) {
    .filter-date-container { margin: 0 12px 14px; padding: 10px 12px; }
    .filter-date-container input { font-size: 12px; padding: 5px 8px; width: 100%; }
}
</style>

<!-- =====================================================
     DESKTOP VIEW
     ===================================================== -->
<div id="desktop-history-view" class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">Magang<span>.usg</span><br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small></div>
        <div class="sidebar-item" onclick="window.location.href='index.php'"><i class="ri-dashboard-line"></i> Dashboard</div>
        <div class="sidebar-item" onclick="window.location.href='index.php#ajukan-cuti'"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
        <div class="sidebar-item active"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='profile.php'"><i class="ri-user-line"></i> Profil</div>
        <div class="sidebar-bottom">
            <div class="sidebar-item" onclick="window.location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <div style="margin-bottom:16px;">
            <h1 style="font-size:24px;font-weight:800;margin-bottom:2px;">Riwayat Cuti</h1>
            <p style="color:#888;font-size:13px;">Riwayat pengajuan cuti Anda</p>
            <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap;">
                <span style="background:#f5f5f0;padding:3px 10px;border-radius:4px;font-size:12px;">Sisa: <strong><?= $sisaCuti ?></strong> hari</span>
                <span style="background:#f5f5f0;padding:3px 10px;border-radius:4px;font-size:12px;">Total: <strong><?= $stats['total'] ?></strong></span>
                <?php if (!empty($filterTanggal)): ?>
                    <span style="background:rgba(184,134,11,.15);padding:3px 10px;border-radius:4px;font-size:12px;color:#B8860B;"><?= formatTanggal($filterTanggal) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="filter-date-container">
            <label style="font-size:12px;color:#888;font-weight:600;">Filter:</label>
            <form method="GET" action="" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>">
                <span style="color:#888;font-size:13px;">atau</span>
                <input type="month" name="bulan" value="<?= $filterBulan ?>">
                <button type="submit" class="btn-filter">Terapkan</button>
                <a href="?" class="btn-reset">Reset</a>
            </form>
        </div>

        <div style="display:grid;grid-template-columns:240px 1fr;gap:20px;">
            <div>
                <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:16px;margin-bottom:12px;">
                    <h4 style="font-size:10px;text-transform:uppercase;color:#888;margin-bottom:8px;">Statistik</h4>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee;"><span>Total</span><strong><?= $stats['total'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee;"><span>Menunggu</span><strong style="color:#B8860B;"><?= $stats['menunggu'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee;"><span>Disetujui</span><strong style="color:#2D7A4F;"><?= $stats['disetujui'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Ditolak</span><strong style="color:#C0392B;"><?= $stats['ditolak'] ?></strong></div>
                </div>
                <button class="btn btn-primary btn-full" onclick="window.location.href='index.php#ajukan-cuti'"><i class="ri-add-line"></i> Ajukan Cuti</button>
            </div>

            <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;overflow:hidden;">
                <div style="padding:10px 14px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                    <span style="font-size:14px;font-weight:700;">Daftar Pengajuan</span>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        <button class="filter-tab active" data-filter="all" style="padding:3px 10px;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;background:transparent;">Semua</button>
                        <button class="filter-tab" data-filter="Menunggu" style="padding:3px 10px;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;background:transparent;">Menunggu</button>
                        <button class="filter-tab" data-filter="Disetujui" style="padding:3px 10px;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;background:transparent;">Disetujui</button>
                        <button class="filter-tab" data-filter="Ditolak" style="padding:3px 10px;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;background:transparent;">Ditolak</button>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">No</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Jenis</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Tanggal</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Durasi</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Status</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($displayData)): ?>
                                <tr><td colspan="6" style="text-align:center;padding:20px;color:#999;">Tidak ada data</td></tr>
                            <?php else: ?>
                                <?php $no = 1; foreach (array_reverse($displayData) as $key => $izin): ?>
                                    <?php if (!is_array($izin)) continue; ?>
                                    <tr class="history-row" data-status="<?= $izin['status'] ?? 'Menunggu' ?>">
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= $no++ ?></td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= escape($izin['jenis_cuti'] ?? '-') ?></td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;font-size:12px;"><?= formatTanggal($izin['created_at'] ?? '') ?></td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= $izin['durasi'] ?? 0 ?> hari</td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= getStatusBadge($izin['status'] ?? 'Menunggu') ?></td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;">
                                            <button class="btn btn-outline btn-sm" onclick="showDetail('<?= $key ?>')"><i class="ri-eye-line"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- =====================================================
     MOBILE VIEW
     ===================================================== -->
<div id="mobile-history-view" style="display:none;padding-bottom:80px;">
    <div style="padding:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div>
            <h2 style="font-size:20px;font-weight:700;margin:0;">Riwayat</h2>
            <div style="font-size:12px;color:#888;">Sisa: <strong><?= $sisaCuti ?></strong> hari</div>
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
            <div style="width:32px;height:32px;border-radius:50%;background:#B8860B;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;cursor:pointer;" onclick="window.location.href='profile.php'">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
            </div>
        </div>
    </div>

    <div class="filter-date-container" style="margin:0 12px 14px;">
        <form method="GET" action="" style="display:flex;flex-direction:column;gap:6px;width:100%;">
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>" style="flex:1;min-width:100px;">
                <input type="month" name="bulan" value="<?= $filterBulan ?>" style="flex:1;min-width:100px;">
            </div>
            <div style="display:flex;gap:6px;">
                <button type="submit" class="btn-filter" style="flex:1;">Terapkan</button>
                <a href="?" class="btn-reset" style="flex:1;text-align:center;text-decoration:none;">Reset</a>
            </div>
        </form>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:0 12px;margin-bottom:12px;">
        <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:10px 12px;text-align:center;"><div style="font-size:20px;font-weight:800;"><?= $stats['total'] ?></div><div style="font-size:10px;color:#888;">Total</div></div>
        <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:10px 12px;text-align:center;border-left:2px solid #B8860B;"><div style="font-size:20px;font-weight:800;color:#B8860B;"><?= $stats['menunggu'] ?></div><div style="font-size:10px;color:#888;">Menunggu</div></div>
        <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:10px 12px;text-align:center;border-left:2px solid #2D7A4F;"><div style="font-size:20px;font-weight:800;color:#2D7A4F;"><?= $stats['disetujui'] ?></div><div style="font-size:10px;color:#888;">Disetujui</div></div>
        <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:10px 12px;text-align:center;border-left:2px solid #C0392B;"><div style="font-size:20px;font-weight:800;color:#C0392B;"><?= $stats['ditolak'] ?></div><div style="font-size:10px;color:#888;">Ditolak</div></div>
    </div>

    <button class="btn btn-primary" style="display:block;width:calc(100% - 24px);margin:0 12px 12px;padding:10px;text-align:center;" onclick="window.location.href='index.php#ajukan-cuti'">+ Ajukan Cuti</button>

    <div style="display:flex;align-items:center;gap:10px;background:#f5f5f0;border:1px solid #e5e5e5;border-radius:8px;padding:8px 12px;margin:0 12px 12px;">
        <i class="ri-search-line"></i>
        <input type="text" placeholder="Cari..." id="mobile-search-history" style="border:none;background:none;flex:1;font-size:14px;outline:none;">
    </div>

    <div style="padding:0 12px;font-size:12px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:6px;">Daftar Pengajuan</div>
    <div style="padding:0 12px;">
        <?php if (empty($displayData)): ?>
            <div style="text-align:center;padding:40px 20px;color:#888;"><i class="ri-inbox-line" style="font-size:48px;display:block;margin-bottom:12px;color:#ddd;"></i><h4 style="font-size:16px;font-weight:600;color:#1a1a1a;">Belum Ada Data</h4></div>
        <?php else: ?>
            <?php foreach (array_reverse($displayData) as $key => $izin): ?>
                <?php if (!is_array($izin)) continue; ?>
                <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:12px;margin-bottom:8px;cursor:pointer;" onclick="showDetail('<?= $key ?>')">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <span style="font-size:11px;font-weight:700;color:#888;font-family:monospace;">#<?= substr($key, 0, 8) ?></span>
                        <?= getStatusBadge($izin['status'] ?? 'Menunggu') ?>
                    </div>
                    <div style="font-size:14px;font-weight:700;margin-bottom:4px;"><?= escape($izin['jenis_cuti'] ?? 'Cuti') ?></div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <span style="font-size:11px;color:#888;display:flex;align-items:center;gap:3px;"><i class="ri-calendar-line"></i> <?= formatTanggal($izin['created_at'] ?? '') ?></span>
                        <span style="font-size:11px;color:#888;display:flex;align-items:center;gap:3px;"><i class="ri-time-line"></i> <?= $izin['durasi'] ?? 0 ?> hari</span>
                    </div>
                    <?php if (!empty($izin['catatan_admin'])): ?>
                        <div style="margin-top:6px;font-size:11px;background:#f5f5f0;padding:6px 10px;border-radius:4px;border-left:2px solid #B8860B;">Catatan: <?= escape($izin['catatan_admin']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($izin['dokumen'])): ?>
                        <a href="<?= escape($izin['dokumen']) ?>" target="_blank" style="margin-top:4px;display:inline-block;font-size:12px;color:#B8860B;text-decoration:underline;">Lihat Dokumen</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DETAIL -->
<div id="detail-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:12px;max-width:500px;width:100%;max-height:80vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #eee;background:#fff;">
            <h3 style="font-size:18px;font-weight:700;margin:0;">Detail Pengajuan</h3>
            <button onclick="closeDetailModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#888;">&times;</button>
        </div>
        <div style="padding:20px;overflow-y:auto;max-height:calc(80vh - 70px);" id="detail-content">
            <p style="text-align:center;color:#999;">Loading...</p>
        </div>
    </div>
</div>

<script>
const allData = <?= json_encode($displayData) ?>;

function showDetail(key) {
    const data = allData[key];
    if (!data) { showToast('Data tidak ditemukan'); return; }
    
    let html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;">';
    const fields = [
        ['ID', data.id || '-'],
        ['Status', data.status || '-'],
        ['Nama', data.user_name || '-'],
        ['NIP', data.nip || '-'],
        ['Jenis', data.jenis_cuti || '-'],
        ['Durasi', (data.durasi || 0) + ' hari'],
        ['Tanggal', (data.tanggal_mulai || '-') + ' s/d ' + (data.tanggal_selesai || '-')],
        ['Alasan', data.alasan || '-']
    ];
    fields.forEach(f => {
        html += `<div ${f[0] === 'Alasan' || f[0] === 'Tanggal' ? 'style="grid-column:span 2;"' : ''}>
            <div style="font-size:10px;color:#999;text-transform:uppercase;">${f[0]}</div>
            <div style="font-weight:600;font-size:14px;">${f[1]}</div>
        </div>`;
    });
    if (data.catatan_admin) {
        html += `<div style="grid-column:span 2;background:#f8f5f0;padding:10px;border-radius:6px;border-left:3px solid #B8860B;">
            <div style="font-size:10px;color:#999;">Catatan Admin</div>
            <div style="font-size:13px;">${data.catatan_admin}</div>
        </div>`;
    }
    if (data.dokumen) {
        html += `<div style="grid-column:span 2;"><a href="${data.dokumen}" target="_blank" style="color:#B8860B;text-decoration:underline;">Lihat Dokumen</a></div>`;
    }
    html += '</div>';
    
    document.getElementById('detail-content').innerHTML = html;
    document.getElementById('detail-modal').style.display = 'flex';
}

function closeDetailModal() {
    document.getElementById('detail-modal').style.display = 'none';
}

document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('detail-modal')) closeDetailModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDetailModal();
});

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

document.getElementById('mobile-search-history')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.mobile-hist-card').forEach(card => {
        const title = card.querySelector('.mobile-hist-title')?.textContent.toLowerCase() || '';
        const id = card.querySelector('.mobile-hist-id')?.textContent.toLowerCase() || '';
        card.style.display = (title.includes(q) || id.includes(q)) ? '' : 'none';
    });
});

function showToast(msg) {
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = msg;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
