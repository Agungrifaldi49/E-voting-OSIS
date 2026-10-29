<?php
// app/Models/Vote.php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once __DIR__ . '/Election.php';
require_once __DIR__ . '/../Helpers/LogHelper.php';

class Vote {
    /**
     * Mengecek apakah user sudah memilih pada periode ini
     */
    public static function hasUserVoted(int $electionId, int $userId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM voting_participation WHERE election_id = :eid AND user_id = :uid LIMIT 1");
        $stmt->execute([':eid' => $electionId, ':uid' => $userId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Mengecek hak pilih (eligibility)
     */
    public static function isUserEligible(int $electionId, int $userId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT eligibility_status FROM voting_eligibility WHERE election_id = :eid AND user_id = :uid LIMIT 1");
        $stmt->execute([':eid' => $electionId, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row && $row['eligibility_status'] === 'eligible';
    }

    /**
     * Melakukan voting secara atomic dengan transaksi database dan secret ballot
     */
    public static function castVote(int $electionId, int $userId, int $candidateId): array {
        $pdo = Database::getConnection();

        // 1. Ambil data periode pemilihan
        $election = Election::getById($electionId);
        if (!$election) {
            return ['success' => false, 'error' => 'Periode pemilihan tidak ditemukan.'];
        }

        // 2. Validasi jadwal pemilihan berdasarkan server time Asia/Jakarta
        $computedStatus = Election::computeStatus($election);
        if ($computedStatus !== 'ongoing') {
            if ($computedStatus === 'scheduled') {
                return ['success' => false, 'error' => 'Pemilihan belum dibuka. Jadwal mulai: ' . date('d M Y H:i', strtotime($election['start_at'])) . ' WIB.'];
            } else {
                return ['success' => false, 'error' => 'Pemilihan telah berakhir atau ditutup pada ' . date('d M Y H:i', strtotime($election['end_at'])) . ' WIB.'];
            }
        }

        // 3. Validasi kandidat
        $candStmt = $pdo->prepare("SELECT id FROM candidates WHERE id = :cid AND election_id = :eid AND publication_status = 'published'");
        $candStmt->execute([':cid' => $candidateId, ':eid' => $electionId]);
        if (!$candStmt->fetch()) {
            return ['success' => false, 'error' => 'Pasangan calon tidak valid atau tidak terdaftar pada pemilihan ini.'];
        }

        // 4. Validasi hak pilih
        if (!self::isUserEligible($electionId, $userId)) {
            return ['success' => false, 'error' => 'Anda tidak terdaftar sebagai pemilih sah untuk periode pemilihan ini.'];
        }

        // 5. Cek apakah sudah memilih
        if (self::hasUserVoted($electionId, $userId)) {
            return ['success' => false, 'error' => 'Anda sudah menggunakan hak pilih Anda pada periode pemilihan ini.'];
        }

        // 6. Jalankan Transaksi Database Atomic
        $pdo->beginTransaction();
        try {
            // Catat partisipasi terlebih dahulu. Karena UNIQUE(election_id, user_id),
            // request concurrent kedua akan langsung gagal dengan duplicate key constraint!
            $partStmt = $pdo->prepare("
                INSERT INTO voting_participation (election_id, user_id, voted_at)
                VALUES (:eid, :uid, NOW())
            ");
            $partStmt->execute([':eid' => $electionId, ':uid' => $userId]);

            // Masukkan suara ke tabel votes TANPA user_id (Kerahasiaan Suara / Secret Ballot)
            $voteStmt = $pdo->prepare("
                INSERT INTO votes (election_id, candidate_id, created_at)
                VALUES (:eid, :cid, NOW())
            ");
            $voteStmt->execute([':eid' => $electionId, ':cid' => $candidateId]);

            $pdo->commit();

            // Catat audit log tanpa mencatat pilihan kandidat!
            LogHelper::log('VOTE_SUBMITTED', "User ID {$userId} telah memberikan suara pada pemilihan ID {$electionId}.", $userId);

            return ['success' => true, 'message' => 'Voting berhasil! Terima kasih telah menggunakan hak pilih Anda.'];
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Duplicate entry error code for MySQL is 23000 / 1062
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
                return ['success' => false, 'error' => 'Hak pilih Anda telah tercatat sebelumnya. Pemungutan suara ganda dicegah.'];
            }
            error_log("Vote transaction failure: " . $e->getMessage());
            return ['success' => false, 'error' => 'Terjadi kesalahan sistem saat memproses suara Anda. Silakan coba lagi.'];
        }
    }

    /**
     * Rekapitulasi hasil voting per pasangan calon
     */
    public static function getTally(int $electionId): array {
        return self::getResults($electionId);
    }

    public static function getResults(int $electionId): array {
        $pdo = Database::getConnection();

        // Total suara sah keseluruhan
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = :eid");
        $totalStmt->execute([':eid' => $electionId]);
        $totalVotes = (int)$totalStmt->fetchColumn();

        // Perolehan suara masing-masing kandidat
        $stmt = $pdo->prepare("
            SELECT c.id, c.nomor_urut, c.nama_ketua, c.nama_wakil, c.kelas_ketua, c.kelas_wakil,
                   c.foto_ketua, c.foto_wakil, c.foto_pasangan,
                   COUNT(v.id) AS total_suara
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id AND v.election_id = :eid1
            WHERE c.election_id = :eid2 AND c.publication_status = 'published'
            GROUP BY c.id
            ORDER BY c.nomor_urut ASC
        ");
        $stmt->execute([':eid1' => $electionId, ':eid2' => $electionId]);
        $candidates = $stmt->fetchAll();

        foreach ($candidates as &$cand) {
            $candVotes = (int)$cand['total_suara'];
            $cand['total_suara'] = $candVotes;
            $cand['persentase'] = $totalVotes > 0 ? round(($candVotes / $totalVotes) * 100, 2) : 0.00;
        }

        return [
            'total_suara_sah' => $totalVotes,
            'candidates'      => $candidates,
            'updated_at'      => date('Y-m-d H:i:s'),
            'updated_at_formatted' => date('d M Y, H:i:s') . ' WIB'
        ];
    }

    /**
     * Statistik partisipasi pemilih
     */
    public static function getParticipationStats(int $electionId): array {
        $pdo = Database::getConnection();

        // Total pemilih eligible
        $elStmt = $pdo->prepare("SELECT COUNT(*) FROM voting_eligibility WHERE election_id = :eid AND eligibility_status = 'eligible'");
        $elStmt->execute([':eid' => $electionId]);
        $totalEligible = (int)$elStmt->fetchColumn();

        // Total sudah memilih
        $partStmt = $pdo->prepare("SELECT COUNT(*) FROM voting_participation WHERE election_id = :eid");
        $partStmt->execute([':eid' => $electionId]);
        $totalVoted = (int)$partStmt->fetchColumn();

        $totalUnvoted = max(0, $totalEligible - $totalVoted);
        $turnoutPct = $totalEligible > 0 ? round(($totalVoted / $totalEligible) * 100, 2) : 0.00;

        return [
            'total_eligible' => $totalEligible,
            'total_voted'    => $totalVoted,
            'total_unvoted'  => $totalUnvoted,
            'turnout_pct'    => $turnoutPct
        ];
    }
}
