<?php $__env->startSection('title', 'E-Slip Gaji M27'); ?>

<?php $__env->startSection('content'); ?>
<!-- BACKGROUND -->
<div class="bg bg-top-left"></div>
<div class="bg bg-top-right"></div>
<div class="bg bg-bottom-left"></div>
<div class="bg bg-bottom-right"></div>
<!-- BACKGROUND MONEY -->
<div aria-hidden="true" class="money-container" id="moneyContainer"></div>
<!-- ANIMATED MONEY -->
<div aria-hidden="true" class="money-container" id="moneyContainer"></div>
<main class="landing-page">
<!-- ================= LEFT ================= -->
<section class="left-section">
<div class="left-content">
<!-- LOGO -->
<img alt="Mitratani Dua Tujuh" class="logo" src="<?php echo e(asset('assets/logo.png')); ?>"/>
<!-- WELCOME -->
<div class="welcome">
<h1>
<span>Selamat Datang di</span>
<strong>E-Slip Gaji</strong>
</h1>
<p>
                        Akses slip gaji Anda dengan cepat, aman, dan mudah kapan saja.
                    </p>
</div>
<!-- LOGIN CARD -->
<div class="login-card">
<form id="loginForm">
<!-- USERNAME -->
<div class="input-box">
<i class="bi bi-person"></i>
<input autocomplete="username" id="username" placeholder="Username" type="text"/>
</div>
<!-- PASSWORD -->
<div class="input-box">
<i class="bi bi-lock"></i>
<input autocomplete="current-password" id="password" placeholder="Password" type="password"/>
<button class="eye-button" id="passwordToggle" type="button">
<i class="bi bi-eye"></i>
</button>
</div>
<!-- OPTIONS -->
<div class="login-options">
<label class="remember">
<input id="rememberMe" type="checkbox"/>
<span class="checkbox">
<i class="bi bi-check"></i>
</span>
<span>Ingat saya</span>
</label>
<a href="#" id="forgotPassword">
                                Lupa password?
                            </a>
</div>
<!-- LOGIN BUTTON -->
<button class="login-button" id="loginButton" type="submit">
<span>Login</span>
<i class="bi bi-arrow-right"></i>
</button>
</form>
</div>
<!-- SECURITY -->
<div class="security">
<i class="bi bi-shield-fill-check"></i>
<span>
                        Aman • Cepat • Terpercaya
                    </span>
</div>
</div>
</section>
<!-- ================= RIGHT ================= -->
<section class="right-section">
<div class="right-content">
<!-- ILLUSTRATION -->
<div class="illustration-wrapper">
<img alt="Ilustrasi E-Slip Gaji" class="illustration" src="<?php echo e(asset('assets/ilustrasi.png')); ?>"/></div>
<!-- TEXT -->
<div class="right-text">
<h2>
                        Mudah, Aman,<br/>
                        Langsung di Tangan Anda
                    </h2>
<p>
                        E-Slip Gaji M27
                    </p>
</div>
</div>
</section>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <script src="<?php echo e(asset('js/login.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip4\resources\views/pages/login.blade.php ENDPATH**/ ?>