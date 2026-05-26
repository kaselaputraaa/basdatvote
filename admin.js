// ============================================================
// FILE   : admin.js
// UPDATE : - Progress voting dengan progress bar per kandidat
//          - Partisipasi siswa (sudah vs belum memilih)
//          - Edit & Hapus siswa dan guru
// ============================================================

const API = 'http://localhost/voting2087/index.php?action=';

let dataSiswa = [];
let dataGuru = [];
let dataKandidat = [];

// ── Guard ─────────────────────────────────────────────────────
(async () => {
  try {
    let roles = sessionStorage.getItem('roles');
    let loggedIn = sessionStorage.getItem('logged_in') === 'true';

    if (!loggedIn) {
      const res = await fetch(`${API}check_session`, { credentials: 'include' });
      const data = await res.json();
      if (!data.status) { window.location.replace('login.html'); return; }
      roles = data.roles;
      sessionStorage.setItem('logged_in', 'true');
      sessionStorage.setItem('roles', roles);
    }

    if (roles !== 'admin' && roles !== 'guru') {
      window.location.replace('login.html');
      return;
    }

    document.body.style.visibility = 'visible';

    if (roles === 'guru') {
      document.querySelectorAll('[data-admin-only]').forEach(el => {
        el.style.display = 'none';
      });
    }

    loadDashboard(roles);

  } catch {
    window.location.replace('login.html');
  }
})();

// ── Helper fetch ──────────────────────────────────────────────
async function apiFetch(action, options = {}) {
  const res = await fetch(`${API}${action}`, {
    credentials: 'include',
    ...options,
  });
  if (res.status === 401 || res.status === 403) {
    window.location.replace('login.html');
    return null;
  }
  return res.json();
}

// ── Navigasi ──────────────────────────────────────────────────
function showPage(id) {
  document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
  document.getElementById(id).classList.remove('hidden');
  document.querySelectorAll('.nav').forEach(b => b.classList.remove('active'));

  const roles = sessionStorage.getItem('roles');
  if (id === 'dashboard') loadDashboard(roles);
  if (id === 'siswa') loadSiswa();
  if (id === 'guru') loadGuru();
  if (id === 'kandidat') loadKandidatAdmin();
  if (id === 'periode') loadPeriode();
}

