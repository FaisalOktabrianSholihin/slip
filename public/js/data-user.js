/* =========================================================
   js/data-user.js
   Halaman: data-user.blade.php
   Tersambung ke UserController (GET/POST/PUT/DELETE /api/users),
   tabel users + roles di database gajii.
   ========================================================= */
document.addEventListener("DOMContentLoaded", function () {

  let users = [];

  let state = {
    search: "",
    sortKey: "id",
    sortDir: "asc",
    perPage: 10,
    page: 1,
    editingId: null,
    deletingId: null,
  };

  const el = (id) => document.getElementById(id);
  const tableBody = el("tableBody");
  const entriesInfo = el("entriesInfo");
  const pagination = el("pagination");
  const statTotalValue = el("statTotalValue");
  const statSuperadminValue = el("statSuperadminValue");
  const statAdminValue = el("statAdminValue");

  if (!tableBody || !entriesInfo || !pagination) {
    return;
  }

  const roleLabel = { superadmin: "Superadmin", admin: "Admin" };

  function toast(message, isError) {
    if (window.showToast) { window.showToast(message, isError); return; }
    UI.toast(message, isError ? 'error' : 'success');
  }

  function getFiltered() {
    const q = state.search.trim().toLowerCase();
    let rows = users.filter((u) =>
      !q ||
      u.nama.toLowerCase().includes(q) ||
      u.email.toLowerCase().includes(q) ||
      (roleLabel[u.role] || u.role || "").toLowerCase().includes(q)
    );
    rows.sort((a, b) => {
      let av = a[state.sortKey], bv = b[state.sortKey];
      if (typeof av === "string") { av = av.toLowerCase(); bv = bv.toLowerCase(); }
      if (av < bv) return state.sortDir === "asc" ? -1 : 1;
      if (av > bv) return state.sortDir === "asc" ? 1 : -1;
      return 0;
    });
    return rows;
  }

  function badgeHtml(role) {
    if (role === "superadmin") {
      return `<span class="badge badge-superadmin"><span class="badge-dot"></span>Superadmin</span>`;
    }
    return `<span class="badge badge-admin"><span class="badge-dot"></span>Admin</span>`;
  }

  function updateStats() {
    if (!statTotalValue) return;
    statTotalValue.textContent = users.length;
    statSuperadminValue.textContent = users.filter((u) => u.role === "superadmin").length;
    statAdminValue.textContent = users.filter((u) => u.role === "admin").length;
  }

  function render() {
    updateStats();

    const filtered = getFiltered();
    const total = filtered.length;
    const totalPages = Math.max(1, Math.ceil(total / state.perPage));
    state.page = Math.min(state.page, totalPages);

    const start = (state.page - 1) * state.perPage;
    const pageRows = filtered.slice(start, start + state.perPage);

    if (pageRows.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="5" class="dm-empty">Tidak ada data yang cocok.</td></tr>`;
    } else {
      tableBody.innerHTML = pageRows.map((u, i) => `
        <tr data-id="${u.id}">
          <td class="col-no">${start + i + 1}</td>
          <td class="col-nama">${escapeHtml(u.nama)}</td>
          <td class="col-email">${escapeHtml(u.email)}</td>
          <td>${badgeHtml(u.role)}</td>
          <td>
            <div class="dm-actions">
              <button class="icon-btn edit" data-action="edit" data-id="${u.id}" aria-label="Edit">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <button class="icon-btn delete" data-action="delete" data-id="${u.id}" aria-label="Hapus">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          </td>
        </tr>
      `).join("");
    }

    entriesInfo.textContent = total === 0
      ? "Menampilkan 0 entri"
      : `Menampilkan ${start + 1} sampai ${Math.min(start + state.perPage, total)} dari ${total} entri`;

    renderPagination(totalPages);
    renderSortIndicators();
  }

  function renderPagination(totalPages) {
    let html = `<button class="page-btn" data-page="prev" ${state.page === 1 ? "disabled" : ""}>Prev</button>`;
    for (let p = 1; p <= totalPages; p++) {
      html += `<button class="page-btn ${p === state.page ? "active" : ""}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="page-btn" data-page="next" ${state.page === totalPages ? "disabled" : ""}>Next</button>`;
    pagination.innerHTML = html;
  }

  function renderSortIndicators() {
    document.querySelectorAll("#userTable thead th.sortable").forEach((th) => {
      const key = th.dataset.key;
      th.classList.toggle("sort-active", key === state.sortKey);
      const arrow = th.querySelector(".sort-icon");
      if (arrow) arrow.textContent = key === state.sortKey ? (state.sortDir === "asc" ? "▲" : "▼") : "↕";
    });
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
    }[c]));
  }

  // ---- search / show entries ----
  el("searchInput").addEventListener("input", (e) => {
    state.search = e.target.value;
    state.page = 1;
    render();
  });

  el("entriesPerPage").addEventListener("change", (e) => {
    state.perPage = parseInt(e.target.value, 10);
    state.page = 1;
    render();
  });

  // ---- sorting ----
  document.querySelectorAll("#userTable thead th.sortable").forEach((th) => {
    th.addEventListener("click", () => {
      const key = th.dataset.key;
      if (state.sortKey === key) {
        state.sortDir = state.sortDir === "asc" ? "desc" : "asc";
      } else {
        state.sortKey = key;
        state.sortDir = "asc";
      }
      render();
    });
  });

  // ---- pagination ----
  pagination.addEventListener("click", (e) => {
    const btn = e.target.closest(".page-btn");
    if (!btn || btn.disabled) return;
    const p = btn.dataset.page;
    if (p === "prev") state.page -= 1;
    else if (p === "next") state.page += 1;
    else state.page = parseInt(p, 10);
    render();
  });

  // ---- row actions ----
  tableBody.addEventListener("click", (e) => {
    const btn = e.target.closest(".icon-btn");
    if (!btn) return;
    const id = parseInt(btn.dataset.id, 10);
    if (btn.dataset.action === "edit") openModal(id);
    if (btn.dataset.action === "delete") openConfirm(id);
  });

  // ---- add/edit modal ----
  const modalOverlay = el("modalOverlay");
  const modalTitle = el("modalTitle");
  const userForm = el("userForm");
  const fieldNama = el("fieldNama");
  const fieldEmail = el("fieldEmail");
  const fieldRole = el("fieldRole");
  const fieldPassword = el("fieldPassword");
  const fieldPasswordConfirm = el("fieldPasswordConfirm");
  const pwOptional = el("pwOptional");

  // tombol mata: lihat / sembunyikan password
  document.querySelectorAll(".dm-pass-toggle").forEach((btn) => {
    btn.addEventListener("click", () => {
      const input = el(btn.dataset.target);
      const show = input.type === "password";
      input.type = show ? "text" : "password";
      btn.querySelector("i").className = show ? "bi bi-eye-slash" : "bi bi-eye";
    });
  });

  function openModal(id) {
    state.editingId = id || null;
    if (id) {
      const u = users.find((x) => x.id === id);
      modalTitle.textContent = "Edit User";
      fieldNama.value = u.nama;
      fieldEmail.value = u.email;
      fieldRole.value = u.role;
    } else {
      modalTitle.textContent = "Tambah User";
      userForm.reset();
    }
    // Password: wajib saat tambah user, opsional saat edit (kosong = tidak diubah).
    fieldPassword.value = "";
    fieldPasswordConfirm.value = "";
    fieldPassword.type = fieldPasswordConfirm.type = "password";
    document.querySelectorAll(".dm-pass-toggle i").forEach((i) => (i.className = "bi bi-eye"));
    pwOptional.hidden = !id;
    modalOverlay.classList.add("open");
    fieldNama.focus();
  }

  function closeModal() {
    modalOverlay.classList.remove("open");
    state.editingId = null;
  }

  el("btnAdd").addEventListener("click", () => openModal(null));
  el("modalClose").addEventListener("click", closeModal);
  el("btnCancel").addEventListener("click", closeModal);
  modalOverlay.addEventListener("click", (e) => { if (e.target === modalOverlay) closeModal(); });

  userForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const payload = {
      nama: fieldNama.value.trim(),
      email: fieldEmail.value.trim(),
      role: fieldRole.value,
    };

    // Validasi password di sisi browser (server tetap memvalidasi ulang).
    const pw = fieldPassword.value;
    const pw2 = fieldPasswordConfirm.value;
    if (!state.editingId && !pw) {
      UI.alert("Password wajib diisi untuk user baru.", { type: "warning" });
      fieldPassword.focus();
      return;
    }
    if (pw) {
      if (pw.length < 8) {
        UI.alert("Password minimal 8 karakter.", { type: "warning" });
        fieldPassword.focus();
        return;
      }
      if (pw !== pw2) {
        UI.alert("Konfirmasi password tidak sama dengan password.", { type: "warning" });
        fieldPasswordConfirm.focus();
        return;
      }
      payload.password = pw;
      payload.password_confirmation = pw2;
    }

    const submitBtn = userForm.querySelector('[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const request = state.editingId
      ? Api.put(`/api/users/${state.editingId}`, payload)
      : Api.post('/api/users', payload);

    request.then((saved) => {
      if (state.editingId) {
        const u = users.find((x) => x.id === state.editingId);
        Object.assign(u, saved);
        toast(pw ? 'Perubahan data user dan password disimpan.' : 'Perubahan data user disimpan.');
      } else {
        users.push(saved);
        toast('User baru dibuat.');
      }
      closeModal();
      render();
    }).catch((err) => toast(err.message, true))
      .finally(() => { if (submitBtn) submitBtn.disabled = false; });
  });

  // ---- delete confirm modal ----
  const confirmOverlay = el("confirmOverlay");

  function openConfirm(id) {
    state.deletingId = id;
    confirmOverlay.classList.add("open");
  }
  function closeConfirm() {
    confirmOverlay.classList.remove("open");
    state.deletingId = null;
  }
  el("confirmCancel").addEventListener("click", closeConfirm);
  confirmOverlay.addEventListener("click", (e) => { if (e.target === confirmOverlay) closeConfirm(); });
  el("confirmDelete").addEventListener("click", () => {
    const id = state.deletingId;
    Api.delete(`/api/users/${id}`).then(() => {
      users = users.filter((u) => u.id !== id);
      closeConfirm();
      render();
      toast('User dihapus.');
    }).catch((err) => { closeConfirm(); toast(err.message, true); });
  });

  // ---- load awal ----
  Api.get('/api/users').then((rows) => { users = rows; render(); })
    .catch((err) => {
      tableBody.innerHTML = `<tr><td colspan="5" class="dm-empty">Gagal memuat data: ${escapeHtml(err.message)}</td></tr>`;
    });
});
