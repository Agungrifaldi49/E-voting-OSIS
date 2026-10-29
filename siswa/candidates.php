<?php
// siswa/candidates.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';
require_once dirname(__DIR__) . '/app/Models/Candidate.php';
require_once dirname(__DIR__) . '/app/Models/Vote.php';

AuthHelper::requireRole(['siswa', 'guru']);
$user = AuthHelper::user();
$userId = (int)$user['id'];

$activeElection = Election::getActive();
$candidates = [];
$hasVoted = false;

if ($activeElection) {
    $candidates = Candidate::getByElectionId($activeElection['id'], true);
    $hasVoted = Vote::hasUserVoted($activeElection['id'], $userId);
}

// Helper untuk format teks poin-poin misi & program kerja
if (!function_exists('formatCandidatePoints')) {
    function formatCandidatePoints(?string $text): string {
        if (empty($text)) {
            return '<p class="text-muted fst-italic mb-0 small">Belum ada keterangan spesifik.</p>';
        }
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $html = '<ul class="list-unstyled mb-0 d-flex flex-column gap-2">';
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;
            if (preg_match('/^(\d+[\.\)]|[a-zA-Z][\.\)]|[\*\-\•])\s*(.*)$/u', $trimmed, $m)) {
                $marker = $m[1];
                $content = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $html .= '<li class="d-flex align-items-start gap-2">' .
                         '<span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 small fw-bold flex-shrink-0" style="font-size:0.75rem;">' . htmlspecialchars($marker, ENT_QUOTES, 'UTF-8') . '</span>' .
                         '<span class="text-dark small leading-relaxed">' . $content . '</span></li>';
            } else {
                $html .= '<li class="text-dark small leading-relaxed d-flex align-items-start gap-2">' .
                         '<i class="bi bi-check2 text-primary flex-shrink-0 mt-1"></i>' .
                         '<span>' . htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8') . '</span></li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }
}

$pageTitle = "Daftar Calon & Visi Misi - E-Voting OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php 
    if ($user['role'] === 'guru') {
        require_once BASE_PATH . '/app/Views/layouts/guru_sidebar.php';
    } else {
        require_once BASE_PATH . '/app/Views/layouts/siswa_sidebar.php';
    }
    ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1">Daftar Pasangan Calon OSIS</h3>
                <p class="text-muted small mb-0">Kenali gagasan, visi misi, dan program kerja unggulan calon ketua &amp; wakil ketua OSIS.</p>
            </div>
            <?php if (!$hasVoted): ?>
                <a href="<?= base_url('voting.php') ?>" class="btn btn-warning text-dark fw-bold btn-sm px-3 shadow-sm">
                    <i class="bi bi-check2-square me-1"></i> Buka Bilik Suara
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php foreach ($candidates as $cand): 
                $photoPasangan = !empty($cand['foto_pasangan']) ? asset('uploads/candidates/' . $cand['foto_pasangan']) : null;
                $photoKetua = !empty($cand['foto_ketua']) ? asset('uploads/candidates/' . $cand['foto_ketua']) : asset('assets/images/default-avatar.svg');
                $photoWakil = !empty($cand['foto_wakil']) ? asset('uploads/candidates/' . $cand['foto_wakil']) : asset('assets/images/default-avatar.svg');
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                    <div class="card-header bg-dark text-white p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark fs-6 fw-bold px-3 py-2 rounded-pill">
                                NO. URUT <?= sprintf('%02d', $cand['nomor_urut']) ?>
                            </span>
                            <h5 class="mb-0 fw-bold text-white ms-1">
                                <?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?> &amp; <?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                            </h5>
                        </div>
                        <?php if (!$hasVoted): ?>
                            <a href="<?= base_url('voting.php') ?>" class="btn btn-sm btn-gold-custom fw-bold px-3">
                                <i class="bi bi-check2-circle me-1"></i> Pilih Paslon Ini
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-3 p-md-4">
                        <div class="row g-4">
                            <!-- Photos -->
                            <div class="col-lg-4 col-md-5">
                                <div class="candidate-photo-wrapper rounded-3 shadow-sm overflow-hidden" style="min-height: 220px; max-height: 290px;">
                                    <?php if ($photoPasangan): ?>
                                        <img src="<?= $photoPasangan ?>" alt="Paslon" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'" style="object-position: top center; object-fit: cover; width: 100%; height: 100%;">
                                    <?php else: ?>
                                        <div class="dual-avatar-box h-100">
                                            <div class="avatar-half position-relative">
                                                <img src="<?= $photoKetua ?>" alt="Ketua" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'" style="object-position: top center; object-fit: cover; width: 100%; height: 100%;">
                                                <span class="avatar-side-tag">Calon Ketua</span>
                                            </div>
                                            <div class="avatar-half position-relative">
                                                <img src="<?= $photoWakil ?>" alt="Wakil" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'" style="object-position: top center; object-fit: cover; width: 100%; height: 100%;">
                                                <span class="avatar-side-tag">Calon Wakil</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="p-3 bg-light rounded-3 border mt-3 text-center">
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?> <span class="badge bg-white border text-dark ms-1"><?= htmlspecialchars($cand['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <small class="text-muted d-block my-1">&amp;</small>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?> <span class="badge bg-white border text-dark ms-1"><?= htmlspecialchars($cand['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                </div>
                            </div>

                            <!-- Visi, Misi, Proker -->
                            <div class="col-lg-8 col-md-7">
                                <div class="mb-3">
                                    <h6 class="fw-bold text-primary text-uppercase small mb-2">
                                        <i class="bi bi-eye-fill me-1"></i> Visi Pasangan Calon
                                    </h6>
                                    <div class="p-3 bg-light rounded-3 border text-dark small leading-relaxed fst-italic">
                                        "<?= nl2br(htmlspecialchars($cand['visi'], ENT_QUOTES, 'UTF-8')) ?>"
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="fw-bold text-primary text-uppercase small mb-2">
                                        <i class="bi bi-list-check me-1"></i> Misi Pasangan Calon
                                    </h6>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <?= formatCandidatePoints($cand['misi']) ?>
                                    </div>
                                </div>

                                <div>
                                    <h6 class="fw-bold text-primary text-uppercase small mb-2">
                                        <i class="bi bi-briefcase-fill me-1"></i> Program Kerja Unggulan
                                    </h6>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <?= formatCandidatePoints($cand['program_kerja']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
