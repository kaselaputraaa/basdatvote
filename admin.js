// ============================================================
// FILE   : admin.js
// FIX    : - Guard role: guru hanya bisa lihat data, TIDAK bisa
//            akses hasil_vote (itu admin only di backend)
//          - loadDashboard: guru tidak load hasil_vote
//          - Escape innerHTML dengan textContent untuk cegah XSS
// ============================================================

const API = 'http://localhost/voting2087/index.php?action=';

// ── Guard: sessionStorage dulu, fallback ke server ───────────
(async () => {
  try {
    let roles    = sessionStorage.getItem('roles');
    let loggedIn = sessionStorage.getItem('logged_in') === 'true';

    if (!loggedIn) {
      const res  = await fetch(`${API}check_session`, { credentials: 'include' });
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

    // BUG FIX: sembunyikan menu hasil_vote untuk guru
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
  if (id === 'siswa')     loadSiswa();
  if (id === 'guru')      loadGuru();
  if (id === 'kandidat')  loadKandidatAdmin();
}

// ── Dashboard ─────────────────────────────────────────────────
// BUG FIX: guru tidak boleh fetch hasil_vote (admin only di backend)
async function loadDashboard(roles) {
  const isAdmin = roles === 'admin';

  const requests = [
    apiFetch('siswa'),
    apiFetch('guru'),
    apiFetch('get_kandidat'),
    isAdmin ? apiFetch('hasil_vote') : Promise.resolve({ data: [] }),
  ];

  const [s, g, k, v] = await Promise.all(requests);
  if (!s || !g || !k) return;

  document.getElementById('totalSiswa').innerText    = s.data?.length ?? 0;
  document.getElementById('totalGuru').innerText     = g.data?.length ?? 0;
  document.getElementById('totalKandidat').innerText = k.data?.length ?? 0;
  document.getElementById('totalVote').innerText     = v?.data?.length ?? 0;

  const tbl = document.getElementById('tblProgress');
  if (tbl) {
    tbl.innerHTML = '';
    (v?.data || []).forEach((x, i) => {
      const tr = tbl.insertRow();
      // BUG FIX: gunakan textContent bukan innerHTML untuk mencegah XSS
      [i + 1, x.nama_ketua, x.jenis, x.jumlah_suara].forEach(val => {
        const td = tr.insertCell();
        td.textContent = val ?? '';
      });
    });
  }
}

// ── Siswa ─────────────────────────────────────────────────────
async function loadSiswa() {
  const d = await apiFetch('siswa');
  if (!d) return;

  const tbl = document.getElementById('tblSiswa');
  if (!tbl) return;
  tbl.innerHTML = '';

  (d.data || []).forEach((s, i) => {
    const tr = tbl.insertRow();
    [i + 1, s.nipd, s.nama_siswa, s.jenis_kelamin].forEach(val => {
      const td = tr.insertCell();
      td.textContent = val ?? '';
    });
    const tdAksi = tr.insertCell();
    const btn    = document.createElement('button');
    btn.className   = 'action delete';
    btn.textContent = 'Hapus';
    btn.onclick     = () => hapusSiswa(s.id_siswa);
    tdAksi.appendChild(btn);
  });
}

async function tambahSiswa() {
  const body = {
    nipd:          document.getElementById('nipd').value,
    nama_siswa:    document.getElementById('nama').value,
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

  const tbl = document.getElementById('tblGuru');
  if (!tbl) return;
  tbl.innerHTML = '';

  (d.data || []).forEach((g, i) => {
    const tr = tbl.insertRow();
    [i + 1, g.kode_guru, g.nama_guru, g.jenis_kelamin].forEach(val => {
      const td = tr.insertCell();
      td.textContent = val ?? '';
    });
  });
}

// ── Kandidat ──────────────────────────────────────────────────
async function loadKandidatAdmin() {
  const d = await apiFetch('get_kandidat');
  if (!d) return;

  const tbl = document.getElementById('tblKandidat');
  if (!tbl) return;
  tbl.innerHTML = '';

  (d.data || []).forEach((k, i) => {
    const tr = tbl.insertRow();
    [i + 1, k.nama_ketua, k.nama_wakil, k.jenis].forEach(val => {
      const td = tr.insertCell();
      td.textContent = val ?? '';
    });
  });
}

// ── Logout ────────────────────────────────────────────────────
async function logout() {
  await fetch(`${API}logout`, { method: 'POST', credentials: 'include' });
  sessionStorage.clear();
  localStorage.removeItem('user');
  window.location.href = 'login.html';
}
