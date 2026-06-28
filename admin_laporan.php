<?php
/**
 * =====================================================
 * FILE: admin_laporan.php
 * FUNGSI: Laporan Statistik (Admin)
 * VERSION: 3.0 - With User Data
 * =====================================================
 */

require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// =====================================================
// AMBIL DATA
// =====================================================

$allPermohonan = $database->getReference('permohonan')->getValue();
$allUsers = $database->getReference('users')->getValue();

$totalCuti = 0;
$statusCount = ['Menunggu' => 0, 'Disetujui' => 0, 'Ditolak' => 0, 'Selesai' => 0];
$jenisCutiCount = [];
$departemenCount = [];
$userCutiCount = [];

if (is_array($allPermohonan) && !empty($allPermohonan)) {
    foreach ($allPermohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        $totalCuti++;
        
        $status = $izin['status'] ?? 'Menunggu';
        if (isset($statusCount[$status])) $statusCount[$status]++;
        
        $jenis = $izin['jenis_cuti'] ?? 'Lainnya';
        if (!isset($jenisCutiCount[$jenis])) $jenisCutiCount[$jenis] = 0;
        $jenisCutiCount[$jenis]++;
        
        $dep = $izin['departemen'] ?? 'Umum';
        if (!isset($departemenCount[$dep])) $departemenCount[$dep] = 0;
        $departemenCount[$dep]++;
        
        // 🔥 Hitung per user
        $userId = $izin['user_id'] ?? 'unknown';
        if (!isset($userCutiCount[$userId])) {
            $userCutiCount[$userId] = [
                'name' => $izin['user_name'] ?? 'Unknown',
                'total' => 0
            ];
        }
        $userCutiCount[$userId]['total']++;
    }
}

// Sorting
arsort($jenisCutiCount);
arsort($departemenCount);
uasort($userCutiCount, function($a, $b) {
    return $b['total'] - $a['total'];
});

$persentaseDisetujui = $totalCuti > 0 ? round(($statusCount['Disetujui'] / $totalCuti) * 100, 1) : 0;
$persentaseDitolak = $totalCuti > 0 ? round(($statusCount['Ditolak'] / $totalCuti) * 100, 1) : 0;
$persentaseMenunggu = $totalCuti > 0 ? round(($statusCount['Menunggu'] / $totalCuti) * 100, 1) : 0;

$currentPage = 'admin-laporan';
include 'includes/header.php';
?>

