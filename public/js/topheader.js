/* ===== Topheader reusable =====
   Pemakaian di setiap halaman:
   <div id="topheader-container"
        data-title="Dashboard SDM"
        data-subtitle="Ringkasan data sumber daya manusia"></div>
*/
document.addEventListener("DOMContentLoaded", function () {

    const container = document.getElementById("topheader-container");
    if (!container) return;

    /* Topheader kini dirender langsung oleh server lewat Blade
       (@include('partials.topheader')), sehingga tidak perlu lagi fetch
       components/topheader.html secara terpisah di sisi klien. */
    initTopheader();


    function initTopheader() {

        /* --- Judul & subjudul dari atribut container --- */
        const title = container.dataset.title || document.title;
        const subtitle = container.dataset.subtitle || "";

        document.getElementById("topheaderTitle").textContent = title;
        document.getElementById("topheaderSubtitle").textContent = subtitle;



        /* --- Ikon halaman: sama dengan ikon menu aktif di sidebar --- */
        const iconWrap = document.getElementById("topheaderIcon");
        const iconEl = iconWrap.querySelector("i");

        // Ambil hanya kelas ikon Bootstrap (bi, bi-xxx), buang kelas lain
        function cleanIconClass(cls) {
            return cls
                .split(/\s+/)
                .filter(function (c) { return c === "bi" || c.indexOf("bi-") === 0; })
                .join(" ");
        }

        function applyIcon(cls) {
            const cleaned = cleanIconClass(cls || "");
            if (!cleaned) return false;
            iconEl.className = cleaned.indexOf("bi ") === 0 || cleaned === "bi"
                ? cleaned
                : "bi " + cleaned;
            iconWrap.hidden = false;
            return true;
        }

        // Cari ikon pertama di dalam elemen, abaikan panah (chevron/caret)
        function pickIconClass(root) {
            const icons = root.querySelectorAll("i[class*='bi-']");
            for (let i = 0; i < icons.length; i++) {
                if (!/bi-(chevron|caret|arrow)/.test(icons[i].className)) {
                    return icons[i].className;
                }
            }
            return "";
        }

        function findSidebarIcon() {
            const sidebarContainer = document.getElementById("sidebar-container");
            if (!sidebarContainer) return "";

            const active = sidebarContainer.querySelector(
                ".menu-item.active, .submenu-item.active"
            );
            if (!active) return "";

            let cls = pickIconClass(active);

            // Submenu tanpa ikon: pakai ikon grup induknya (mis. Master Data)
            if (!cls) {
                const group = active.closest(".menu-group");
                const toggle = group && group.querySelector("#masterDataToggle, .menu-item");
                if (toggle) cls = pickIconClass(toggle);
            }
            return cls;
        }

        // Cadangan sementara dari atribut data-icon (mis. "bi-speedometer2")
        applyIcon(container.dataset.icon);

        // Sidebar dimuat asinkron, jadi tunggu sampai menu aktif tersedia
        if (!applyIcon(findSidebarIcon())) {
            const sidebarContainer = document.getElementById("sidebar-container");
            if (sidebarContainer) {
                const observer = new MutationObserver(function () {
                    if (applyIcon(findSidebarIcon())) observer.disconnect();
                });
                observer.observe(sidebarContainer, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ["class"]
                });
                setTimeout(function () { observer.disconnect(); }, 5000);
            }
        }


        /* --- Data pengguna (dari sesi login, dengan fallback ke atribut container) --- */
        const user = {
            name: sessionStorage.getItem("eslip_user_name") || container.dataset.userName || "Administrator",
            role: container.dataset.userRole || "Admin SDM"
        };

        document.getElementById("thUserName").textContent = user.name;
        document.getElementById("thUserRole").textContent = user.role;
        document.getElementById("thAvatar").textContent =
            user.name.trim().charAt(0).toUpperCase();



        /* --- Dropdown akun --- */
        const userBtn = document.getElementById("thUserBtn");
        const menu = document.getElementById("thMenu");
        const wrapper = document.getElementById("thUser");

        function closeMenu() {
            menu.hidden = true;
            userBtn.setAttribute("aria-expanded", "false");
        }

        function toggleMenu() {
            const willOpen = menu.hidden;
            menu.hidden = !willOpen;
            userBtn.setAttribute("aria-expanded", String(willOpen));
        }

        userBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            toggleMenu();
        });

        document.addEventListener("click", function (e) {
            if (!wrapper.contains(e.target)) closeMenu();
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") closeMenu();
        });


        /* --- Keluar: bersihkan sesi lalu pindah ke login --- */
        const logout = document.getElementById("thLogout");
        if (logout) {
            logout.addEventListener("click", function (e) {
                e.preventDefault();
                sessionStorage.removeItem("eslip_logged_in");
                sessionStorage.removeItem("eslip_user_name");
                window.location.href = "/login";
            });
        }
    }

});