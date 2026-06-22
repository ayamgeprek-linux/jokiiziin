<?php
/**
 * =====================================================
 * FILE: admin_laporan.php
 * FUNGSI: Laporan Statistik Profesional (Admin)
 * VERSION: 2.0 - Premium Dashboard Style
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
$bulanan = [];
$userCuti = [];
$departemenCount = [];

if (is_array($allPermohonan) && !empty($allPermohonan)) {
    foreach ($allPermohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        $totalCuti++;
        
        // Statistik status
        $status = $izin['status'] ?? 'Menunggu';
        if (isset($statusCount[$status])) $statusCount[$status]++;
        
        // Statistik jenis cuti
        $jenis = $izin['jenis_cuti'] ?? 'Lainnya';
        if (!isset($jenisCutiCount[$jenis])) $jenisCutiCount[$jenis] = 0;
        $jenisCutiCount[$jenis]++;
        
        // Statistik bulanan
        $bulan = date('Y-m', strtotime($izin['created_at'] ?? 'now'));
        if (!isset($bulanan[$bulan])) $bulanan[$bulan] = 0;
        $bulanan[$bulan]++;
        
        // Statistik per user
        $uid = $izin['user_id'] ?? 'unknown';
        if (!isset($userCuti[$uid])) {
            $userCuti[$uid] = [
                'name' => $izin['user_name'] ?? 'Unknown',
                'total' => 0,
                'disetujui' => 0,
                'ditolak' => 0,
                'menunggu' => 0
            ];
        }
        $userCuti[$uid]['total']++;
        if ($status === 'Disetujui') $userCuti[$uid]['disetujui']++;
        if ($status === 'Ditolak') $userCuti[$uid]['ditolak']++;
        if ($status === 'Menunggu') $userCuti[$uid]['menunggu']++;
        
        // Statistik departemen
        $dep = $izin['departemen'] ?? 'Umum';
        if (!isset($departemenCount[$dep])) $departemenCount[$dep] = 0;
        $departemenCount[$dep]++;
    }
}

// Urutkan userCuti berdasarkan total terbanyak
uasort($userCuti, function($a, $b) {
    return $b['total'] - $a['total'];
});

// Sorting departemen
arsort($departemenCount);

// Hitung persentase
$persentaseDisetujui = $totalCuti > 0 ? round(($statusCount['Disetujui'] / $totalCuti) * 100, 1) : 0;
$persentaseDitolak = $totalCuti > 0 ? round(($statusCount['Ditolak'] / $totalCuti) * 100, 1) : 0;
$persentaseMenunggu = $totalCuti > 0 ? round(($statusCount['Menunggu'] / $totalCuti) * 100, 1) : 0;

// Data untuk chart (JSON)
$chartData = [
    'labels' => array_keys($jenisCutiCount),
    'values' => array_values($jenisCutiCount),
    'colors' => ['#B8860B', '#2D7A4F', '#C0392B', '#3498DB', '#8E44AD', '#E67E22', '#1ABC9C']
];

$bulanData = [
    'labels' => array_keys($bulanan),
    'values' => array_values($bulanan)
];

$currentPage = 'admin-laporan';
include 'includes/header.php';
?>

<!-- =====================================================
     LAPORAN CONTENT - PROFESSIONAL
     ===================================================== -->
<style>
:root {
    --gradient-gold: linear-gradient(135deg, #B8860B, #D4A017, #F4D03F);
    --gradient-dark: linear-gradient(135deg, #1A1A1A, #2D2D2D);
    --gradient-success: linear-gradient(135deg, #2D7A4F, #1ABC9C);
    --gradient-danger: linear-gradient(135deg, #C0392B, #E74C3C);
    --gradient-warning: linear-gradient(135deg, #B8860B, #F39C12);
}

.laporan-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
}

.laporan-header {
    background: var(--gradient-dark);
    border-radius: var(--r-xl);
    padding: 30px 40px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.laporan-header::after {
    content: '📊';
    position: absolute;
    right: 30px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 80px;
    opacity: 0.1;
}
.laporan-header h1 {
    color: #fff;
    font-family: var(--font-display);
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 4px;
}
.laporan-header p {
    color: rgba(255,255,255,0.6);
    font-size: 14px;
}
.laporan-header .header-date {
    color: rgba(255,255,255,0.4);
    font-size: 12px;
    margin-top: 8px;
}

/* Stat Cards Premium */
.stat-cards-premium {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card-premium {
    background: #fff;
    border-radius: var(--r-lg);
    padding: 20px 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}
.stat-card-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.10);
}
.stat-card-premium .stat-icon {
    position: absolute;
    right: 16px;
    top: 16px;
    font-size: 28px;
    opacity: 0.15;
}
.stat-card-premium .stat-label {
    font-size: 12px;
    color: var(--clr-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.stat-card-premium .stat-number {
    font-family: var(--font-display);
    font-size: 32px;
    font-weight: 800;
    margin-top: 4px;
}
.stat-card-premium .stat-change {
    font-size: 11px;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.stat-card-premium .stat-change.positive { color: var(--clr-success); }
.stat-card-premium .stat-change.negative { color: var(--clr-danger); }
.stat-card-premium .stat-change.neutral { color: var(--clr-muted); }

.stat-card-premium.total .stat-number { color: #2C3E50; }
.stat-card-premium.menunggu .stat-number { color: var(--clr-primary); }
.stat-card-premium.disetujui .stat-number { color: var(--clr-success); }
.stat-card-premium.ditolak .stat-number { color: var(--clr-danger); }

/* Premium Grid */
.laporan-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}
.laporan-grid-full {
    grid-template-columns: 1fr;
}

/* Card Premium */
.card-premium {
    background: #fff;
    border-radius: var(--r-lg);
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
}
.card-premium .card-title {
    font-family: var(--font-display);
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.card-premium .card-title .title-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.card-premium .card-title .title-icon.gold { background: rgba(184,134,11,0.12); color: var(--clr-primary); }
.card-premium .card-title .title-icon.green { background: rgba(45,122,79,0.12); color: var(--clr-success); }
.card-premium .card-title .title-icon.blue { background: rgba(52,152,219,0.12); color: #3498DB; }
.card-premium .card-title .title-icon.purple { background: rgba(142,68,173,0.12); color: #8E44AD; }
.card-premium .card-title .title-icon.orange { background: rgba(230,126,34,0.12); color: #E67E22; }

/* Chart Bar */
.chart-bar-container {
    margin-top: 8px;
}
.chart-bar-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.chart-bar-item .bar-label {
    font-size: 13px;
    min-width: 100px;
    color: var(--clr-dark);
    font-weight: 500;
}
.chart-bar-item .bar-track {
    flex: 1;
    height: 24px;
    background: #f0f0f0;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}
.chart-bar-item .bar-track .bar-fill {
    height: 100%;
    border-radius: 12px;
    transition: width 1s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    font-size: 11px;
    font-weight: 600;
    color: #fff;
}
.chart-bar-item .bar-value {
    font-weight: 700;
    font-size: 14px;
    min-width: 40px;
    text-align: right;
}

/* Progress Ring */
.progress-ring-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    text-align: center;
}
.progress-ring-item .ring-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-display);
    font-size: 22px;
    font-weight: 800;
    position: relative;
}
.progress-ring-item .ring-circle::before {
    content: '';
    position: absolute;
    inset: 4px;
    border-radius: 50%;
    background: #fff;
}
.progress-ring-item .ring-circle .ring-text {
    position: relative;
    z-index: 1;
}
.progress-ring-item .ring-label {
    font-size: 12px;
    color: var(--clr-muted);
}
.progress-ring-item .ring-value {
    font-size: 13px;
    font-weight: 600;
}

/* Ring Colors */
.ring-gold { background: conic-gradient(var(--clr-primary) var(--pct), #f0f0f0 var(--pct)); }
.ring-green { background: conic-gradient(var(--clr-success) var(--pct), #f0f0f0 var(--pct)); }
.ring-red { background: conic-gradient(var(--clr-danger) var(--pct), #f0f0f0 var(--pct)); }

/* Departemen List */
.departemen-list .dep-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}
.departemen-list .dep-item:last-child { border-bottom: none; }
.departemen-list .dep-item .dep-name {
    flex: 1;
    font-size: 13px;
}
.departemen-list .dep-item .dep-count {
    font-weight: 700;
    font-size: 14px;
}
.departemen-list .dep-item .dep-bar {
    width: 60px;
    height: 6px;
    background: #f0f0f0;
    border-radius: 3px;
    overflow: hidden;
}
.departemen-list .dep-item .dep-bar .dep-fill {
    height: 100%;
    border-radius: 3px;
}

/* Top Users Table */
.top-users-table {
    width: 100%;
    border-collapse: collapse;
}
.top-users-table th {
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--clr-muted);
    padding: 8px 0;
    border-bottom: 2px solid #f0f0f0;
}
.top-users-table td {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}
.top-users-table tr:last-child td { border-bottom: none; }
.top-users-table .rank {
    font-weight: 700;
    color: var(--clr-muted);
    font-size: 12px;
}
.top-users-table .rank.gold { color: #B8860B; }
.top-users-table .rank.silver { color: #95A5A6; }
.top-users-table .rank.bronze { color: #CD7F32; }

/* Responsive */
@media (max-width: 768px) {
    .stat-cards-premium { grid-template-columns: 1fr 1fr; }
    .laporan-grid { grid-template-columns: 1fr; }
    .progress-ring-container { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .stat-cards-premium { grid-template-columns: 1fr; }
}
</style>

<div class="laporan-container">
    <!-- HEADER -->
    <div class="laporan-header">
        <div>
            <h1>📊 Dashboard Laporan Cuti</h1>
            <p>Analisis statistik dan performa pengajuan cuti karyawan secara real-time</p>
            <div class="header-date">
                <i class="ri-calendar-line"></i> 
                Periode: <?= date('d F Y') ?> | 
                <i class="ri-refresh-line"></i> Update terakhir: <?= date('H:i:s') ?> WIB
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
            <button class="btn btn-gold" onclick="window.location.href='admin_cetak_pdf.php'">
                <i class="ri-printer-line"></i> Cetak Laporan
            </button>
            <button class="btn btn-outline" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);color:#fff;" onclick="window.location.reload()">
                <i class="ri-refresh-line"></i> Refresh
            </button>
        </div>
    </div>

    <!-- STAT CARDS PREMIUM -->
    <div class="stat-cards-premium">
        <div class="stat-card-premium total">
            <div class="stat-icon">📋</div>
            <div class="stat-label">Total Pengajuan</div>
            <div class="stat-number"><?= $totalCuti ?></div>
            <div class="stat-change neutral">
                <i class="ri-file-list-3-line"></i> Semua pengajuan
            </div>
        </div>
        <div class="stat-card-premium menunggu">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Menunggu Review</div>
            <div class="stat-number"><?= $statusCount['Menunggu'] ?></div>
            <div class="stat-change <?= $persentaseMenunggu > 20 ? 'negative' : 'neutral' ?>">
                <i class="ri-time-line"></i> <?= $persentaseMenunggu ?>% dari total
            </div>
        </div>
        <div class="stat-card-premium disetujui">
            <div class="stat-icon">✅</div>
            <div class="stat-label">Disetujui</div>
            <div class="stat-number"><?= $statusCount['Disetujui'] ?></div>
            <div class="stat-change positive">
                <i class="ri-arrow-up-line"></i> <?= $persentaseDisetujui ?>% tingkat persetujuan
            </div>
        </div>
        <div class="stat-card-premium ditolak">
            <div class="stat-icon">❌</div>
            <div class="stat-label">Ditolak</div>
            <div class="stat-number"><?= $statusCount['Ditolak'] ?></div>
            <div class="stat-change negative">
                <i class="ri-arrow-down-line"></i> <?= $persentaseDitolak ?>% tingkat penolakan
            </div>
        </div>
    </div>

    <!-- PROGRESS RING -->
    <div class="card-premium" style="margin-bottom:24px;">
        <div class="card-title">
            <span class="title-icon gold"><i class="ri-pie-chart-2-line"></i></span>
            Distribusi Status Cuti
        </div>
        <div class="progress-ring-container">
            <div class="progress-ring-item">
                <div class="ring-circle ring-gold" style="--pct: <?= $persentaseMenunggu ?>%;">
                    <span class="ring-text" style="color:var(--clr-primary);"><?= $persentaseMenunggu ?>%</span>
                </div>
                <div class="ring-label">Menunggu</div>
                <div class="ring-value"><?= $statusCount['Menunggu'] ?> pengajuan</div>
            </div>
            <div class="progress-ring-item">
                <div class="ring-circle ring-green" style="--pct: <?= $persentaseDisetujui ?>%;">
                    <span class="ring-text" style="color:var(--clr-success);"><?= $persentaseDisetujui ?>%</span>
                </div>
                <div class="ring-label">Disetujui</div>
                <div class="ring-value"><?= $statusCount['Disetujui'] ?> pengajuan</div>
            </div>
            <div class="progress-ring-item">
                <div class="ring-circle ring-red" style="--pct: <?= $persentaseDitolak ?>%;">
                    <span class="ring-text" style="color:var(--clr-danger);"><?= $persentaseDitolak ?>%</span>
                </div>
                <div class="ring-label">Ditolak</div>
                <div class="ring-value"><?= $statusCount['Ditolak'] ?> pengajuan</div>
            </div>
        </div>
    </div>

    <!-- GRID 2 KOLOM -->
    <div class="laporan-grid">
        <!-- Jenis Cuti -->
        <div class="card-premium">
            <div class="card-title">
                <span class="title-icon gold"><i class="ri-file-list-3-line"></i></span>
                Jenis Cuti Terbanyak
            </div>
            <div class="chart-bar-container">
                <?php 
                $maxJenis = max($jenisCutiCount) ?: 1;
                $colors = ['#B8860B', '#2D7A4F', '#3498DB', '#8E44AD', '#E67E22', '#1ABC9C', '#C0392B'];
                $i = 0;
                foreach ($jenisCutiCount as $jenis => $count): 
                    $pct = round(($count / $maxJenis) * 100, 1);
                    $color = $colors[$i % count($colors)];
                ?>
                <div class="chart-bar-item">
                    <span class="bar-label"><?= escape($jenis) ?></span>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;">
                            <?= $count ?>
                        </div>
                    </div>
                    <span class="bar-value"><?= $count ?></span>
                </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>

        <!-- Departemen -->
        <div class="card-premium">
            <div class="card-title">
                <span class="title-icon blue"><i class="ri-building-line"></i></span>
                Pengajuan per Departemen
            </div>
            <div class="departemen-list">
                <?php 
                $maxDep = max($departemenCount) ?: 1;
                $depColors = ['#B8860B', '#2D7A4F', '#3498DB', '#8E44AD', '#E67E22', '#1ABC9C'];
                $i = 0;
                foreach ($departemenCount as $dep => $count): 
                    $pct = round(($count / $maxDep) * 100, 1);
                    $color = $depColors[$i % count($depColors)];
                ?>
                <div class="dep-item">
                    <span class="dep-name"><?= escape($dep) ?></span>
                    <div class="dep-bar">
                        <div class="dep-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                    </div>
                    <span class="dep-count"><?= $count ?></span>
                </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>
    </div>

    <!-- TOP USERS + BULANAN -->
    <div class="laporan-grid">
        <!-- Top Users -->
        <div class="card-premium">
            <div class="card-title">
                <span class="title-icon gold"><i class="ri-trophy-line"></i></span>
                Top 5 Pengaju Cuti Terbanyak
            </div>
            <table class="top-users-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Nama</th>
                        <th style="text-align:center;">Total</th>
                        <th style="text-align:center;">✅</th>
                        <th style="text-align:center;">❌</th>
                        <th style="text-align:center;">⏳</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $userSlice = array_slice($userCuti, 0, 5);
                    foreach ($userSlice as $uid => $data): 
                        $rankClass = $no === 1 ? 'gold' : ($no === 2 ? 'silver' : ($no === 3 ? 'bronze' : ''));
                    ?>
                    <tr>
                        <td><span class="rank <?= $rankClass ?>">#<?= $no++ ?></span></td>
                        <td><?= escape($data['name']) ?></td>
                        <td style="text-align:center;font-weight:700;"><?= $data['total'] ?></td>
                        <td style="text-align:center;color:var(--clr-success);"><?= $data['disetujui'] ?></td>
                        <td style="text-align:center;color:var(--clr-danger);"><?= $data['ditolak'] ?></td>
                        <td style="text-align:center;color:var(--clr-primary);"><?= $data['menunggu'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Bulanan -->
        <div class="card-premium">
            <div class="card-title">
                <span class="title-icon purple"><i class="ri-calendar-2-line"></i></span>
                Tren Pengajuan per Bulan
            </div>
            <div class="chart-bar-container">
                <?php 
                $maxBulan = max($bulanan) ?: 1;
                $bulanColors = ['#B8860B', '#2D7A4F', '#3498DB', '#8E44AD', '#E67E22', '#1ABC9C'];
                $i = 0;
                ksort($bulanan);
                $bulanan = array_slice($bulanan, -6, 6, true);
                foreach ($bulanan as $bulan => $count): 
                    $pct = round(($count / $maxBulan) * 100, 1);
                    $color = $bulanColors[$i % count($bulanColors)];
                ?>
                <div class="chart-bar-item">
                    <span class="bar-label"><?= date('M Y', strtotime($bulan . '-01')) ?></span>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;">
                            <?= $count ?>
                        </div>
                    </div>
                    <span class="bar-value"><?= $count ?></span>
                </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>
    </div>

    <!-- INSIGHT CARD -->
    <div class="card-premium" style="border-left:4px solid var(--clr-primary);">
        <div class="card-title">
            <span class="title-icon gold"><i class="ri-lightbulb-line"></i></span>
            Insight & Rekomendasi
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <div style="background:#f8f9fa;border-radius:var(--r-md);padding:16px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">📈</div>
                <div style="font-size:13px;font-weight:600;">Tingkat Persetujuan</div>
                <div style="font-size:24px;font-weight:800;color:var(--clr-success);"><?= $persentaseDisetujui ?>%</div>
                <div style="font-size:11px;color:var(--clr-muted);">Dari <?= $totalCuti ?> pengajuan</div>
            </div>
            <div style="background:#f8f9fa;border-radius:var(--r-md);padding:16px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">⏱️</div>
                <div style="font-size:13px;font-weight:600;">Rata-rata Durasi Cuti</div>
                <div style="font-size:24px;font-weight:800;color:var(--clr-primary);">
                    <?php 
                    $totalDurasi = 0;
                    if (is_array($allPermohonan)) {
                        foreach ($allPermohonan as $izin) {
                            if (is_array($izin) && isset($izin['durasi'])) {
                                $totalDurasi += (int)$izin['durasi'];
                            }
                        }
                    }
                    $avgDurasi = $totalCuti > 0 ? round($totalDurasi / $totalCuti, 1) : 0;
                    echo $avgDurasi;
                    ?>
                </div>
                <div style="font-size:11px;color:var(--clr-muted);">Hari per pengajuan</div>
            </div>
            <div style="background:#f8f9fa;border-radius:var(--r-md);padding:16px;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">🏢</div>
                <div style="font-size:13px;font-weight:600;">Departemen Teraktif</div>
                <div style="font-size:24px;font-weight:800;color:#3498DB;">
                    <?= !empty($departemenCount) ? escape(array_key_first($departemenCount)) : '-' ?>
                </div>
                <div style="font-size:11px;color:var(--clr-muted);">
                    <?= !empty($departemenCount) ? current($departemenCount) . ' pengajuan' : '' ?>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align:center;padding:20px;color:var(--clr-muted);font-size:12px;border-top:1px solid #e0e0e0;margin-top:20px;">
        <i class="ri-database-2-line"></i> Data real-time dari Firebase Realtime Database | 
        © <?= date('Y') ?> Magang.usg - Sistem Manajemen Cuti
    </div>
</div>

<?php include 'includes/footer.php'; ?>