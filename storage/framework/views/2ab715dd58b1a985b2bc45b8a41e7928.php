<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
</head>
<body style="font-family:Arial,sans-serif;color:#203b30;line-height:1.6;background:#f7faf8;padding:24px;">
<div style="max-width:640px;margin:auto;background:#fff;border:1px solid #dfe9e4;border-radius:14px;padding:28px;">
    <p style="margin-top:0;color:#176b4d;font-size:12px;font-weight:bold;letter-spacing:1px;">PT MITRATANI DUA TUJUH</p>
    <h2 style="margin:0 0 16px;">Slip Gaji <?php echo e($payroll->periode->translatedFormat('F Y')); ?></h2>

    <p>Yth. <strong><?php echo e($namaKaryawan); ?></strong>,</p>
    <p>Berikut kami sampaikan slip gaji Anda untuk periode <strong><?php echo e($payroll->periode->translatedFormat('F Y')); ?></strong>.</p>

    <table cellpadding="7" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:560px;">
        <tr>
            <td style="border-bottom:1px solid #edf2ef;">Gaji Pokok</td>
            <td style="border-bottom:1px solid #edf2ef;text-align:right;">Rp <?php echo e(number_format($payroll->gaji_pokok,0,',','.')); ?></td>
        </tr>
        <?php $__currentLoopData = $payroll->items->where('tipe','tambahan')->sortBy('urutan'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td style="border-bottom:1px solid #edf2ef;"><?php echo e($item->nama); ?></td>
            <td style="border-bottom:1px solid #edf2ef;text-align:right;">Rp <?php echo e(number_format($item->jumlah,0,',','.')); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td style="border-top:1px solid #cbd9df;"><strong>Total Penerimaan</strong></td>
            <td style="border-top:1px solid #cbd9df;text-align:right;"><strong>Rp <?php echo e(number_format($payroll->gaji_pokok + $payroll->total_tambahan,0,',','.')); ?></strong></td>
        </tr>
        <?php $__currentLoopData = $payroll->items->where('tipe','potongan')->sortBy('urutan'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td style="border-bottom:1px solid #edf2ef;"><?php echo e($item->nama); ?></td>
            <td style="border-bottom:1px solid #edf2ef;text-align:right;color:#b42318;">- Rp <?php echo e(number_format($item->jumlah,0,',','.')); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><strong>Total Potongan</strong></td>
            <td style="text-align:right;color:#b42318;"><strong>- Rp <?php echo e(number_format($payroll->total_potongan,0,',','.')); ?></strong></td>
        </tr>
        <tr>
            <td style="background:#176b4d;color:#fff;padding:12px;"><strong>TAKE HOME PAY</strong></td>
            <td style="background:#176b4d;color:#fff;text-align:right;padding:12px;"><strong>Rp <?php echo e(number_format($payroll->gaji_bersih,0,',','.')); ?></strong></td>
        </tr>
    </table>

    <p style="margin-top:20px;color:#61738a;font-size:12px;">
        Slip gaji lengkap terlampir dalam PDF. Email ini dikirim otomatis oleh sistem E-Slip, mohon tidak membalas email ini.
    </p>
    <p style="color:#61738a;font-size:12px;">Terima kasih.</p>
</div>
</body>
</html>
<?php /**PATH D:\project slip gaji\slip6\slip4\resources\views/emails/slip-gaji.blade.php ENDPATH**/ ?>