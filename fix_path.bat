cd C:\xampp-new\htdocs\izin

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "FIX PATH SEMUA FILE BY doret.joki" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# ========================================
# 1. FIX AUTH/LOGIN.PHP
# ========================================
Write-Host "🔧 Fixing auth/login.php..." -ForegroundColor Yellow
(Get-Content auth/login.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content auth/login.php
(Get-Content auth/login.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content auth/login.php
(Get-Content auth/login.php) -replace "require_once 'includes/functions.php'", "require_once __DIR__ . '/../includes/functions.php'" | Set-Content auth/login.php
(Get-Content auth/login.php) -replace 'href="assets/css/style.css"', 'href="../assets/css/style.css"' | Set-Content auth/login.php
(Get-Content auth/login.php) -replace 'src="assets/js/app.js"', 'src="../assets/js/app.js"' | Set-Content auth/login.php

# ========================================
# 2. FIX ADMIN/INDEX.PHP
# ========================================
Write-Host "🔧 Fixing admin/index.php..." -ForegroundColor Yellow
(Get-Content admin/index.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "require_once 'includes/functions.php'", "require_once __DIR__ . '/../includes/functions.php'" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "require_once 'includes/notifikasi.php'", "require_once __DIR__ . '/../includes/notifikasi.php'" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "header\('Location: login.php'\)", "header('Location: ../auth/login.php')" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "header\('Location: home.php'\)", "header('Location: ../user/index.php')" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "header\('Location: admin.php'\)", "header('Location: index.php')" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "window.location.href='admin_riwayat.php'", "window.location.href='riwayat.php'" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "window.location.href='admin_laporan.php'", "window.location.href='laporan.php'" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "window.location.href='admin_cetak_pdf.php'", "window.location.href='cetak_pdf.php'" | Set-Content admin/index.php
(Get-Content admin/index.php) -replace "window.location.href='logout.php'", "window.location.href='../auth/logout.php'" | Set-Content admin/index.php

# ========================================
# 3. FIX ADMIN/RIWAYAT.PHP
# ========================================
Write-Host "🔧 Fixing admin/riwayat.php..." -ForegroundColor Yellow
(Get-Content admin/riwayat.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content admin/riwayat.php
(Get-Content admin/riwayat.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content admin/riwayat.php
(Get-Content admin/riwayat.php) -replace "require_once 'includes/functions.php'", "require_once __DIR__ . '/../includes/functions.php'" | Set-Content admin/riwayat.php
(Get-Content admin/riwayat.php) -replace "window.location.href='admin.php'", "window.location.href='index.php'" | Set-Content admin/riwayat.php
(Get-Content admin/riwayat.php) -replace "window.location.href='admin_laporan.php'", "window.location.href='laporan.php'" | Set-Content admin/riwayat.php
(Get-Content admin/riwayat.php) -replace "window.location.href='admin_cetak_pdf.php'", "window.location.href='cetak_pdf.php'" | Set-Content admin/riwayat.php
(Get-Content admin/riwayat.php) -replace "window.location.href='logout.php'", "window.location.href='../auth/logout.php'" | Set-Content admin/riwayat.php
(Get-Content admin/riwayat.php) -replace "window.location.href='home.php'", "window.location.href='../user/index.php'" | Set-Content admin/riwayat.php
(Get-Content admin/riwayat.php) -replace "window.location.href='profile.php'", "window.location.href='../user/profile.php'" | Set-Content admin/riwayat.php

# ========================================
# 4. FIX ADMIN/LAPORAN.PHP
# ========================================
Write-Host "🔧 Fixing admin/laporan.php..." -ForegroundColor Yellow
(Get-Content admin/laporan.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content admin/laporan.php
(Get-Content admin/laporan.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content admin/laporan.php
(Get-Content admin/laporan.php) -replace "require_once 'includes/functions.php'", "require_once __DIR__ . '/../includes/functions.php'" | Set-Content admin/laporan.php
(Get-Content admin/laporan.php) -replace "window.location.href='admin.php'", "window.location.href='index.php'" | Set-Content admin/laporan.php
(Get-Content admin/laporan.php) -replace "window.location.href='admin_riwayat.php'", "window.location.href='riwayat.php'" | Set-Content admin/laporan.php
(Get-Content admin/laporan.php) -replace "window.location.href='admin_cetak_pdf.php'", "window.location.href='cetak_pdf.php'" | Set-Content admin/laporan.php
(Get-Content admin/laporan.php) -replace "window.location.href='logout.php'", "window.location.href='../auth/logout.php'" | Set-Content admin/laporan.php
(Get-Content admin/laporan.php) -replace "window.location.href='profile.php'", "window.location.href='../user/profile.php'" | Set-Content admin/laporan.php

# ========================================
# 5. FIX ADMIN/CETAK_PDF.PHP
# ========================================
Write-Host "🔧 Fixing admin/cetak_pdf.php..." -ForegroundColor Yellow
(Get-Content admin/cetak_pdf.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content admin/cetak_pdf.php
(Get-Content admin/cetak_pdf.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content admin/cetak_pdf.php
(Get-Content admin/cetak_pdf.php) -replace "require_once 'includes/functions.php'", "require_once __DIR__ . '/../includes/functions.php'" | Set-Content admin/cetak_pdf.php
(Get-Content admin/cetak_pdf.php) -replace "window.location.href='admin_laporan.php'", "window.location.href='laporan.php'" | Set-Content admin/cetak_pdf.php

# ========================================
# 6. FIX USER/INDEX.PHP
# ========================================
Write-Host "🔧 Fixing user/index.php..." -ForegroundColor Yellow
(Get-Content user/index.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content user/index.php
(Get-Content user/index.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content user/index.php
(Get-Content user/index.php) -replace "require_once 'includes/functions.php'", "require_once __DIR__ . '/../includes/functions.php'" | Set-Content user/index.php
(Get-Content user/index.php) -replace "require_once 'includes/notifikasi.php'", "require_once __DIR__ . '/../includes/notifikasi.php'" | Set-Content user/index.php
(Get-Content user/index.php) -replace "require_once 'config/supabase.php'", "require_once __DIR__ . '/../config/supabase.php'" | Set-Content user/index.php
(Get-Content user/index.php) -replace "header\('Location: login.php'\)", "header('Location: ../auth/login.php')" | Set-Content user/index.php
(Get-Content user/index.php) -replace "header\('Location: home.php'\)", "header('Location: index.php')" | Set-Content user/index.php
(Get-Content user/index.php) -replace "window.location.href='riwayat.php'", "window.location.href='riwayat.php'" | Set-Content user/index.php
(Get-Content user/index.php) -replace "window.location.href='profile.php'", "window.location.href='profile.php'" | Set-Content user/index.php
(Get-Content user/index.php) -replace "window.location.href='logout.php'", "window.location.href='../auth/logout.php'" | Set-Content user/index.php

# ========================================
# 7. FIX USER/RIWAYAT.PHP
# ========================================
Write-Host "🔧 Fixing user/riwayat.php..." -ForegroundColor Yellow
(Get-Content user/riwayat.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content user/riwayat.php
(Get-Content user/riwayat.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content user/riwayat.php
(Get-Content user/riwayat.php) -replace "require_once 'includes/functions.php'", "require_once __DIR__ . '/../includes/functions.php'" | Set-Content user/riwayat.php
(Get-Content user/riwayat.php) -replace "window.location.href='home.php'", "window.location.href='index.php'" | Set-Content user/riwayat.php
(Get-Content user/riwayat.php) -replace "window.location.href='profile.php'", "window.location.href='profile.php'" | Set-Content user/riwayat.php
(Get-Content user/riwayat.php) -replace "window.location.href='logout.php'", "window.location.href='../auth/logout.php'" | Set-Content user/riwayat.php

# ========================================
# 8. FIX USER/PROFILE.PHP
# ========================================
Write-Host "🔧 Fixing user/profile.php..." -ForegroundColor Yellow
(Get-Content user/profile.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content user/profile.php
(Get-Content user/profile.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content user/profile.php
(Get-Content user/profile.php) -replace "require_once 'includes/functions.php'", "require_once __DIR__ . '/../includes/functions.php'" | Set-Content user/profile.php
(Get-Content user/profile.php) -replace "window.location.href='home.php'", "window.location.href='index.php'" | Set-Content user/profile.php
(Get-Content user/profile.php) -replace "window.location.href='riwayat.php'", "window.location.href='riwayat.php'" | Set-Content user/profile.php
(Get-Content user/profile.php) -replace "window.location.href='logout.php'", "window.location.href='../auth/logout.php'" | Set-Content user/profile.php

# ========================================
# 9. FIX AJAX FILES
# ========================================
Write-Host "🔧 Fixing ajax files..." -ForegroundColor Yellow
(Get-Content ajax/mark_read.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content ajax/mark_read.php
(Get-Content ajax/mark_read.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content ajax/mark_read.php
(Get-Content ajax/mark_read.php) -replace "require_once 'includes/notifikasi.php'", "require_once __DIR__ . '/../includes/notifikasi.php'" | Set-Content ajax/mark_read.php

(Get-Content ajax/mark_all_read.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content ajax/mark_all_read.php
(Get-Content ajax/mark_all_read.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content ajax/mark_all_read.php
(Get-Content ajax/mark_all_read.php) -replace "require_once 'includes/notifikasi.php'", "require_once __DIR__ . '/../includes/notifikasi.php'" | Set-Content ajax/mark_all_read.php

(Get-Content ajax/delete_notif.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content ajax/delete_notif.php
(Get-Content ajax/delete_notif.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content ajax/delete_notif.php
(Get-Content ajax/delete_notif.php) -replace "require_once 'includes/notifikasi.php'", "require_once __DIR__ . '/../includes/notifikasi.php'" | Set-Content ajax/delete_notif.php

(Get-Content ajax/get_notif_count.php) -replace "require_once 'config/firebase.php'", "require_once __DIR__ . '/../config/firebase.php'" | Set-Content ajax/get_notif_count.php
(Get-Content ajax/get_notif_count.php) -replace "require_once 'includes/auth.php'", "require_once __DIR__ . '/../includes/auth.php'" | Set-Content ajax/get_notif_count.php
(Get-Content ajax/get_notif_count.php) -replace "require_once 'includes/notifikasi.php'", "require_once __DIR__ . '/../includes/notifikasi.php'" | Set-Content ajax/get_notif_count.php

# ========================================
# 10. FIX INCLUDES/FUNCTIONS.PHP - base_url
# ========================================
Write-Host "🔧 Fixing includes/functions.php (base_url)..." -ForegroundColor Yellow
$content = Get-Content includes/functions.php -Raw
if ($content -match "function base_url") {
    Write-Host "✅ base_url sudah ada" -ForegroundColor Green
} else {
    Write-Host "⚠️ base_url tidak ditemukan, menambahkan..." -ForegroundColor Yellow
    $newFunction = @"
function base_url(`$path = '') {
    `$host = `$_SERVER['HTTP_HOST'] ?? '';
    `$isLocal = strpos(`$host, 'localhost') !== false || 
               strpos(`$host, '127.0.0.1') !== false ||
               strpos(`$host, '192.168.') !== false;
    
    if (`$isLocal) {
        `$base = '/izin';
    } else {
        `$base = '';
    }
    
    `$path = ltrim(`$path, '/');
    if (empty(`$path)) {
        return `$base . '/';
    }
    return `$base . '/' . `$path;
}
"@
    Add-Content -Path includes/functions.php -Value "`n`n$newFunction"
}

# ========================================
# SELESAI
# ========================================
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ SEMUA FILE SUDAH DI FIX!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "📌 AKSES: http://localhost/izin/auth/login.php" -ForegroundColor Yellow
Write-Host ""
Write-Host "📂 STRUKTUR FOLDER:" -ForegroundColor Cyan
Write-Host "  📁 admin/   → admin/index.php, riwayat.php, laporan.php, cetak_pdf.php" -ForegroundColor Gray
Write-Host "  📁 user/    → user/index.php, riwayat.php, profile.php" -ForegroundColor Gray
Write-Host "  📁 auth/    → auth/login.php, logout.php" -ForegroundColor Gray
Write-Host "  📁 ajax/    → mark_read.php, mark_all_read.php, delete_notif.php, get_notif_count.php" -ForegroundColor Gray
Write-Host "  📁 config/  → firebase.php, supabase.php" -ForegroundColor Gray
Write-Host "  📁 includes/ → header.php, footer.php, functions.php, auth.php, notifikasi.php" -ForegroundColor Gray
Write-Host "  📁 assets/  → css/, js/" -ForegroundColor Gray
Write-Host ""
Write-Host "========================================" -ForegroundColor Green