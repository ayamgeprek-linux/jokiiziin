<?php
/**
 * =====================================================
 * FILE: admin.php
 * FUNGSI: Admin Panel - Review Pengajuan Cuti
 * VERSION: 3.0 - With Complete Sidebar Menu
 * =====================================================
 */

require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/notifikasi.php';

// Cek login dan role admin
requireAdmin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// =====================================================
// PROSES REVIEW CUTI
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_cuti'])) {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    
    if ($id && $status) {
        try {
            // Ambil data cuti dulu untuk notifikasi
            $cutiData = $database->getReference('permohonan/' . $id)->getValue();
            
            // Update status
            $database->getReference('permohonan/' . $id)->update([
                'status' => $status,
                'catatan' => $catatan,
                'reviewed_by' => $user['name'] ?? 'Admin',
                'reviewed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // KIRIM NOTIFIKASI KE USER
            NotifikasiManager::notifikasiStatusBerubah(
                $database, 
                $cutiData['user_id'] ?? '', 
                $cutiData, 
                $status
            );
            
            $_SESSION['flash_message'] = 'Pengajuan cuti berhasil di-' . strtolower($status) . '!';
            redirect('admin.php');
        } catch (Exception $e) {
            error_log('ERROR REVIEW: ' . $e->getMessage());
            $error = 'Gagal update data: ' . $e->getMessage();
        }
    }
}

// =====================================================
// AMBIL DATA DARI FIREBASE (REST API)
// =====================================================

// Ambil SEMUA data, filter manual
$allRequests = $database->getReference('permohonan')->getValue();

// Filter data yang menunggu
$pendingRequests = [];
if (is_array($allRequests) && !empty($allRequests)) {
    foreach ($allRequests as $key => $request) {
        if (!is_array($request)) continue;
        if (($request['status'] ?? '') === 'Menunggu') {
            $pendingRequests[$key] = $request;
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

if (is_array($allRequests) && !empty($allRequests)) {
    foreach ($allRequests as $request) {
        if (!is_array($request)) continue;
        $stats['total']++;
        $status = $request['status'] ?? '';
        switch ($status) {
            case 'Menunggu': $stats['menunggu']++; break;
            case 'Disetujui': $stats['disetujui']++; break;
            case 'Ditolak': $stats['ditolak']++; break;
            case 'Selesai': $stats['selesai']++; break;
        }
    }
}

$currentPage = 'admin';
include 'includes/header.php';
?>

<!-- =====================================================
     ADMIN CONTENT
     ===================================================== -->
<div class="layout-with-sidebar page-with-mobile-nav">
    <!-- SIDEBAR - LENGKAP -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            Magang<span>.usg</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Admin Panel</small>
        </div>
        <div class="sidebar-item active"><i class="ri-file-search-line"></i> Review Cuti</div>
        <div class="sidebar-item" onclick="window.location.href='admin_riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='admin_laporan.php'"><i class="ri-file-chart-line"></i> Laporan</div>
        <div class="sidebar-bottom">
            <div class="sidebar-pdf-btn" onclick="window.location.href='admin_cetak_pdf.php'">
                <i class="ri-printer-line"></i> Cetak Laporan PDF
            </div>
            <div class="sidebar-item"><i class="ri-settings-3-line"></i> Pengaturan</div>
            <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Flash Message -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div style="background:#E8F5EE;border:1px solid #2D7A4F;border-radius:var(--r-md);padding:12px 16px;margin-bottom:16px;color:#2D7A4F;font-size:13px;">
                <i class="ri-checkbox-circle-line"></i> <?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div style="background:#FDECEA;border:1px solid #C0392B;border-radius:var(--r-md);padding:12px 16px;margin-bottom:16px;color:#C0392B;font-size:13px;">
                <i class="ri-error-warning-line"></i> <?= escape($error) ?>
            </div>
        <?php endif; ?>

        <!-- Header Card -->
        <div class="panel-header-card">
            <div style="display:grid;grid-template-columns:1fr auto auto;gap:16px;align-items:start;flex-wrap:wrap;">
                <div>
                    <h2>Panel Review Cuti</h2>
                    <p>Tinjau dan verifikasi pengajuan cuti karyawan. Pastikan semua dokumen sesuai dengan kebijakan perusahaan.</p>
                    <div class="panel-stats-row">
                        <div class="panel-stat">
                            <div class="panel-stat-label">Menunggu Review</div>
                            <div class="panel-stat-value"><?= $stats['menunggu'] ?></div>
                        </div>
                        <div class="panel-stat">
                            <div class="panel-stat-label">Total Pengajuan</div>
                            <div class="panel-stat-value"><?= $stats['total'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="metric-card" style="min-width:160px;margin:0;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);">
                    <div class="metric-card-header">
                        <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.5);">Waktu Respon</span>
                        <div class="metric-icon amber"><i class="ri-time-line"></i></div>
                    </div>
                    <div class="metric-value" style="color:#fff;">1.2 Jam</div>
                    <div class="metric-badge-good"><i class="ri-arrow-up-line"></i> 12% lebih cepat</div>
                </div>
                <div class="metric-card" style="min-width:160px;margin:0;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);">
                    <div class="metric-card-header">
                        <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.5);">Tingkat Penolakan</span>
                        <div class="metric-icon green"><i class="ri-checkbox-circle-line"></i></div>
                    </div>
                    <div class="metric-value" style="color:#fff;">
                        <?= $stats['total'] > 0 ? round(($stats['ditolak'] / $stats['total']) * 100, 1) : 0 ?>%
                    </div>
                    <div class="metric-badge-ok" style="color:rgba(255,255,255,.5);font-size:12px;">Dalam batas standar</div>
                </div>
            </div>
        </div>

        <!-- Waiting List Table -->
        <div class="section-card">
            <div class="section-card-header">
                <div>
                    <h3>Daftar Pengajuan Menunggu</h3>
                    <p>Membutuhkan tindakan verifikasi segera</p>
                </div>
                <div class="section-card-actions">
                    <button class="btn btn-outline btn-sm"><i class="ri-filter-3-line"></i> Filter</button>
                    <button class="btn btn-primary btn-sm"><i class="ri-sort-desc"></i> Urutkan</button>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pemohon</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Durasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingRequests)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;padding:40px;color:var(--clr-muted);">
                                <i class="ri-inbox-line" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                Tidak ada pengajuan cuti yang menunggu
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingRequests as $key => $request): ?>
                            <?php if (!is_array($request)) continue; ?>
                            <tr>
                                <td>
                                    <div class="applicant-cell">
                                        <div class="applicant-avatar">
                                            <?= strtoupper(substr($request['user_name'] ?? 'U', 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="applicant-name"><?= escape($request['user_name'] ?? '-') ?></div>
                                            <div class="applicant-id">NIP: <?= escape($request['nip'] ?? '-') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="permit-tags">
                                        <span class="permit-tag"><?= escape($request['jenis_cuti'] ?? '-') ?></span>
                                        <span class="permit-tag"><?= escape($request['departemen'] ?? '-') ?></span>
                                    </div>
                                </td>
                                <td style="white-space:nowrap;">
                                    <?= formatTanggal($request['created_at'] ?? '') ?>
                                    <br><span style="color:var(--clr-muted);font-size:12px;">
                                        <?= date('H:i', strtotime($request['created_at'] ?? '')) ?>
                                    </span>
                                </td>
                                <td><?= $request['durasi'] ?? 0 ?> hari</td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick="openReviewModal('<?= $key ?>')">
                                        Tinjau
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="table-pagination">
                <span>Menampilkan <?= count($pendingRequests) ?> dari <?= $stats['menunggu'] ?> antrean</span>
                <div class="pagination-btns">
                    <button class="page-btn"><i class="ri-arrow-left-s-line"></i></button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn"><i class="ri-arrow-right-s-line"></i></button>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="bottom-row">
            <div class="tip-card">
                <h4><i class="ri-information-line" style="color:var(--clr-primary);"></i> Panduan Review Cepat</h4>
                <ul>
                    <li>Verifikasi NIP dan data karyawan yang mengajukan cuti</li>
                    <li>Pastikan sisa cuti karyawan mencukupi</li>
                    <li>Periksa dokumen pendukung (jika ada)</li>
                    <li>Waktu batas review adalah 48 jam sejak pengajuan</li>
                </ul>
            </div>
            <div class="promo-card">
                <div class="promo-card-eyebrow">Magang.usg</div>
                <h4>Efisiensi Dalam Setiap Pengajuan</h4>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>
</div>

<!-- =====================================================
     REVIEW MODAL - FIXED
     ===================================================== -->
<div id="review-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:500;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:var(--r-xl);max-width:520px;width:100%;padding:32px;box-shadow:var(--shadow-lg);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-family:var(--font-display);font-size:20px;font-weight:700;">Tinjau Pengajuan Cuti</h3>
            <button onclick="closeModal()" style="background:var(--clr-bg);border:1px solid var(--clr-border);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                <i class="ri-close-line"></i>
            </button>
        </div>
        
        <form method="POST" action="" id="review-form">
            <input type="hidden" name="review_cuti" value="1">
            <input type="hidden" name="id" id="review-id" value="">
            <input type="hidden" name="status" id="review-status" value="">
            
            <div id="review-data" style="background:var(--clr-bg);border-radius:var(--r-md);padding:16px;margin-bottom:20px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:12px;color:var(--clr-muted);">Nama</span>
                    <strong id="review-name">-</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:12px;color:var(--clr-muted);">NIP</span>
                    <strong id="review-nip">-</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:12px;color:var(--clr-muted);">Jenis Cuti</span>
                    <strong id="review-jenis">-</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:12px;color:var(--clr-muted);">Durasi</span>
                    <strong id="review-durasi">-</strong>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="font-size:12px;color:var(--clr-muted);">Alasan</span>
                    <strong id="review-alasan" style="max-width:200px;text-align:right;">-</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:1px solid var(--clr-border);">
                    <span style="font-size:12px;color:var(--clr-muted);">Tanggal Pengajuan</span>
                    <strong id="review-tanggal" style="font-size:12px;">-</strong>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Catatan Reviewer</label>
                <textarea name="catatan" id="review-catatan" class="form-control" rows="3" placeholder="Masukkan catatan atau alasan keputusan..." style="resize:vertical;"></textarea>
            </div>
            
            <div style="display:flex;gap:12px;">
                <button type="button" onclick="submitReview('Ditolak')" class="btn btn-outline" style="flex:1;">
                    <i class="ri-close-circle-line"></i> Tolak
                </button>
                <button type="button" onclick="submitReview('Disetujui')" class="btn btn-gold" style="flex:1;">
                    <i class="ri-checkbox-circle-line"></i> Setujui
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Global Toast -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
// Data untuk review modal
const pendingData = <?= json_encode($pendingRequests) ?>;

function openReviewModal(id) {
    console.log('Opening review for ID:', id);
    console.log('Pending Data:', pendingData);
    
    const data = pendingData[id];
    if (!data) {
        console.error('Data tidak ditemukan untuk ID:', id);
        showToast('Data tidak ditemukan!', 'ri-error-warning-line');
        return;
    }
    
    console.log('Data found:', data);
    
    document.getElementById('review-id').value = id;
    document.getElementById('review-name').textContent = data.user_name || '-';
    document.getElementById('review-nip').textContent = data.nip || '-';
    document.getElementById('review-jenis').textContent = data.jenis_cuti || '-';
    document.getElementById('review-durasi').textContent = (data.durasi || 0) + ' hari';
    document.getElementById('review-alasan').textContent = data.alasan || '-';
    document.getElementById('review-tanggal').textContent = data.created_at || '-';
    document.getElementById('review-catatan').value = '';
    
    document.getElementById('review-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('review-modal').style.display = 'none';
}

function submitReview(status) {
    console.log('Submitting review with status:', status);
    document.getElementById('review-status').value = status;
    document.getElementById('review-form').submit();
}

// Close modal on backdrop click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('review-modal');
    if (e.target === modal) closeModal();
});

// Show toast from PHP flash messages
function showToast(msg, icon) {
    if (typeof icon === 'undefined') icon = 'ri-information-line';
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = `<i class="${icon}"></i> ${msg}`;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3000);
}

// Debug: Tampilkan data di console
console.log('Pending Data Count:', Object.keys(pendingData).length);
console.log('Pending Data:', pendingData);
</script>

<?php include 'includes/footer.php'; ?>