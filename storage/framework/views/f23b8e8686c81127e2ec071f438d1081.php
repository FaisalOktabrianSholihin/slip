<?php $__env->startSection('title', 'Preview Slip'); ?>
<?php $__env->startSection('page-title', 'Preview Slip'); ?>
<?php $__env->startSection('page-subtitle', 'Periksa slip hasil import sebelum dikirim melalui email atau WhatsApp'); ?>
<?php $__env->startSection('page-icon', 'bi-eye-fill'); ?>

<?php $__env->startPush('styles'); ?>
  <link rel="stylesheet" href="<?php echo e(asset('css/kirim-slip.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/preview-slip.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<!-- ====== KONTEN HALAMAN PREVIEW SLIP ====== -->
<div class="ks ps">
<div class="ks-card">
<div class="ps-top">
<a class="ks-btn ks-btn--outline" href="<?php echo e(route('kirim-slip')); ?>">
<i aria-hidden="true" class="bi bi-arrow-left"></i> <span>Kembali ke Data Karyawan</span>
</a>
<p aria-live="polite" class="ps-summary" id="psSummary"></p>
</div>
<!-- Daftar slip (disembunyikan bila kosong) -->
<div hidden="" id="psContent">
<!-- Cari divisi -->
<div class="ps-search" id="psCombo">
<label class="ps-sr" for="psSearch">Cari divisi</label>
<i aria-hidden="true" class="bi bi-search ps-search__icon"></i>
<input aria-autocomplete="list" aria-controls="psSuggest" aria-expanded="false" autocomplete="off" id="psSearch" placeholder="Cari atau ketik divisi..." role="combobox" type="text"/>
<ul class="ps-suggest" hidden="" id="psSuggest" role="listbox"></ul>
</div>
<!-- Slip per divisi (diisi preview-slip.js) -->
<div class="ps-list" id="psList"></div>
<!-- Kirim semua -->
<div class="ps-foot">
<button class="ks-btn ks-btn--primary" id="psSendAll" type="button">
<i aria-hidden="true" class="bi bi-envelope-fill"></i> <span>Kirim Semua Email</span>
</button>
<button class="ks-btn ks-btn--outline" id="psSendAllWa" title="Kirim semua slip WhatsApp" type="button">
<i aria-hidden="true" class="bi bi-whatsapp"></i> <span>Kirim Semua WhatsApp</span>
</button>
</div>
</div>
<!-- Keadaan kosong -->
<div class="ps-empty" hidden="" id="psEmpty">
<i aria-hidden="true" class="bi bi-inbox"></i>
<p class="ps-empty__title" id="psEmptyTitle">Belum ada slip untuk dipreview</p>
<p class="ps-empty__text" id="psEmptyText">Import Excel gaji terlebih dahulu pada halaman Data Karyawan.</p>
<div class="ps-empty__actions">
<a class="ks-btn ks-btn--primary" href="<?php echo e(route('kirim-slip')); ?>"><i aria-hidden="true" class="bi bi-file-earmark-spreadsheet"></i> <span>Ke Import Gaji</span></a>
<a class="ks-btn ks-btn--outline" href="<?php echo e(route('detail-riwayat')); ?>"><i aria-hidden="true" class="bi bi-clock-history"></i> <span>Lihat Riwayat</span></a>
</div>
</div>
</div>
</div>
<!-- ====== SELESAI KONTEN HALAMAN ====== -->
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
<div aria-live="polite" class="ks-toasts" id="ksToasts" role="status"></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <script src="<?php echo e(asset('js/slip-common.js')); ?>"></script>
  <script src="<?php echo e(asset('js/preview-slip.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip4\resources\views/pages/preview-slip.blade.php ENDPATH**/ ?>