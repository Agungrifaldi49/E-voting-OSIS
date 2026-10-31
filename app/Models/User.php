<?php
// app/Models/User.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/Helpers/LogHelper.php';

class User {
    public static function findByUsername(string $username): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*,
                   sp.nisn, sp.nama_lengkap as student_nama, sp.kelas, sp.jurusan,
                   tp.employee_identifier, tp.nama_lengkap as teacher_nama, tp.mata_pelajaran
            FROM users u
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE u.username = :username
            LIMIT 1
        ");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        // Normalize nama_lengkap
        if ($user['role'] === 'siswa') {
            $user['nama_lengkap'] = $user['student_nama'] ?? $user['username'];
        } elseif ($user['role'] === 'guru') {
            $user['nama_lengkap'] = $user['teacher_nama'] ?? $user['username'];
        } else {
            $user['nama_lengkap'] = !empty($user['full_name']) ? $user['full_name'] : 'Administrator Sistem';
        }

        return $user;
    }

    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*,
                   sp.nisn, sp.nama_lengkap as student_nama, sp.kelas, sp.jurusan,
                   tp.employee_identifier, tp.nama_lengkap as teacher_nama, tp.mata_pelajaran
            FROM users u
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        if ($user['role'] === 'siswa') {
            $user['nama_lengkap'] = $user['student_nama'] ?? $user['username'];
        } elseif ($user['role'] === 'guru') {
            $user['nama_lengkap'] = $user['teacher_nama'] ?? $user['username'];
        } else {
            $user['nama_lengkap'] = !empty($user['full_name']) ? $user['full_name'] : 'Administrator Sistem';
        }

        return $user;
    }

    public static function createStudent(array $data): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Check uniqueness of username/NISN
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :nisn");
            $checkStmt->execute([':nisn' => $data['nisn']]);
            if ($checkStmt->fetch()) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'NISN sudah terdaftar dalam sistem.'];
            }

            // Insert User with pending status
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $userStmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, account_status, created_at)
                VALUES (:username, :email, :hash, 'siswa', 'pending', NOW())
            ");
            $userStmt->execute([
                ':username' => $data['nisn'],
                ':email'    => !empty($data['email']) ? $data['email'] : null,
                ':hash'     => $hash
            ]);
            $userId = (int)$pdo->lastInsertId();

            // Insert Student Profile
            $profileStmt = $pdo->prepare("
                INSERT INTO student_profiles (user_id, nisn, nama_lengkap, kelas, jurusan, created_at)
                VALUES (:user_id, :nisn, :nama, :kelas, :jurusan, NOW())
            ");
            $profileStmt->execute([
                ':user_id' => $userId,
                ':nisn'    => $data['nisn'],
                ':nama'    => $data['nama_lengkap'],
                ':kelas'   => $data['kelas'],
                ':jurusan' => $data['jurusan']
            ]);

            // Update student_registry status if exists
            $regStmt = $pdo->prepare("UPDATE student_registry SET status = 'registered' WHERE nisn = :nisn");
            $regStmt->execute([':nisn' => $data['nisn']]);

            $pdo->commit();
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error creating student: " . $e->getMessage());
            return ['success' => false, 'error' => 'Gagal mendaftarkan akun. Silakan coba lagi.'];
        }
    }

    public static function createTeacher(array $data): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :id");
            $checkStmt->execute([':id' => $data['identifier']]);
            if ($checkStmt->fetch()) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'NIP/Identitas guru sudah terdaftar dalam sistem.'];
            }

            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $userStmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, account_status, created_at)
                VALUES (:username, :email, :hash, 'guru', 'pending', NOW())
            ");
            $userStmt->execute([
                ':username' => $data['identifier'],
                ':email'    => !empty($data['email']) ? $data['email'] : null,
                ':hash'     => $hash
            ]);
            $userId = (int)$pdo->lastInsertId();

            $profileStmt = $pdo->prepare("
                INSERT INTO teacher_profiles (user_id, employee_identifier, nama_lengkap, mata_pelajaran, created_at)
                VALUES (:user_id, :identifier, :nama, :mapel, NOW())
            ");
            $profileStmt->execute([
                ':user_id'    => $userId,
                ':identifier' => $data['identifier'],
                ':nama'       => $data['nama_lengkap'],
                ':mapel'      => $data['mata_pelajaran'] ?? null
            ]);

            $regStmt = $pdo->prepare("UPDATE teacher_registry SET status = 'registered' WHERE employee_identifier = :id");
            $regStmt->execute([':id' => $data['identifier']]);

            $pdo->commit();
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error creating teacher: " . $e->getMessage());
            return ['success' => false, 'error' => 'Gagal mendaftarkan akun guru. Silakan coba lagi.'];
        }
    }

    public static function getPendingRegistrations(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT u.id, u.username, u.email, u.role, u.account_status, u.created_at,
                   COALESCE(sp.nama_lengkap, tp.nama_lengkap, u.username) AS nama_lengkap,
                   sp.kelas, sp.jurusan, tp.mata_pelajaran
            FROM users u
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE u.account_status = 'pending'
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $userId, string $status, ?string $reason = null): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE users
            SET account_status = :status, rejection_reason = :reason, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':status' => $status,
            ':reason' => $reason,
            ':id'     => $userId
        ]);
    }

    public static function getAllStudents(string $search = '', string $kelas = '', int $limit = 20, int $offset = 0): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT u.id, u.username, u.email, u.account_status, u.created_at,
                   sp.nisn, sp.nama_lengkap, sp.kelas, sp.jurusan
            FROM users u
            JOIN student_profiles sp ON u.id = sp.user_id
            WHERE u.role = 'siswa'
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (sp.nama_lengkap LIKE :s1 OR sp.nisn LIKE :s2 OR sp.jurusan LIKE :s3 OR sp.kelas LIKE :s4)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
        }
        if (!empty($kelas)) {
            $sql .= " AND (sp.kelas = :kelas_exact OR sp.kelas LIKE :kelas_prefix)";
            $params[':kelas_exact'] = $kelas;
            $params[':kelas_prefix'] = "{$kelas} %";
        }

        $sql .= " ORDER BY sp.nama_lengkap ASC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countAllStudents(string $search = '', string $kelas = ''): int {
        $pdo = Database::getConnection();
        $sql = "
            SELECT COUNT(*) FROM users u
            JOIN student_profiles sp ON u.id = sp.user_id
            WHERE u.role = 'siswa'
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (sp.nama_lengkap LIKE :s1 OR sp.nisn LIKE :s2 OR sp.jurusan LIKE :s3 OR sp.kelas LIKE :s4)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
        }
        if (!empty($kelas)) {
            $sql .= " AND (sp.kelas = :kelas_exact OR sp.kelas LIKE :kelas_prefix)";
            $params[':kelas_exact'] = $kelas;
            $params[':kelas_prefix'] = "{$kelas} %";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function getAllTeachers(string $search = '', int $limit = 20, int $offset = 0): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT u.id, u.username, u.email, u.account_status, u.created_at,
                   tp.employee_identifier, tp.nama_lengkap, tp.mata_pelajaran
            FROM users u
            JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE u.role = 'guru'
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (tp.nama_lengkap LIKE :s1 OR tp.employee_identifier LIKE :s2)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
        }

        $sql .= " ORDER BY tp.nama_lengkap ASC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countAllTeachers(string $search = ''): int {
        $pdo = Database::getConnection();
        $sql = "
            SELECT COUNT(*) FROM users u
            JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE u.role = 'guru'
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (tp.nama_lengkap LIKE :s1 OR tp.employee_identifier LIKE :s2)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function updatePassword(int $userId, string $newPassword): bool {
        $pdo = Database::getConnection();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([':hash' => $hash, ':id' => $userId]);
    }

    public static function updateStudentAcademic(int $userId, string $kelas, string $jurusan): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE student_profiles
            SET kelas = :kelas, jurusan = :jurusan, updated_at = NOW()
            WHERE user_id = :uid
        ");
        return $stmt->execute([
            ':kelas'   => $kelas,
            ':jurusan' => $jurusan,
            ':uid'     => $userId,
        ]);
    }

    public static function updateAdminProfile(int $userId, array $data, ?array $avatarFile = null): array {
        $pdo = Database::getConnection();

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $fullName = trim($data['full_name'] ?? '');

        if (empty($username) || empty($fullName)) {
            return ['success' => false, 'error' => 'Nama lengkap dan username tidak boleh kosong.'];
        }

        // Check if username taken by another user
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :u AND id != :id");
        $checkStmt->execute([':u' => $username, ':id' => $userId]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'error' => 'Username tersebut sudah digunakan oleh akun lain.'];
        }

        $avatarPath = null;
        if (!empty($avatarFile['tmp_name']) && is_uploaded_file($avatarFile['tmp_name'])) {
            $maxSize = 2 * 1024 * 1024;
            if ($avatarFile['size'] > $maxSize) {
                return ['success' => false, 'error' => 'Ukuran foto profil maksimal 2MB.'];
            }
            $ext = strtolower(pathinfo($avatarFile['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                return ['success' => false, 'error' => 'Format foto harus berupa JPG, PNG, atau WEBP.'];
            }
            $targetDir = PUBLIC_PATH . '/uploads/avatars';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $filename = 'admin_avatar_' . $userId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($avatarFile['tmp_name'], $targetDir . '/' . $filename)) {
                $avatarPath = 'uploads/avatars/' . $filename;
            }
        }

        if ($avatarPath !== null) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = :u, full_name = :fn, email = :e, avatar = :av, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmt->execute([
                ':u'  => $username,
                ':fn' => $fullName,
                ':e'  => $email ?: null,
                ':av' => $avatarPath,
                ':id' => $userId
            ]);
            $_SESSION['user']['avatar'] = $avatarPath;
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = :u, full_name = :fn, email = :e, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmt->execute([
                ':u'  => $username,
                ':fn' => $fullName,
                ':e'  => $email ?: null,
                ':id' => $userId
            ]);
        }

        // Update session
        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['nama_lengkap'] = $fullName;
        $_SESSION['user']['email'] = $email;

        LogHelper::log('ADMIN_PROFILE_UPDATED', "Administrator memperbarui profil akun ({$username}).", $userId);

        return ['success' => true];
    }

    public static function deleteUser(int $userId): array {
        $pdo = Database::getConnection();

        $targetUser = self::findById($userId);
        if (!$targetUser) {
            return ['success' => false, 'error' => 'Pengguna tidak ditemukan dalam sistem.'];
        }

        if ($targetUser['role'] === 'admin') {
            return ['success' => false, 'error' => 'Akun Administrator tidak dapat dihapus melalui fitur ini demi keamanan sistem.'];
        }

        $pdo->beginTransaction();
        try {
            // 1. Reset master data registry status so the student/teacher can re-register if needed
            if ($targetUser['role'] === 'siswa' && !empty($targetUser['nisn'])) {
                $regStmt = $pdo->prepare("UPDATE student_registry SET status = 'unregistered' WHERE nisn = :nisn");
                $regStmt->execute([':nisn' => $targetUser['nisn']]);
            } elseif ($targetUser['role'] === 'guru' && !empty($targetUser['employee_identifier'])) {
                $regStmt = $pdo->prepare("UPDATE teacher_registry SET status = 'unregistered' WHERE employee_identifier = :nip");
                $regStmt->execute([':nip' => $targetUser['employee_identifier']]);
            }

            // 2. Remove avatar file if uploaded
            if (!empty($targetUser['avatar'])) {
                $avatarFile = PUBLIC_PATH . '/' . ltrim($targetUser['avatar'], '/\\');
                if (file_exists($avatarFile) && is_file($avatarFile)) {
                    @unlink($avatarFile);
                }
            }

            // 3. Delete dependent rows explicitly for bulletproof data integrity across all environments
            $pdo->prepare("DELETE FROM student_profiles WHERE user_id = :id")->execute([':id' => $userId]);
            $pdo->prepare("DELETE FROM teacher_profiles WHERE user_id = :id")->execute([':id' => $userId]);
            $pdo->prepare("DELETE FROM voting_participation WHERE user_id = :id")->execute([':id' => $userId]);
            $pdo->prepare("DELETE FROM voting_eligibility WHERE user_id = :id")->execute([':id' => $userId]);

            // 4. Delete user record
            $delStmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $delStmt->execute([':id' => $userId]);

            $pdo->commit();

            // 5. Activity log
            $adminId = $_SESSION['user']['id'] ?? null;
            $nama = $targetUser['nama_lengkap'] ?? $targetUser['username'];
            $roleLabel = ($targetUser['role'] === 'siswa') ? 'siswa' : 'dewan guru';
            $ident = ($targetUser['role'] === 'siswa') ? ($targetUser['nisn'] ?? $targetUser['username']) : ($targetUser['employee_identifier'] ?? $targetUser['username']);
            LogHelper::log('USER_DELETED', "Admin menghapus akun {$roleLabel}: {$nama} (Identitas: {$ident}).", $adminId);

            return ['success' => true, 'user' => $targetUser];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error deleting user #{$userId}: " . $e->getMessage());
            return ['success' => false, 'error' => 'Gagal menghapus data: ' . $e->getMessage()];
        }
    }
}