// ── Dashboard ─────────────────────────────────────────────────
async function loadDashboard(roles) {
  const isAdmin = roles === 'admin';

  const [s, g, k, v] = await Promise.all([
    apiFetch('siswa'),
    apiFetch('guru'),
    apiFetch('get_kandidat'),
    isAdmin ? apiFetch('hasil_vote') : Promise.resolve({ data: [] }),
  ]);
  if (!s || !g || !k) return;

  const totalSiswa = s.data?.length ?? 0;
  const voteData = v?.data || [];
  const totalVote = voteData.reduce((sum, x) => sum + (parseInt(x.jumlah_suara) || 0), 0);

  document.getElementById('totalSiswa').innerText = totalSiswa;
  document.getElementById('totalGuru').innerText = g.data?.length ?? 0;
  document.getElementById('totalKandidat').innerText = k.data?.length ?? 0;
  document.getElementById('totalVote').innerText = totalVote;

  // ── Partisipasi (lingkaran) ───────────────────────────────
  const pct = totalSiswa > 0 ? Math.round((totalVote / totalSiswa) * 100) : 0;
  const belum = Math.max(0, totalSiswa - totalVote);
  const radius = 32;
  const circumference = 2 * Math.PI * radius; // ~201

  const circle = document.getElementById('circleProgress');
  if (circle) {
    const offset = circumference - (pct / 100) * circumference;
    circle.style.strokeDasharray = circumference;
    circle.style.strokeDashoffset = offset;
  }
  const pctEl = document.getElementById('pctText');
  if (pctEl) pctEl.textContent = pct + '%';

  const labelEl = document.getElementById('partisipasiLabel');
  if (labelEl) labelEl.textContent = `${totalVote} dari ${totalSiswa} siswa sudah memilih`;

  const belumEl = document.getElementById('partisipasiBelum');
  if (belumEl) belumEl.textContent = `${belum} siswa belum memilih`;

  // ── Progress bar per kandidat ─────────────────────────────
  const wrap = document.getElementById('progressKandidat');
  if (wrap) {
    wrap.innerHTML = '';
    if (voteData.length === 0) {
      wrap.innerHTML = '<div class="progress-empty">Belum ada data voting</div>';
    } else {
      const maxSuara = Math.max(...voteData.map(x => parseInt(x.jumlah_suara) || 0));
      voteData.forEach(x => {
        const suara = parseInt(x.jumlah_suara) || 0;
        const pctBar = maxSuara > 0 ? Math.round((suara / totalVote) * 100) : 0;

        const div = document.createElement('div');
        div.className = 'progress-wrap';
        div.innerHTML = `
          <div class="progress-label">
            <span>${x.nama_ketua} <small style="color:#aaa">(${x.jenis})</small></span>
            <span>${suara} suara &nbsp;·&nbsp; ${pctBar}%</span>
          </div>
          <div class="progress-bar-bg">
            <div class="progress-bar-fill" style="width:${pctBar}%"></div>
          </div>
        `;
        wrap.appendChild(div);
      });
    }
  }

  // ── Tabel rekap ───────────────────────────────────────────
  const tbl = document.getElementById('tblProgress');
  if (tbl) {
    tbl.innerHTML = '';
    if (voteData.length === 0) {
      const tr = tbl.insertRow();
      const td = tr.insertCell();
      td.colSpan = 4;
      td.textContent = 'Belum ada data';
      td.style.textAlign = 'center';
      td.style.color = '#aaa';
    } else {
      voteData.forEach((x, i) => {
        const tr = tbl.insertRow();
        [i + 1, x.nama_ketua, x.jenis, x.jumlah_suara].forEach(val => {
          const td = tr.insertCell();
          td.textContent = val ?? '';
        });
      });
    }
  }
}

// ── Siswa ─────────────────────────────────────────────────────
async function loadSiswa() {
  const d = await apiFetch('siswa');
  if (!d) return;
  dataSiswa = d.data || [];
  renderSiswa(dataSiswa);
}

function renderSiswa(data) {
  const tbl = document.getElementById('tblSiswa');
  if (!tbl) return;
  tbl.innerHTML = '';

  data.forEach((s, i) => {
    const tr = tbl.insertRow();
    [i + 1, s.nipd, s.nama_siswa, s.jenis_kelamin].forEach(val => {
      const td = tr.insertCell();
      td.textContent = val ?? '';
    });

    const tdAksi = tr.insertCell();

    const btnEdit = document.createElement('button');
    btnEdit.className = 'action edit';
    btnEdit.textContent = 'Edit';
    btnEdit.onclick = () => editSiswa(s.id_siswa, s.nipd, s.nama_siswa, s.jenis_kelamin);
    tdAksi.appendChild(btnEdit);

    const btnHapus = document.createElement('button');
    btnHapus.className = 'action delete';
    btnHapus.textContent = 'Hapus';
    btnHapus.onclick = () => hapusSiswa(s.id_siswa);
    tdAksi.appendChild(btnHapus);
  });
}

// Tambahkan fungsi search
function searchSiswa() {
  const keyword = document.getElementById('searchSiswa').value.toLowerCase();
  const filtered = dataSiswa.filter(s =>
    s.nama_siswa.toLowerCase().includes(keyword) ||
    s.nipd.toLowerCase().includes(keyword)
  );
  renderSiswa(filtered);
}

