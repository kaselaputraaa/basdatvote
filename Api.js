const BASE_URL = 'http://localhost/voting2026/index.php';

// ─────────────────────────────────────────────
// API
// ─────────────────────────────────────────────
async function apiPost(action, body) {

  const res = await fetch(
    `${BASE_URL}?action=${action}`,
    {
      method: 'POST',

      headers: {
        'Content-Type': 'application/json'
      },

      body: JSON.stringify(body),
    }
  );

  if (!res.ok) {
    throw new Error(`HTTP ${res.status}`);
  }

  return res.json();
}

async function apiGet(action, params = {}) {

  const qs = new URLSearchParams({
    action,
    ...params
  });

  const res = await fetch(
    `${BASE_URL}?${qs}`
  );

  if (!res.ok) {
    throw new Error(`HTTP ${res.status}`);
  }

  return res.json();
}

// ─────────────────────────────────────────────
// SESSION
// ─────────────────────────────────────────────
const Session = {

  set(user) {

    localStorage.setItem(
      'user',
      JSON.stringify(user)
    );

  },

  get() {

    return JSON.parse(
      localStorage.getItem('user') || 'null'
    );

  },

  clear() {

    localStorage.removeItem('user');

  },

  require() {

    if (!Session.get()) {

      window.location.href =
        'login.html';

    }
  },
};

// ─────────────────────────────────────────────
// LOGIN
// ─────────────────────────────────────────────
async function loginUser(event) {

  event.preventDefault();

  const username =
    document.querySelector(
      'input[name="username"]'
    ).value.trim();

  const password =
    document.querySelector(
      'input[name="password"]'
    ).value.trim();

  if (!username || !password) {

    showPopup(
      'errorPopup',
      'Username dan password wajib diisi'
    );

    return;
  }

  setLoading(true);

  try {

    const data = await apiPost(
      'login',
      {
        username,
        password
      }
    );

    console.log(
      'LOGIN RESPONSE:',
      data
    );

    if (data.status === 'success') {

      // ✅ FIX SESSION
      Session.set(data.user);

      console.log(
        'SESSION:',
        Session.get()
      );

      window.location.href =
        'index.html';

    } else {

      showPopup(
        'errorPopup',
        data.message || 'Login gagal'
      );

    }

  } catch (err) {

    console.error(err);

    showPopup(
      'errorPopup',
      'Gagal koneksi server'
    );

  } finally {

    setLoading(false);

  }
}

// ─────────────────────────────────────────────
// LOGOUT
// ─────────────────────────────────────────────
function logout() {

  Session.clear();

  window.location.href =
    'login.html';
}

// ─────────────────────────────────────────────
// LOAD KANDIDAT
// ─────────────────────────────────────────────
async function loadKandidat(jenis) {

  try {

    const res = await apiGet(
      'get_kandidat',
      { jenis }
    );

    if (res.status !== 'success') {
      return [];
    }

    return res.data || [];

  } catch (err) {

    console.error(
      'Gagal load kandidat:',
      err
    );

    return [];
  }
}

