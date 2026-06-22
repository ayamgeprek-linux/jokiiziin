<?php
/**
 * =====================================================
 * FILE: includes/header.php
 * FUNGSI: Header/Topbar untuk semua halaman
 * VERSION: 2.0 - With Notifikasi
 * =====================================================
 */

// Pastikan user sudah login
if (!isset($user)) {
    $user = $_SESSION['user'] ?? null;
}

if (!isset($currentPage)) {
    $currentPage = 'home';
}

$isAdmin = isset($user['role']) && $user['role'] === 'admin';

// 🔥 LOAD NOTIFIKASI
require_once __DIR__ . '/notifikasi.php';
$notifManager = new NotifikasiManager($database, $uid, $isAdmin);
$unreadCount = $notifManager->getUnreadCount();
$recentNotif = $notifManager->getRecentNotifikasi(5);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>doret.id — Manajemen Cuti Karyawan</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
    
    <style>
        /* 🔥 NOTIFIKASI DROPDOWN STYLE */
        .notif-dropdown {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            width: 360px;
            max-height: 400px;
            overflow-y: auto;
            background: #fff;
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--clr-border);
            z-index: 1000;
        }
        .notif-dropdown.active {
            display: block;
        }
        .notif-dropdown-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--clr-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 14px;
        }
        .notif-dropdown-header .mark-all {
            font-size: 12px;
            color: var(--clr-primary);
            cursor: pointer;
            font-weight: 500;
        }
        .notif-dropdown-header .mark-all:hover {
            text-decoration: underline;
        }
        .notif-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--clr-border);
            cursor: pointer;
            transition: background .2s;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .notif-item:hover {
            background: var(--clr-bg);
        }
        .notif-item.unread {
            background: rgba(184,134,11,.05);
            border-left: 3px solid var(--clr-primary);
        }
        .notif-item .notif-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }
        .notif-item .notif-icon.success { background: var(--clr-success-bg); color: var(--clr-success); }
        .notif-item .notif-icon.warning { background: var(--clr-warning-bg); color: var(--clr-primary); }
        .notif-item .notif-icon.danger { background: var(--clr-danger-bg); color: var(--clr-danger); }
        .notif-item .notif-icon.info { background: var(--clr-bg); color: var(--clr-muted); }
        
        .notif-item .notif-body {
            flex: 1;
        }
        .notif-item .notif-body .notif-judul {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .notif-item .notif-body .notif-pesan {
            font-size: 12px;
            color: var(--clr-muted);
            line-height: 1.4;
        }
        .notif-item .notif-body .notif-waktu {
            font-size: 10px;
            color: var(--clr-muted);
            margin-top: 4px;
        }
        .notif-empty {
            padding: 30px 20px;
            text-align: center;
            color: var(--clr-muted);
        }
        .notif-empty i {
            font-size: 36px;
            display: block;
            margin-bottom: 8px;
            color: var(--clr-border);
        }
        .notif-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #C0392B;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid #fff;
        }
        .notif-btn-wrapper {
            position: relative;
        }
    </style>
</head>
<body>

<!-- =====================================================
     TOPBAR
     ===================================================== -->
