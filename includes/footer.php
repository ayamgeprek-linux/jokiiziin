<?php
/**
 * =====================================================
 * FILE: includes/footer.php
 * FUNGSI: Footer untuk semua halaman
 * VERSION: 3.0 - Fixed (Only 1 Footer)
 * =====================================================
 */
?>

<!-- =====================================================
     FOOTER - HANYA 1
     ===================================================== -->
<footer class="footer">
    <div class="footer-brand">
        <strong style="font-size:16px;font-family:var(--font-display);">Magang<span style="color:var(--clr-primary);">.usg</span></strong>
        <br>
        <small style="color:var(--clr-muted);font-size:12px;">© <?= date('Y') ?> Magang.usg - Manajemen Cuti Karyawan</small>
    </div>
    <div class="footer-links">
        <a href="#">Panduan</a>
        <a href="#">Privasi</a>
        <a href="#">Kontak</a>
    </div>
</footer>

<!-- =====================================================
     GLOBAL TOAST (Jika belum ada di file lain)
     ===================================================== -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->
<script src="/assets/js/app.js"></script>

<script>
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

<?php if (isset($_SESSION['flash_message'])): ?>
    showToast('<?= addslashes($_SESSION['flash_message']) ?>');
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
</script>

</body>
</html>