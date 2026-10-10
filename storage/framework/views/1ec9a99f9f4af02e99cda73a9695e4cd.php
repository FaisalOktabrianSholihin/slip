<?php $__env->startSection('title', 'Demografi'); ?>
<?php $__env->startSection('page-title', 'Demografi'); ?>
<?php $__env->startSection('page-subtitle', 'Atur kelompok usia dan tingkat pendidikan yang ditampilkan di dashboard'); ?>
<?php $__env->startSection('page-icon', 'bi-people'); ?>
<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/pengaturan.css')); ?>?v=20261009-2">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="settings-page demografi-page">
        <div class="settings-intro">
            <div class="settings-icon"><i class="bi bi-people"></i></div>
            <div>
                <h1>Pengaturan Demografi</h1>
                <p>Maksimal 5 kelompok usia dan 5 kategori pendidikan. Perubahan akan digunakan pada grafik dan matriks
                    dashboard.</p>
            </div>
        </div>
        <div class="save-notice" id="demografiNotice" hidden></div>
        <section class="settings-card">
            <div class="card-head">
                <div><span class="eyebrow">USIA</span>
                    <h2>Rentang usia</h2>
                </div><button class="primary-btn" id="addAge" type="button">+ Tambah rentang</button>
            </div>
            <div id="ageRanges"></div>
        </section>
        <section class="settings-card">
            <div class="card-head">
                <div><span class="eyebrow">PENDIDIKAN</span>
                    <h2>Kategori pendidikan</h2>
                    <p>Contoh: S1, DIII, SMA, SMP. Maksimal 5 kategori.</p>
                </div><button class="primary-btn" id="addEducation" type="button">+ Tambah pendidikan</button>
            </div>
            <div id="educationLevels"></div>
        </section>
        <div class="settings-actions"><button class="primary-btn" id="saveDemografi" type="button">Simpan
                Demografi</button></div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('js/demografi.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip6\slip4\resources\views/pages/demografi.blade.php ENDPATH**/ ?>