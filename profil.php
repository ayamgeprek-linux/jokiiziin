<?php
/**
 * =====================================================
 * FILE: profile.php
 * FUNGSI: Halaman Profil User
 * VERSION: 1.0
 * =====================================================
 */

require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

$sisaCuti = getSisaCuti($uid, $database);
$currentPage = 'profile';
include 'includes/header.php';
?>

<!-- =====================================================
     PROFIL CONTENT
     ===================================================== -->
<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            Magang<span>.usg</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small>
        </div>
        <div class="sidebar-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i> Dashboard</div>
        <div class="sidebar-item" onclick="window.location.href='home.php#ajukan-cuti'"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
        <div class="sidebar-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item active"><i class="ri-user-line"></i> Profil</div>
        <div class="sidebar-bottom">
            <div class="sidebar-item"><i class="ri-settings-3-line"></i> Pengaturan</div>
            <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <h1 style="font-family:var(--font-display);font-size:28px;font-weight:800;margin-bottom:24px;">👤 Profil Saya</h1>

        <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;">
            <!-- Avatar Card -->
            <div class="card" style="padding:32px;text-align:center;">
                <div style="width:120px;height:120px;border-radius:50%;background:var(--clr-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:48px;margin:0 auto 16px;border:4px solid var(--clr-border);">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
                </div>
                <h3 style="font-weight:700;"><?= escape($user['name'] ?? 'User') ?></h3>
                <p style="color:var(--clr-muted);font-size:13px;"><?= escape($user['jabatan'] ?? 'Karyawan') ?></p>
                <p style="color:var(--clr-muted);font-size:13px;"><?= escape($user['departemen'] ?? '-') ?></p>
                <span class="badge badge-success" style="margin-top:8px;">Aktif</span>
            </div>

            <!-- Detail Card -->
            <div class="card" style="padding:32px;">
                <h3 style="font-weight:700;margin-bottom:16px;">Informasi Detail</h3>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="font-size:11px;color:var(--clr-muted);text-transform:uppercase;letter-spacing:0.5px;">Nama Lengkap</label>
                        <div style="font-weight:600;font-size:16px;padding:8px 0;"><?= escape($user['name'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label style="font-size:11px;color:var(--clr-muted);text-transform:uppercase;letter-spacing:0.5px;">Email</label>
                        <div style="font-weight:600;font-size:16px;padding:8px 0;"><?= escape($user['email'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label style="font-size:11px;color:var(--clr-muted);text-transform:uppercase;letter-spacing:0.5px;">NIP / NIK</label>
                        <div style="font-weight:600;font-size:16px;padding:8px 0;"><?= escape($user['nip'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label style="font-size:11px;color:var(--clr-muted);text-transform:uppercase;letter-spacing:0.5px;">Jabatan</label>
                        <div style="font-weight:600;font-size:16px;padding:8px 0;"><?= escape($user['jabatan'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label style="font-size:11px;color:var(--clr-muted);text-transform:uppercase;letter-spacing:0.5px;">Departemen</label>
                        <div style="font-weight:600;font-size:16px;padding:8px 0;"><?= escape($user['departemen'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label style="font-size:11px;color:var(--clr-muted);text-transform:uppercase;letter-spacing:0.5px;">Sisa Cuti</label>
                        <div style="font-weight:600;font-size:16px;padding:8px 0;color:var(--clr-primary);"><?= $sisaCuti ?> Hari</div>
                    </div>
                </div>

                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--clr-border);">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <span style="font-size:12px;color:var(--clr-muted);">Status: <span class="badge badge-success">Aktif</span></span>
                        <span style="font-size:12px;color:var(--clr-muted);">Role: <span class="badge badge-dark"><?= $user['role'] ?? 'User' ?></span></span>
                        <span style="font-size:12px;color:var(--clr-muted);">Bergabung: <?= formatTanggal($user['created_at'] ?? '') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;">
            <button class="btn btn-outline" onclick="showToast('Fitur edit profil sedang dalam pengembangan')">
                <i class="ri-edit-line"></i> Edit Profil
            </button>
            <button class="btn btn-outline" onclick="showToast('Fitur ganti password sedang dalam pengembangan')">
                <i class="ri-lock-line"></i> Ganti Password
            </button>
            <button class="btn btn-danger" style="background:var(--clr-danger);color:#fff;" onclick="if(confirm('Yakin ingin logout?')) window.location.href='logout.php'">
                <i class="ri-logout-box-line"></i> Logout
            </button>
        </div>

        
    </main>
</div>

<!-- Mobile Nav -->
<nav class="mobile-nav-bar">
    <button class="mobile-nav-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
    <button class="mobile-nav-item" onclick="window.location.href='home.php#ajukan-cuti-mobile'"><i class="ri-add-circle-line"></i>Ajukan</button>
    <button class="mobile-nav-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i>Riwayat</button>
    <button class="mobile-nav-item active"><i class="ri-user-line"></i>Profil</button>
</nav>

<?php include 'includes/footer.php'; ?>