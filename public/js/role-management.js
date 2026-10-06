/* =========================================================
   js/role-management.js
   Hak akses per MENU dan per AKSI (Lihat / Tambah / Ubah / Hapus / dst).
   Katalog menu & aksi ditarik dari server (GET /api/permissions,
   App\Support\Permissions) sehingga menu baru cukup ditambah di server.
   Kunci izin berformat "<menu>.<aksi>", mis. "master-karyawan.view".
   ========================================================= */

/* Katalog dari server: [{ key, label, actions:[{key,label}] }] */
let CATALOG = [];

/* Data role ditarik dari database (tabel roles, gajii) lewat RoleController. */
let ROLES = [];

/* Menu yang masuk preset "Admin" (semua aksi pada menu-menu ini). */
const ADMIN_MODULES = [
  "dashboard", "master-user", "master-divisi", "master-jabatan", "master-karyawan",
  "kirim-slip", "detail-riwayat", "template-email",
];

let editingId = null;

const tableBody = document.getElementById("tableBody");
const emptyState = document.getElementById("emptyState");
const footerCount = document.getElementById("footerCount");
const searchInput = document.getElementById("searchRole");

const modalOverlay = document.getElementById("modalOverlay");
const modalTitle = document.getElementById("modalTitle");
const modalClose = document.getElementById("modalClose");
const modalCancel = document.getElementById("modalCancel");
const modalSave = document.getElementById("modalSave");
const roleNameInput = document.getElementById("roleName");
const roleDescInput = document.getElementById("roleDesc");
const permissionGrid = document.getElementById("permissionGrid");
const addRoleBtn = document.getElementById("addRoleBtn");
const presetButtons = document.getElementById("presetButtons");
const selectAllPermissions = document.getElementById("selectAllPermissions");

