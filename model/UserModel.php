<?php
// ============================================================
// FILE   : model/UserModel.php
// FIX    : hapus kolom id_siswa/id_guru yang tidak ada di DB baru
//          m_user baru hanya punya: nipd, passwordd, roles, is_active
// ============================================================

class UserModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function insert(array $data): bool
    {
        $hashed    = password_hash($data['passwordd'], PASSWORD_BCRYPT);
        $is_active = $data['is_active'] ?? 'Y';

        $stmt = $this->conn->prepare(
            'INSERT INTO m_user (nipd, passwordd, roles, is_active) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $data['nipd'], $hashed, $data['roles'], $is_active);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function insertBanyak(array $dataArray): int
    {
        $berhasil = 0;
        foreach ($dataArray as $data) {
            if ($this->insert($data)) $berhasil++;
        }
        return $berhasil;
    }
}
