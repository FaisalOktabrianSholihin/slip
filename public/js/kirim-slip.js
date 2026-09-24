/* =========================================================
   Kirim Slip Gaji - Import Excel Payroll
   Alur baru:
   1. Klik Pilih File -> tampil popup konfigurasi nama Tambahan/Potongan.
   2. Simpan konfigurasi -> dialog file Excel terbuka.
   3. Excel hanya berisi NIK, Nama, Gaji Pokok, Tambahan 1-9, Potongan 1-10.
   4. NIK wajib 8 digit dan harus ditemukan di master Karyawan.
   5. Kontak Email/WhatsApp diambil dari master Karyawan, bukan Excel.
   ========================================================= */
(function () {
  'use strict';
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
  const excel = $('#ksExcel');
  const excelName = $('#ksExcelName');
  const error = $('#ksExcelError');
  const result = $('#ksImportResult');
  const btnImport = $('#ksBtnImport');
  const btnTemplate = $('#ksBtnTemplate');
  const btnPreview = $('#ksBtnPreview');
  const badge = $('#ksPendingBadge');
  const chooseBtn = $('#ksChooseFile');
  let busy = false;

  const ADD_DEFAULTS = [
    'Tunjangan Jabatan','Tunjangan Bansos','Tunjangan Managerial','Tambahan 4',
    'Tambahan 5','Tambahan 6','Tambahan 7','Tambahan 8','Tambahan 9','Tambahan 10',
    'Tambahan 11','Tambahan 12','Tambahan 13','Tambahan 14','Tambahan 15',
    'Tambahan 16','Tambahan 17','Tambahan 18','Tambahan 19','Tambahan 20'
  ];
  const DEDUCT_DEFAULTS = [
    'BP JAMSOSTEK (JHT: 2%); (JP: 1%)','BPJS-Kshtn (JK: 1%)','BNI (DPLK Simponi)',
    'KSU Ke. Mitratani','Iuran Anggota SPA','BTN (KPR)','BRI Cab Jember',
    'Potongan 8','Potongan 9','Potongan 10','Potongan 11','Potongan 12','Potongan 13','Potongan 14','Potongan 15','Potongan 16','Potongan 17','Potongan 18','Potongan 19','Potongan 20'
  ];
  const CFG_KEY = 'eslip_payroll_components';
  const MAX_COMPONENTS = 20;
  const HEADERS = { nik:'nik', nama:'nama', gapok:'gajiPokok', gajipokok:'gajiPokok' };


  function esc(v) { return SlipUI.esc(v); }
  function norm(v) { return String(v == null ? '' : v).toLowerCase().replace(/[^a-z0-9]/g, ''); }
  function text(v) {
    if (v == null) return '';
    if (typeof v === 'object') {
      if (v.richText) return v.richText.map(x => x.text || '').join('').trim();
      if (v.text != null) return text(v.text);
      if (v.result != null) return text(v.result);
      return '';
    }
    return String(v).trim();
  }
  function num(v) {
    if (v == null || v === '') return 0;
    if (typeof v === 'number') return isFinite(v) ? v : 0;
    const n = Number(String(v).replace(/[^0-9.-]/g, ''));
    return isFinite(n) ? n : 0;
  }
  function today() { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
  function cell(row, col) { return col ? row.getCell(col).value : null; }
  function getConfig() {
    try {
      const x = JSON.parse(localStorage.getItem(CFG_KEY) || '{}');
      let additions = Array.isArray(x.additions) ? x.additions.filter(v => String(v).trim()).map(v => String(v).trim()) : ADD_DEFAULTS.slice();
      let deductions = Array.isArray(x.deductions) ? x.deductions.filter(v => String(v).trim()).map(v => String(v).trim()) : DEDUCT_DEFAULTS.slice();
      // Versi frontend lama menyimpan "Gaji Pokok" sebagai komponen tambahan.
      // Gaji Pokok sekarang tetap sebagai field wajib terpisah.
      additions = additions.filter(v => v.toLowerCase() !== 'gaji pokok').slice(0, MAX_COMPONENTS);
      deductions = deductions.slice(0, MAX_COMPONENTS);
      return { additions, deductions };
    } catch (_) { return { additions: ADD_DEFAULTS.slice(), deductions: DEDUCT_DEFAULTS.slice() }; }
  }
  function saveConfig(c) { localStorage.setItem(CFG_KEY, JSON.stringify(c)); }
  function setError(msg) { error.textContent = msg || ''; excel.classList.toggle('is-invalid', !!msg); }
  function refreshBadge() {
    SlipStore.getPreviewRows().then(function (rows) {
      const n = rows.length;
      badge.textContent = n;
      badge.hidden = n === 0;
    }).catch(function () { badge.hidden = true; });
  }

  function configModal() {
    const cfg = getConfig();
    const modal = document.createElement('div'); modal.className = 'ks-component-modal';
    const makeRows = (arr,type) => arr.map((name,i) => '<tr data-type="'+type+'" data-index="'+i+'"><td>'+(i+1)+'</td><td class="ks-component-name"><input class="ks-component-input" maxlength="150" value="'+esc(name)+'" aria-label="Nama komponen '+(i+1)+'"></td><td><button type="button" class="ks-component-delete" title="Hapus"><i class="bi bi-trash3"></i></button></td></tr>').join('');
    const renderTables = () => {
      modal.querySelector('[data-add-body]').innerHTML = makeRows(cfg.additions,'additions') || '<tr><td colspan="3" class="ks-component-empty">Belum ada komponen tambahan.</td></tr>';
      modal.querySelector('[data-deduct-body]').innerHTML = makeRows(cfg.deductions,'deductions') || '<tr><td colspan="3" class="ks-component-empty">Belum ada komponen potongan.</td></tr>';
      modal.querySelector('[data-add-count]').textContent = cfg.additions.length + ' / 20 slot';
      modal.querySelector('[data-deduct-count]').textContent = cfg.deductions.length + ' / 20 slot';
    };
    modal.innerHTML = '<div class="ks-component-backdrop"></div><div class="ks-component-dialog" role="dialog" aria-modal="true" aria-labelledby="ksComponentTitle">'
      + '<div class="ks-component-head"><div class="ks-warning-icon"><i class="bi bi-exclamation-triangle-fill"></i></div><div><span class="ks-component-eyebrow">PERHATIAN</span><h2 id="ksComponentTitle">Atur Komponen Slip Gaji</h2><p>Maksimal 20 baris untuk Tambahan dan 20 baris untuk Potongan. Jika baris dihapus, kamu masih dapat menambahkannya kembali sampai batas 20.</p></div><button class="ks-component-close" type="button"><i class="bi bi-x-lg"></i></button></div>'
      + '<div class="ks-component-body ks-component-grid">'
      + '<div class="ks-component-panel"><div class="ks-component-panel-head"><div><strong>Tambahan</strong><span data-add-count></span></div><button type="button" class="ks-btn ks-btn--outline ks-btn--sm" data-add-row><i class="bi bi-plus-lg"></i> Tambah baris</button></div><div class="ks-component-scroll"><table class="ks-component-table"><thead><tr><th>No</th><th>Nama</th><th>Aksi</th></tr></thead><tbody data-add-body></tbody></table></div></div>'
      + '<div class="ks-component-panel"><div class="ks-component-panel-head"><div><strong>Potongan</strong><span data-deduct-count></span></div><button type="button" class="ks-btn ks-btn--outline ks-btn--sm" data-add-deduct><i class="bi bi-plus-lg"></i> Tambah baris</button></div><div class="ks-component-scroll"><table class="ks-component-table"><thead><tr><th>No</th><th>Nama</th><th>Aksi</th></tr></thead><tbody data-deduct-body></tbody></table></div></div>'
      + '</div><div class="ks-component-foot"><button type="button" class="ks-btn ks-btn--outline" data-cancel>Batal</button><button type="button" class="ks-btn ks-btn--primary" data-save><i class="bi bi-check2"></i> Simpan & Pilih Excel</button></div></div>';
    document.body.appendChild(modal); renderTables();
    const close=()=>modal.remove(); modal.querySelector('.ks-component-close').onclick=close; modal.querySelector('[data-cancel]').onclick=close; modal.querySelector('.ks-component-backdrop').onclick=close;
    modal.querySelector('[data-add-row]').onclick=()=>{if(cfg.additions.length>=MAX_COMPONENTS){SlipUI.toast('Tambahan maksimal 20 baris.','error');return;}cfg.additions.push('Tambahan '+(cfg.additions.length+1));renderTables();};
    modal.querySelector('[data-add-deduct]').onclick=()=>{if(cfg.deductions.length>=MAX_COMPONENTS){SlipUI.toast('Potongan maksimal 20 baris.','error');return;}cfg.deductions.push('Potongan '+(cfg.deductions.length+1));renderTables();};
    modal.addEventListener('click',e=>{
      const del=e.target.closest('.ks-component-delete'); if(!del)return;
      const tr=e.target.closest('tr'),type=tr.dataset.type,index=Number(tr.dataset.index);
      cfg[type].splice(index,1); renderTables();
    });
    modal.querySelector('[data-save]').onclick=()=>{
      const inputs=Array.from(modal.querySelectorAll('.ks-component-input'));
      const values={additions:[],deductions:[]};
      inputs.forEach(input=>{
        const tr=input.closest('tr'), type=tr.dataset.type, value=input.value.trim();
        if(value) values[type].push(value);
      });
      if(!values.additions.length && !values.deductions.length){
        SlipUI.toast('Minimal satu komponen Tambahan atau Potongan harus diisi.','error'); return;
      }
      cfg.additions=values.additions.slice(0,MAX_COMPONENTS);
      cfg.deductions=values.deductions.slice(0,MAX_COMPONENTS);
      saveConfig(cfg); close(); setTimeout(()=>excel.click(),100);
    };
    document.addEventListener('keydown',function escKey(e){if(e.key==='Escape'&&document.body.contains(modal)){document.removeEventListener('keydown',escKey);close();}});
  }

  chooseBtn.addEventListener('click', () => { if (!busy) configModal(); });
  excel.addEventListener('change', function () {
    setError(''); const f = this.files[0];
    if (!f) { excelName.innerHTML = '<i class="bi bi-file-earmark-excel"></i> Belum ada file dipilih'; btnImport.disabled = true; return; }
    excelName.innerHTML = '<i class="bi bi-file-earmark-excel-fill"></i> '+esc(f.name)+' <span>('+Math.ceil(f.size/1024)+' KB)</span>';
    btnImport.disabled = !/\.xlsx$/i.test(f.name);
    if (!btnImport.disabled) SlipUI.toast('File Excel siap diimport.','info'); else setError('File harus berformat .xlsx.');
  });
  function readBuffer(file){ return new Promise((resolve,reject)=>{const fr=new FileReader();fr.onload=()=>resolve(fr.result);fr.onerror=()=>reject(fr.error||new Error('File gagal dibaca'));fr.readAsArrayBuffer(file);}); }

  function parseSheet(ws) {
    const cfg=getConfig(), col={};
    ws.getRow(1).eachCell({includeEmpty:false},(c,n)=>{
      const k=norm(c.value);
      if(k==='nik') col.nik=n; else if(k==='nama') col.nama=n; else if(k==='gapok'||k==='gajipokok') col.gajiPokok=n;
      const ma=k.match(/^tambahan(\d+)$/); if(ma) col['tambahan'+ma[1]]=n;
      const mp=k.match(/^potongan(\d+)$/); if(mp) col['potongan'+mp[1]]=n;
    });
    if(!col.nik||!col.nama||!col.gajiPokok)return {fatal:'Header Excel tidak sesuai. Kolom wajib: NIK, Nama, dan Gaji Pokok.'};
    const errors=[],rows=[],seen={};
    for(let rn=2;rn<=ws.rowCount;rn++){
      const row=ws.getRow(rn); const nik=text(cell(row,col.nik)).replace(/\D/g,''); const excelNama=text(cell(row,col.nama));
      if(!nik&&!excelNama)continue; const msgs=[];
      if(!/^\d{8}$/.test(nik))msgs.push('NIK harus tepat 8 digit'); else if(seen[nik])msgs.push('NIK duplikat di file Excel');
      if(msgs.length){errors.push({row:rn,msg:msgs.join('; ')});continue;}
      // BACKEND: NIK dicocokkan & kontak (Email/WA) divalidasi di server
      // saat POST /api/slip/import (SlipController@importPayroll), bukan
      // di sini lagi - hasilnya dikembalikan lewat res.gagal.
      const tambahan=[], potongan=[];
      for(let i=1;i<=cfg.additions.length;i++){const v=num(cell(row,col['tambahan'+i]));if(v)tambahan.push({nama:cfg.additions[i-1],jumlah:v});}
      for(let i=1;i<=cfg.deductions.length;i++){const v=num(cell(row,col['potongan'+i]));if(v)potongan.push({nama:cfg.deductions[i-1],jumlah:v});}
      rows.push({row:rn,nik,nama:excelNama,gajiPokok:num(cell(row,col.gajiPokok)),tambahan,potongan}); seen[nik]=true;
    }
    return {rows,errors};
  }

  function periodeAwalBulan(){ const d=new Date(); return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-01'; }

  async function kirimKeServer(parsed){
    if(!parsed.rows.length) return {berhasil:0, gagal:[]};
    const cfg = getConfig();
    const payload = {
      periode: periodeAwalBulan(),
      componentConfig: { additions: cfg.additions, deductions: cfg.deductions },
      rows: parsed.rows.map(r => ({ nik:r.nik, gajiPokok:r.gajiPokok, tambahan:r.tambahan, potongan:r.potongan }))
    };
    const res = await Api.post('/api/slip/import', payload);
    // Gabungkan pesan gagal dari server (NIK tak ditemukan / kontak kosong)
    // dengan nomor baris Excel aslinya, supaya pesan tetap "Baris N ...".
    const byNik = {}; parsed.rows.forEach(r => { byNik[r.nik] = r.row; });
    const gagalServer = (res.gagal || []).map(g => ({ row: byNik[g.nik] || '-', msg: g.alasan }));
    return { berhasil: res.berhasil, gagal: gagalServer };
  }

  function renderResult(res){
    result.hidden=false;
    if(res.fatal){result.className='ks-result ks-result--error';result.innerHTML='<strong><i class="bi bi-x-circle-fill"></i> Import gagal</strong><p>'+esc(res.fatal)+'</p>';return;}
    const ok=res.berhasil!=null?res.berhasil:res.rows.length,fail=res.errors.length; let html='<div class="ks-result-stats"><div><strong>'+ok+'</strong><span>Berhasil</span></div><div><strong>'+fail+'</strong><span>Ditolak</span></div></div>';
    html+='<strong><i class="bi '+(fail?'bi-exclamation-circle-fill':'bi-check-circle-fill')+'"></i> '+(fail?'Import selesai dengan validasi':'Import berhasil')+'</strong>';
    if(ok) html+='<p>Data gaji tersimpan ke database. NIK dicocokkan dan kontak Email/WhatsApp diambil dari master Karyawan.</p>';
    if(fail) html+='<div class="ks-error-list">'+res.errors.slice(0,50).map(e=>'<div><b>Baris '+e.row+'</b> '+esc(e.msg)+'</div>').join('')+(fail>50?'<div>… dan '+(fail-50)+' baris lainnya.</div>':'')+'</div>';
    result.className='ks-result '+(fail&&!ok?'ks-result--error':fail?'ks-result--warn':'ks-result--success'); result.innerHTML=html;
  }

  async function showImportConfirmation(serverRes){
    const items = serverRes.data || [];
    const list = items.slice(0, 100).map((r,i) =>
      '<tr><td>'+(i+1)+'</td><td><strong>'+esc(r.nik)+'</strong></td><td>'+esc(r.nama||'-')+'</td><td>'+esc(r.divisi||'-')+'</td><td>'+esc(r.email||r.wa||'-')+'</td></tr>'
    ).join('');
    const html =
      '<p class="ks-result__lead">NIK berhasil dicocokkan dengan Master Karyawan. Data identitas dan kontak yang digunakan untuk slip berasal dari master.</p>' +
      '<div class="ks-stats"><div class="ks-stat ks-stat--ok"><span class="ks-stat__num">'+items.length+'</span><span class="ks-stat__label">Payroll siap</span></div><div class="ks-stat"><span class="ks-stat__num">'+(serverRes.gagal||[]).length+'</span><span class="ks-stat__label">Ditolak</span></div></div>' +
      (items.length ? '<div class="ks-import-match-table"><table class="ks-sheet"><thead><tr><th>No</th><th>NIK</th><th>Nama Master</th><th>Divisi</th><th>Kontak</th></tr></thead><tbody>'+list+'</tbody></table>'+(items.length>100?'<p class="ks-sheet-note">Menampilkan 100 data pertama.</p>':'')+'</div>' : '');
    return SlipUI.dialog({
      title:'Validasi Master Karyawan',
      html:html,
      buttons:[
        {label:'Tutup',kind:'outline',value:'close'},
        {label:'Lanjut Preview Slip',kind:'primary',value:'preview'}
      ]
    });
  }

  async function importExcel(){
    if(busy)return; const file=excel.files[0]; if(!file)return setError('Pilih file Excel terlebih dahulu.'); if(!/\.xlsx$/i.test(file.name))return setError('File harus berformat .xlsx.'); if(typeof ExcelJS==='undefined')return setError('Library Excel gagal dimuat.');
    busy=true;btnImport.disabled=true;btnTemplate.setAttribute('aria-disabled','true'); btnTemplate.style.pointerEvents='none';btnPreview.disabled=true;btnImport.innerHTML='<i class="bi bi-arrow-repeat is-spin"></i><span>Memproses...</span>';setError('');
    try{
      const wb=new ExcelJS.Workbook();await wb.xlsx.load(await readBuffer(file));const ws=wb.worksheets[0];if(!ws)throw new Error('Sheet pertama kosong');
      const parsed=parseSheet(ws);if(parsed.fatal){renderResult(parsed);return;}
      const serverRes = await kirimKeServer(parsed);
      const combined = { berhasil: serverRes.berhasil, errors: parsed.errors.concat(serverRes.gagal) };
      renderResult(combined);refreshBadge();
      if(combined.berhasil){
        SlipUI.toast(combined.berhasil+' data gaji berhasil disimpan.');
        const choice = await showImportConfirmation(serverRes);
        if(choice === 'preview') window.location.href='/preview-slip';
      } else {
        SlipUI.toast('Tidak ada data valid yang dapat diimport.','error');
      }
    }
    catch(e){console.error(e);renderResult({fatal:'File tidak bisa dibaca, atau gagal disimpan ke server ('+(e.message||'')+').'});}
    finally{busy=false;btnImport.disabled=!excel.files.length;btnTemplate.removeAttribute('aria-disabled'); btnTemplate.style.pointerEvents='';btnPreview.disabled=false;btnImport.innerHTML='<i class="bi bi-cloud-arrow-up-fill"></i><span>Import Excel</span>';}
  }

  async function downloadTemplate(e){
    // Fallback utama: file template fisik yang ikut di dalam project.
    // Ini tetap bekerja walaupun CDN ExcelJS tidak tersedia.
    if (e) e.preventDefault();
    const directUrl = './assets/template-import-gaji.xlsx';
    try {
      const response = await fetch(directUrl, { cache: 'no-store' });
      if (!response.ok) throw new Error('HTTP ' + response.status);
      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'template-import-gaji.xlsx';
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
      if (SlipStore.addActivity) SlipStore.addActivity('Download template payroll', 'Mengunduh template payroll Tambahan 1-20 dan Potongan 1-20.');
      return;
    } catch (directErr) {
      // Jika fetch diblokir (mis. dibuka langsung dengan file://), gunakan navigasi
      // ke file statis. Browser akan membuka/menawarkan download file Excel.
      const a = document.createElement('a');
      a.href = directUrl;
      a.download = 'template-import-gaji.xlsx';
      a.target = '_self';
      document.body.appendChild(a);
      a.click();
      a.remove();
      if (SlipStore.addActivity) SlipStore.addActivity('Download template payroll', 'Mengunduh template payroll Tambahan 1-20 dan Potongan 1-20.');
    }
  }
  btnImport.addEventListener('click',importExcel);btnTemplate.addEventListener('click',downloadTemplate);btnPreview.addEventListener('click',()=>window.location.href='/preview-slip');refreshBadge();
})();
