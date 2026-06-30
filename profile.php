<?php
/**
 * =====================================================
 * FILE: profile.php
 * FUNGSI: Halaman Profil User
 * VERSION: FINAL - Fixed Admin Role
 * =====================================================
 */

require_once 'config/firebase.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Cek login dulu
requireLogin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// 🔥 CEK: Apakah user masih admin?
$isAdmin = isset($user['role']) && $user['role'] === 'admin';

// 🔥 Jika admin, pastikan session tetap menyimpan role admin
if ($isAdmin && isset($_SESSION['user'])) {
    $_SESSION['user']['role'] = 'admin';
}

$sisaCuti = getSisaCuti($uid, $database);

$currentPage = 'profile';
include 'includes/header.php';
?>

<!-- =====================================================
     PROFIL CONTENT - SAMA UNTUK USER & ADMIN
     ===================================================== -->
<style>
.profile-container { max-width: 800px; margin: 0 auto; }
.profile-card {
    background: var(--clr-surface);
    border: 1px solid var(--clr-border);
    border-radius: var(--r-lg);
    padding: 32px;
    box-shadow: var(--shadow-sm);
}
.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: var(--clr-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 36px;
    margin: 0 auto 16px;
    border: 4px solid var(--clr-border);
}
.profile-name { text-align: center; font-size: 24px; font-weight: 700; margin-bottom: 4px; }
.profile-role { text-align: center; color: var(--clr-muted); font-size: 14px; margin-bottom: 24px; }
.profile-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.profile-info .info-item {
    padding: 12px 16px;
    background: var(--clr-bg);
    border-radius: var(--r-md);
}
.profile-info .info-item .label {
    font-size: 11px;
    color: var(--clr-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.profile-info .info-item .value {
    font-size: 16px;
    font-weight: 600;
    margin-top: 2px;
    color: var(--clr-dark);
}
.profile-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    flex-wrap: wrap;
}
.profile-actions .btn { flex: 1; min-width: 120px; }

@media (max-width: 768px) {
    .profile-card { padding: 20px; }
    .profile-avatar { width: 72px; height: 72px; font-size: 28px; }
    .profile-name { font-size: 20px; }
    .profile-info { grid-template-columns: 1fr; }
    .profile-info .info-item { padding: 10px 14px; }
    .profile-actions .btn { flex: 1 1 100%; }
}
</style>

<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            Magang<span>.usg</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small>
        </div>
        
        <!-- 🔥 MENU SIDEBAR - Menyesuaikan Role -->
        <?php if ($isAdmin): ?>
            <!-- Menu Admin -->
            <div class="sidebar-item" onclick="window.location.href='admin.php'"><i class="ri-file-search-line"></i> Review Cuti</div>
            <div class="sidebar-item" onclick="window.location.href='admin_riwayat.php'"><i class="ri-history-line"></i> Riwayat Admin</div>
            <div class="sidebar-item" onclick="window.location.href='admin_laporan.php'"><i class="ri-file-chart-line"></i> Laporan</div>
            <div class="sidebar-item active"><i class="ri-user-line"></i> Profil</div>
            <div class="sidebar-item"><i class="ri-user-settings-line"></i> Kelola User</div>
            <div class="sidebar-bottom">
                <div class="sidebar-item"><i class="ri-settings-3-line"></i> Pengaturan</div>
                <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
            </div>
        <?php else: ?>
            <!-- Menu User -->
            <div class="sidebar-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i> Dashboard</div>
            <div class="sidebar-item" onclick="window.location.href='home.php#ajukan-cuti'"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
            <div class="sidebar-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
            <div class="sidebar-item active"><i class="ri-user-line"></i> Profil</div>
            <div class="sidebar-bottom">
                <div class="sidebar-item"><i class="ri-settings-3-line"></i> Pengaturan</div>
                <div class="sidebar-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
            </div>
        <?php endif; ?>
    </aside>

    <main class="main-content">
        <div class="profile-container">
            <h1 style="font-family:var(--font-display);font-size:28px;font-weight:800;margin-bottom:24px;">Profil Saya</h1>

            <div class="profile-card">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
                </div>
                
                <div class="profile-name"><?= escape($user['name'] ?? 'User') ?></div>
                <div class="profile-role">
                    <?= escape($user['jabatan'] ?? 'Karyawan') ?> · 
                    <?= escape($user['departemen'] ?? '-') ?>
                    <?php if ($isAdmin): ?>
                        <span class="badge badge-warning" style="margin-left:8px;">Admin</span>
                    <?php else: ?>
                        <span class="badge badge-success" style="margin-left:8px;">Aktif</span>
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <div class="info-item"><div class="label">Nama Lengkap</div><div class="value"><?= escape($user['name'] ?? '-') ?></div></div>
                    <div class="info-item"><div class="label">Email</div><div class="value"><?= escape($user['email'] ?? '-') ?></div></div>
                    <div class="info-item"><div class="label">NIP / NIK</div><div class="value"><?= escape($user['nip'] ?? '-') ?></div></div>
                    <div class="info-item"><div class="label">Jabatan</div><div class="value"><?= escape($user['jabatan'] ?? '-') ?></div></div>
                    <div class="info-item"><div class="label">Departemen</div><div class="value"><?= escape($user['departemen'] ?? '-') ?></div></div>
                    <div class="info-item" style="border-left:3px solid var(--clr-primary);">
                        <div class="label">Sisa Cuti</div>
                        <div class="value" style="color:var(--clr-primary);"><?= $sisaCuti ?> Hari</div>
                    </div>
                    <div class="info-item"><div class="label">Role</div><div class="value"><span class="badge badge-dark"><?= $user['role'] ?? 'User' ?></span></div></div>
                    <div class="info-item"><div class="label">Bergabung</div><div class="value"><?= formatTanggal($user['created_at'] ?? '') ?></div></div>
                </div>

                <div class="profile-actions">
                    <button class="btn btn-outline" onclick="showToast('Fitur edit profil sedang dalam pengembangan')">
                        <i class="ri-edit-line"></i> Edit Profil
                    </button>
                    <button class="btn btn-outline" onclick="showToast('Fitur ganti password sedang dalam pengembangan')">
                        <i class="ri-lock-line"></i> Ganti Password
                    </button>
                    <?php if ($isAdmin): ?>
                        <button class="btn btn-gold" onclick="window.location.href='admin.php'">
                            <i class="ri-file-search-line"></i> Kembali ke Admin
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-danger" style="background:var(--clr-danger);color:#fff;" onclick="if(confirm('Yakin ingin logout?')) window.location.href='logout.php'">
                        <i class="ri-logout-box-line"></i> Logout
                    </button>
                </div>
            </div>
        </div>

        
    </main>
</div>

<!-- Mobile Nav -->
<nav class="mobile-nav-bar">
    <?php if ($isAdmin): ?>
        <!-- Admin Mobile -->
        <button class="mobile-nav-item" onclick="window.location.href='admin.php'"><i class="ri-file-search-line"></i>Review</button>
        <button class="mobile-nav-item" onclick="window.location.href='admin_riwayat.php'"><i class="ri-history-line"></i>Riwayat</button>
        <button class="mobile-nav-item" onclick="window.location.href='admin_laporan.php'"><i class="ri-file-chart-line"></i>Laporan</button>
        <button class="mobile-nav-item active"><i class="ri-user-line"></i>Profil</button>
        <button class="mobile-nav-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i>Keluar</button>
    <?php else: ?>
        <!-- User Mobile -->
        <button class="mobile-nav-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
        <button class="mobile-nav-item" onclick="window.location.href='home.php#ajukan-cuti'"><i class="ri-add-circle-line"></i>Ajukan</button>
        <button class="mobile-nav-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i>Riwayat</button>
        <button class="mobile-nav-item active"><i class="ri-user-line"></i>Profil</button>
        <button class="mobile-nav-item" onclick="window.location.href='logout.php'"><i class="ri-logout-box-line"></i>Keluar</button>
    <?php endif; ?>
</nav>

<!-- Global Toast -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
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