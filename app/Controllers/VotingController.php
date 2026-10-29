<?php
// app/Controllers/VotingController.php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/CsrfHelper.php';
require_once __DIR__ . '/../Helpers/LogHelper.php';
require_once __DIR__ . '/../Models/Election.php';
require_once __DIR__ . '/../Models/Candidate.php';
require_once __DIR__ . '/../Models/Vote.php';

class VotingController {
    public static function showVotingPage(): void {
        AuthHelper::requireRole(['siswa', 'guru']);
        $user = AuthHelper::user();
        $userId = $user['id'];

        $activeElection = Election::getActive();
        if (!$activeElection) {
            set_flash('info', 'Saat ini belum ada periode pemilihan yang sedang aktif.');
            header("Location: " . base_url($user['role'] . '/dashboard.php'));
            exit;
        }

        // Check if role is guru and if teachers are allowed to vote
        if ($user['role'] === 'guru' && empty($activeElection['teacher_can_vote'])) {
            set_flash('error', 'Pada periode pemilihan ini, Dewan Guru tidak memiliki hak suara (hanya siswa yang berhak memilih).');
            header("Location: " . base_url('guru/dashboard.php'));
            exit;
        }

        // Check if user already voted
        $alreadyVoted = Vote::hasUserVoted($activeElection['id'], $userId);
        if ($alreadyVoted) {
            set_flash('info', 'Anda telah berhasil menggunakan hak pilih Anda pada pemilihan ini. Terima kasih atas partisipasi Anda!');
            header("Location: " . base_url($user['role'] . '/status.php'));
            exit;
        }

        // Check if user is eligible
        $isEligible = Vote::isUserEligible($activeElection['id'], $userId);
        if (!$isEligible) {
            set_flash('error', 'Akun Anda tidak terdaftar dalam Daftar Pemilih Tetap (DPT) untuk periode pemilihan ini. Silakan hubungi panitia pemilihan.');
            header("Location: " . base_url($user['role'] . '/dashboard.php'));
            exit;
        }

        // Check computed election status
        $status = Election::computeStatus($activeElection);
        if ($status !== 'ongoing') {
            if ($status === 'scheduled') {
                set_flash('warning', 'Pemilihan belum dibuka. Jadwal mulai: ' . date('d M Y H:i', strtotime($activeElection['start_at'])) . ' WIB.');
            } else {
                set_flash('error', 'Pemilihan telah berakhir atau ditutup.');
            }
            header("Location: " . base_url($user['role'] . '/dashboard.php'));
            exit;
        }

        $candidates = Candidate::getByElectionId($activeElection['id'], true);

        // Include voting view
        require_once BASE_PATH . '/app/Views/siswa/voting.php';
    }

    public static function submitVote(): void {
        AuthHelper::requireRole(['siswa', 'guru']);
        CsrfHelper::validateOrAbort();

        $user = AuthHelper::user();
        $userId = $user['id'];
        $electionId = (int)($_POST['election_id'] ?? 0);
        $candidateId = (int)($_POST['candidate_id'] ?? 0);

        if (!$electionId || !$candidateId) {
            set_flash('error', 'Pilihan calon tidak valid.');
            header("Location: " . base_url('voting.php'));
            exit;
        }

        $activeElection = Election::getActive();
        if (!$activeElection || (int)$activeElection['id'] !== $electionId) {
            set_flash('error', 'Periode pemilihan tidak cocok dengan pemilihan yang sedang aktif.');
            header("Location: " . base_url($user['role'] . '/dashboard.php'));
            exit;
        }

        // Execute atomic vote transaction
        $res = Vote::castVote($electionId, $userId, $candidateId);

        if ($res['success']) {
            set_flash('success', $res['message']);
            header("Location: " . base_url($user['role'] . '/status.php'));
            exit;
        } else {
            set_flash('error', $res['error']);
            header("Location: " . base_url('voting.php'));
            exit;
        }
    }
}
