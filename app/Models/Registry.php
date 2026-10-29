<?php
// app/Models/Registry.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once __DIR__ . '/../Helpers/ValidationHelper.php';

class Registry {
    public static function checkStudent(string $nisn): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM student_registry WHERE nisn = :nisn LIMIT 1");
        $stmt->execute([':nisn' => trim($nisn)]);
        return $stmt->fetch() ?: null;
    }

    public static function checkTeacher(string $identifier): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM teacher_registry WHERE employee_identifier = :id LIMIT 1");
        $stmt->execute([':id' => trim($identifier)]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Import Master Siswa dari file CSV
     */
    public static function importStudentsCsv(string $tmpPath): array {
        $pdo = Database::getConnection();
        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Tidak dapat membuka file CSV yang diunggah.'];
        }

        // Detect BOM and read header
        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'error' => 'File CSV kosong.'];
        }

        // Clean UTF-8 BOM from headers
        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        $header = array_map(fn($col) => strtolower(trim($col)), $header);

        $nisnIdx = array_search('nisn', $header);
        $namaIdx = array_search('nama_lengkap', $header);
        if ($namaIdx === false) {
            $namaIdx = array_search('nama', $header);
        }
        $kelasIdx = array_search('kelas', $header);
        $jurusanIdx = array_search('jurusan', $header);

        if ($nisnIdx === false || $namaIdx === false || $kelasIdx === false || $jurusanIdx === false) {
            fclose($handle);
            return [
                'success' => false,
                'error' => 'Header CSV tidak sesuai. Wajib memiliki kolom: nisn, nama_lengkap (atau nama), kelas, jurusan.'
            ];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        $stmt = $pdo->prepare("
            INSERT INTO student_registry (nisn, nama_lengkap, kelas, jurusan, status, created_at)
            VALUES (:nisn, :nama, :kelas, :jurusan, 'unregistered', NOW())
            ON DUPLICATE KEY UPDATE
                nama_lengkap = VALUES(nama_lengkap),
                kelas = VALUES(kelas),
                jurusan = VALUES(jurusan)
        ");

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNum++;
            // Check empty row
            if (empty(array_filter($row))) {
                continue;
            }

            $nisn = trim($row[$nisnIdx] ?? '');
            $nama = trim($row[$namaIdx] ?? '');
            $kelas = trim($row[$kelasIdx] ?? '');
            $jurusan = trim($row[$jurusanIdx] ?? '');

            if (empty($nisn) || empty($nama) || empty($kelas) || empty($jurusan)) {
                $errors[] = "Baris {$rowNum}: Kolom data tidak lengkap.";
                $skipped++;
                continue;
            }

            if (!ValidationHelper::validateNISN($nisn)) {
                $errors[] = "Baris {$rowNum}: Format NISN '{$nisn}' tidak valid (harus 10 digit angka).";
                $skipped++;
                continue;
            }

            try {
                $stmt->execute([
                    ':nisn'    => $nisn,
                    ':nama'    => $nama,
                    ':kelas'   => $kelas,
                    ':jurusan' => $jurusan
                ]);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Baris {$rowNum}: Gagal menyimpan NISN {$nisn}.";
                $skipped++;
            }
        }

        fclose($handle);

        return [
            'success'  => true,
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors
        ];
    }

    /**
     * Import Master Guru dari file CSV
     */
    public static function importTeachersCsv(string $tmpPath): array {
        $pdo = Database::getConnection();
        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Tidak dapat membuka file CSV yang diunggah.'];
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'error' => 'File CSV kosong.'];
        }

        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        $header = array_map(fn($col) => strtolower(trim($col)), $header);

        $idIdx = array_search('employee_identifier', $header);
        if ($idIdx === false) {
            $idIdx = array_search('nip', $header);
        }
        if ($idIdx === false) {
            $idIdx = array_search('id_guru', $header);
        }
        $namaIdx = array_search('nama_lengkap', $header);
        if ($namaIdx === false) {
            $namaIdx = array_search('nama', $header);
        }

        if ($idIdx === false || $namaIdx === false) {
            fclose($handle);
            return [
                'success' => false,
                'error' => 'Header CSV tidak sesuai. Wajib memiliki kolom: employee_identifier (atau nip) dan nama_lengkap (atau nama).'
            ];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        $stmt = $pdo->prepare("
            INSERT INTO teacher_registry (employee_identifier, nama_lengkap, status, created_at)
            VALUES (:id, :nama, 'unregistered', NOW())
            ON DUPLICATE KEY UPDATE
                nama_lengkap = VALUES(nama_lengkap)
        ");

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) {
                continue;
            }

            $id = trim($row[$idIdx] ?? '');
            $nama = trim($row[$namaIdx] ?? '');

            if (empty($id) || empty($nama)) {
                $errors[] = "Baris {$rowNum}: Kolom NIP/Nama tidak lengkap.";
                $skipped++;
                continue;
            }

            try {
                $stmt->execute([':id' => $id, ':nama' => $nama]);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Baris {$rowNum}: Gagal menyimpan data guru '{$id}'.";
                $skipped++;
            }
        }

        fclose($handle);

        return [
            'success'  => true,
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors
        ];
    }
}
