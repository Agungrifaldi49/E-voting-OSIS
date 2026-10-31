<?php
// app/Models/AcademicMaster.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/Helpers/LogHelper.php';

class AcademicMaster {
    private static bool $initialized = false;

    /**
     * Ensure `majors` and `school_classes` tables exist and seed initial data if empty.
     */
    public static function ensureTables(): void {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        $pdo = Database::getConnection();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `majors` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `kode_jurusan` VARCHAR(25) NOT NULL,
                `nama_jurusan` VARCHAR(120) NOT NULL,
                `deskripsi` VARCHAR(255) DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_kode_jurusan` (`kode_jurusan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `school_classes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `tingkat` VARCHAR(10) NOT NULL,
                `major_id` INT(11) NOT NULL,
                `nomor_rombel` VARCHAR(15) DEFAULT NULL,
                `nama_kelas` VARCHAR(64) NOT NULL,
                `wali_kelas` VARCHAR(120) DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_nama_kelas` (`nama_kelas`),
                KEY `idx_major_id` (`major_id`),
                KEY `idx_tingkat` (`tingkat`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Seed default majors if empty
        $countMajors = (int)$pdo->query("SELECT COUNT(*) FROM `majors`")->fetchColumn();
        if ($countMajors === 0) {
            $defaultMajors = [
                ['RPL',  'Rekayasa Perangkat Lunak',              'Program Keahlian Pengembangan Perangkat Lunak dan Gim'],
                ['TKJ',  'Teknik Komputer & Jaringan',            'Program Keahlian Teknik Jaringan Komputer dan Telekomunikasi'],
                ['AKL',  'Akuntansi dan Keuangan Lembaga',        'Program Keahlian Akuntansi dan Keuangan Lembaga'],
                ['OTKP', 'Otomatisasi & Tata Kelola Perkantoran', 'Program Keahlian Manajemen Perkantoran dan Layanan Bisnis'],
                ['BDP',  'Bisnis Daring dan Pemasaran',           'Program Keahlian Pemasaran dan Bisnis Digital'],
            ];
            $insMajor = $pdo->prepare("
                INSERT INTO `majors` (`kode_jurusan`, `nama_jurusan`, `deskripsi`, `is_active`)
                VALUES (:kode, :nama, :deskripsi, 1)
            ");
            foreach ($defaultMajors as $m) {
                $insMajor->execute([
                    ':kode'      => $m[0],
                    ':nama'      => $m[1],
                    ':deskripsi' => $m[2],
                ]);
            }

            // Seed default classes (X, XI, XII for each major)
            $majorsRows = $pdo->query("SELECT id, kode_jurusan FROM `majors` ORDER BY id ASC")->fetchAll();
            $insClass = $pdo->prepare("
                INSERT IGNORE INTO `school_classes` (`tingkat`, `major_id`, `nomor_rombel`, `nama_kelas`, `is_active`)
                VALUES (:tingkat, :major_id, :rombel, :nama_kelas, 1)
            ");
            foreach ($majorsRows as $mr) {
                foreach (['X', 'XI', 'XII'] as $tingkat) {
                    $rombel = '1';
                    $namaKelas = "{$tingkat} {$mr['kode_jurusan']} {$rombel}";
                    $insClass->execute([
                        ':tingkat'    => $tingkat,
                        ':major_id'   => $mr['id'],
                        ':rombel'     => $rombel,
                        ':nama_kelas' => $namaKelas,
                    ]);
                }
            }
        }
    }

    // ==================== MAJORS (JURUSAN) ====================

    public static function getAllMajors(string $search = '', bool $onlyActive = false): array {
        self::ensureTables();
        $pdo = Database::getConnection();

        $sql = "
            SELECT m.*,
                   (SELECT COUNT(*) FROM school_classes sc WHERE sc.major_id = m.id) AS total_classes,
                   (SELECT COUNT(*) FROM student_profiles sp WHERE sp.jurusan = m.nama_jurusan OR sp.jurusan = m.kode_jurusan) AS total_students
            FROM majors m
            WHERE 1=1
        ";
        $params = [];

        if ($onlyActive) {
            $sql .= " AND m.is_active = 1";
        }
        if (!empty($search)) {
            $sql .= " AND (m.kode_jurusan LIKE :s1 OR m.nama_jurusan LIKE :s2 OR m.deskripsi LIKE :s3)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        $sql .= " ORDER BY m.kode_jurusan ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findMajorById(int $id): ?array {
        self::ensureTables();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM majors WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createMajor(array $data): array {
        self::ensureTables();
        $pdo = Database::getConnection();

        $kode = strtoupper(trim($data['kode_jurusan'] ?? ''));
        $nama = trim($data['nama_jurusan'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if ($kode === '' || $nama === '') {
            return ['success' => false, 'error' => 'Kode singkatan jurusan dan nama lengkap jurusan wajib diisi.'];
        }

        $chk = $pdo->prepare("SELECT id FROM majors WHERE kode_jurusan = :kode OR nama_jurusan = :nama LIMIT 1");
        $chk->execute([':kode' => $kode, ':nama' => $nama]);
        if ($chk->fetch()) {
            return ['success' => false, 'error' => "Jurusan dengan kode '{$kode}' atau nama '{$nama}' sudah terdaftar."];
        }

        $stmt = $pdo->prepare("
            INSERT INTO majors (kode_jurusan, nama_jurusan, deskripsi, is_active)
            VALUES (:kode, :nama, :deskripsi, :active)
        ");
        $stmt->execute([
            ':kode'      => $kode,
            ':nama'      => $nama,
            ':deskripsi' => $deskripsi ?: null,
            ':active'    => $isActive ? 1 : 0,
        ]);

        LogHelper::log('MAJOR_CREATED', "Admin menambahkan jurusan baru: {$nama} ({$kode}).");
        return ['success' => true, 'id' => (int)$pdo->lastInsertId()];
    }

    public static function updateMajor(int $id, array $data): array {
        self::ensureTables();
        $pdo = Database::getConnection();

        $existing = self::findMajorById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Data jurusan tidak ditemukan.'];
        }

        $kode = strtoupper(trim($data['kode_jurusan'] ?? ''));
        $nama = trim($data['nama_jurusan'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : (int)$existing['is_active'];

        if ($kode === '' || $nama === '') {
            return ['success' => false, 'error' => 'Kode singkatan jurusan dan nama lengkap jurusan wajib diisi.'];
        }

        $chk = $pdo->prepare("SELECT id FROM majors WHERE (kode_jurusan = :kode OR nama_jurusan = :nama) AND id != :id LIMIT 1");
        $chk->execute([':kode' => $kode, ':nama' => $nama, ':id' => $id]);
        if ($chk->fetch()) {
            return ['success' => false, 'error' => "Kode jurusan '{$kode}' atau nama '{$nama}' sudah digunakan oleh jurusan lain."];
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE majors
                SET kode_jurusan = :kode, nama_jurusan = :nama, deskripsi = :deskripsi, is_active = :active
                WHERE id = :id
            ");
            $stmt->execute([
                ':kode'      => $kode,
                ':nama'      => $nama,
                ':deskripsi' => $deskripsi ?: null,
                ':active'    => $isActive ? 1 : 0,
                ':id'        => $id,
            ]);

            // Sync student_profiles & student_registry if major name changed
            if ($existing['nama_jurusan'] !== $nama) {
                $upSp = $pdo->prepare("UPDATE student_profiles SET jurusan = :new_nama WHERE jurusan = :old_nama");
                $upSp->execute([':new_nama' => $nama, ':old_nama' => $existing['nama_jurusan']]);

                $upSr = $pdo->prepare("UPDATE student_registry SET jurusan = :new_nama WHERE jurusan = :old_nama");
                $upSr->execute([':new_nama' => $nama, ':old_nama' => $existing['nama_jurusan']]);
            }

            $pdo->commit();
            LogHelper::log('MAJOR_UPDATED', "Admin memperbarui jurusan: {$nama} ({$kode}).");
            return ['success' => true];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Gagal memperbarui jurusan: ' . $e->getMessage()];
        }
    }

    public static function toggleMajorStatus(int $id): array {
        self::ensureTables();
        $pdo = Database::getConnection();
        $existing = self::findMajorById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Jurusan tidak ditemukan.'];
        }

        $newStatus = $existing['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE majors SET is_active = :s WHERE id = :id");
        $stmt->execute([':s' => $newStatus, ':id' => $id]);

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        LogHelper::log('MAJOR_STATUS', "Admin mengubah status jurusan {$existing['nama_jurusan']} ({$existing['kode_jurusan']}) menjadi {$statusLabel}.");
        return ['success' => true, 'new_status' => $newStatus, 'major' => $existing];
    }

    public static function deleteMajor(int $id): array {
        self::ensureTables();
        $pdo = Database::getConnection();
        $existing = self::findMajorById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Data jurusan tidak ditemukan.'];
        }

        // Check if classes depend on this major
        $chkClasses = $pdo->prepare("SELECT COUNT(*) FROM school_classes WHERE major_id = :id");
        $chkClasses->execute([':id' => $id]);
        $classCount = (int)$chkClasses->fetchColumn();
        if ($classCount > 0) {
            return [
                'success' => false,
                'error'   => "Jurusan <strong>{$existing['nama_jurusan']} ({$existing['kode_jurusan']})</strong> masih memiliki <strong>{$classCount} kelas</strong> terdaftar. Hapus atau pindahkan kelas terkait terlebih dahulu."
            ];
        }

        $stmt = $pdo->prepare("DELETE FROM majors WHERE id = :id");
        $stmt->execute([':id' => $id]);

        LogHelper::log('MAJOR_DELETED', "Admin menghapus jurusan: {$existing['nama_jurusan']} ({$existing['kode_jurusan']}).");
        return ['success' => true, 'major' => $existing];
    }

    // ==================== SCHOOL CLASSES (KELAS) ====================

    public static function getAllClasses(string $search = '', string $tingkat = '', int $majorId = 0, bool $onlyActive = false): array {
        self::ensureTables();
        $pdo = Database::getConnection();

        $sql = "
            SELECT sc.*,
                   m.kode_jurusan,
                   m.nama_jurusan,
                   (SELECT COUNT(*) FROM student_profiles sp WHERE sp.kelas = sc.nama_kelas) AS total_students
            FROM school_classes sc
            LEFT JOIN majors m ON sc.major_id = m.id
            WHERE 1=1
        ";
        $params = [];

        if ($onlyActive) {
            $sql .= " AND sc.is_active = 1";
        }
        if ($tingkat !== '') {
            $sql .= " AND sc.tingkat = :tingkat";
            $params[':tingkat'] = $tingkat;
        }
        if ($majorId > 0) {
            $sql .= " AND sc.major_id = :major_id";
            $params[':major_id'] = $majorId;
        }
        if ($search !== '') {
            $sql .= " AND (sc.nama_kelas LIKE :s1 OR m.nama_jurusan LIKE :s2 OR m.kode_jurusan LIKE :s3 OR sc.wali_kelas LIKE :s4)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
        }

        $sql .= " ORDER BY FIELD(sc.tingkat, 'X', 'XI', 'XII'), m.kode_jurusan ASC, sc.nomor_rombel ASC, sc.nama_kelas ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findClassById(int $id): ?array {
        self::ensureTables();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sc.*, m.kode_jurusan, m.nama_jurusan
            FROM school_classes sc
            LEFT JOIN majors m ON sc.major_id = m.id
            WHERE sc.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createClass(array $data): array {
        self::ensureTables();
        $pdo = Database::getConnection();

        $tingkat = strtoupper(trim($data['tingkat'] ?? ''));
        $majorId = (int)($data['major_id'] ?? 0);
        $rombel  = trim($data['nomor_rombel'] ?? '');
        $namaKelas = trim($data['nama_kelas'] ?? '');
        $waliKelas = trim($data['wali_kelas'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if (!in_array($tingkat, ['X', 'XI', 'XII'], true)) {
            return ['success' => false, 'error' => 'Tingkat kelas harus dipilih (X, XI, atau XII).'];
        }

        $major = self::findMajorById($majorId);
        if (!$major) {
            return ['success' => false, 'error' => 'Silakan pilih jurusan terlebih dahulu.'];
        }

        if ($namaKelas === '') {
            $namaKelas = $tingkat . ' ' . $major['kode_jurusan'] . ($rombel !== '' ? ' ' . $rombel : '');
        }

        $chk = $pdo->prepare("SELECT id FROM school_classes WHERE nama_kelas = :nama LIMIT 1");
        $chk->execute([':nama' => $namaKelas]);
        if ($chk->fetch()) {
            return ['success' => false, 'error' => "Kelas <strong>{$namaKelas}</strong> sudah terdaftar di dalam sistem."];
        }

        $stmt = $pdo->prepare("
            INSERT INTO school_classes (tingkat, major_id, nomor_rombel, nama_kelas, wali_kelas, is_active)
            VALUES (:tingkat, :major_id, :rombel, :nama_kelas, :wali, :active)
        ");
        $stmt->execute([
            ':tingkat'    => $tingkat,
            ':major_id'   => $majorId,
            ':rombel'     => $rombel !== '' ? $rombel : null,
            ':nama_kelas' => $namaKelas,
            ':wali'       => $waliKelas !== '' ? $waliKelas : null,
            ':active'     => $isActive ? 1 : 0,
        ]);

        LogHelper::log('CLASS_CREATED', "Admin menambahkan kelas baru: {$namaKelas} ({$major['nama_jurusan']}).");
        return ['success' => true, 'id' => (int)$pdo->lastInsertId(), 'nama_kelas' => $namaKelas];
    }

    public static function updateClass(int $id, array $data): array {
        self::ensureTables();
        $pdo = Database::getConnection();

        $existing = self::findClassById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Data kelas tidak ditemukan.'];
        }

        $tingkat = strtoupper(trim($data['tingkat'] ?? ''));
        $majorId = (int)($data['major_id'] ?? 0);
        $rombel  = trim($data['nomor_rombel'] ?? '');
        $namaKelas = trim($data['nama_kelas'] ?? '');
        $waliKelas = trim($data['wali_kelas'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : (int)$existing['is_active'];

        if (!in_array($tingkat, ['X', 'XI', 'XII'], true)) {
            return ['success' => false, 'error' => 'Tingkat kelas harus dipilih (X, XI, atau XII).'];
        }

        $major = self::findMajorById($majorId);
        if (!$major) {
            return ['success' => false, 'error' => 'Silakan pilih jurusan terlebih dahulu.'];
        }

        if ($namaKelas === '') {
            $namaKelas = $tingkat . ' ' . $major['kode_jurusan'] . ($rombel !== '' ? ' ' . $rombel : '');
        }

        $chk = $pdo->prepare("SELECT id FROM school_classes WHERE nama_kelas = :nama AND id != :id LIMIT 1");
        $chk->execute([':nama' => $namaKelas, ':id' => $id]);
        if ($chk->fetch()) {
            return ['success' => false, 'error' => "Nama kelas <strong>{$namaKelas}</strong> sudah digunakan oleh kelas lain."];
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE school_classes
                SET tingkat = :tingkat, major_id = :major_id, nomor_rombel = :rombel,
                    nama_kelas = :nama_kelas, wali_kelas = :wali, is_active = :active
                WHERE id = :id
            ");
            $stmt->execute([
                ':tingkat'    => $tingkat,
                ':major_id'   => $majorId,
                ':rombel'     => $rombel !== '' ? $rombel : null,
                ':nama_kelas' => $namaKelas,
                ':wali'       => $waliKelas !== '' ? $waliKelas : null,
                ':active'     => $isActive ? 1 : 0,
                ':id'        => $id,
            ]);

            // Sync student_profiles & student_registry if class name changed
            if ($existing['nama_kelas'] !== $namaKelas) {
                $upSp = $pdo->prepare("UPDATE student_profiles SET kelas = :new_kelas, jurusan = :new_jurusan WHERE kelas = :old_kelas");
                $upSp->execute([
                    ':new_kelas'   => $namaKelas,
                    ':new_jurusan' => $major['nama_jurusan'],
                    ':old_kelas'   => $existing['nama_kelas']
                ]);

                $upSr = $pdo->prepare("UPDATE student_registry SET kelas = :new_kelas, jurusan = :new_jurusan WHERE kelas = :old_kelas");
                $upSr->execute([
                    ':new_kelas'   => $namaKelas,
                    ':new_jurusan' => $major['nama_jurusan'],
                    ':old_kelas'   => $existing['nama_kelas']
                ]);
            }

            $pdo->commit();
            LogHelper::log('CLASS_UPDATED', "Admin memperbarui data kelas: {$namaKelas} ({$major['nama_jurusan']}).");
            return ['success' => true, 'nama_kelas' => $namaKelas];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Gagal memperbarui kelas: ' . $e->getMessage()];
        }
    }

    public static function toggleClassStatus(int $id): array {
        self::ensureTables();
        $pdo = Database::getConnection();
        $existing = self::findClassById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Data kelas tidak ditemukan.'];
        }

        $newStatus = $existing['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE school_classes SET is_active = :s WHERE id = :id");
        $stmt->execute([':s' => $newStatus, ':id' => $id]);

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        LogHelper::log('CLASS_STATUS', "Admin mengubah status kelas {$existing['nama_kelas']} menjadi {$statusLabel}.");
        return ['success' => true, 'new_status' => $newStatus, 'class' => $existing];
    }

    public static function deleteClass(int $id): array {
        self::ensureTables();
        $pdo = Database::getConnection();
        $existing = self::findClassById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Data kelas tidak ditemukan.'];
        }

        $stmt = $pdo->prepare("DELETE FROM school_classes WHERE id = :id");
        $stmt->execute([':id' => $id]);

        LogHelper::log('CLASS_DELETED', "Admin menghapus kelas: {$existing['nama_kelas']}.");
        return ['success' => true, 'class' => $existing];
    }
}

