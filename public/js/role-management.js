/* Daftar menu yang bisa diberi hak akses.
   Sesuaikan key & label ini dengan menu di sidebar.html kamu. */
const MENU_LIST = [
  { key: "dashboard", label: "Dashboard" },
  { key: "master-user", label: "Master Data - User" },
  { key: "master-divisi", label: "Master Data - Divisi" },
  { key: "master-jabatan", label: "Master Data - Jabatan" },
  { key: "master-karyawan", label: "Master Data - Karyawan" },
  { key: "kirim-slip", label: "Kirim Slip Gaji" },
  { key: "detail-riwayat", label: "Detail Riwayat" },
  { key: "setting", label: "Setting" },
  { key: "role-management", label: "Role Management" },
];

/* Data role ditarik dari database (tabel roles, gajii) lewat
   RoleController - lihat loadRoles() di bagian bawah file ini. */
let ROLES = [];

/* Template hak akses siap pakai. Sesuaikan isinya sesuai kebutuhanmu. */
const ROLE_PRESETS = {
  staff: ["dashboard", "detail-riwayat"],
  admin: ["dashboard", "master-user", "master-divisi", "master-jabatan", "master-karyawan", "kirim-slip", "detail-riwayat"],
  superadmin: MENU_LIST.map(m => m.key),
};

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

function menuLabel(key) {
  const found = MENU_LIST.find(m => m.key === key);
  return found ? found.label : key;
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

      const visibleTags = role.akses.slice(0, 3).map(k => `<span class="permission-tag">${menuLabel(k)}</span>`).join("");
      const moreCount = role.akses.length - 3;
      const moreTag = moreCount > 0 ? `<span class="permission-tag more">+${moreCount} lagi</span>` : "";

      tr.innerHTML = `
        <td>${idx + 1}</td>
        <td class="role-name">${role.nama}</td>
        <td class="role-desc">${role.deskripsi || "-"}</td>
        <td><div class="permission-tags">${visibleTags}${moreTag}</div></td>
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

function getCheckedKeys() {
  return Array.from(permissionGrid.querySelectorAll("input[type='checkbox']:checked")).map(cb => cb.value);
}

function setCheckedKeys(keys) {
  permissionGrid.querySelectorAll("input[type='checkbox']").forEach(cb => {
    cb.checked = keys.includes(cb.value);
  });
}

/* Cocokkan pilihan checkbox saat ini dengan salah satu preset.
   Kalau tidak cocok dengan preset manapun, anggap "Manual". */
function syncPresetHighlight() {
  const current = getCheckedKeys().slice().sort().join(",");

  let matched = "manual";
  for (const key of Object.keys(ROLE_PRESETS)) {
    const presetSorted = ROLE_PRESETS[key].slice().sort().join(",");
    if (presetSorted === current && current !== "") {
      matched = key;
      break;
    }
  }

  presetButtons.querySelectorAll(".preset-btn").forEach(btn => {
    btn.classList.toggle("active", btn.dataset.preset === matched);
  });

  selectAllPermissions.checked = getCheckedKeys().length === MENU_LIST.length;
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

  setCheckedKeys(ROLE_PRESETS[preset]);
  syncPresetHighlight();
});

selectAllPermissions.addEventListener("change", () => {
  if (selectAllPermissions.checked) {
    setCheckedKeys(MENU_LIST.map(m => m.key));
  } else {
    setCheckedKeys([]);
  }
  syncPresetHighlight();
});

permissionGrid.addEventListener("change", (e) => {
  if (e.target.matches("input[type='checkbox']")) syncPresetHighlight();
});

/* ===== Modal: buka/tutup ===== */
function buildPermissionGrid(checkedKeys) {
  permissionGrid.innerHTML = "";
  MENU_LIST.forEach(menu => {
    const isChecked = checkedKeys.includes(menu.key);
    const label = document.createElement("label");
    label.className = "permission-item";
    label.innerHTML = `
      <input type="checkbox" value="${menu.key}" ${isChecked ? "checked" : ""}>
      <span>${menu.label}</span>
    `;
    permissionGrid.appendChild(label);
  });
}

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

  const akses = Array.from(permissionGrid.querySelectorAll("input[type='checkbox']:checked")).map(cb => cb.value);
  const deskripsi = roleDescInput.value.trim();
  const payload = { nama, deskripsi, akses };

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
  Api.get('/api/roles').then((rows) => { ROLES = rows; render(); })
    .catch((err) => { UI.alert('Gagal memuat data role: ' + err.message, { type: 'error' }); });
}

loadRoles();