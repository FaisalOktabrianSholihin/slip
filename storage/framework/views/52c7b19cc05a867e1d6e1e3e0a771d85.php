<?php $__env->startSection('title', 'Template Email - E-Slip Gaji Mitratani'); ?>
<?php $__env->startSection('page-title', 'Template Email'); ?>
<?php $__env->startSection('page-subtitle', 'Ubah redaksi email slip gaji yang diterima karyawan'); ?>
<?php $__env->startSection('page-icon', 'bi-envelope-paper-fill'); ?>

<?php $__env->startPush('styles'); ?>
  <link rel="stylesheet" href="<?php echo e(asset('css/template-email.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="tpl-page">

  <div class="tpl-info">
    <i class="bi bi-shield-lock-fill"></i>
    <span>Lampiran e-Slip Gaji otomatis dikunci dengan password <strong>tanggal lahir karyawan (DDMMYYYY)</strong>.
    Pastikan redaksi email tetap menjelaskan hal ini kepada penerima.</span>
  </div>

  <div class="tpl-grid">

    
    <section class="tpl-card">
      <div class="tpl-card-head">
        <div>
          <span class="tpl-eyebrow">EDITOR</span>
          <h2>Isi Template</h2>
        </div>
        <small id="tplUpdated">-</small>
      </div>

      <div class="tpl-field">
        <label for="tplSubject">Subject</label>
        <input id="tplSubject" type="text" maxlength="200" autocomplete="off" placeholder="mis. Slip Gaji {periode}">
      </div>

      <div class="tpl-field">
        <label for="tplBody">Isi Email</label>
        <textarea id="tplBody" rows="18" spellcheck="true"></textarea>
        <p class="tpl-hint">Pisahkan paragraf dengan satu baris kosong. Baris baru tunggal tetap dipertahankan.</p>
      </div>

      <div class="tpl-field">
        <label>Placeholder <span class="tpl-hint-inline">(klik untuk menyisipkan di posisi kursor)</span></label>
        <div class="tpl-chips" id="tplChips"></div>
        <p class="tpl-warn" id="tplUnknown" hidden></p>
      </div>

      <div class="tpl-actions">
        <button type="button" class="tpl-btn tpl-btn-ghost" id="tplReset">
          <i class="bi bi-arrow-counterclockwise"></i> Kembalikan Default
        </button>
        <span class="tpl-dirty" id="tplDirty" hidden>Belum disimpan</span>
        <button type="button" class="tpl-btn tpl-btn-primary" id="tplSave">
          <i class="bi bi-check2-circle"></i> Simpan Template
        </button>
      </div>
    </section>

    
    <section class="tpl-card">
      <div class="tpl-card-head">
        <div>
          <span class="tpl-eyebrow">PRATINJAU</span>
          <h2>Tampilan di Email</h2>
        </div>
        <small>Contoh data</small>
      </div>

      <div class="tpl-mail">
        <div class="tpl-mail-meta">
          <div><span>Kepada</span><strong id="pvTo">budi.santoso@contoh.com</strong></div>
          <div><span>Subject</span><strong id="pvSubject"></strong></div>
          <div><span>Lampiran</span><strong><i class="bi bi-file-earmark-lock2-fill"></i> <span id="pvFile"></span></strong></div>
        </div>
        <div class="tpl-mail-body" id="pvBody"></div>
      </div>
    </section>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <script src="<?php echo e(asset('js/template-email.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip6\slip4\resources\views/pages/template-email.blade.php ENDPATH**/ ?>