// ── Edit Siswa ────────────────────────────────────────────────
async function editSiswa(id, nipd, nama, jk) {
  const { value: formValues } = await Swal.fire({
    title: 'Edit Siswa',
    width: 500,
    html: `
      <div style="display:flex;flex-direction:column;gap:10px;text-align:left;padding:0 8px">
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">NIPD</label>
          <input id="swal-nipd" class="swal2-input" style="margin:0;width:100%" placeholder="NIPD" value="${nipd ?? ''}">
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Nama Siswa</label>
          <input id="swal-nama" class="swal2-input" style="margin:0;width:100%" placeholder="Nama Siswa" value="${nama ?? ''}">
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Jenis Kelamin</label>
          <select id="swal-jk" style="width:100%;padding:10px;border:1px solid #d9d9d9;border-radius:4px;font-size:14px">
            <option value="L" ${jk === 'L' ? 'selected' : ''}>Laki-laki</option>
            <option value="P" ${jk === 'P' ? 'selected' : ''}>Perempuan</option>
          </select>
        </div>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Simpan',
    cancelButtonText: 'Batal',
    preConfirm: () => {
      const nipdVal = document.getElementById('swal-nipd').value.trim();
      const namaVal = document.getElementById('swal-nama').value.trim();
      const jkVal = document.getElementById('swal-jk').value;
      if (!nipdVal || !namaVal) {
        Swal.showValidationMessage('NIPD dan Nama Siswa wajib diisi');
        return false;
      }
      return { nipd: nipdVal, nama_siswa: namaVal, jenis_kelamin: jkVal };
    },
  });
  if (!formValues) return;
  await simpanEditSiswa(id, formValues);
}
async function simpanEditSiswa(id, body) {
  const hasil = await apiFetch('edit_siswa', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, ...body }),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, 'success');
  loadSiswa();
}
// ── Tambah Siswa ──────────────────────────────────────────────
async function tambahSiswa() {
  const body = {
    nipd: document.getElementById('nipd').value,
    nama_siswa: document.getElementById('nama').value,
    jenis_kelamin: document.getElementById('jk').value,
  };
  const hasil = await apiFetch('tambah_siswa', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, 'success');
  loadSiswa();
}

// ── Hapus Siswa ───────────────────────────────────────────────
async function hapusSiswa(id) {
  const result = await Swal.fire({
    title: 'Yakin hapus?', icon: 'warning', showCancelButton: true,
  });
  if (!result.isConfirmed) return;
  const hasil = await apiFetch('hapus_siswa', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, 'success');
  loadSiswa();
}

// ── Guru ──────────────────────────────────────────────────────
async function loadGuru() {
  const d = await apiFetch('guru');
  if (!d) return;
  dataGuru = d.data || [];
  renderGuru(dataGuru);
}

function renderGuru(data) {
  const tbl = document.getElementById('tblGuru');
  if (!tbl) return;
  tbl.innerHTML = '';

  data.forEach((g, i) => {
    const tr = tbl.insertRow();
    [i + 1, g.kode_guru, g.nama_guru, g.jenis_kelamin].forEach(val => {
      const td = tr.insertCell();
      td.textContent = val ?? '';
    });

    const tdAksi = tr.insertCell();

    const btnEdit = document.createElement('button');
    btnEdit.className = 'action edit';
    btnEdit.textContent = 'Edit';
    btnEdit.onclick = () => editGuru(g.id_guru, g.kode_guru, g.nama_guru, g.jenis_kelamin);
    tdAksi.appendChild(btnEdit);

    const btnHapus = document.createElement('button');
    btnHapus.className = 'action delete';
    btnHapus.textContent = 'Hapus';
    btnHapus.onclick = () => hapusGuru(g.id_guru);
    tdAksi.appendChild(btnHapus);
  });
}

function searchGuru() {
  const keyword = document.getElementById('searchGuru').value.toLowerCase();
  const filtered = dataGuru.filter(g =>
    g.nama_guru.toLowerCase().includes(keyword) ||
    g.kode_guru.toLowerCase().includes(keyword)
  );
  renderGuru(filtered);
}

async function tambahGuru() {
  const body = {
    kode_guru: document.getElementById('kode_guru').value,
    nama_guru: document.getElementById('nama_guru').value,
    jenis_kelamin: document.getElementById('jk_guru').value,
  };

  if (!body.kode_guru || !body.nama_guru) {
    Swal.fire('Error', 'Kode Guru dan Nama wajib diisi!', 'error');
    return;
  }

  const hasil = await apiFetch('tambah_guru', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, hasil.status ? 'success' : 'error');
  loadGuru();
}

// ── Edit Guru ─────────────────────────────────────────────────
async function editGuru(id, kode, nama, jk) {
  const { value: formValues } = await Swal.fire({
    title: 'Edit Guru',
    width: 500,
    html: `
      <div style="display:flex;flex-direction:column;gap:10px;text-align:left;padding:0 8px">
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Kode Guru</label>
          <input id="swal-kode" class="swal2-input" style="margin:0;width:100%" placeholder="Kode Guru" value="${kode ?? ''}">
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Nama Guru</label>
          <input id="swal-nama" class="swal2-input" style="margin:0;width:100%" placeholder="Nama Guru" value="${nama ?? ''}">
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Jenis Kelamin</label>
          <select id="swal-jk" style="width:100%;padding:10px;border:1px solid #d9d9d9;border-radius:4px;font-size:14px">
            <option value="L" ${jk === 'L' ? 'selected' : ''}>Laki-laki</option>
            <option value="P" ${jk === 'P' ? 'selected' : ''}>Perempuan</option>
          </select>
        </div>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Simpan',
    cancelButtonText: 'Batal',
    preConfirm: () => {
      const kodeVal = document.getElementById('swal-kode').value.trim();
      const namaVal = document.getElementById('swal-nama').value.trim();
      const jkVal = document.getElementById('swal-jk').value;
      if (!kodeVal || !namaVal) {
        Swal.showValidationMessage('Kode Guru dan Nama wajib diisi');
        return false;
      }
      return { kode_guru: kodeVal, nama_guru: namaVal, jenis_kelamin: jkVal };
    },
  });
  if (!formValues) return;

  const hasil = await apiFetch('edit_guru', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, ...formValues }),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, 'success');
  loadGuru();
}

