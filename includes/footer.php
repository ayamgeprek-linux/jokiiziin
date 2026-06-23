<?php
/**
 * =====================================================
 * FILE: includes/footer.php
 * FUNGSI: Footer untuk semua halaman
 * VERSION: 2.0 - Updated with Magang.usg brand
 * =====================================================
 */
?>

<!-- =====================================================
     FOOTER
     ===================================================== -->
<footer class="footer">
    <div class="footer-brand">
        <strong style="font-size:16px;font-family:var(--font-display);">Magang<span style="color:var(--clr-primary);">.usg</span></strong>
        <br>
        <small style="color:var(--clr-muted);font-size:12px;">© <?= date('Y') ?> Magang.usg - Manajemen Cuti Karyawan</small>
    </div>
    <div class="footer-links">
        <a href="#">Panduan Pengguna</a>
        <a href="#">Kebijakan Privasi</a>
        <a href="#">Kontak</a>
        <a href="#">Tentang Kami</a>
    </div>
</footer>

<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->
<script src="/assets/js/app.js"></script>

<!-- Toast Notification -->
<script>
// Override showToast untuk integrasi dengan PHP
function showToast(message, icon = 'ri-information-line') {
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = `<i class="${icon}"></i> ${message}`;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => { 
        t.style.display = 'none'; 
    }, 3000);
}

// Auto-show toast jika ada pesan dari PHP
<?php if (isset($_SESSION['flash_message'])): ?>
    showToast('<?= addslashes($_SESSION['flash_message']) ?>');
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
</script>

</body>
</html>