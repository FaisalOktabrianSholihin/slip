<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page { margin: 28px 30px; }
* { box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; color:#203b30; font-size:9px; margin:0; }
.header { border-bottom:2px solid #176b4d; padding-bottom:10px; }
.brand { width:100%; border-collapse:collapse; }
.logo { width:42px; height:42px; border-radius:9px; background:#edf7f1; text-align:center; vertical-align:middle; }
.logo img { width:38px; height:38px; }
.brand-name { padding-left:10px; vertical-align:middle; }
.brand-name strong { display:block; font-size:12px; letter-spacing:1px; }
.brand-name span { display:block; margin-top:3px; color:#718279; font-size:8px; letter-spacing:1.5px; }
.secret { text-align:right; vertical-align:middle; color:#176b4d; font-weight:bold; font-size:8px; }
.number { width:100%; border-collapse:collapse; margin:10px 0; }
.number td { vertical-align:middle; }
.number .label { color:#87988f; font-size:7px; font-weight:bold; letter-spacing:1px; }
.number .value { color:#176b4d; font-size:14px; font-weight:bold; padding-left:8px; }
.number .date { text-align:right; color:#718279; font-weight:bold; }
.identity { width:100%; border-collapse:collapse; border:1px solid #dfe9e4; margin-bottom:12px; }
.identity td { width:33.33%; padding:8px 9px; border:1px solid #dfe9e4; }
.label2 { display:block; color:#819189; font-size:6.5px; font-weight:bold; text-transform:uppercase; letter-spacing:.7px; }
.value2 { display:block; color:#203b30; font-size:8.5px; font-weight:bold; margin-top:3px; }
.columns { width:100%; border-collapse:separate; border-spacing:10px 0; margin-left:-10px; width:calc(100% + 20px); }
.box { border:1px solid #dfe9e4; vertical-align:top; width:50%; }
.title { padding:8px 10px; background:#f1f7f4; color:#176b4d; font-size:8px; font-weight:bold; letter-spacing:1px; }
.line { width:100%; border-collapse:collapse; }
.line td { padding:7px 10px; border-bottom:1px solid #edf2ef; }
.line td:last-child { text-align:right; font-weight:bold; white-space:nowrap; }
.minus { color:#b42318; }
.subtotal td { background:#fafcfb; border-bottom:0; font-weight:bold; }
.empty { padding:15px 10px; color:#8a9991; text-align:center; }
.net { margin-top:14px; padding:11px 13px; background:#176b4d; color:#fff; border-radius:8px; width:100%; }
.net table { width:100%; border-collapse:collapse; }
.net small { font-size:7px; opacity:.8; }
.net strong { font-size:14px; }
.footer { margin-top:13px; padding-top:9px; border-top:1px solid #edf2f5; color:#8797a3; font-size:7px; text-align:center; }
</style>
</head>
<body>
<?php
    $config = $payroll->component_config ?: [];
    $addNames = array_values($config['additions'] ?? []);
    $deductNames = array_values($config['deductions'] ?? []);
    $addItems = $payroll->items->where('tipe', 'tambahan')->sortBy('urutan')->values();
    $deductItems = $payroll->items->where('tipe', 'potongan')->sortBy('urutan')->values();
    $totalAdd = (float) $payroll->total_tambahan;
    $totalDeduct = (float) $payroll->total_potongan;
    $net = (float) $payroll->gaji_bersih;
    $period = optional($payroll->periode)->translatedFormat('F Y');
    $logo = public_path('assets/logo.png');
?>

<div class="header">
<table class="brand">
<tr>
<td class="logo">
<?php if(is_file($logo)): ?>
<img src="data:image/png;base64,<?php echo e(base64_encode(file_get_contents($logo))); ?>" alt="Logo">
<?php endif; ?>
</td>
<td class="brand-name">
<strong>PT MITRATANI DUA TUJUH</strong>
<span>SLIP GAJI KARYAWAN</span>
</td>
<td class="secret">DOKUMEN RAHASIA<br>CONFIDENTIAL</td>
</tr>
</table>
</div>

<table class="number">
<tr>
<td class="label">NOMOR URUT</td>
<td class="value"><?php echo e($karyawan?->no_absen ?: '-'); ?></td>
<td class="date"><?php echo e($period); ?></td>
</tr>
</table>

<table class="identity">
<tr>
<td><span class="label2">Nama</span><span class="value2"><?php echo e($payroll->nama_snapshot ?: '-'); ?></span></td>
<td><span class="label2">NIK</span><span class="value2"><?php echo e($payroll->karyawan_nik); ?></span></td>
<td><span class="label2">Pangkat / Golongan</span><span class="value2"><?php echo e($payroll->golongan_snapshot ?: '-'); ?></span></td>
</tr>
<tr>
<td><span class="label2">Jabatan</span><span class="value2"><?php echo e($payroll->jabatan_snapshot ?: '-'); ?></span></td>
<td><span class="label2">Rekening</span><span class="value2"><?php echo e($payroll->rekening_snapshot ?: '-'); ?></span></td>
<td><span class="label2">Divisi</span><span class="value2"><?php echo e($payroll->divisi_snapshot ?: '-'); ?></span></td>
</tr>
</table>

<table class="columns">
<tr>
<td class="box">
<div class="title">PENERIMAAN</div>
<table class="line">
<tr><td>Gaji Pokok</td><td>Rp <?php echo e(number_format($payroll->gaji_pokok,0,',','.')); ?></td></tr>
<?php $__currentLoopData = $addItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<tr><td><?php echo e($item->nama); ?></td><td>Rp <?php echo e(number_format($item->jumlah,0,',','.')); ?></td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<tr class="subtotal"><td>Total Penerimaan</td><td>Rp <?php echo e(number_format((float)$payroll->gaji_pokok + $totalAdd,0,',','.')); ?></td></tr>
</table>
</td>
<td class="box">
<div class="title">POTONGAN</div>
<table class="line">
<?php if($deductItems->count()): ?>
<?php $__currentLoopData = $deductItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<tr><td><?php echo e($item->nama); ?></td><td class="minus">- Rp <?php echo e(number_format($item->jumlah,0,',','.')); ?></td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php else: ?>
<tr><td colspan="2" class="empty">Tidak ada potongan</td></tr>
<?php endif; ?>
<tr class="subtotal"><td>Total Potongan</td><td class="minus">- Rp <?php echo e(number_format($totalDeduct,0,',','.')); ?></td></tr>
</table>
</td>
</tr>
</table>

<div class="net">
<table>
<tr>
<td><strong>TAKE HOME PAY</strong><br><small>Jumlah yang diterima setelah seluruh potongan</small></td>
<td style="text-align:right"><strong>Rp <?php echo e(number_format($net,0,',','.')); ?></strong></td>
</tr>
</table>
</div>

<div class="footer">
Dokumen ini dibuat secara otomatis dari data payroll yang telah divalidasi terhadap master Karyawan.<br>
Slip ini bersifat rahasia dan ditujukan hanya untuk penerima yang tercantum.
</div>
</body>
</html>
<?php /**PATH D:\project slip gaji\slip6\slip4\resources\views/pdf/slip-gaji.blade.php ENDPATH**/ ?>