// ── Hapus Guru ────────────────────────────────────────────────
async function hapusGuru(id) {
  const result = await Swal.fire({
    title: 'Yakin hapus?', icon: 'warning', showCancelButton: true,
  });
  if (!result.isConfirmed) return;

  const hasil = await apiFetch('hapus_guru', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, 'success');
  loadGuru();
}

// ── Kandidat ──────────────────────────────────────────────────
async function loadKandidatAdmin() {
  const d = await apiFetch('get_kandidat');
  if (!d) return;
  dataKandidat = d.data || [];
  renderKandidat(dataKandidat);
}

function renderKandidat(data) {
  const tbl = document.getElementById('tblKandidat');
  if (!tbl) return;
  tbl.innerHTML = '';

  data.forEach((k, i) => {
    const tr = tbl.insertRow();
    [i + 1, k.nama_ketua, k.nama_wakil, k.jenis, k.nama_periode ?? '-'].forEach(val => {
      const td = tr.insertCell();
      td.textContent = val ?? '';
    });

    const tdAksi = tr.insertCell();

    const btnEdit = document.createElement('button');
    btnEdit.className = 'action edit';
    btnEdit.textContent = 'Edit';
    btnEdit.onclick = () => editKandidat(k.id_kandidat, k.nama_ketua, k.nama_wakil, k.jenis);
    tdAksi.appendChild(btnEdit);

    const btnHapus = document.createElement('button');
    btnHapus.className = 'action delete';
    btnHapus.textContent = 'Hapus';
    btnHapus.onclick = () => hapusKandidat(k.id_kandidat);
    tdAksi.appendChild(btnHapus);
  });
}

function searchKandidat() {
  const keyword = document.getElementById('searchKandidat').value.toLowerCase();
  const filtered = dataKandidat.filter(k =>
    k.nama_ketua.toLowerCase().includes(keyword) ||
    k.nama_wakil.toLowerCase().includes(keyword)
  );
  renderKandidat(filtered);
}