<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            Magang<span>.usg</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Admin Panel</small>
        </div>
        <div class="sidebar-item" onclick="window.location.href='admin.php'"><i class="ri-file-search-line"></i> Review Cuti</div>
        <div class="sidebar-item" onclick="window.location.href='admin_riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item active"><i class="ri-file-chart-line"></i> Laporan</div>
        <div class="sidebar-bottom">
            <div class="sidebar-pdf-btn" onclick="window.location.href='admin_cetak_pdf.php'"><i class="ri-printer-line"></i> Cetak Laporan PDF</div>
            <div class="sidebar-item"><i class="ri-settings-3-line"></i> Pengaturan</div>
            <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:var(--font-display);font-size:28px;font-weight:800;">📊 Laporan Cuti</h1>
                <p style="color:var(--clr-muted);font-size:14px;">Ringkasan statistik pengajuan cuti</p>
            </div>
            <button class="btn btn-gold" onclick="window.location.href='admin_cetak_pdf.php'">
                <i class="ri-printer-line"></i> Cetak PDF
            </button>
        </div>

        <!-- STAT CARDS -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
            <div class="card" style="padding:20px;text-align:center;">
                <div style="font-size:32px;font-weight:800;"><?= $totalCuti ?></div>
                <div style="color:var(--clr-muted);font-size:13px;">Total Pengajuan</div>
            </div>
            <div class="card" style="padding:20px;text-align:center;border-bottom:3px solid var(--clr-primary);">
                <div style="font-size:32px;font-weight:800;color:var(--clr-primary);"><?= $statusCount['Menunggu'] ?></div>
                <div style="color:var(--clr-muted);font-size:13px;">Menunggu (<?= $persentaseMenunggu ?>%)</div>
            </div>
            <div class="card" style="padding:20px;text-align:center;border-bottom:3px solid var(--clr-success);">
                <div style="font-size:32px;font-weight:800;color:var(--clr-success);"><?= $statusCount['Disetujui'] ?></div>
                <div style="color:var(--clr-muted);font-size:13px;">Disetujui (<?= $persentaseDisetujui ?>%)</div>
            </div>
            <div class="card" style="padding:20px;text-align:center;border-bottom:3px solid var(--clr-danger);">
                <div style="font-size:32px;font-weight:800;color:var(--clr-danger);"><?= $statusCount['Ditolak'] ?></div>
                <div style="color:var(--clr-muted);font-size:13px;">Ditolak (<?= $persentaseDitolak ?>%)</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
            <!-- JENIS CUTI -->
            <div class="card" style="padding:24px;">
                <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">📋 Jenis Cuti</h3>
                <?php if (empty($jenisCutiCount)): ?>
                    <p style="color:var(--clr-muted);">Belum ada data</p>
                <?php else: ?>
                    <?php foreach ($jenisCutiCount as $jenis => $count): ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--clr-border);">
                            <span><?= escape($jenis) ?></span>
                            <span style="font-weight:600;"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- DEPARTEMEN -->
            <div class="card" style="padding:24px;">
                <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">🏢 Departemen</h3>
                <?php if (empty($departemenCount)): ?>
                    <p style="color:var(--clr-muted);">Belum ada data</p>
                <?php else: ?>
                    <?php foreach ($departemenCount as $dep => $count): ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--clr-border);">
                            <span><?= escape($dep) ?></span>
                            <span style="font-weight:600;"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 🔥 TOP USER CUTI -->
        <div class="card" style="padding:24px;margin-bottom:24px;">
            <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">👥 Top Pengaju Cuti</h3>
            <?php if (empty($userCutiCount)): ?>
                <p style="color:var(--clr-muted);">Belum ada data</p>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <?php 
                    $topUsers = array_slice($userCutiCount, 0, 10);
                    $no = 1;
                    foreach ($topUsers as $userId => $data): 
                        $bgColor = $no <= 3 ? 'rgba(184,134,11,0.1)' : 'transparent';
                        $borderColor = $no === 1 ? 'var(--clr-primary)' : ($no === 2 ? '#C0C0C0' : ($no === 3 ? '#CD7F32' : 'var(--clr-border)'));
                    ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 12px;background:<?= $bgColor ?>;border-radius:var(--r-sm);border-left:3px solid <?= $borderColor ?>;">
                            <span>
                                <span style="font-weight:700;color:var(--clr-muted);font-size:12px;">#<?= $no++ ?></span>
                                <?= escape($data['name']) ?>
                            </span>
                            <span style="font-weight:700;color:var(--clr-primary);"><?= $data['total'] ?>x</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="padding:16px 20px;background:#f8f9fa;border-radius:var(--r-md);border:1px solid var(--clr-border);">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;">
                <div>
                    <div style="font-size:11px;color:var(--clr-muted);">Tingkat Persetujuan</div>
                    <div style="font-size:20px;font-weight:800;color:var(--clr-success);"><?= $persentaseDisetujui ?>%</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--clr-muted);">Tingkat Penolakan</div>
                    <div style="font-size:20px;font-weight:800;color:var(--clr-danger);"><?= $persentaseDitolak ?>%</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--clr-muted);">Menunggu</div>
                    <div style="font-size:20px;font-weight:800;color:var(--clr-primary);"><?= $persentaseMenunggu ?>%</div>
                </div>
            </div>
        </div>

        <!-- 🔥 DATA KARYAWAN YANG SUDAH CUTI -->
        <div style="margin-top:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
                <div>
                    <h3 style="font-size:16px;font-weight:700;margin:0;">📋 Data Karyawan & Sisa Cuti</h3>
                </div>
                <span style="font-size:12px;color:var(--clr-muted);">Total karyawan: <?= is_array($allUsers) ? count($allUsers) : 0 ?></span>
            </div>
            <div class="card" style="padding:0;overflow:hidden;">
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>NIP</th>
                                <th>Jabatan</th>
                                <th>Departemen</th>
                                <th>Sisa Cuti</th>
                                <th>Total Cuti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allUsers)): ?>
                                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--clr-muted);">Belum ada data karyawan</td></tr>
                            <?php else: ?>
                                <?php foreach ($allUsers as $uidUser => $dataUser): ?>
                                    <?php if (!is_array($dataUser)) continue; ?>
                                    <tr>
                                        <td><strong><?= escape($dataUser['name'] ?? '-') ?></strong></td>
                                        <td><?= escape($dataUser['nip'] ?? '-') ?></td>
                                        <td><?= escape($dataUser['jabatan'] ?? '-') ?></td>
                                        <td><?= escape($dataUser['departemen'] ?? '-') ?></td>
                                        <td style="font-weight:700;color:var(--clr-primary);"><?= $dataUser['sisa_cuti'] ?? 12 ?> hari</td>
                                        <td>
                                            <?php 
                                            $totalUserCuti = $userCutiCount[$uidUser]['total'] ?? 0;
                                            echo $totalUserCuti . 'x';
                                            ?>
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

<?php include 'includes/footer.php'; ?>