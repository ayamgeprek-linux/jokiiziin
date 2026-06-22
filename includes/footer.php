<?php
/**
 * =====================================================
 * FILE: includes/footer.php
 * FUNGSI: Footer untuk semua halaman
 * =====================================================
 * 
 * @package doret-cuti
 * @version 1.0.0
 */
?>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-brand">
        doret.id<br>
        <small>© <?= date('Y') ?> doret.id - Manajemen Cuti Karyawan</small>
    </div>
    <div class="footer-links">
        <a href="#">Panduan Pengguna</a>
        <a href="#">Kebijakan Privasi</a>
        <a href="#">Kontak</a>
    </div>
</footer>

<!-- JavaScript -->
<script src="assets/js/app.js"></script>

<!-- Inisialisasi Toast -->
<script>
// Override showToast untuk integrasi dengan PHP
function showToast(message, icon = 'ri-information-line') {
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = `<i class="${icon}"></i> ${message}`;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3000);
}

// Auto-show toast jika ada pesan dari PHP
<?php if (isset($_SESSION['flash_message'])): ?>
    showToast('<?= addslashes($_SESSION['flash_message']) ?>');
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
</script>

</body>
</html>