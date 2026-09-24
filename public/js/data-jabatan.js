/* =========================================================
   js/data-jabatan.js
   Halaman: data-jabatan.blade.php
   Tersambung ke database db_indukk lewat JabatanController
   (GET/POST/PUT/DELETE /api/jabatan).
   ========================================================= */
document.addEventListener("DOMContentLoaded", function () {

  let jabatanList = [];

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

  if (!tableBody || !entriesInfo || !pagination) {
    return;
  }

  function getFiltered() {
    const q = state.search.trim().toLowerCase();
    let rows = jabatanList.filter((d) => !q || d.nama.toLowerCase().includes(q));
    rows.sort((a, b) => {
      let av = a[state.sortKey], bv = b[state.sortKey];
      if (typeof av === "string") { av = av.toLowerCase(); bv = bv.toLowerCase(); }
      if (av < bv) return state.sortDir === "asc" ? -1 : 1;
      if (av > bv) return state.sortDir === "asc" ? 1 : -1;
      return 0;
    });
    return rows;
  }

  function updateStats() {
    if (statTotalValue) statTotalValue.textContent = jabatanList.length;
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
      tableBody.innerHTML = `<tr><td colspan="3" class="dm-empty">Tidak ada jabatan yang cocok.</td></tr>`;
    } else {
      tableBody.innerHTML = pageRows.map((d, i) => `
        <tr data-id="${d.id}">
          <td class="col-no">${start + i + 1}</td>
          <td>
            <div class="dm-jabatan-cell">
              <span class="dm-jabatan-icon"><i class="bi bi-briefcase-fill"></i></span>
              <span class="dm-jabatan-name">${escapeHtml(d.nama)}</span>
            </div>
          </td>
          <td>
            <div class="dm-actions">
              <button class="icon-btn edit" data-action="edit" data-id="${d.id}" aria-label="Edit">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <button class="icon-btn delete" data-action="delete" data-id="${d.id}" aria-label="Hapus">
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
    document.querySelectorAll("#jabatanTable thead th.sortable").forEach((th) => {
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

  function toast(message, isError) {
    if (window.showToast) { window.showToast(message, isError); return; }
    if (isError) alert(message);
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
  document.querySelectorAll("#jabatanTable thead th.sortable").forEach((th) => {
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
  const jabatanForm = el("jabatanForm");
  const fieldNama = el("fieldNama");

  function openModal(id) {
    state.editingId = id || null;
    if (id) {
      const d = jabatanList.find((x) => x.id === id);
      modalTitle.textContent = "Edit Jabatan";
      fieldNama.value = d.nama;
    } else {
      modalTitle.textContent = "Tambah Jabatan";
      jabatanForm.reset();
    }
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

  jabatanForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const nama = fieldNama.value.trim();
    const submitBtn = jabatanForm.querySelector('[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const request = state.editingId
      ? Api.put(`/api/jabatan/${state.editingId}`, { nama })
      : Api.post('/api/jabatan', { nama });

    request
      .then((saved) => {
        if (state.editingId) {
          const d = jabatanList.find((x) => x.id === state.editingId);
          Object.assign(d, saved);
        } else {
          jabatanList.push(saved);
        }
        closeModal();
        render();
        toast('Data jabatan disimpan.');
      })
      .catch((err) => toast(err.message, true))
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
    Api.delete(`/api/jabatan/${id}`)
      .then(() => {
        jabatanList = jabatanList.filter((d) => d.id !== id);
        closeConfirm();
        render();
        toast('Jabatan dihapus.');
      })
      .catch((err) => { closeConfirm(); toast(err.message, true); });
  });

  // ---- load awal dari database ----
  function loadJabatan() {
    Api.get('/api/jabatan')
      .then((rows) => { jabatanList = rows; render(); })
      .catch((err) => {
        tableBody.innerHTML = `<tr><td colspan="3" class="dm-empty">Gagal memuat data: ${escapeHtml(err.message)}</td></tr>`;
      });
  }

  loadJabatan();
});