async function editKandidat(id, ketua, wakil, jenis) {
  // Ambil data siswa dan periode dari database
  const [siswa, periode] = await Promise.all([
    apiFetch('siswa'),
    apiFetch('get_periode'),
  ]);

  const optSiswa = (siswa?.data || []).map(s =>
    `<option value="${s.id_siswa}">${s.nama_siswa}</option>`
  ).join('');

  const optPeriode = (periode?.data || []).map(p =>
    `<option value="${p.id_periode}">${p.nama_periode}</option>`
  ).join('');

  const { value: formValues } = await Swal.fire({
    title: 'Edit Kandidat',
    width: 500,
    html: `
      <div style="display:flex;flex-direction:column;gap:10px;text-align:left;padding:0 8px">
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Ketua</label>
          <select id="swal-ketua" style="width:100%;padding:10px;border:1px solid #d9d9d9;border-radius:4px;font-size:14px">
            ${optSiswa}
          </select>
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Wakil</label>
          <select id="swal-wakil" style="width:100%;padding:10px;border:1px solid #d9d9d9;border-radius:4px;font-size:14px">
            ${optSiswa}
          </select>
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Jenis</label>
          <select id="swal-jenis" style="width:100%;padding:10px;border:1px solid #d9d9d9;border-radius:4px;font-size:14px">
            <option value="OSIS" ${jenis === 'OSIS' ? 'selected' : ''}>OSIS</option>
            <option value="MPK"  ${jenis === 'MPK' ? 'selected' : ''}>MPK</option>
          </select>
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Periode</label>
          <select id="swal-periode" style="width:100%;padding:10px;border:1px solid #d9d9d9;border-radius:4px;font-size:14px">
            ${optPeriode}
          </select>
        </div>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Simpan',
    cancelButtonText: 'Batal',
    preConfirm: () => {
      return {
        id_ketua: parseInt(document.getElementById('swal-ketua').value),
        id_wakil: parseInt(document.getElementById('swal-wakil').value),
        jenis: document.getElementById('swal-jenis').value,
        id_periode: parseInt(document.getElementById('swal-periode').value),
      };
    },
  });
  if (!formValues) return;

  const hasil = await apiFetch('edit_kandidat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, ...formValues }),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, hasil.status ? 'success' : 'error');
  loadKandidatAdmin();
}

async function hapusKandidat(id) {
  const result = await Swal.fire({
    title: 'Yakin hapus?', icon: 'warning', showCancelButton: true,
    confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal',
  });
  if (!result.isConfirmed) return;

  const hasil = await apiFetch('hapus_kandidat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, hasil.status ? 'success' : 'error');
  loadKandidatAdmin();
}

async function tambahKandidatAdmin() {
  const body = {
    id_ketua: parseInt(document.getElementById('k_ketua').value),
    id_wakil: parseInt(document.getElementById('k_wakil').value),
    jenis: document.getElementById('k_jenis').value,
    nomor_urut: parseInt(document.getElementById('k_nomor_urut').value),
    id_periode: parseInt(document.getElementById('k_periode').value),
    visi: '-',
    misi: '-',
    foto: 'default.png',
    is_active: 'Y',
  };

  if (!body.id_ketua || !body.id_wakil || !body.id_periode || !body.nomor_urut) {
    Swal.fire('Error', 'Semua field wajib diisi!', 'error');
    return;
  }

  const hasil = await apiFetch('insert_kandidat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, hasil.status ? 'success' : 'error');
  loadKandidatAdmin();
}

// ── Periode ──────────────────────────────────────────────────
async function loadPeriode() {
  const d = await apiFetch('get_periode');
  if (!d) return;

  const tbl = document.getElementById('tblPeriode');
  if (!tbl) return;
  tbl.innerHTML = '';

  (d.data || []).forEach((p, i) => {
    const tr = tbl.insertRow();
    [p.nama_periode, p.tanggal_mulai, p.tanggal_selesai].forEach(val => {
      const td = tr.insertCell();
      td.textContent = val ?? '';
    });

    const tdAksi = tr.insertCell();

    const btnEdit = document.createElement('button');
    btnEdit.className = 'action edit';
    btnEdit.textContent = 'Edit';
    btnEdit.onclick = () => editPeriode(p.id_periode, p.nama_periode, p.tanggal_mulai, p.tanggal_selesai, p.is_active);
    tdAksi.appendChild(btnEdit);

    const btnHapus = document.createElement('button');
    btnHapus.className = 'action delete';
    btnHapus.textContent = 'Hapus';
    btnHapus.onclick = () => hapusPeriode(p.id_periode);
    tdAksi.appendChild(btnHapus);
  });
}

async function tambahPeriode() {
  const body = {
    nama_periode: document.getElementById('Periode').value,
    tanggal_mulai: document.getElementById('tanggal_mulai').value,
    tanggal_selesai: document.getElementById('tanggal_selesai').value,
    is_active: 'N',
  };
  const hasil = await apiFetch('insert_periode', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, hasil.status ? 'success' : 'error');
  loadPeriode();


}
async function editPeriode(id, nama, tglMulai, tglSelesai, isActive) {
  const { value: formValues } = await Swal.fire({
    title: 'Edit Periode',
    width: 500,
    html: `
      <div style="display:flex;flex-direction:column;gap:10px;text-align:left;padding:0 8px">
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Nama Periode</label>
          <input id="swal-nama" class="swal2-input" style="margin:0;width:100%"
            placeholder="Nama Periode" value="${nama ?? ''}">
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Tanggal Mulai</label>
          <input id="swal-mulai" class="swal2-input" type="date" style="margin:0;width:100%"
            value="${tglMulai ?? ''}">
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Tanggal Selesai</label>
          <input id="swal-selesai" class="swal2-input" type="date" style="margin:0;width:100%"
            value="${tglSelesai ?? ''}">
        </div>
        <div>
          <label style="font-size:13px;color:#555;margin-bottom:4px;display:block">Status</label>
          <select id="swal-active" style="width:100%;padding:10px;border:1px solid #d9d9d9;border-radius:4px;font-size:14px">
            <option value="N" ${(isActive === 'N' || !isActive) ? 'selected' : ''}>Tidak Aktif</option>
            <option value="Y" ${isActive === 'Y' ? 'selected' : ''}>Aktif</option>
          </select>
        </div>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Simpan',
    cancelButtonText: 'Batal',
    preConfirm: () => {
      const namaVal = document.getElementById('swal-nama').value.trim();
      const mulaiVal = document.getElementById('swal-mulai').value;
      const selesaiVal = document.getElementById('swal-selesai').value;
      const activeVal = document.getElementById('swal-active').value;
      if (!namaVal || !mulaiVal || !selesaiVal) {
        Swal.showValidationMessage('Semua field wajib diisi');
        return false;
      }
      return { nama_periode: namaVal, tanggal_mulai: mulaiVal, tanggal_selesai: selesaiVal, is_active: activeVal };
    },
  });
  if (!formValues) return;

  const hasil = await apiFetch('edit_periode', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, ...formValues }),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, hasil.status ? 'success' : 'error');
  loadPeriode();
}

async function hapusPeriode(id) {
  const result = await Swal.fire({
    title: 'Yakin hapus?', icon: 'warning', showCancelButton: true,
  });
  if (!result.isConfirmed) return;

  const hasil = await apiFetch('hapus_periode', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  });
  if (!hasil) return;
  Swal.fire('Berhasil', hasil.message, hasil.status ? 'success' : 'error');
  loadPeriode();
}
// ── Logout ────────────────────────────────────────────────────
async function logout() {
  await fetch(`${API}logout`, { method: 'POST', credentials: 'include' });
  sessionStorage.clear();
  localStorage.removeItem('user');
  window.location.href = 'login.html';
} 