function escapeHtml(str) {
  return String(str == null ? "" : str).replace(/[&<>"']/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  }[c]));
}

/* ---------- Helper katalog ---------- */
function allKeys() {
  const keys = [];
  CATALOG.forEach(m => m.actions.forEach(a => keys.push(m.key + "." + a.key)));
  return keys;
}

function moduleKeys(moduleKey) {
  const m = CATALOG.find(x => x.key === moduleKey);
  return m ? m.actions.map(a => m.key + "." + a.key) : [];
}

function getPresets() {
  const admin = [];
  ADMIN_MODULES.forEach(k => admin.push(...moduleKeys(k)));
  return {
    staff: ["dashboard.view", "detail-riwayat.view"].filter(k => allKeys().includes(k)),
    admin,
    superadmin: allKeys(),
  };
}

/* Ringkasan izin satu role per menu: [{label, granted, total, aksi:[label]}] */
function summarize(akses) {
  const set = new Set(akses || []);
  return CATALOG.map(m => {
    const granted = m.actions.filter(a => set.has(m.key + "." + a.key));
    return { label: m.label, granted: granted.length, total: m.actions.length, aksi: granted.map(a => a.label) };
  }).filter(x => x.granted > 0);
}

function getFilteredRoles() {
  const q = searchInput.value.trim().toLowerCase();
  return ROLES.filter(r => !q || r.nama.toLowerCase().includes(q));
}

function render() {
  const filtered = getFilteredRoles();
  tableBody.innerHTML = "";

  if (filtered.length === 0) {
    emptyState.style.display = "block";
  } else {
    emptyState.style.display = "none";
    filtered.forEach((role, idx) => {
      const tr = document.createElement("tr");

      const ringkas = summarize(role.akses);
      const tag = (x) => {
        const count = x.granted < x.total ? ` <em>${x.granted}/${x.total}</em>` : "";
        return `<span class="permission-tag" title="${escapeHtml(x.aksi.join(", "))}">${escapeHtml(x.label)}${count}</span>`;
      };
      const visibleTags = ringkas.slice(0, 3).map(tag).join("");
      const moreCount = ringkas.length - 3;
      const moreTitle = ringkas.slice(3).map(x => x.label + " (" + x.aksi.join(", ") + ")").join("\n");
      const moreTag = moreCount > 0 ? `<span class="permission-tag more" title="${escapeHtml(moreTitle)}">+${moreCount} lagi</span>` : "";
      const emptyTag = ringkas.length === 0 ? `<span class="permission-tag more">Tanpa akses</span>` : "";

      tr.innerHTML = `
        <td>${idx + 1}</td>
        <td class="role-name">${escapeHtml(role.nama)}</td>
        <td class="role-desc">${escapeHtml(role.deskripsi || "-")}</td>
        <td><div class="permission-tags">${visibleTags}${moreTag}${emptyTag}</div></td>
        <td>
          <span class="user-count">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/><path d="M17 3.5a4 4 0 0 1 0 7"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
            ${role.jumlahPengguna}
          </span>
        </td>
        <td>
          <div class="row-actions">
            <button type="button" class="btn-icon" data-action="edit" data-id="${role.id}" title="Edit role">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </button>
            <button type="button" class="btn-icon danger" data-action="delete" data-id="${role.id}" title="Hapus role">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
            </button>
          </div>
        </td>
      `;
      tableBody.appendChild(tr);
    });
  }

  footerCount.textContent = `Menampilkan ${filtered.length} dari ${ROLES.length} data`;
}

/* ---------- Matriks hak akses di dalam modal ---------- */
function actionInputs() {
  return Array.from(permissionGrid.querySelectorAll("input[data-key]"));
}

function getCheckedKeys() {
  return actionInputs().filter(cb => cb.checked).map(cb => cb.dataset.key);
}

function setCheckedKeys(keys) {
  const set = new Set(keys);
  actionInputs().forEach(cb => { cb.checked = set.has(cb.dataset.key); });
  refreshModuleStates();
}

/* Status checkbox "semua" tiap menu: penuh / sebagian / kosong. */
function refreshModuleStates() {
  permissionGrid.querySelectorAll(".perm-row").forEach(row => {
    const boxes = Array.from(row.querySelectorAll("input[data-key]"));
    const checked = boxes.filter(b => b.checked).length;
    const master = row.querySelector("input.perm-all");
    master.checked = checked === boxes.length;
    master.indeterminate = checked > 0 && checked < boxes.length;
    row.classList.toggle("has-access", checked > 0);
  });
}

/* Cocokkan pilihan saat ini dengan salah satu preset; selain itu = "Manual". */
function syncPresetHighlight() {
  const current = getCheckedKeys().slice().sort().join(",");
  const presets = getPresets();

  let matched = "manual";
  for (const key of Object.keys(presets)) {
    const presetSorted = presets[key].slice().sort().join(",");
    if (presetSorted === current && current !== "") {
      matched = key;
      break;
    }
  }

  presetButtons.querySelectorAll(".preset-btn").forEach(btn => {
    btn.classList.toggle("active", btn.dataset.preset === matched);
  });

  selectAllPermissions.checked = getCheckedKeys().length === allKeys().length && allKeys().length > 0;
}

presetButtons.addEventListener("click", (e) => {
  const btn = e.target.closest(".preset-btn");
  if (!btn) return;

  const preset = btn.dataset.preset;
  if (preset === "manual") {
    // Mode manual: tidak mengubah pilihan yang sudah ada, cuma menandai mode aktif.
    presetButtons.querySelectorAll(".preset-btn").forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
    return;
  }

  setCheckedKeys(getPresets()[preset] || []);
  syncPresetHighlight();
});

selectAllPermissions.addEventListener("change", () => {
  setCheckedKeys(selectAllPermissions.checked ? allKeys() : []);
  syncPresetHighlight();
});

permissionGrid.addEventListener("change", (e) => {
  const input = e.target;
  if (!input.matches("input[type='checkbox']")) return;

  const row = input.closest(".perm-row");

  if (input.classList.contains("perm-all")) {
    // "Semua" pada satu menu: centang / kosongkan seluruh aksinya.
    row.querySelectorAll("input[data-key]").forEach(b => { b.checked = input.checked; });
  } else if (input.dataset.key.endsWith(".view")) {
    // Tanpa "Lihat", aksi lain pada menu itu tidak berguna: ikut dikosongkan.
    if (!input.checked) row.querySelectorAll("input[data-key]").forEach(b => { b.checked = false; });
  } else if (input.checked) {
    // Tambah / Ubah / Hapus / dst otomatis menyertakan "Lihat".
    const view = row.querySelector("input[data-key$='.view']");
    if (view) view.checked = true;
  }

  refreshModuleStates();
  syncPresetHighlight();
});

function buildPermissionGrid(checkedKeys) {
  const set = new Set(checkedKeys || []);
  permissionGrid.innerHTML = CATALOG.map(m => {
    const chips = m.actions.map(a => {
      const key = m.key + "." + a.key;
      return `<label class="perm-chip"><input type="checkbox" data-key="${escapeHtml(key)}" ${set.has(key) ? "checked" : ""}><span>${escapeHtml(a.label)}</span></label>`;
    }).join("");

    return `
      <div class="perm-row">
        <label class="perm-module">
          <input type="checkbox" class="perm-all" aria-label="Pilih semua aksi ${escapeHtml(m.label)}">
          <span>${escapeHtml(m.label)}</span>
        </label>
        <div class="perm-actions">${chips}</div>
      </div>`;
  }).join("");

  refreshModuleStates();
}

/* ===== Modal: buka/tutup ===== */
function openModal(mode, role) {
  editingId = mode === "edit" ? role.id : null;
  modalTitle.textContent = mode === "edit" ? "Edit Role" : "Tambah Role";
  roleNameInput.value = mode === "edit" ? role.nama : "";
  roleDescInput.value = mode === "edit" ? (role.deskripsi || "") : "";
  buildPermissionGrid(mode === "edit" ? role.akses : []);
  syncPresetHighlight();
  modalOverlay.hidden = false;
  roleNameInput.focus();
}

function closeModal() {
  modalOverlay.hidden = true;
  editingId = null;
}

addRoleBtn.addEventListener("click", () => openModal("add"));
modalClose.addEventListener("click", closeModal);
modalCancel.addEventListener("click", closeModal);

modalOverlay.addEventListener("click", (e) => {
  if (e.target === modalOverlay) closeModal();
});
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && !modalOverlay.hidden) closeModal();
});

