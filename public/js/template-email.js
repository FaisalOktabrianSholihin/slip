/* =========================================================
   js/template-email.js
   Halaman: template-email.blade.php
   Tersambung ke EmailTemplateController (GET/PUT /api/template-email).
   Template dipakai SlipGajiMail saat slip gaji dikirim.
   ========================================================= */
document.addEventListener("DOMContentLoaded", function () {

  const el = (id) => document.getElementById(id);
  const subjectInput = el("tplSubject");
  const bodyInput = el("tplBody");
  const chipsBox = el("tplChips");
  const unknownBox = el("tplUnknown");
  const updatedEl = el("tplUpdated");
  const dirtyEl = el("tplDirty");
  const saveBtn = el("tplSave");
  const resetBtn = el("tplReset");

  if (!subjectInput || !bodyInput) return;

  let PLACEHOLDERS = {};
  let DEFAULT = { subject: "", body: "" };
  let saved = { subject: "", body: "" };
  let lastField = bodyInput; // kolom terakhir yang difokuskan (target penyisipan placeholder)

  /* Contoh data untuk pratinjau (periode = bulan berjalan). */
  const BULAN = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
  const now = new Date();
  const SAMPLE = {
    nama: "Budi Santoso",
    nik: "12345601",
    divisi: "Produksi",
    periode: BULAN[now.getMonth()] + " " + now.getFullYear(),
  };

  function esc(v) {
    return String(v == null ? "" : v).replace(/[&<>"']/g, (c) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
    }[c]));
  }

  function fill(text) {
    return String(text).replace(/\{(\w+)\}/g, (m, k) => (k in SAMPLE ? SAMPLE[k] : m));
  }

  /* Sama dengan EmailTemplate::keHtml() di server. */
  function toHtml(text) {
    return String(text).replace(/\r\n?/g, "\n").trim().split(/\n{2,}/)
      .map((p) => "<p>" + esc(p.trim()).replace(/\n/g, "<br>") + "</p>").join("");
  }

  function renderPreview() {
    el("pvSubject").textContent = fill(subjectInput.value).replace(/\s+/g, " ").trim();
    el("pvBody").innerHTML = toHtml(fill(bodyInput.value));
    el("pvFile").textContent = "SLIP_" + SAMPLE.nik + "_" + now.getFullYear() + String(now.getMonth() + 1).padStart(2, "0") + ".pdf";

    // peringatkan placeholder yang tidak dikenal (akan tampil apa adanya di email)
    const used = (subjectInput.value + " " + bodyInput.value).match(/\{\w+\}/g) || [];
    const unknown = Array.from(new Set(used)).filter((p) => !(p.slice(1, -1) in PLACEHOLDERS));
    unknownBox.hidden = unknown.length === 0;
    if (unknown.length) {
      unknownBox.textContent = "Placeholder tidak dikenal (akan tampil apa adanya): " + unknown.join(", ");
    }

    const dirty = subjectInput.value !== saved.subject || bodyInput.value !== saved.body;
    dirtyEl.hidden = !dirty;
  }

  function buildChips() {
    chipsBox.innerHTML = Object.keys(PLACEHOLDERS).map((k) =>
      '<button type="button" class="tpl-chip" data-ph="' + esc(k) + '" title="' + esc(PLACEHOLDERS[k]) + '">' +
      "<code>{" + esc(k) + "}</code><small>" + esc(PLACEHOLDERS[k]) + "</small></button>"
    ).join("");
  }

  function apply(data) {
    subjectInput.value = data.subject;
    bodyInput.value = data.body;
    renderPreview();
  }

  subjectInput.addEventListener("focus", () => { lastField = subjectInput; });
  bodyInput.addEventListener("focus", () => { lastField = bodyInput; });
  subjectInput.addEventListener("input", renderPreview);
  bodyInput.addEventListener("input", renderPreview);

  chipsBox.addEventListener("click", (e) => {
    const chip = e.target.closest(".tpl-chip");
    if (!chip) return;
    const token = "{" + chip.dataset.ph + "}";
    const f = lastField;
    const start = f.selectionStart == null ? f.value.length : f.selectionStart;
    const end = f.selectionEnd == null ? f.value.length : f.selectionEnd;
    f.value = f.value.slice(0, start) + token + f.value.slice(end);
    f.focus();
    f.setSelectionRange(start + token.length, start + token.length);
    renderPreview();
  });

  resetBtn.addEventListener("click", () => {
    UI.confirm("Kembalikan subject dan isi email ke teks bawaan? Perubahan baru tersimpan setelah Anda menekan Simpan Template.", {
      title: "Kembalikan Default?", okText: "Ya, Kembalikan",
    }).then((ok) => {
      if (!ok) return;
      apply(DEFAULT);
      UI.toast("Teks bawaan dimuat. Klik Simpan Template untuk menyimpan.");
    });
  });

  saveBtn.addEventListener("click", () => {
    const subject = subjectInput.value.trim();
    const body = bodyInput.value.trim();

    if (!subject) {
      UI.alert("Subject email wajib diisi.", { type: "warning" });
      subjectInput.focus();
      return;
    }
    if (!body) {
      UI.alert("Isi email wajib diisi.", { type: "warning" });
      bodyInput.focus();
      return;
    }

    saveBtn.disabled = true;
    Api.put("/api/template-email", { subject, body })
      .then((res) => {
        saved = { subject: res.subject, body: res.body };
        apply(saved);
        updatedEl.textContent = "Terakhir diperbarui: " + (res.updatedAt || "-");
        UI.toast("Template email disimpan.");
      })
      .catch((err) => UI.alert(err.message || "Gagal menyimpan template.", { type: "error" }))
      .finally(() => { saveBtn.disabled = false; });
  });

  Api.get("/api/template-email")
    .then((res) => {
      PLACEHOLDERS = res.placeholders || {};
      DEFAULT = res.default || DEFAULT;
      saved = { subject: res.subject, body: res.body };
      buildChips();
      apply(saved);
      updatedEl.textContent = "Terakhir diperbarui: " + (res.updatedAt || "-");
    })
    .catch((err) => UI.alert("Gagal memuat template: " + err.message, { type: "error" }));
});
