<?php
// app/Models/Election.php
require_once dirname(__DIR__, 2) . '/config/database.php';

class Election {
    public static function getActive(): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM elections WHERE is_active = 1 LIMIT 1");
        $election = $stmt->fetch();
        if ($election) {
            $election['computed_status'] = self::computeStatus($election);
        }
        return $election ?: null;
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $election = $stmt->fetch();
        if ($election) {
            $election['computed_status'] = self::computeStatus($election);
        }
        return $election ?: null;
    }

    public static function getAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM elections ORDER BY id DESC");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['computed_status'] = self::computeStatus($row);
        }
        return $rows;
    }

    public static function computeStatus(array $election): string {
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $start = new DateTime($election['start_at'], new DateTimeZone('Asia/Jakarta'));
        $end = new DateTime($election['end_at'], new DateTimeZone('Asia/Jakarta'));

        if ($election['status'] === 'draft') {
            return 'draft';
        }
        if ($election['status'] === 'results_published') {
            return 'results_published';
        }
        if ($now < $start) {
            return 'scheduled';
        }
        if ($now >= $start && $now <= $end) {
            return 'ongoing';
        }
        return 'closed';
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO elections (nama_periode, tahun, deskripsi, start_at, end_at, status, is_active, teacher_can_vote, results_visibility, created_at)
            VALUES (:nama, :tahun, :deskripsi, :start, :end, :status, :active, :teacher, :results, NOW())
        ");

        $isActive = !empty($data['is_active']) ? 1 : 0;
        if ($isActive) {
            $pdo->query("UPDATE elections SET is_active = 0");
        }

        $stmt->execute([
            ':nama'      => $data['nama_periode'],
            ':tahun'     => $data['tahun'],
            ':deskripsi' => $data['deskripsi'] ?? null,
            ':start'     => $data['start_at'],
            ':end'       => $data['end_at'],
            ':status'    => $data['status'] ?? 'draft',
            ':active'    => $isActive,
            ':teacher'   => !empty($data['teacher_can_vote']) ? 1 : 0,
            ':results'   => $data['results_visibility'] ?? 'hidden'
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $isActive = !empty($data['is_active']) ? 1 : 0;
        if ($isActive) {
            $pdo->query("UPDATE elections SET is_active = 0 WHERE id != {$id}");
        }

        $stmt = $pdo->prepare("
            UPDATE elections
            SET nama_periode = :nama,
                tahun = :tahun,
                deskripsi = :deskripsi,
                start_at = :start,
                end_at = :end,
                status = :status,
                is_active = :active,
                teacher_can_vote = :teacher,
                results_visibility = :results,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id'        => $id,
            ':nama'      => $data['nama_periode'],
            ':tahun'     => $data['tahun'],
            ':deskripsi' => $data['deskripsi'] ?? null,
            ':start'     => $data['start_at'],
            ':end'       => $data['end_at'],
            ':status'    => $data['status'],
            ':active'    => $isActive,
            ':teacher'   => !empty($data['teacher_can_vote']) ? 1 : 0,
            ':results'   => $data['results_visibility'] ?? 'hidden'
        ]);
    }

    public static function setActive(int $id): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->query("UPDATE elections SET is_active = 0");
            $stmt = $pdo->prepare("UPDATE elections SET is_active = 1 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function setResultsVisibility(int $id, string $visibility): bool {
        $pdo = Database::getConnection();
        $publishedAtExpr = ($visibility !== 'hidden') ? "NOW()" : "results_published_at";
        $stmt = $pdo->prepare("
            UPDATE elections
            SET results_visibility = :vis,
                results_published_at = {$publishedAtExpr},
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([':vis' => $visibility, ':id' => $id]);
    }

    public static function syncEligibleVoters(int $electionId, bool $includeTeachers = false): int {
        $pdo = Database::getConnection();
        $roleCondition = $includeTeachers ? "u.role IN ('siswa', 'guru')" : "u.role = 'siswa'";
        
        $sql = "
            INSERT IGNORE INTO voting_eligibility (election_id, user_id, eligibility_status, created_at)
            SELECT :election_id, u.id, 'eligible', NOW()
            FROM users u
            WHERE u.account_status = 'active' AND {$roleCondition}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':election_id' => $electionId]);
        return $stmt->rowCount();
    }
}