modalSave.addEventListener("click", () => {
  const nama = roleNameInput.value.trim();
  if (!nama) {
    UI.alert("Nama role tidak boleh kosong.", { type: "warning" });
    roleNameInput.focus();
    return;
  }

  const deskripsi = roleDescInput.value.trim();
  const payload = { nama, deskripsi, akses: getCheckedKeys() };

  modalSave.disabled = true;
  const request = editingId
    ? Api.put(`/api/roles/${editingId}`, payload)
    : Api.post('/api/roles', payload);

  request.then((saved) => {
    if (editingId) {
      const role = ROLES.find(r => r.id === editingId);
      Object.assign(role, saved);
    } else {
      ROLES.push(saved);
    }
    closeModal();
    render();
    UI.toast("Role disimpan.");
  }).catch((err) => {
    UI.alert(err.message || 'Gagal menyimpan role.', { type: 'error' });
  }).finally(() => { modalSave.disabled = false; });
});

/* ===== Aksi tabel: edit / hapus ===== */
tableBody.addEventListener("click", (e) => {
  const btn = e.target.closest("button[data-action]");
  if (!btn) return;

  const id = Number(btn.dataset.id);
  const role = ROLES.find(r => r.id === id);
  if (!role) return;

  if (btn.dataset.action === "edit") {
    openModal("edit", role);
  } else if (btn.dataset.action === "delete") {
    UI.confirm(`Hapus role "${role.nama}"? Pengguna dengan role ini perlu dipindahkan ke role lain.`, { danger: true, title: 'Hapus Role?' }).then((confirmed) => {
      if (!confirmed) return;
      Api.delete(`/api/roles/${id}`).then(() => {
        ROLES = ROLES.filter(r => r.id !== id);
        render();
        UI.toast('Role dihapus.');
      }).catch((err) => UI.alert(err.message || 'Gagal menghapus role.', { type: 'error' }));
    });
  }
});

searchInput.addEventListener("input", render);

function loadRoles() {
  Api.get('/api/permissions')
    .then((res) => { CATALOG = res.modules || []; return Api.get('/api/roles'); })
    .then((rows) => { ROLES = rows; render(); })
    .catch((err) => { UI.alert('Gagal memuat data role: ' + err.message, { type: 'error' }); });
}

loadRoles();
