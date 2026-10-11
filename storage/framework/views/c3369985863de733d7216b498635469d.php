<?php $__env->startSection('title', 'Dashboard SDM'); ?>
<?php $__env->startSection('page-title', 'Dashboard SDM'); ?>
<?php $__env->startSection('page-subtitle', 'Ringkasan data sumber daya manusia'); ?>
<?php $__env->startSection('page-icon', 'bi-speedometer2'); ?>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

    <div class="dashboard">
        <!-- Ringkasan utama: dua kartu -->
        <section class="kpi-grid">
            <article class="kpi-card kpi-total-card">
                <div class="kpi-main-icon" aria-hidden="true">
                    <svg class="kpi-icon" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="3.2"></circle>
                        <path d="M5.5 20v-1.5a4.5 4.5 0 0 1 4.5-4.5h4a4.5 4.5 0 0 1 4.5 4.5V20"></path>
                        <circle cx="4.5" cy="10" r="2"></circle>
                        <circle cx="19.5" cy="10" r="2"></circle>
                    </svg>
                </div>
                <div class="kpi-total-content">
                    <div class="kpi-total-heading">
                        <div><span class="kpi-eyebrow">RINGKASAN SDM</span><span class="kpi-label">Total Karyawan</span>
                        </div><strong id="kpiTotal">0</strong>
                    </div>
                    <div class="kpi-status-breakdown" id="kpiStatusBreakdown"
                        aria-label="Jumlah karyawan berdasarkan status">
                        <div class="status-metric"><span class="status-dot dot-pkwtt"></span><span
                                class="status-name">PKWTT</span><strong data-status="pkwtt">0</strong></div>
                        <div class="status-metric"><span class="status-dot dot-pkwt"></span><span
                                class="status-name">PKWT</span><strong data-status="pkwt">0</strong></div>
                        <div class="status-metric"><span class="status-dot dot-hl"></span><span
                                class="status-name">PKWT-HL</span><strong data-status="pkwt_hl">0</strong></div>
                        <div class="status-metric"><span class="status-dot dot-os"></span><span
                                class="status-name">PKWT-OS</span><strong data-status="pkwt_os">0</strong></div>
                        <div class="status-metric"><span class="status-dot dot-penugasan"></span><span
                                class="status-name">Penugasan</span><strong data-status="penugasan">0</strong></div>
                    </div>
                    <p class="kpi-updated" id="kpiUpdated"><i class="bi bi-clock-history" aria-hidden="true"></i>
                        <span>Memuat tanggal pembaruan data...</span></p>
                </div>
            </article>
            <article class="kpi-card kpi-tenure-card" id="kpiMasaKerjaCard">
                <div class="kpi-main-icon tenure-icon" aria-hidden="true"><svg class="kpi-icon" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2"></path>
                    </svg></div>
                <div class="tenure-content"><span class="kpi-eyebrow">LOYALITAS KARYAWAN</span><span class="kpi-label">Masa
                        Kerja &gt; 5 Tahun</span><strong id="kpiMasaKerja">0</strong><span class="tenure-caption"></span>
                </div>
            </article>
        </section>
        <!-- CHARTS -->
        <section class="chart-grid">
            <article class="card">
                <h2 class="card-title">Pendidikan Karyawan</h2>
                <div class="chart-box"><canvas id="chartPendidikan"></canvas></div>
            </article>
            <article class="card">
                <h2 class="card-title">Komposisi Status</h2>
                <div class="chart-box"><canvas id="chartStatus"></canvas></div>
            </article>
            <article class="card">
                <h2 class="card-title">Usia Karyawan</h2>
                <div class="chart-box"><canvas id="chartUsia"></canvas></div>
            </article>
        </section>
        <!-- MATRIKS -->
        <section class="card matrix-card">
            <h2 class="card-title left">Matriks Komposisi SDM</h2>
            <div class="table-wrap">
                <table class="matrix" id="matrixTable">
                    <thead>
                        <tr>
                            <th>Komposisi SDM</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                </table>
            </div>
        </section>
    </div><!-- /.dashboard -->
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js">
    </script>
    <script src="<?php echo e(asset('js/dashboard.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip6\slip4\resources\views/pages/dashboard.blade.php ENDPATH**/ ?>