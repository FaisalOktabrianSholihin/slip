<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <title><?php echo $__env->yieldContent('title', 'E-Slip Gaji Mitratani'); ?></title>

  <link rel="icon" href="<?php echo e(asset('assets/logo.png')); ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap Icons: dipakai sidebar.js & topheader.js -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <link rel="stylesheet" href="<?php echo e(asset('css/sidebar.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('css/topheader.css')); ?>">

  <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body <?php if(Route::currentRouteName()): ?> data-nav="<?php echo e(Route::currentRouteName()); ?>" <?php endif; ?>>

  <!-- WAJIB: sidebar.js hanya berjalan jika .app-container dan #sidebar-container ada -->
  <div class="app-container">

    <div id="sidebar-container">
        <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    <main class="main-content">

      <div id="topheader-container"
           data-title="<?php echo $__env->yieldContent('page-title', 'Halaman'); ?>"
           data-subtitle="<?php echo $__env->yieldContent('page-subtitle', ''); ?>"
           data-icon="<?php echo $__env->yieldContent('page-icon', ''); ?>"
           data-user-name="Administrator"
           data-user-role="Admin SDM">
        <?php echo $__env->make('partials.topheader', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      </div>

      <?php echo $__env->yieldContent('content'); ?>

    </main>

  </div><!-- /.app-container -->

  <?php echo $__env->yieldContent('modals'); ?>

  <script src="<?php echo e(asset('js/api-client.js')); ?>"></script>
  <script src="<?php echo e(asset('js/sidebar.js')); ?>"></script>
  <script src="<?php echo e(asset('js/topheader.js')); ?>"></script>
  <script src="<?php echo e(asset('js/auth.js')); ?>"></script>
  <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\project slip gaji\slip4\resources\views/layouts/app.blade.php ENDPATH**/ ?>