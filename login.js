// ============================================================
// FILE   : login.js
// FIX    : - Hapus duplikasi BASE_URL (sudah ada di Api.js)
//          - Tambah set 'roles' di sessionStorage setelah login
//            agar guard di halaman lain bisa baca roles dengan benar
// ============================================================

window.onload = function () {
  const popup = document.getElementById('popup');
  if (popup) popup.style.display = 'flex';
};

function closePopup() {
  const popup = document.getElementById('popup');
  if (popup) popup.style.display = 'none';
}

// BUG FIX: BASE_URL tidak diduplikasi — gunakan yang sudah ada di Api.js
// Api.js harus di-load SEBELUM login.js di login.html

document.getElementById('loginForm').addEventListener('submit', async function (e) {
  e.preventDefault();

  const nipd     = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();
  const msgEl    = document.getElementById('msg');

  if (!nipd || !password) {
    if (msgEl) msgEl.innerText = 'NIPD dan password wajib diisi.';
    return;
  }

  try {
    const res = await fetch(`${BASE_URL}?action=login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ nipd, password }),
    });

    const data = await res.json();

    if (data.status === true) {
      // BUG FIX: simpan nama DAN roles agar guard halaman lain bisa baca
      sessionStorage.setItem('nama',      data.nama  || '');
      sessionStorage.setItem('roles',     data.roles || '');
      sessionStorage.setItem('logged_in', 'true');
      window.location.href = data.redirect;
    } else {
      if (msgEl) msgEl.innerText = data.message || 'Login gagal!';
    }

  } catch (err) {
    console.error(err);
    if (msgEl) msgEl.innerText = 'Server error!';
  }
});
