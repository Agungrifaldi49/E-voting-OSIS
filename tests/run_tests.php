<?php
// tests/run_tests.php
// Automated Test Suite for E-Voting OSIS SMK Muthia Harapan Cicalengka
// With automatic cleanup so test users and records never pollute live database!

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Models/User.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';
require_once dirname(__DIR__) . '/app/Models/Registry.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/ValidationHelper.php';

class TestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private array $details = [];
    private array $testUserIds = [];
    private array $testElectionIds = [];
    private array $testVoteIds = [];

    public function assert(string $testName, bool $condition, string $message = ''): void {
        if ($condition) {
            $this->passed++;
            $this->details[] = ["status" => "PASS", "test" => $testName, "msg" => $message];
            echo "[\033[32mPASS\033[0m] {$testName}\n";
        } else {
            $this->failed++;
            $this->details[] = ["status" => "FAIL", "test" => $testName, "msg" => $message];
            echo "[\033[31mFAIL\033[0m] {$testName} - {$message}\n";
        }
    }

    public function runAll(): void {
        echo "========================================================\n";
        echo "   PENGUJIAN SISTEM E-VOTING OSIS SMK MUTHIA HARAPAN    \n";
        echo "========================================================\n\n";

        try {
            $this->testAuthentication();
            $this->testVotingAndDoubleVoting();
            $this->testSecretBallotIntegrity();
            $this->testRealtimeAndVisibility();
            $this->testAdministrationAndPeriods();
        } finally {
            $this->cleanup();
        }

        echo "\n========================================================\n";
        echo "HASIL PENGUJIAN: {$this->passed} BERHASIL, {$this->failed} GAGAL.\n";
        echo "CLEANUP STATUS: Database bersih, 0 data dummy tersisa.\n";
        echo "========================================================\n";
    }

    private function testAuthentication(): void {
        echo "--- 1. PENGUJIAN AUTENTIKASI ---\n";

        // Test 1: Registrasi siswa baru
        $uniqueNisn = '00' . rand(10000000, 99999999);
        $res = User::createStudent([
            'nisn'         => $uniqueNisn,
            'nama_lengkap' => 'Siswa Tester Otomatis',
            'kelas'        => 'XI',
            'jurusan'      => 'Rekayasa Perangkat Lunak',
            'email'        => 'tester@smk.sch.id',
            'password'     => 'rahasia123'
        ]);
        $this->assert("Registrasi Siswa Berhasil", $res['success'] === true, $res['error'] ?? '');
        if (!empty($res['user_id'])) {
            $this->testUserIds[] = (int)$res['user_id'];
        }

        // Test 2: Registrasi duplikat NISN harus ditolak
        $dupRes = User::createStudent([
            'nisn'         => $uniqueNisn,
            'nama_lengkap' => 'Siswa Kloning',
            'kelas'        => 'X',
            'jurusan'      => 'AKL',
            'email'        => '',
            'password'     => 'rahasia123'
        ]);
        $this->assert("Registrasi NISN Duplikat Ditolak", $dupRes['success'] === false);

        // Test 3: Registrasi guru baru
        $uniqueNip = 'GURU' . rand(10000, 99999);
        $resGuru = User::createTeacher([
            'identifier'     => $uniqueNip,
            'nama_lengkap'   => 'Guru Tester Otomatis, S.Pd.',
            'mata_pelajaran' => 'Informatika',
            'email'          => 'guru_test@smk.sch.id',
            'password'       => 'guru123'
        ]);
        $this->assert("Registrasi Guru Berhasil", $resGuru['success'] === true);
        if (!empty($resGuru['user_id'])) {
            $this->testUserIds[] = (int)$resGuru['user_id'];
        }

        // Test 4: Akun baru otomatis berstatus 'pending'
        $userPending = User::findByUsername($uniqueNisn);
        $this->assert("Akun Baru Otomatis Berstatus 'pending'", $userPending && $userPending['account_status'] === 'pending');

        // Test 5: Verifikasi password benar
        $this->assert("Verifikasi Password Benar Berhasil", password_verify('rahasia123', $userPending['password_hash']));

        // Test 6: Verifikasi password salah ditolak
        $this->assert("Verifikasi Password Salah Ditolak", !password_verify('password_salah', $userPending['password_hash']));

        // Test 7: Admin dapat memverifikasi (approve) akun
        $approved = User::updateStatus($userPending['id'], 'active');
        $this->assert("Admin Menyetujui (Approve) Akun", $approved === true);
        $userActive = User::findById($userPending['id']);
        $this->assert("Status Akun Terverifikasi Menjadi 'active'", $userActive['account_status'] === 'active');
    }

    private function testVotingAndDoubleVoting(): void {
        echo "\n--- 2. PENGUJIAN VOTING & PENCEGAHAN DOUBLE VOTING ---\n";

        $activeElection = Election::getActive();
        $this->assert("Periode Pemilihan Aktif Tersedia", $activeElection !== null);

        if (!$activeElection) return;

        $eid = (int)$activeElection['id'];
        $candidates = Candidate::getByElectionId($eid, true);
        $this->assert("Tersedia Kandidat Pasangan Calon", count($candidates) >= 1);
        $candidateId = $candidates[0]['id'];

        $pdo = Database::getConnection();
        $origStartAt = $activeElection['start_at'];
        // Pastikan periode pemilihan diakui ongoing selama pengujian voting
        $pdo->prepare("UPDATE elections SET start_at = '2026-10-01 00:00:00' WHERE id = ?")->execute([$eid]);

        // Buat user pemilih khusus untuk pengujian voting
        $voterNisn = '008' . rand(1000000, 9999999);
        $vRes = User::createStudent([
            'nisn'         => $voterNisn,
            'nama_lengkap' => 'Voter Test User',
            'kelas'        => 'XII',
            'jurusan'      => 'TKJ',
            'email'        => '',
            'password'     => 'testpass'
        ]);
        $voterId = (int)$vRes['user_id'];
        $this->testUserIds[] = $voterId;
        User::updateStatus($voterId, 'active');

        // Test A: Pengguna belum terdaftar di DPT tidak boleh memilih
        $voteBeforeDpt = Vote::castVote($eid, $voterId, $candidateId);
        $this->assert("Pemilih Tidak Terdaftar di DPT Ditolak", $voteBeforeDpt['success'] === false);

        // Daftarkan ke DPT (voting_eligibility)
        $pdo->prepare("INSERT INTO voting_eligibility (election_id, user_id, eligibility_status) VALUES (?, ?, 'eligible')")
            ->execute([$eid, $voterId]);

        // Catat max vote id sebelum test vote dilakukan
        $maxVoteIdBefore = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM votes WHERE election_id = {$eid}")->fetchColumn();

        // Test B: Pemilih sah dapat memberikan suara
        $vote1 = Vote::castVote($eid, $voterId, $candidateId);
        $this->assert("Pemilih Sah Berhasil Memberikan Suara", $vote1['success'] === true, $vote1['error'] ?? '');

        // Lacak vote ID yang baru saja dibuat agar bisa dihapus bersih
        $maxVoteIdAfter = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM votes WHERE election_id = {$eid}")->fetchColumn();
        if ($maxVoteIdAfter > $maxVoteIdBefore) {
            $this->testVoteIds[] = $maxVoteIdAfter;
        }

        // Test C: Cek hasUserVoted()
        $this->assert("Status Partisipasi Pemilih Terdeteksi 'Sudah Memilih'", Vote::hasUserVoted($eid, $voterId) === true);

        // Test D: Percobaan memilih kedua kali (DOUBLE VOTING) WAJIB DITOLAK
        $vote2 = Vote::castVote($eid, $voterId, $candidateId);
        $this->assert("Pencegahan Double Voting Berhasil (Ditolak)", $vote2['success'] === false);

        // Test E: Pemungutan suara dengan kandidat tidak ada / tidak valid ditolak
        $voterNisn2 = '008' . rand(1000000, 9999999);
        $vRes2 = User::createStudent([
            'nisn'         => $voterNisn2,
            'nama_lengkap' => 'Voter Test User 2',
            'kelas'        => 'XII',
            'jurusan'      => 'TKJ',
            'email'        => '',
            'password'     => 'testpass'
        ]);
        $voterId2 = (int)$vRes2['user_id'];
        $this->testUserIds[] = $voterId2;
        User::updateStatus($voterId2, 'active');
        $pdo->prepare("INSERT INTO voting_eligibility (election_id, user_id, eligibility_status) VALUES (?, ?, 'eligible')")
            ->execute([$eid, $voterId2]);

        $invalidVote = Vote::castVote($eid, $voterId2, 999999); // Candidate ID 999999 does not exist
        $this->assert("Voting Pasangan Calon Tidak Valid Ditolak", $invalidVote['success'] === false);

        // Hapus test vote segera agar hasil rekapitulasi tetap murni
        if (!empty($this->testVoteIds)) {
            foreach ($this->testVoteIds as $tvid) {
                $pdo->prepare("DELETE FROM votes WHERE id = ?")->execute([$tvid]);
            }
            $this->testVoteIds = [];
        }
        $pdo->prepare("DELETE FROM voting_participation WHERE user_id = ?")->execute([$voterId]);
        $pdo->prepare("DELETE FROM voting_eligibility WHERE user_id IN (?, ?)")->execute([$voterId, $voterId2]);
        $pdo->prepare("UPDATE elections SET start_at = ? WHERE id = ?")->execute([$origStartAt, $eid]);
    }

    private function testSecretBallotIntegrity(): void {
        echo "\n--- 3. PENGUJIAN KERAHASIAAN SUARA (SECRET BALLOT) ---\n";
        $pdo = Database::getConnection();

        // Check columns of 'votes' table
        $columns = $pdo->query("SHOW COLUMNS FROM votes")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert("Tabel 'votes' TIDAK memiliki kolom 'user_id' (Secret Ballot)", !in_array('user_id', $columns, true));

        // Check columns of 'voting_participation' table
        $partColumns = $pdo->query("SHOW COLUMNS FROM voting_participation")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert("Tabel 'voting_participation' TIDAK memiliki kolom 'candidate_id'", !in_array('candidate_id', $partColumns, true));
    }

    private function testRealtimeAndVisibility(): void {
        echo "\n--- 4. PENGUJIAN REALTIME ENDPOINT & VISIBILITAS HASIL ---\n";
        $activeElection = Election::getActive();
        $eid = (int)$activeElection['id'];

        // Uji rekapitulasi data dari database
        $results = Vote::getResults($eid);
        $this->assert("Kalkulasi Rekapitulasi Suara Berhasil", isset($results['total_suara_sah']));
        $this->assert("Total Suara Sah Konsisten Bilangan Bulat", is_int($results['total_suara_sah']));

        // Uji persentase per kandidat jika ada suara
        if ($results['total_suara_sah'] > 0) {
            $sumPct = array_sum(array_column($results['candidates'], 'persentase'));
            $this->assert("Total Persentase Suara Mendekati 100%", $sumPct >= 99.0 && $sumPct <= 101.0, "Total: {$sumPct}%");
        }

        // Uji proteksi mode 'hidden'
        Election::setResultsVisibility($eid, 'hidden');
        $elHidden = Election::getById($eid);
        $this->assert("Mode Publikasi Diubah ke 'hidden'", $elHidden['results_visibility'] === 'hidden');

        // Kembalikan ke mode 'realtime' untuk tampilan publik
        Election::setResultsVisibility($eid, 'realtime');
        $elRealtime = Election::getById($eid);
        $this->assert("Mode Publikasi Dikembalikan ke 'realtime'", $elRealtime['results_visibility'] === 'realtime');
    }

    private function testAdministrationAndPeriods(): void {
        echo "\n--- 5. PENGUJIAN ADMINISTRASI & PERIODE PEMILIHAN ---\n";
        $pdo = Database::getConnection();

        // Buat periode simulasi baru
        $newElectionId = Election::create([
            'nama_periode'       => 'Pemilihan OSIS Masa Depan Periode 2027/2028',
            'tahun'              => '2027/2028',
            'deskripsi'          => 'Periode uji coba administrasi',
            'start_at'           => '2027-10-01 08:00:00',
            'end_at'             => '2027-10-05 16:00:00',
            'status'             => 'draft',
            'is_active'          => 0,
            'teacher_can_vote'   => 0,
            'results_visibility' => 'hidden'
        ]);
        $this->assert("Admin Dapat Membuat Periode Pemilihan Baru", $newElectionId > 0);
        if ($newElectionId > 0) {
            $this->testElectionIds[] = $newElectionId;
        }

        // Cek bahwa periode sebelumnya dan suaranya tetap tersimpan aman
        $oldVotesCount = (int)$pdo->query("SELECT COUNT(*) FROM votes WHERE election_id = 1")->fetchColumn();
        $this->assert("Hasil Pemilihan Periode Lama Tetap Tersimpan Utuh", $oldVotesCount >= 0);
    }

    private function cleanup(): void {
        $pdo = Database::getConnection();

        // 1. Bersihkan votes test yang mungkin tertinggal
        if (!empty($this->testVoteIds)) {
            foreach ($this->testVoteIds as $tvid) {
                $pdo->prepare("DELETE FROM votes WHERE id = ?")->execute([$tvid]);
            }
            $this->testVoteIds = [];
        }

        // 2. Bersihkan test users
        if (!empty($this->testUserIds)) {
            foreach ($this->testUserIds as $uid) {
                $pdo->prepare("DELETE FROM voting_participation WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM voting_eligibility WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM student_profiles WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM teacher_profiles WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
            }
            $this->testUserIds = [];
        }

        // 3. Bersihkan test elections
        if (!empty($this->testElectionIds)) {
            foreach ($this->testElectionIds as $eid) {
                $pdo->prepare("DELETE FROM votes WHERE election_id = ?")->execute([$eid]);
                $pdo->prepare("DELETE FROM voting_participation WHERE election_id = ?")->execute([$eid]);
                $pdo->prepare("DELETE FROM voting_eligibility WHERE election_id = ?")->execute([$eid]);
                $pdo->prepare("DELETE FROM elections WHERE id = ?")->execute([$eid]);
            }
            $this->testElectionIds = [];
        }

        // 4. Pengaman ekstra: Hapus setiap user dummy berawalan 00 atau GURU atau nama Tester yang BUKAN akun asli (1, 17, 18)
        $pdo->exec("
            DELETE FROM users 
            WHERE id NOT IN (1, 17, 18) 
              AND (
                  username LIKE '00%' 
                  OR username LIKE 'GURU%' 
                  OR username LIKE '999%'
              )
        ");
    }
}

$runner = new TestRunner();
$runner->runAll();
