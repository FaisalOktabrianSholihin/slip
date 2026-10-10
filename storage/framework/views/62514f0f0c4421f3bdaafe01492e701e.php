<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <img src="<?php echo e(asset('assets/logo.png')); ?>" alt="Mitratani">
        </div>

        <div class="sidebar-brand-text">
            <span>E-SLIP GAJI</span>
            <span>MITRATANI</span>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <nav class="sidebar-menu">

        <?php if(auth()->user()->hasPermission('dashboard.view')): ?>
            <a href="<?php echo e(route('dashboard')); ?>" class="menu-item" data-page="dashboard.html"><i
                    class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        <?php endif; ?>

        <div class="menu-title system-title">
            MENU UTAMA
        </div>

        <?php if(auth()->user()->hasPermission('master-user.view') ||
                auth()->user()->hasPermission('master-divisi.view') ||
                auth()->user()->hasPermission('master-jabatan.view') ||
                auth()->user()->hasPermission('master-karyawan.view')): ?>
            <div class="menu-group">

                <button type="button" class="menu-item master-data-toggle" id="masterDataToggle">
                    <i class="bi bi-database-fill"></i>

                    <span>Master Data</span>

                    <i class="bi bi-chevron-down menu-arrow"></i>
                </button>


                <div class="master-data-submenu" id="masterDataSubmenu">

                    <?php if(auth()->user()->hasPermission('master-user.view')): ?>
                        <a href="<?php echo e(route('data-user')); ?>" class="submenu-item" data-page="data-user.html">User</a>
                    <?php endif; ?>

                    <?php if(auth()->user()->hasPermission('master-divisi.view')): ?>
                        <a href="<?php echo e(route('data-divisi')); ?>" class="submenu-item"
                            data-page="data-divisi.html">Divisi</a>
                    <?php endif; ?>

                    <?php if(auth()->user()->hasPermission('master-jabatan.view')): ?>
                        <a href="<?php echo e(route('data-jabatan')); ?>" class="submenu-item"
                            data-page="data-jabatan.html">Jabatan</a>
                    <?php endif; ?>

                    <?php if(auth()->user()->hasPermission('master-karyawan.view')): ?>
                        <a href="<?php echo e(route('data-karyawan')); ?>" class="submenu-item"
                            data-page="data-karyawan.html">Karyawan</a>
                    <?php endif; ?>

                </div>

            </div>
        <?php endif; ?>

        <?php if(auth()->user()->hasPermission('demografi.view')): ?>
            <a href="<?php echo e(route('demografi')); ?>" class="menu-item"><i
                    class="bi bi-people-fill"></i><span>Demografi</span></a>
        <?php endif; ?>
        <?php if(auth()->user()->hasPermission('kirim-slip.view')): ?>
            <a href="<?php echo e(route('kirim-slip')); ?>" class="menu-item" data-page="kirim-slip.html">
                <i class="bi bi-send-fill"></i>
                <span>Kirim Slip Gaji</span>
            </a>
        <?php endif; ?>


        <?php if(auth()->user()->hasPermission('detail-riwayat.view')): ?>
            <a href="<?php echo e(route('detail-riwayat')); ?>" class="menu-item" data-page="detail-riwayat.html">
                <i class="bi bi-clock-history"></i>
                <span>Detail Riwayat</span>
            </a>
        <?php endif; ?>

        <?php if(auth()->user()->hasPermission('template-email.view')): ?>
            <a href="<?php echo e(route('template-email')); ?>" class="menu-item" data-page="template-email.html">
                <i class="bi bi-envelope-paper-fill"></i>
                <span>Template Email</span>
            </a>
        <?php endif; ?>

        <?php if(auth()->user()->hasPermission('role-management.view') ||
                auth()->user()->hasPermission('setting.view') ||
                auth()->user()->hasPermission('log-aktivitas.view')): ?>
            <div class="menu-title system-title">SISTEM</div>
        <?php endif; ?>


        <?php if(auth()->user()->hasPermission('role-management.view')): ?>
            <a href="<?php echo e(route('role-management')); ?>" class="menu-item" data-page="role-management.html">
                <i class="bi bi-shield-fill"></i>
                <span>Role Management</span>
            </a>
        <?php endif; ?>

        <?php if(auth()->user()->hasPermission('setting.view')): ?>
            <a href="<?php echo e(route('pengaturan')); ?>" class="menu-item" data-page="pengaturan.html">
                <i class="bi bi-gear-fill"></i>
                <span>Pengaturan</span>
            </a>
        <?php endif; ?>

        <?php if(auth()->user()->hasPermission('log-aktivitas.view')): ?>
            <a href="<?php echo e(route('log-aktivitas')); ?>" class="menu-item" data-page="log-aktivitas.html">
                <i class="bi bi-journal-text"></i>
                <span>Log Aktivitas</span>
            </a>
        <?php endif; ?>

    </nav>


    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Tutup sidebar" title="Tutup sidebar">
        <i class="bi bi-chevron-left"></i>
    </button>

</aside>
<?php /**PATH D:\project slip gaji\slip6\slip4\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>