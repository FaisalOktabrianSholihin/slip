<?php $__env->startSection('title', 'Pengaturan'); ?>
<?php $__env->startSection('page-title', 'Pengaturan'); ?>
<?php $__env->startSection('page-subtitle', 'Atur penyimpanan data slip dan channel pengiriman'); ?>
<?php $__env->startSection('page-icon', 'bi-gear-fill'); ?>

<?php $__env->startPush('styles'); ?>
  <link rel="stylesheet" href="<?php echo e(asset('css/pengaturan.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="settings-page">
<div class="settings-intro"><div class="settings-icon"><i class="bi bi-sliders2"></i></div><div><h1>Pengaturan Sistem</h1><p>Pengaturan ini menjadi dasar konfigurasi aplikasi. Saat backend/database terhubung, kebijakan yang sama dapat diterapkan di server.</p></div></div>
<div class="save-notice" hidden="" id="saveNotice"></div>
<section class="settings-card"><div class="card-head"><div><span class="eyebrow">RETENSI DATA</span><h2>Penyimpanan riwayat slip</h2><p>Tentukan berapa lama data slip gaji disimpan sebelum otomatis dibersihkan dari penyimpanan aplikasi.</p></div><i class="bi bi-database-fill-gear"></i></div>
<div class="setting-row"><div class="setting-copy"><strong>Simpan data slip selama</strong><span>Riwayat, data payroll sementara, dan slip yang masih menunggu diproses mengikuti periode ini.</span></div><select id="retentionMonths"><option value="1">1 bulan</option><option value="2">2 bulan</option><option value="3">3 bulan</option><option value="6">6 bulan</option><option value="12">12 bulan</option><option value="24">24 bulan</option></select></div>
<div class="retention-info"><i class="bi bi-info-circle-fill"></i><span>Log aktivitas pengguna tidak ikut dihapus oleh kebijakan retensi slip agar jejak aktivitas tetap tersedia.</span></div>
</section>
<section class="settings-card"><div class="card-head"><div><span class="eyebrow">CHANNEL PENGIRIMAN</span><h2>Email &amp; WhatsApp</h2><p>Channel yang dinonaktifkan tidak dapat digunakan untuk mengirim slip, termasuk tombol kirim per divisi.</p></div><i class="bi bi-send-check-fill"></i></div>
<div class="channel-setting"><div class="channel-icon email"><i class="bi bi-envelope-fill"></i></div><div class="setting-copy"><strong>Pengiriman Email</strong><span>Izinkan slip dikirim ke alamat Email dari master Karyawan.</span></div><label class="switch"><input id="emailEnabled" type="checkbox"/><span></span></label></div>
<div class="channel-setting"><div class="channel-icon wa"><i class="bi bi-whatsapp"></i></div><div class="setting-copy"><strong>Pengiriman WhatsApp</strong><span>Izinkan slip dikirim ke nomor WhatsApp/HP dari master Karyawan.</span></div><label class="switch"><input id="waEnabled" type="checkbox"/><span></span></label></div>
</section>
<section class="settings-card storage-card"><div class="card-head"><div><span class="eyebrow">STATUS PENYIMPANAN</span><h2>Data saat ini</h2><p>Ringkasan data yang tersimpan pada browser untuk simulasi aplikasi.</p></div><i class="bi bi-bar-chart-fill"></i></div><div class="storage-stats"><div><strong id="historyCount">0</strong><span>Riwayat slip</span></div><div><strong id="payrollCount">0</strong><span>Payroll menunggu</span></div><div><strong id="activityCount">0</strong><span>Log aktivitas</span></div></div><button class="danger-btn" id="cleanupBtn" type="button"><i class="bi bi-arrow-repeat"></i> Terapkan retensi sekarang</button></section>
<div class="settings-actions"><button class="secondary-btn" id="resetBtn" type="button">Kembalikan Default</button><button class="primary-btn" id="saveBtn" type="button"><i class="bi bi-check2-circle"></i> Simpan Pengaturan</button></div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <script src="<?php echo e(asset('js/slip-common.js')); ?>"></script>
  <script src="<?php echo e(asset('js/pengaturan.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip4\resources\views/pages/pengaturan.blade.php ENDPATH**/ ?>