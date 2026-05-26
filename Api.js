// ============================================================
// FILE   : Api.js
// ============================================================

const BASE_URL = 'http://localhost/voting2087/index.php';

// ── Helper fetch ─────────────────────────────────────────────
async function apiPost(action, body = {}) {
  const res = await fetch(`${BASE_URL}?action=${action}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(body),
  });
  if (!res.ok && res.status !== 401 && res.status !== 403) {
    throw new Error(`HTTP ${res.status}`);
  }
  return res.json();
}

async function apiGet(action, params = {}) {
  const qs = new URLSearchParams({ action, ...params });
  const res = await fetch(`${BASE_URL}?${qs}`, {
    credentials: 'include',
  });
  if (!res.ok && res.status !== 401 && res.status !== 403) {
    throw new Error(`HTTP ${res.status}`);
  }
  return res.json();
}

// ── Session ──────────────────────────────────────────────────
const Session = {

  set(data) {
    sessionStorage.setItem('nama', data.nama || '');
    sessionStorage.setItem('roles', data.roles || '');
    sessionStorage.setItem('logged_in', 'true');
  },

  getNama() { return sessionStorage.getItem('nama') || 'User'; },
  getRoles() { return sessionStorage.getItem('roles') || ''; },
  isLoggedIn() { return sessionStorage.getItem('logged_in') === 'true'; },

  clear() {
    sessionStorage.clear();
    localStorage.removeItem('user');
  },

  async verify() {
    if (this.isLoggedIn()) return true;
    try {
      const res = await apiGet('check_session');
      if (res.status === true) {
        sessionStorage.setItem('logged_in', 'true');
        sessionStorage.setItem('roles', res.roles || '');
        return true;
      }
      return false;
    } catch {
      return false;
    }
  },

  async require() {
    const ok = await this.verify();
    if (!ok) window.location.replace('login.html');
    return ok;
  },
};

// ── Login ─────────────────────────────────────────────────────
async function loginUser(event) {
  event.preventDefault();

  const nipd = document.querySelector('input[name="username"]').value.trim();
  const password = document.querySelector('input[name="password"]').value.trim();

  if (!nipd || !password) {
    showPopup('errorPopup', 'NIPD dan password wajib diisi.');
    return;
  }

  setLoading(true);

  try {
    const data = await apiPost('login', { nipd, password });

    if (data.status) {
      Session.set({ nama: data.nama, roles: data.roles });
      window.location.href = data.redirect;
    } else {
      showPopup('errorPopup', data.message || 'Login gagal.');
    }

  } catch (err) {
    console.error(err);
    showPopup('errorPopup', 'Gagal koneksi ke server.');
  } finally {
    setLoading(false);
  }
}

// ── Logout ────────────────────────────────────────────────────
async function logout() {
  try {
    await apiPost('logout');
  } finally {
    Session.clear();
    window.location.href = 'login.html';
  }
}

// ── Load Kandidat ─────────────────────────────────────────────
async function loadKandidat(jenis) {
  try {
    const res = await apiGet('get_kandidat', { jenis });
    return res.status === true ? (res.data || []) : [];
  } catch (err) {
    console.error('Gagal load kandidat:', err);
    return [];
  }
}

// ── Render kartu kandidat ─────────────────────────────────────
function renderKartu(kandidat, container) {
  container.innerHTML = '';

  if (kandidat.length === 0) {
    container.innerHTML = '<p style="color:#888;padding:40px">Tidak ada kandidat</p>';
    return;
  }

  kandidat.forEach(k => {
    const nomor = k.nomor_urut ?? '';
    const namaLabel = `Paslon ${nomor}`;
    const namaKetua = k.nama_ketua || '?';
    const namaWakil = k.nama_wakil || '?';
    const namaLengkap = `${namaKetua} & ${namaWakil}`;
    const visiText = (k.visi || '-').replace(/'/g, "\\'");
    const misiText = (k.misi || '-').replace(/'/g, "\\'");

    const card = document.createElement('div');
    card.className = 'card';

    // ── mapping foto pisah OSIS & MPK ────────────────────────
    const BASE_IMG = '/voting2087/gambar/';
    const jenisfoto = (k.jenis || '').toLowerCase(); // 'osis' atau 'mpk'
    let foto = BASE_IMG + 'default.png';

    if (jenisfoto === 'osis') {
      if (nomor == 1) foto = BASE_IMG + 'osis/1.jpg';
      else if (nomor == 2) foto = BASE_IMG + 'osis/1.jpg';
      else if (nomor == 3) foto = BASE_IMG + 'osis/1.jpg';
    } else if (jenisfoto === 'mpk') {
      if (nomor == 1) foto = BASE_IMG + 'mpk/3.png';
      else if (nomor == 2) foto = BASE_IMG + 'mpk/3.png';
      else if (nomor == 3) foto = BASE_IMG + 'mpk/3.png';
    }

    card.innerHTML = `
      <img
        src="${foto}"
        alt="Foto ${namaLabel}"
        onerror="this.src='${BASE_IMG}default.png'"
        style="
          width: 120px;
          height: 120px;
          object-fit: cover;
          border-radius: 12px;
          display: block;
          margin: 0 auto 16px auto;
          border: 3px solid #e0e0e0;
        "
      >
      <div class="name">${namaLabel}</div>
      <div class="sub-name">${namaLengkap}</div>
      <div class="visi">${(k.visi || '').substring(0, 60)}${k.visi && k.visi.length > 60 ? '...' : ''}</div>

      <button class="detail-btn" onclick="showDetail('${visiText}','${misiText}')">
        Visi &amp; Misi
      </button>

      <button class="btn-pilih" onclick="pilih(${k.id_kandidat},'${namaLabel}','${namaLengkap.replace(/'/g, "\\'")}','${k.jenis}')">
        Pilih
      </button>
    `;

    container.appendChild(card);
  });
}

// ── Voting ────────────────────────────────────────────────────
let _pilihanIdKandidat = null;
let _pilihanJenis = null;
let _pilihanNama = null;

function pilih(idKandidat, labelKandidat, namaKandidat, jenis) {
  _pilihanIdKandidat = idKandidat;
  _pilihanJenis = jenis;
  _pilihanNama = `${labelKandidat} – ${namaKandidat}`;

  const el = document.getElementById('namaKandidat');
  if (el) el.textContent = _pilihanNama;

  showOverlay('overlayKonfirmasi');
}

function tutupKonfirmasi() { hideOverlay('overlayKonfirmasi'); }

async function konfirmasi() {
  if (!_pilihanIdKandidat) { tutupKonfirmasi(); return; }

  setLoading(true);

  try {
    const data = await apiPost('insert_voting', {
      id_kandidat: _pilihanIdKandidat,
      jenis: _pilihanJenis,
    });

    tutupKonfirmasi();

    if (data.status === true) {
      const el = document.getElementById('namaKandidatSuccess');
      if (el) el.textContent = _pilihanNama;
      showOverlay('overlaySuccess');
      disableSemuaTombol();
    } else {
      alert(data.message || 'Voting gagal.');
    }

  } catch (err) {
    console.error(err);
    alert('Gagal koneksi server.');
  } finally {
    setLoading(false);
  }
}

function disableSemuaTombol(label = 'Sudah Dipilih') {
  document.querySelectorAll('.btn-pilih').forEach(btn => {
    btn.disabled = true;
    btn.innerText = label;
    btn.style.opacity = '0.6';
  });
}

async function selesai() {
  hideOverlay('overlaySuccess');

  try {
    const res = await apiGet('cek_vote');

    if (res.osis && res.mpk) {
      // Sudah vote keduanya → logout → login
      await apiPost('logout');
      Session.clear();
      window.location.href = 'login.html';
    } else {
      // Belum keduanya → balik ke halaman pilih
      window.location.href = 'index.html';
    }
  } catch (err) {
    console.error(err);
    window.location.href = 'index.html';
  }
}

// ── Cek sudah voting ──────────────────────────────────────────
async function cekSudahVoting(jenis = null) {
  try {
    const res = await apiGet('cek_vote');
    const sudah = jenis
      ? res[jenis.toLowerCase()] === true
      : res.sudah === true;
    if (sudah) disableSemuaTombol('Sudah Voting');
    return res;
  } catch (err) {
    console.warn('cekSudahVoting error:', err);
  }
}

// ── Overlay & UI helpers ──────────────────────────────────────
function showOverlay(id) { document.getElementById(id)?.classList.add('show'); }
function hideOverlay(id) { document.getElementById(id)?.classList.remove('show'); }

function showPopup(id, pesan) {
  const el = document.getElementById(id);
  if (!el) return;
  const p = el.querySelector('p');
  if (p) p.textContent = pesan;
  el.classList.add('show');
}

function closePopup(id) { document.getElementById(id)?.classList.remove('show'); }

function setLoading(on) {
  const btn = document.querySelector('.btn-login, .btn-confirm');
  if (!btn) return;
  btn.disabled = on;
  btn.style.opacity = on ? '0.6' : '1';
}

function showDetail(visi, misi) {
  document.getElementById('detailVisi').textContent = visi || '-';
  document.getElementById('detailMisi').textContent = misi || '-';
  document.getElementById('detailOverlay').classList.add('show');
}

function closeDetail() {
  document.getElementById('detailOverlay').classList.remove('show');
}