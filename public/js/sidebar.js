document.addEventListener("DOMContentLoaded", function () {

    const STORAGE_KEY = "eslip_sidebar_state";

    const sidebarContainer = document.getElementById("sidebar-container");
    const appContainer = document.querySelector(".app-container");

    if (!sidebarContainer || !appContainer) {
        return;
    }


    /* Terapkan status tersimpan SEKARANG, sebelum sidebar selesai dimuat.
       Sebelumnya status baru diterapkan setelah fetch selesai, sehingga
       halaman sempat tampil dengan sidebar terbuka lalu "meloncat" menutup.
       Kelas sidebar-preload mematikan animasi selama pemuatan awal. */
    appContainer.classList.add("sidebar-preload");

    if (readState() === "closed") {
        appContainer.classList.add("sidebar-closed");
    }

    /* Sidebar kini dirender langsung oleh server lewat Blade
       (@include('partials.sidebar')), sehingga tidak perlu lagi fetch
       components/sidebar.html secara terpisah di sisi klien. */
    initializeSidebar();

    // Nyalakan kembali animasi setelah tampilan awal selesai digambar
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            appContainer.classList.remove("sidebar-preload");
        });
    });


    /* ---------- Penyimpanan status (aman jika localStorage diblokir) ---------- */

    function readState() {

        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            return null;
        }

    }


    function saveState(value) {

        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch (error) {
            /* diabaikan: sidebar tetap berfungsi tanpa penyimpanan */
        }

    }


    /* ---------- Inisialisasi ---------- */

    function initializeSidebar() {

        const sidebar = document.getElementById("sidebar");
        const sidebarToggle = document.getElementById("sidebarToggle");
        const masterDataToggle = document.getElementById("masterDataToggle");

        if (!sidebar || !sidebarToggle) {
            return;
        }


        setActiveMenu();

        updateToggleIcon(isClosed());


        if (masterDataToggle) {

            masterDataToggle.setAttribute("aria-controls", "masterDataSubmenu");
            masterDataToggle.setAttribute("aria-expanded", "false");

        }


        sidebarToggle.addEventListener("click", function () {

            setClosed(!isClosed());

        });


        if (masterDataToggle) {

            masterDataToggle.addEventListener("click", function () {

                const group = getMasterDataGroup();

                if (!group) {
                    return;
                }

                /* Saat sidebar tertutup submenu tidak punya tempat tampil,
                   jadi klik pertama membuka sidebar sekaligus submenunya.
                   (Sebelumnya klik ini tidak melakukan apa-apa, sehingga
                   halaman di dalam Master Data tidak bisa dibuka.) */
                if (isClosed()) {

                    setClosed(false);
                    setMasterData(true);

                    return;

                }

                setMasterData(!group.classList.contains("open"));

            });

        }


        openMasterDataForActivePage();

    }


    /* ---------- Buka / tutup sidebar ---------- */

    function isClosed() {

        return appContainer.classList.contains("sidebar-closed");

    }


    function setClosed(closed) {

        appContainer.classList.toggle("sidebar-closed", closed);

        saveState(closed ? "closed" : "open");

        updateToggleIcon(closed);

        if (closed) {

            setMasterData(false);

        } else {

            // Saat dibuka kembali, submenu halaman aktif ikut terbuka lagi
            openMasterDataForActivePage();

        }

    }


    function getMasterDataGroup() {

        return document.querySelector(".menu-group");

    }


    function setMasterData(open) {

        const group = getMasterDataGroup();
        const toggle = document.getElementById("masterDataToggle");

        if (!group) {
            return;
        }

        group.classList.toggle("open", open);

        if (toggle) {
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        }

    }


    function updateToggleIcon(closed) {

        const sidebarToggle = document.getElementById("sidebarToggle");

        if (!sidebarToggle) {
            return;
        }

        const icon = sidebarToggle.querySelector("i");

        if (!icon) {
            return;
        }

        icon.className = closed ? "bi bi-chevron-right" : "bi bi-chevron-left";

        sidebarToggle.setAttribute("aria-label", closed ? "Buka sidebar" : "Tutup sidebar");
        sidebarToggle.setAttribute("title", closed ? "Buka sidebar" : "Tutup sidebar");

    }


    /* ---------- Menu aktif ---------- */

    // "Kirim-Slip.html", "kirim-slip.html" dan "kirim-slip" dianggap sama
    function normalizePage(value) {

        return String(value || "").toLowerCase().replace(/\.html$/, "");

    }


    function getCurrentPage() {
        const override = document.body.getAttribute("data-nav");
        const file = override || window.location.pathname.split("/").pop();
        return normalizePage(file) || "dashboard";
    }

    function setActiveMenu() {

        const currentPage = getCurrentPage();

        const items = document.querySelectorAll(
            ".menu-item[data-page], .submenu-item[data-page]"
        );

        items.forEach(function (item) {

            const isActive =
                normalizePage(item.getAttribute("data-page")) === currentPage;

            item.classList.toggle("active", isActive);

            if (isActive) {
                item.setAttribute("aria-current", "page");
            } else {
                item.removeAttribute("aria-current");
            }

        });

    }


    function openMasterDataForActivePage() {

        const activeSubmenu = document.querySelector(".submenu-item.active");

        if (activeSubmenu && !isClosed()) {
            setMasterData(true);
        }

    }

});