// ─────────────────────────────────────────────
// RENDER KARTU
// ─────────────────────────────────────────────
function renderKartu(
  kandidat,
  container
) {

  container.innerHTML = '';

  if (kandidat.length === 0) {

    container.innerHTML = `
      <p style="
        color:#888;
        padding:40px
      ">
        Tidak ada kandidat
      </p>
    `;

    return;
  }

  kandidat.forEach(k => {

    const nomor =
      k.nomor_urut ?? '';

    const namaLabel =
      `Paslon ${nomor}`;

    const namaKetua =
      k.nama_ketua || '?';

    const namaWakil =
      k.nama_wakil || '?';

    const namaLengkap =
      `${namaKetua} & ${namaWakil}`;

    const visiText =
      (k.visi || '-')
      .replace(/'/g, "\\'");

    const misiText =
      (k.misi || '-')
      .replace(/'/g, "\\'");

    const card =
      document.createElement('div');

    card.className = 'card';

    card.innerHTML = `
      <div class="name">
        ${namaLabel}
      </div>

      <div class="sub-name"
        style="
          font-size:13px;
          color:#444;
          margin-top:4px
        ">
        ${namaLengkap}
      </div>

      <div class="visi">
        ${(k.visi || '').substring(0, 60)}
        ${k.visi && k.visi.length > 60 ? '...' : ''}
      </div>

      <button
        class="detail-btn"
        onclick="showDetail(
          '${visiText}',
          '${misiText}'
        )"
      >
        Visi &amp; Misi
      </button>

      <button
        class="btn-pilih"

        onclick="pilih(
          ${k.id_kandidat},
          '${namaLabel}',
          '${namaLengkap.replace(/'/g,"\\'")}',
          '${k.jenis}'
        )"
      >
        Pilih
      </button>
    `;

    container.appendChild(card);
  });
}

// ─────────────────────────────────────────────
// VOTING
// ─────────────────────────────────────────────
let _pilihanIdKandidat = null;
let _pilihanJenis      = null;
let _pilihanNama       = null;

// ─────────────────────────────────────────────
function pilih(
  idKandidat,
  labelKandidat,
  namaKandidat,
  jenis
) {

  _pilihanIdKandidat =
    idKandidat;

  _pilihanJenis =
    jenis;

  // ✅ FIX NAMA
  _pilihanNama =
    `${labelKandidat} – ${namaKandidat}`;

  const el =
    document.getElementById(
      'namaKandidat'
    );

  if (el) {

    el.textContent =
      _pilihanNama;

  }

  showOverlay(
    'overlayKonfirmasi'
  );
}

// ─────────────────────────────────────────────
function tutupKonfirmasi() {

  hideOverlay(
    'overlayKonfirmasi'
  );
}

// ─────────────────────────────────────────────
async function konfirmasi() {

  Session.require();

  const user = Session.get();

  console.log(
    'USER:',
    user
  );

  if (!_pilihanIdKandidat) {

    tutupKonfirmasi();

    return;
  }

  setLoading(true);

  try {

    const data = await apiPost(
      'insert_voting',
      {

        id_user:
          user.id_user,

        id_kandidat:
          _pilihanIdKandidat,

        jenis:
          _pilihanJenis,

      }
    );

    console.log(
      'HASIL:',
      data
    );

    tutupKonfirmasi();

    if (data.status === 'success') {

      // ✅ FIX STRIP
      const el =
        document.getElementById(
          'namaKandidatSuccess'
        );

      if (el) {

        el.textContent =
          _pilihanNama;

      }

      showOverlay(
        'overlaySuccess'
      );

      disableSemuaTombol();

    } else {

      alert(
        data.message ||
        'Voting gagal'
      );

    }

  } catch (err) {

    console.error(err);

    alert(
      'Gagal koneksi server'
    );

  } finally {

    setLoading(false);

  }
}

// ─────────────────────────────────────────────
function disableSemuaTombol(
  label = 'Sudah Dipilih'
) {

  document
    .querySelectorAll('.btn-pilih')

    .forEach(btn => {

      btn.disabled = true;

      btn.innerText = label;

      btn.style.opacity = '0.6';

    });
}

// ─────────────────────────────────────────────
function selesai() {

  hideOverlay(
    'overlaySuccess'
  );

  window.location.href =
    'index.html';
}

// ─────────────────────────────────────────────
// CEK VOTING
// ─────────────────────────────────────────────
async function cekSudahVoting(
  jenis = null
) {

  const user = Session.get();

  if (!user) return;

  try {

    const res = await apiGet(
      'cek_vote',
      {
        id_user:
          user.id_user
      }
    );

    const sudah =
      jenis
      ? res[jenis.toLowerCase()] === true
      : res.sudah === true;

    if (sudah) {

      disableSemuaTombol(
        'Sudah Voting'
      );

    }

    return res;

  } catch (err) {

    console.warn(
      'cekSudahVoting error:',
      err
    );
  }
}

// ─────────────────────────────────────────────
// OVERLAY
// ─────────────────────────────────────────────
function showOverlay(id) {

  document
    .getElementById(id)
    ?.classList.add('show');
}

function hideOverlay(id) {

  document
    .getElementById(id)
    ?.classList.remove('show');
}

// ─────────────────────────────────────────────
// POPUP
// ─────────────────────────────────────────────
function showPopup(
  id,
  pesan
) {

  const el =
    document.getElementById(id);

  if (!el) return;

  const p =
    el.querySelector('p');

  if (p) {

    p.textContent =
      pesan;

  }

  el.classList.add('show');
}

function closePopup(id) {

  document
    .getElementById(id)
    ?.classList.remove('show');
}

// ─────────────────────────────────────────────
// LOADING
// ─────────────────────────────────────────────
function setLoading(on) {

  const btn =
    document.querySelector(
      '.btn-confirm'
    );

  if (!btn) return;

  btn.disabled = on;

  btn.style.opacity =
    on ? '0.6' : '1';
}