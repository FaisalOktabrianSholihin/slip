<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <img src="{{ asset('assets/logo.png') }}" alt="Mitratani">
        </div>

        <div class="sidebar-brand-text">
            <span>E-SLIP GAJI</span>
            <span>MITRATANI</span>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <nav class="sidebar-menu">

        @if (auth()->user()->hasPermission('dashboard.view'))
            <a href="{{ route('dashboard') }}" class="menu-item" data-page="dashboard.html"><i
                    class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        @endif

        <div class="menu-title system-title">
            MENU UTAMA
        </div>

        @if (auth()->user()->hasPermission('master-user.view') ||
                auth()->user()->hasPermission('master-divisi.view') ||
                auth()->user()->hasPermission('master-jabatan.view') ||
                auth()->user()->hasPermission('master-karyawan.view'))
            <div class="menu-group">

                <button type="button" class="menu-item master-data-toggle" id="masterDataToggle">
                    <i class="bi bi-database-fill"></i>

                    <span>Master Data</span>

                    <i class="bi bi-chevron-down menu-arrow"></i>
                </button>


                <div class="master-data-submenu" id="masterDataSubmenu">

                    @if (auth()->user()->hasPermission('master-user.view'))
                        <a href="{{ route('data-user') }}" class="submenu-item" data-page="data-user.html">User</a>
                    @endif

                    @if (auth()->user()->hasPermission('master-divisi.view'))
                        <a href="{{ route('data-divisi') }}" class="submenu-item"
                            data-page="data-divisi.html">Divisi</a>
                    @endif

                    @if (auth()->user()->hasPermission('master-jabatan.view'))
                        <a href="{{ route('data-jabatan') }}" class="submenu-item"
                            data-page="data-jabatan.html">Jabatan</a>
                    @endif

                    @if (auth()->user()->hasPermission('master-karyawan.view'))
                        <a href="{{ route('data-karyawan') }}" class="submenu-item"
                            data-page="data-karyawan.html">Karyawan</a>
                    @endif

                </div>

            </div>
        @endif

        @if (auth()->user()->hasPermission('demografi.view'))
            <a href="{{ route('demografi') }}" class="menu-item"><i
                    class="bi bi-people-fill"></i><span>Demografi</span></a>
        @endif
        @if (auth()->user()->hasPermission('kirim-slip.view'))
            <a href="{{ route('kirim-slip') }}" class="menu-item" data-page="kirim-slip.html">
                <i class="bi bi-send-fill"></i>
                <span>Kirim Slip Gaji</span>
            </a>
        @endif


        @if (auth()->user()->hasPermission('detail-riwayat.view'))
            <a href="{{ route('detail-riwayat') }}" class="menu-item" data-page="detail-riwayat.html">
                <i class="bi bi-clock-history"></i>
                <span>Detail Riwayat</span>
            </a>
        @endif

        @if (auth()->user()->hasPermission('template-email.view'))
            <a href="{{ route('template-email') }}" class="menu-item" data-page="template-email.html">
                <i class="bi bi-envelope-paper-fill"></i>
                <span>Template Email</span>
            </a>
        @endif

        @if (auth()->user()->hasPermission('role-management.view') ||
                auth()->user()->hasPermission('setting.view') ||
                auth()->user()->hasPermission('log-aktivitas.view'))
            <div class="menu-title system-title">SISTEM</div>
        @endif


        @if (auth()->user()->hasPermission('role-management.view'))
            <a href="{{ route('role-management') }}" class="menu-item" data-page="role-management.html">
                <i class="bi bi-shield-fill"></i>
                <span>Role Management</span>
            </a>
        @endif

        @if (auth()->user()->hasPermission('setting.view'))
            <a href="{{ route('pengaturan') }}" class="menu-item" data-page="pengaturan.html">
                <i class="bi bi-gear-fill"></i>
                <span>Pengaturan</span>
            </a>
        @endif

        @if (auth()->user()->hasPermission('log-aktivitas.view'))
            <a href="{{ route('log-aktivitas') }}" class="menu-item" data-page="log-aktivitas.html">
                <i class="bi bi-journal-text"></i>
                <span>Log Aktivitas</span>
            </a>
        @endif

    </nav>


    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Tutup sidebar" title="Tutup sidebar">
        <i class="bi bi-chevron-left"></i>
    </button>

</aside>
