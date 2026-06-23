<?php
/**
 * =====================================================
 * FILE: profile.php
 * FUNGSI: Halaman Profil User
 * VERSION: 2.0 - Mobile Fixed
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

<style>
/* ===== RESPONSIVE SHOW/HIDE ===== */
@media (min-width: 769px) {
    #mobile-profile-view { display: none !important; }
    .mobile-nav-bar { display: none !important; }
}
@media (max-width: 768px) {
    #desktop-profile-view { display: none !important; }

    * { box-sizing: border-box; }
    body { overflow-x: hidden; width: 100%; }

    #mobile-profile-view {
        display: block !important;
        width: 100%;
        overflow-x: hidden;
        padding-bottom: 90px;
    }

    /* ---- Header Banner ---- */
    .mp-banner {
        background: var(--clr-primary);
        padding: 32px 20px 60px;
        position: relative;
    }
    .mp-banner-title {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        margin: 0 0 2px;
    }
    .mp-banner-sub {
        font-size: 12px;
        color: rgba(255,255,255,0.65);
    }

    /* ---- Avatar overlapping banner ---- */
    .mp-avatar-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: -44px;
        margin-bottom: 12px;
        position: relative;
        z-index: 2;
    }
    .mp-avatar {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: var(--clr-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 32px;
        border: 4px solid var(--clr-surface);
        box-shadow: 0 2px 12px rgba(0,0,0,0.12);
    }
    .mp-name {
        font-size: 17px;
        font-weight: 700;
        color: var(--clr-dark);
        margin-top: 10px;
        text-align: center;
    }
    .mp-role-badge {
        margin-top: 4px;
        font-size: 11px;
        padding: 3px 12px;
        border-radius: 20px;
        background: var(--clr-bg);
        border: 1px solid var(--clr-border);
        color: var(--clr-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    /* ---- Sisa cuti highlight ---- */
    .mp-cuti-bar {
        margin: 0 16px 16px;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-lg);
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .mp-cuti-bar .label {
        font-size: 13px;
        color: var(--clr-muted);
        font-weight: 500;
    }
    .mp-cuti-bar .value {
        font-size: 22px;
        font-weight: 800;
        color: var(--clr-primary);
        line-height: 1;
    }
    .mp-cuti-bar .value span {
        font-size: 13px;
        font-weight: 500;
        color: var(--clr-muted);
        margin-left: 2px;
    }

    /* ---- Info section ---- */
    .mp-section {
        margin: 0 16px 14px;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-lg);
        overflow: hidden;
    }
    .mp-section-title {
        font-size: 11px;
        font-weight: 700;
        color: var(--clr-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px 8px;
        border-bottom: 1px solid var(--clr-border);
    }
    .mp-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 13px 16px;
        border-bottom: 1px solid var(--clr-border);
        gap: 12px;
    }
    .mp-info-row:last-child { border-bottom: none; }
    .mp-info-label {
        font-size: 13px;
        color: var(--clr-muted);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .mp-info-label i { font-size: 16px; }
    .mp-info-value {
        font-size: 13px;
        font-weight: 600;
        color: var(--clr-dark);
        text-align: right;
        word-break: break-all;
        min-width: 0;
    }

    /* ---- Action buttons ---- */
    .mp-actions {
        margin: 0 16px 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .mp-action-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 14px 16px;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--r-lg);
        font-size: 14px;
        font-weight: 600;
        color: var(--clr-dark);
        cursor: pointer;
        text-align: left;
        transition: background .15s;
    }
    .mp-action-btn:active { background: var(--clr-bg); }
    .mp-action-btn i {
        font-size: 18px;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--clr-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .mp-action-btn.danger { color: var(--clr-danger, #ef4444); }
    .mp-action-btn.danger i {
        background: rgba(239,68,68,0.08);
        color: var(--clr-danger, #ef4444);
    }
    .mp-action-btn .chevron {
        margin-left: auto;
        font-size: 18px;
        color: var(--clr-muted);
    }

    /* ---- Footer ---- */
    .mp-footer {
        text-align: center;
        padding: 8px 16px 20px;
        font-size: 11px;
        color: var(--clr-muted);
    }
}
</style>

<!-- =====================================================
     DESKTOP VIEW
     ===================================================== -->
<div id="desktop-profile-view" class="layout-with-sidebar page-with-mobile-nav">
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

<!-- =====================================================
     MOBILE PROFILE VIEW
     ===================================================== -->
<div id="mobile-profile-view" class="page-with-mobile-nav">

    <!-- Banner -->
    <div class="mp-banner">
        <div class="mp-banner-title">Profil Saya</div>
        <div class="mp-banner-sub">Informasi akun dan pengaturan</div>
    </div>

    <!-- Avatar -->
    <div class="mp-avatar-wrap">
        <div class="mp-avatar">
            <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
        </div>
        <div class="mp-name"><?= escape($user['name'] ?? 'User') ?></div>
        <div class="mp-role-badge"><?= escape($user['role'] ?? 'User') ?> &bull; <?= $user['departemen'] ?? '-' ?></div>
    </div>

    <!-- Sisa Cuti Highlight -->
    <div class="mp-cuti-bar">
        <div>
            <div class="label">Sisa Cuti Tersedia</div>
            <div style="font-size:11px;color:var(--clr-muted);margin-top:2px;">Tahun <?= date('Y') ?></div>
        </div>
        <div class="value"><?= $sisaCuti ?><span>hari</span></div>
    </div>

    <!-- Info Pribadi -->
    <div class="mp-section">
        <div class="mp-section-title">Informasi Pribadi</div>
        <div class="mp-info-row">
            <span class="mp-info-label"><i class="ri-user-3-line"></i> Nama Lengkap</span>
            <span class="mp-info-value"><?= escape($user['name'] ?? '-') ?></span>
        </div>
        <div class="mp-info-row">
            <span class="mp-info-label"><i class="ri-mail-line"></i> Email</span>
            <span class="mp-info-value"><?= escape($user['email'] ?? '-') ?></span>
        </div>
        <div class="mp-info-row">
            <span class="mp-info-label"><i class="ri-id-card-line"></i> NIP / NIK</span>
            <span class="mp-info-value"><?= escape($user['nip'] ?? '-') ?></span>
        </div>
    </div>

    <!-- Info Pekerjaan -->
    <div class="mp-section">
        <div class="mp-section-title">Informasi Pekerjaan</div>
        <div class="mp-info-row">
            <span class="mp-info-label"><i class="ri-briefcase-line"></i> Jabatan</span>
            <span class="mp-info-value"><?= escape($user['jabatan'] ?? '-') ?></span>
        </div>
        <div class="mp-info-row">
            <span class="mp-info-label"><i class="ri-building-line"></i> Departemen</span>
            <span class="mp-info-value"><?= escape($user['departemen'] ?? '-') ?></span>
        </div>
        <div class="mp-info-row">
            <span class="mp-info-label"><i class="ri-shield-check-line"></i> Status</span>
            <span class="mp-info-value"><span class="badge badge-success">Aktif</span></span>
        </div>
        <div class="mp-info-row">
            <span class="mp-info-label"><i class="ri-calendar-check-line"></i> Bergabung</span>
            <span class="mp-info-value"><?= formatTanggal($user['created_at'] ?? '') ?></span>
        </div>
    </div>

    <!-- Aksi -->
    <div class="mp-actions">
        <button class="mp-action-btn" onclick="showToast('Fitur edit profil sedang dalam pengembangan')">
            <i class="ri-edit-line"></i>
            <span>Edit Profil</span>
            <i class="ri-arrow-right-s-line chevron"></i>
        </button>
        <button class="mp-action-btn" onclick="showToast('Fitur ganti password sedang dalam pengembangan')">
            <i class="ri-lock-password-line"></i>
            <span>Ganti Password</span>
            <i class="ri-arrow-right-s-line chevron"></i>
        </button>
        <button class="mp-action-btn danger" onclick="if(confirm('Yakin ingin logout?')) window.location.href='logout.php'">
            <i class="ri-logout-box-line"></i>
            <span>Logout</span>
            <i class="ri-arrow-right-s-line chevron"></i>
        </button>
    </div>

    <div class="mp-footer">Magang.usg &copy; <?= date('Y') ?></div>
</div>

<!-- =====================================================
     MOBILE NAV
     ===================================================== -->
<nav class="mobile-nav-bar">
    <button class="mobile-nav-item" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
    <button class="mobile-nav-item" onclick="window.location.href='home.php#ajukan-cuti-mobile'"><i class="ri-add-circle-line"></i>Ajukan</button>
    <button class="mobile-nav-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i>Riwayat</button>
    <button class="mobile-nav-item active"><i class="ri-user-line"></i>Profil</button>
</nav>

<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
function applyView() {
    const isMobile = window.innerWidth <= 768;
    document.getElementById('desktop-profile-view').style.display = isMobile ? 'none' : '';
    document.getElementById('mobile-profile-view').style.display  = isMobile ? 'block' : 'none';
}
applyView();
window.addEventListener('resize', applyView);

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