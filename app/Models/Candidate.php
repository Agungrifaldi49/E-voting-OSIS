<?php
// app/Models/Candidate.php
require_once dirname(__DIR__, 2) . '/config/database.php';

class Candidate {
    public static function getByElectionId(int $electionId, bool $onlyPublished = false): array {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM candidates WHERE election_id = :election_id";
        if ($onlyPublished) {
            $sql .= " AND publication_status = 'published'";
        }
        $sql .= " ORDER BY nomor_urut ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':election_id' => $electionId]);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function isNumberTaken(int $electionId, int $nomorUrut, ?int $ignoreId = null): bool {
        $pdo = Database::getConnection();
        $sql = "SELECT id FROM candidates WHERE election_id = :election_id AND nomor_urut = :nomor";
        $params = [':election_id' => $electionId, ':nomor' => $nomorUrut];
        if ($ignoreId !== null) {
            $sql .= " AND id != :ignore_id";
            $params[':ignore_id'] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public static function hasVotes(int $candidateId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = :id");
        $stmt->execute([':id' => $candidateId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO candidates (
                election_id, nomor_urut, nama_ketua, kelas_ketua, foto_ketua,
                nama_wakil, kelas_wakil, foto_wakil, foto_pasangan,
                visi, misi, program_kerja, publication_status, created_at
            ) VALUES (
                :election_id, :nomor_urut, :nama_ketua, :kelas_ketua, :foto_ketua,
                :nama_wakil, :kelas_wakil, :foto_wakil, :foto_pasangan,
                :visi, :misi, :program_kerja, :status, NOW()
            )
        ");

        $stmt->execute([
            ':election_id'    => $data['election_id'],
            ':nomor_urut'     => $data['nomor_urut'],
            ':nama_ketua'     => $data['nama_ketua'],
            ':kelas_ketua'    => $data['kelas_ketua'],
            ':foto_ketua'     => $data['foto_ketua'] ?? null,
            ':nama_wakil'     => $data['nama_wakil'],
            ':kelas_wakil'    => $data['kelas_wakil'],
            ':foto_wakil'     => $data['foto_wakil'] ?? null,
            ':foto_pasangan'  => $data['foto_pasangan'] ?? null,
            ':visi'           => $data['visi'],
            ':misi'           => $data['misi'],
            ':program_kerja'  => $data['program_kerja'] ?? null,
            ':status'         => $data['publication_status'] ?? 'published'
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $fields = [
            'nomor_urut = :nomor_urut',
            'nama_ketua = :nama_ketua',
            'kelas_ketua = :kelas_ketua',
            'nama_wakil = :nama_wakil',
            'kelas_wakil = :kelas_wakil',
            'visi = :visi',
            'misi = :misi',
            'program_kerja = :program_kerja',
            'publication_status = :status',
            'updated_at = NOW()'
        ];

        $params = [
            ':id'            => $id,
            ':nomor_urut'    => $data['nomor_urut'],
            ':nama_ketua'    => $data['nama_ketua'],
            ':kelas_ketua'   => $data['kelas_ketua'],
            ':nama_wakil'    => $data['nama_wakil'],
            ':kelas_wakil'   => $data['kelas_wakil'],
            ':visi'          => $data['visi'],
            ':misi'          => $data['misi'],
            ':program_kerja' => $data['program_kerja'] ?? null,
            ':status'        => $data['publication_status'] ?? 'published'
        ];

        if (!empty($data['foto_ketua'])) {
            $fields[] = 'foto_ketua = :foto_ketua';
            $params[':foto_ketua'] = $data['foto_ketua'];
        }
        if (!empty($data['foto_wakil'])) {
            $fields[] = 'foto_wakil = :foto_wakil';
            $params[':foto_wakil'] = $data['foto_wakil'];
        }
        if (!empty($data['foto_pasangan'])) {
            $fields[] = 'foto_pasangan = :foto_pasangan';
            $params[':foto_pasangan'] = $data['foto_pasangan'];
        }

        $sql = "UPDATE candidates SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool {
        // Prevent deletion if votes already cast
        if (self::hasVotes($id)) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
