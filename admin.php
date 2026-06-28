<?php
/**
 * =====================================================
 * FILE: admin.php
 * FUNGSI: Admin Panel - Review + Kelola User
 * VERSION: 6.0 - POPUP FIX
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
            $cutiData = $database->getReference('permohonan/' . $id)->getValue();
            
            $updateData = [
                'status' => $status,
                'reviewed_by' => $user['name'] ?? 'Admin',
                'reviewed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($catatan)) {
                $updateData['catatan_admin'] = $catatan;
            }
            
            $database->getReference('permohonan/' . $id)->update($updateData);
            
            if ($status === 'Disetujui') {
                $durasi = (int)($cutiData['durasi'] ?? 0);
                $userId = $cutiData['user_id'] ?? '';
                
                if ($userId && $durasi > 0) {
                    updateSisaCuti($userId, $durasi, $database);
                }
            }
            
            NotifikasiManager::notifikasiStatusBerubah(
                $database, 
                $cutiData['user_id'] ?? '', 
                $cutiData, 
                $status,
                $catatan
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
// PROSES TAMBAH SISA CUTI
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_cuti'])) {
    $userId = $_POST['user_id'] ?? '';
    $tambah = (int)($_POST['tambah'] ?? 0);
    
    if ($userId && $tambah > 0) {
        $sisaBaru = tambahSisaCuti($userId, $tambah, $database);
        if ($sisaBaru !== false) {
            $_SESSION['flash_message'] = "✅ Sisa cuti berhasil ditambah $tambah hari!";
        } else {
            $error = 'Gagal menambah sisa cuti';
        }
        redirect('admin.php');
    } else {
        $error = 'Pilih karyawan dan masukkan jumlah hari';
    }
}

// =====================================================
// AMBIL DATA DARI FIREBASE
// =====================================================

$allRequests = $database->getReference('permohonan')->getValue();

$pendingRequests = [];
if (is_array($allRequests) && !empty($allRequests)) {
    foreach ($allRequests as $key => $request) {
        if (!is_array($request)) continue;
        if (($request['status'] ?? '') === 'Menunggu') {
            $pendingRequests[$key] = $request;
        }
    }
}

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

$allUsers = $database->getReference('users')->getValue();
if (!is_array($allUsers)) {
    $allUsers = [];
}

$currentPage = 'admin';
include 'includes/header.php';
?>

<!-- =====================================================
     STYLE MODAL
     ===================================================== -->
<style>
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal-overlay.active {
        display: flex !important;
    }
    .modal-box {
        background: #fff;
        border-radius: 16px;
        max-width: 560px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: modalIn 0.3s ease;
    }
    @keyframes modalIn {
        from { transform: scale(0.95) translateY(20px); opacity: 0; }
        to { transform: scale(1) translateY(0); opacity: 1; }
    }
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        border-bottom: 1px solid #e0e0e0;
        background: #fff;
        position: sticky;
        top: 0;
        z-index: 10;
        border-radius: 16px 16px 0 0;
    }
    .modal-header h3 {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
    }
    .modal-close-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: #f5f5f5;
        color: #666;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .modal-close-btn:hover {
        background: #C0392B;
        color: #fff;
        transform: rotate(90deg);
    }
    .modal-body {
        padding: 24px;
    }
    .field-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .field-full {
        grid-column: span 2;
    }
    .field-label {
        font-size: 11px;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .field-value {
        font-size: 14px;
        font-weight: 600;
        color: #1A1A1A;
        margin-top: 2px;
        word-break: break-word;
    }
    .note-box {
        grid-column: span 2;
        background: #f8f5f0;
        padding: 12px 16px;
        border-radius: 8px;
        border-left: 3px solid #B8860B;
    }
    .photo-preview {
        grid-column: span 2;
        margin-top: 8px;
    }
    .photo-preview img {
        max-width: 100%;
        max-height: 250px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        object-fit: contain;
        background: #f8f9fa;
    }
    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 12px;
        background: #fafafa;
        border-radius: 0 0 16px 16px;
    }
    .modal-footer .btn {
        flex: 1;
        padding: 11px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .btn-approve {
        background: #2D7A4F;
        color: #fff;
    }
    .btn-approve:hover { background: #1a5a3a; }
    .btn-reject {
        background: #C0392B;
        color: #fff;
    }
    .btn-reject:hover { background: #922B21; }
    .btn-cancel {
        background: #f5f5f5;
        color: #1A1A1A;
        border: 1px solid #e0e0e0;
    }
    .btn-cancel:hover { background: #e0e0e0; }

    @media (max-width: 480px) {
        .field-group {
            grid-template-columns: 1fr;
        }
        .field-full {
            grid-column: span 1;
        }
        .modal-box {
            max-width: 100%;
            margin: 10px;
        }
        .modal-footer {
            flex-direction: column;
        }
    }
</style>

<!-- =====================================================
     ADMIN CONTENT
     ===================================================== -->
<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            Magang<span>.usg</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Admin Panel</small>
        </div>
        <div class="sidebar-item active"><i class="ri-file-search-line"></i> Review Cuti</div>
        <div class="sidebar-item" onclick="window.location.href='admin_riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='admin_laporan.php'"><i class="ri-file-chart-line"></i> Laporan</div>
        <div class="sidebar-item"><i class="ri-user-settings-line"></i> Kelola User</div>
        <div class="sidebar-bottom">
            <div class="sidebar-pdf-btn" onclick="window.location.href='admin_cetak_pdf.php'"><i class="ri-printer-line"></i> Cetak Laporan PDF</div>
            <div class="sidebar-item"><i class="ri-settings-3-line"></i> Pengaturan</div>
            <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
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
                    <p>Tinjau dan verifikasi pengajuan cuti karyawan.</p>
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

        <!-- =====================================================
             KELOLA USER - TAMBAH SISA CUTI
             ===================================================== -->
        <div class="kelola-user-section" id="kelola-user" style="margin-top:32px;padding-top:24px;border-top:2px solid var(--clr-border);">
            <h3 style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:16px;">👥 Kelola Sisa Cuti Karyawan</h3>
            
            <div class="card" style="padding:24px;margin-bottom:24px;">
                <form method="POST" action="">
                    <input type="hidden" name="tambah_cuti" value="1">
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label class="form-label">Pilih Karyawan <span style="color:red;">*</span></label>
                            <select name="user_id" class="form-control" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php foreach ($allUsers as $uidUser => $dataUser): ?>
                                    <?php if (!is_array($dataUser)) continue; ?>
                                    <option value="<?= $uidUser ?>">
                                        <?= escape($dataUser['name'] ?? 'Unknown') ?> 
                                        (NIP: <?= escape($dataUser['nip'] ?? '-') ?>) 
                                        - Sisa: <?= $dataUser['sisa_cuti'] ?? 12 ?> hari
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tambah Hari <span style="color:red;">*</span></label>
                            <input type="number" name="tambah" class="form-control" placeholder="Contoh: 5" min="1" max="30" required>
                        </div>
                        
                        <div style="display:flex;align-items:flex-end;">
                            <button type="submit" class="btn btn-gold btn-full">
                                <i class="ri-add-line"></i> Tambah Sisa Cuti
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <div class="section-card-header">
                    <div>
                        <h3>📋 Data Karyawan</h3>
                        <p>Semua karyawan dan sisa cuti mereka</p>
                    </div>
                    <span style="font-size:12px;color:var(--clr-muted);">Total: <?= count($allUsers) ?> karyawan</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NIP</th>
                            <th>Jabatan</th>
                            <th>Departemen</th>
                            <th>Sisa Cuti</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allUsers)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--clr-muted);">
                                    Belum ada data karyawan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allUsers as $uidUser => $dataUser): ?>
                                <?php if (!is_array($dataUser)) continue; ?>
                                <tr>
                                    <td><strong><?= escape($dataUser['name'] ?? '-') ?></strong></td>
                                    <td><?= escape($dataUser['nip'] ?? '-') ?></td>
                                    <td><?= escape($dataUser['jabatan'] ?? '-') ?></td>
                                    <td><?= escape($dataUser['departemen'] ?? '-') ?></td>
                                    <td style="font-weight:700;color:var(--clr-primary);">
                                        <?= $dataUser['sisa_cuti'] ?? 12 ?> hari
                                    </td>
                                    <td><span class="badge badge-success">Aktif</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
    </main>
</div>

<!-- =====================================================
     🔥 REVIEW MODAL
     ===================================================== -->
<div id="review-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>📋 Tinjau Pengajuan Cuti</h3>
            <button class="modal-close-btn" onclick="closeReviewModal()">
                <i class="ri-close-line"></i>
            </button>
        </div>
        
        <form method="POST" action="" id="review-form">
            <input type="hidden" name="review_cuti" value="1">
            <input type="hidden" name="id" id="review-id" value="">
            
            <div class="modal-body" id="review-content">
                <div style="text-align:center;padding:20px;color:#999;">
                    <i class="ri-loader-4-line spin"></i> Memuat data...
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeReviewModal()">
                    <i class="ri-close-line"></i> Batal
                </button>
                <button type="submit" name="status" value="Ditolak" class="btn btn-reject">
                    <i class="ri-close-circle-line"></i> Tolak
                </button>
                <button type="submit" name="status" value="Disetujui" class="btn btn-approve">
                    <i class="ri-checkbox-circle-line"></i> Setujui
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Global Toast -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
// =====================================================
// 🔥 DATA UNTUK REVIEW MODAL
// =====================================================
const pendingData = <?= json_encode($pendingRequests) ?>;

console.log('✅ Admin panel loaded');
console.log('📦 Pending Data:', pendingData);
console.log('📊 Jumlah data: ' + Object.keys(pendingData).length);

// =====================================================
// 🔥 OPEN REVIEW MODAL
// =====================================================
function openReviewModal(id) {
    console.log('🔍 Klik Tinjau dengan ID:', id);
    
    const data = pendingData[id];
    console.log('📦 Data ditemukan:', data);
    
    if (!data) {
        alert('⚠️ Data tidak ditemukan! ID: ' + id);
        return;
    }
    
    // Set ID form
    document.getElementById('review-id').value = id;
    
    // Build HTML
    let html = `<div class="field-group">`;
    
    const fields = [
        ['Nama', data.user_name || '-'],
        ['NIP', data.nip || '-'],
        ['Jabatan', data.jabatan || '-'],
        ['Departemen', data.departemen || '-'],
        ['Jenis Cuti', data.jenis_cuti || '-'],
        ['Durasi', (data.durasi || 0) + ' hari'],
        ['Tanggal', (data.tanggal_mulai || '-') + ' s/d ' + (data.tanggal_selesai || '-')],
        ['Alasan', data.alasan || '-'],
    ];
    
    fields.forEach(function(f) {
        var isFull = f[0] === 'Alasan' || f[0] === 'Tanggal';
        html += `
            <div ${isFull ? 'class="field-full"' : ''}>
                <div class="field-label">${f[0]}</div>
                <div class="field-value">${f[1]}</div>
            </div>
        `;
    });
    
    // 🔥 TAMPILKAN FOTO/DOkUMEN
    if (data.dokumen) {
        html += `
            <div class="field-full photo-preview">
                <div class="field-label">📎 Dokumen Pendukung</div>
                <div style="margin-top:6px;">
                    <a href="${data.dokumen}" target="_blank" style="color:#B8860B;text-decoration:underline;display:inline-block;margin-bottom:8px;">
                        <i class="ri-file-pdf-line"></i> Buka Dokumen
                    </a>
                    <br>
                    <img src="${data.dokumen}" alt="Dokumen Cuti" 
                         style="max-width:100%;max-height:250px;border-radius:8px;border:1px solid #e0e0e0;object-fit:contain;background:#f8f9fa;"
                         onerror="this.style.display='none';">
                </div>
            </div>
        `;
    }
    
    if (data.catatan_admin) {
        html += `
            <div class="field-full note-box">
                <div class="label" style="font-size:11px;color:#999;font-weight:600;">📝 Catatan Sebelumnya</div>
                <div class="text" style="margin-top:4px;font-size:13px;color:#1A1A1A;">${data.catatan_admin}</div>
            </div>
        `;
    }
    
    html += `
        <div class="field-full" style="margin-top:8px;">
            <div class="field-label">Catatan Review <span style="color:red;">*</span></div>
            <textarea name="catatan" id="review-catatan" class="form-control" rows="3" 
                      placeholder="Masukkan catatan atau alasan keputusan..." 
                      style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:8px;font-family:inherit;resize:vertical;margin-top:4px;font-size:14px;" required></textarea>
        </div>
    `;
    
    html += `</div>`;
    
    document.getElementById('review-content').innerHTML = html;
    document.getElementById('review-modal').classList.add('active');
    
    console.log('✅ Modal opened for ID:', id);
}

// =====================================================
// 🔥 CLOSE REVIEW MODAL
// =====================================================
function closeReviewModal() {
    document.getElementById('review-modal').classList.remove('active');
    console.log('❌ Modal closed');
}

// =====================================================
// 🔥 CLOSE MODAL - KLIK BACKDROP
// =====================================================
document.addEventListener('click', function(e) {
    var modal = document.getElementById('review-modal');
    if (modal && e.target === modal) {
        closeReviewModal();
    }
});

// =====================================================
// 🔥 CLOSE MODAL - TOMBOL ESC
// =====================================================
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReviewModal();
    }
});

// =====================================================
// 🔥 VALIDASI FORM - CATATAN WAJIB
// =====================================================
document.getElementById('review-form').addEventListener('submit', function(e) {
    var catatan = document.getElementById('review-catatan');
    if (catatan && !catatan.value.trim()) {
        e.preventDefault();
        alert('⚠️ Mohon isi catatan review terlebih dahulu!');
        catatan.focus();
    }
});

// =====================================================
// 🔥 TOAST NOTIFICATION
// =====================================================
function showToast(msg, icon) {
    icon = icon || 'ri-information-line';
    var t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = '<i class="' + icon + '"></i> ' + msg;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(function() {
        t.style.display = 'none';
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>