<header class="topbar">
    <div class="topbar-logo">Magang<span>.usg</span></div>
    
    <?php if ($isAdmin): ?>
    <!-- Navbar untuk Admin -->
    <nav class="topbar-nav">
        <a href="admin.php" class="<?= $currentPage === 'admin' ? 'active' : '' ?>">Dashboard</a>
        <a href="admin.php" class="<?= $currentPage === 'review' ? 'active' : '' ?>">Review Cuti</a>
        <a href="riwayat.php" class="<?= $currentPage === 'admin-riwayat' ? 'active' : '' ?>">Riwayat</a>
        <a href="admin.php?tab=laporan">Laporan</a>
    </nav>
    <?php else: ?>
    <!-- Navbar untuk User -->
    <nav class="topbar-nav">
        <a href="home.php" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Dashboard</a>
        <a href="home.php#ajukan-cuti" class="<?= $currentPage === 'ajukan' ? 'active' : '' ?>">Ajukan Cuti</a>
        <a href="riwayat.php" class="<?= $currentPage === 'riwayat' ? 'active' : '' ?>">Riwayat</a>
    </nav>
    <?php endif; ?>
    
    <div class="topbar-right">
        <!-- Search -->
        <div class="topbar-search">
            <i class="ri-search-line" style="color:var(--clr-muted);"></i>
            <input type="text" placeholder="Cari...">
        </div>
        
        <!-- Admin Badge -->
        <?php if ($isAdmin): ?>
        <div class="admin-mode-badge">
            <i class="ri-settings-3-line"></i> Admin Mode
        </div>
        <?php endif; ?>
        
        <!-- 🔥 NOTIFIKASI LONCENG -->
        <div class="notif-btn-wrapper">
            <button class="notif-btn" id="notifToggle" onclick="toggleNotif()">
                <i class="ri-notification-3-line"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="notif-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                <?php endif; ?>
            </button>
            
            <!-- Dropdown Notifikasi -->
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header">
                    <span>🔔 Notifikasi</span>
                    <?php if (!empty($recentNotif)): ?>
                        <span class="mark-all" onclick="markAllNotif()">Tandai semua sudah dibaca</span>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($recentNotif)): ?>
                    <div class="notif-empty">
                        <i class="ri-inbox-line"></i>
                        <p>Belum ada notifikasi</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentNotif as $key => $notif): ?>
                        <?php if (!is_array($notif)) continue; ?>
                        <div class="notif-item <?= ($notif['dibaca'] ?? false) ? '' : 'unread' ?>" 
                             onclick="clickNotif('<?= $key ?>', '<?= $notif['link'] ?? '#' ?>')">
                            <div class="notif-icon <?= $notif['type'] ?? 'info' ?>">
                                <i class="ri-notification-3-line"></i>
                            </div>
                            <div class="notif-body">
                                <div class="notif-judul"><?= escape($notif['judul'] ?? 'Notifikasi') ?></div>
                                <div class="notif-pesan"><?= escape($notif['pesan'] ?? '') ?></div>
                                <div class="notif-waktu"><?= formatTanggalWaktu($notif['created_at'] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User Info -->
        <div class="user-info">
            <strong><?= escape($user['name'] ?? 'User') ?></strong>
            <small><?= escape($user['jabatan'] ?? 'Karyawan') ?></small>
        </div>
        
        <!-- Avatar -->
        <div class="topbar-avatar">
            <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
        </div>
    </div>
</header>

<!-- 🔥 SCRIPT NOTIFIKASI -->
<script>
// Toggle dropdown notifikasi
function toggleNotif() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('active');
}

// Tutup dropdown jika klik di luar
document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notif-btn-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('notifDropdown').classList.remove('active');
    }
});

// Klik notifikasi
function clickNotif(id, link) {
    // Tandai sebagai dibaca via AJAX
    fetch('ajax_mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (link && link !== '#') {
            window.location.href = link;
        } else {
            location.reload();
        }
    })
    .catch(() => {
        if (link && link !== '#') {
            window.location.href = link;
        }
    });
}

// Tandai semua notifikasi sudah dibaca
function markAllNotif() {
    fetch('ajax_mark_all_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        location.reload();
    })
    .catch(() => {
        location.reload();
    });
}

// Auto refresh notifikasi setiap 30 detik
setInterval(function() {
    fetch('ajax_get_notif_count.php')
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector('.notif-badge');
        if (data.count > 0) {
            if (badge) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
            } else {
                const btn = document.querySelector('.notif-btn');
                if (btn) {
                    btn.innerHTML += `<span class="notif-badge">${data.count > 99 ? '99+' : data.count}</span>`;
                }
            }
        } else {
            if (badge) badge.remove();
        }
    });
}, 30000);
</script>