<header class="topheader" id="topheader">
  <div class="topheader-inner">

    <!-- Kiri: ikon halaman (disalin dari menu aktif di sidebar) + judul -->
    <div class="topheader-left">
      <span class="topheader-icon" id="topheaderIcon" hidden aria-hidden="true"><i class="bi"></i></span>

      <div class="topheader-title">
        <h1 id="topheaderTitle">Judul Halaman</h1>
        <p id="topheaderSubtitle"></p>
      </div>
    </div>

    <!-- Kanan: akun -->
    <div class="topheader-actions">

      <div class="th-user" id="thUser">
        <button type="button" class="th-user-btn" id="thUserBtn"
                aria-haspopup="true" aria-expanded="false">
          <span class="th-avatar" id="thAvatar">A</span>
          <span class="th-user-text">
            <strong id="thUserName">Administrator</strong>
            <small id="thUserRole">Admin SDM</small>
          </span>
          <i class="bi bi-chevron-down th-caret"></i>
        </button>

        <ul class="th-menu" id="thMenu" role="menu" hidden>
          <li role="none"><a role="menuitem" href="<?php echo e(route('login')); ?>" id="thLogout"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
        </ul>
      </div>

    </div>
  </div>
</header>
<?php /**PATH D:\project slip gaji\slip4\resources\views/partials/topheader.blade.php ENDPATH**/ ?>