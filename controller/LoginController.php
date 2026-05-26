<?php
// ============================================================
// FILE   : controller/LoginController.php
// FIX    : Hapus CALL sp_login() — SP lama JOIN ke kolom
//          id_siswa/id_guru yang sudah tidak ada di m_user
//          → Fatal error "Unknown column 'u.id_siswa'".
//          Sekarang query langsung ke m_user (lebih aman & cepat).
// ============================================================

class LoginController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $nipd = trim($body['nipd'] ?? '');
        $password = trim($body['password'] ?? '');

        if ($nipd === '' || $password === '') {
            echo json_encode(['status' => false, 'message' => 'NIPD dan password wajib diisi.']);
            return;
        }

        // FIX: query langsung ke m_user, tidak pakai sp_login yang error
        $stmt = $this->conn->prepare(
            'SELECT id_user, nipd, passwordd, roles, is_active
             FROM m_user
             WHERE nipd = ?
             LIMIT 1'
        );
        $stmt->bind_param('s', $nipd);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            echo json_encode(['status' => false, 'message' => 'NIPD atau password salah.']);
            return;
        }

        if ($user['is_active'] !== 'Y') {
            echo json_encode(['status' => false, 'message' => 'Akun tidak aktif. Hubungi administrator.']);
            return;
        }

        // Verifikasi password (bcrypt atau plaintext lama + auto-upgrade)
        $password_ok = false;

        if (password_verify($password, $user['passwordd'])) {
            $password_ok = true;

        } elseif ($user['passwordd'] === $password) {
            // Auto-upgrade plaintext → bcrypt
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $upd = $this->conn->prepare('UPDATE m_user SET passwordd = ? WHERE id_user = ?');
            $upd->bind_param('si', $hashed, $user['id_user']);
            $upd->execute();
            $upd->close();
            $password_ok = true;
        }

        if (!$password_ok) {
            echo json_encode(['status' => false, 'message' => 'NIPD atau password salah.']);
            return;
        }

        // Ambil nama dari tabel siswa/guru sesuai role
        $nama = $this->getNamaFallback($nipd, $user['roles']);

        // Login berhasil — simpan ke SESSION
        session_regenerate_id(true);
        $_SESSION['id_user'] = (int) $user['id_user'];
        $_SESSION['roles'] = $user['roles'];
        $_SESSION['logged_in'] = true;

        $redirect = match ($user['roles']) {
            'admin' => 'admin.html',
            default => 'index.html',
        };
        echo json_encode([
            'status' => true,
            'message' => 'Login berhasil.',
            'redirect' => $redirect,
            'nama' => $nama ?: 'User',
            'roles' => $user['roles'],
        ]);
    }

    // Ambil nama dari tabel siswa atau guru sesuai role
    private function getNamaFallback(string $nipd, string $roles): string
    {
        if ($roles === 'siswa') {
            $stmt = $this->conn->prepare('SELECT nama_siswa FROM m_siswa WHERE nipd = ? LIMIT 1');
            $stmt->bind_param('s', $nipd);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row['nama_siswa'] ?? '';
        }

        if (in_array($roles, ['guru', 'admin'], true)) {
            $stmt = $this->conn->prepare('SELECT nama_guru FROM m_guru WHERE kode_guru = ? LIMIT 1');
            $stmt->bind_param('s', $nipd);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row['nama_guru'] ?? 'Administrator';
        }

        return 'Administrator';
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        echo json_encode(['status' => true, 'message' => 'Logout berhasil.']);
    }

    public function checkSession(): void
    {
        if (is_logged_in()) {
            echo json_encode(['status' => true, 'roles' => current_user_role()]);
        } else {
            http_response_code(401);
            echo json_encode(['status' => false]);
        }
    }
}
