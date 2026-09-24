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

        <a href="<?php echo e(route('dashboard')); ?>" class="menu-item" data-page="dashboard.html">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-title system-title">
            MENU UTAMA
        </div>

        <div class="menu-group">

            <button
                type="button"
                class="menu-item master-data-toggle"
                id="masterDataToggle"
            >
                <i class="bi bi-database-fill"></i>

                <span>Master Data</span>

                <i class="bi bi-chevron-down menu-arrow"></i>
            </button>


            <div class="master-data-submenu" id="masterDataSubmenu">

                <a
                    href="<?php echo e(route('data-user')); ?>"
                    class="submenu-item"
                    data-page="data-user.html"
                >
                    User
                </a>

                <a
                    href="<?php echo e(route('data-divisi')); ?>"
                    class="submenu-item"
                    data-page="data-divisi.html"
                >
                    Divisi
                </a>

                <a
                    href="<?php echo e(route('data-jabatan')); ?>"
                    class="submenu-item"
                    data-page="data-jabatan.html"
                >
                    Jabatan
                </a>

                <a
                    href="<?php echo e(route('data-karyawan')); ?>"
                    class="submenu-item"
                    data-page="data-karyawan.html"
                >
                    Karyawan
                </a>

            </div>

        </div>


        <a
            href="<?php echo e(route('kirim-slip')); ?>"
            class="menu-item"
            data-page="kirim-slip.html"
        >
            <i class="bi bi-send-fill"></i>
            <span>Kirim Slip Gaji</span>
        </a>


        <a
            href="<?php echo e(route('detail-riwayat')); ?>"
            class="menu-item"
            data-page="detail-riwayat.html"
        >
            <i class="bi bi-clock-history"></i>
            <span>Detail Riwayat</span>
        </a>

        <div class="menu-title system-title">
            SISTEM
        </div>


        <a
            href="<?php echo e(route('role-management')); ?>"
            class="menu-item"
            data-page="role-management.html"
        >
            <i class="bi bi-shield-fill"></i>
            <span>Role Management</span>
        </a>

        <a
            href="<?php echo e(route('pengaturan')); ?>"
            class="menu-item"
            data-page="pengaturan.html"
        >
            <i class="bi bi-gear-fill"></i>
            <span>Pengaturan</span>
        </a>

        <a
            href="<?php echo e(route('log-aktivitas')); ?>"
            class="menu-item"
            data-page="log-aktivitas.html"
        >
            <i class="bi bi-journal-text"></i>
            <span>Log Aktivitas</span>
        </a>

    </nav>


    <button
        type="button"
        class="sidebar-toggle"
        id="sidebarToggle"
        aria-label="Tutup sidebar"
        title="Tutup sidebar"
    >
        <i class="bi bi-chevron-left"></i>
    </button>

</aside><?php /**PATH D:\project slip gaji\slip6\slip4\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>