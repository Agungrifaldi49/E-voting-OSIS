<?php
// app/Views/siswa/voting.php
// Bilik Suara Elektronik (Bilik Voting)
$pageTitle = "Bilik Suara Elektronik - Pemilihan OSIS";
require_once BASE_PATH . '/app/Views/layouts/header.php';
$currentUser = AuthHelper::user();
$dashboardUrl = base_url(($currentUser['role'] ?? 'siswa') . '/dashboard.php');
?>

<!-- Dedicated Voting Booth Navigation Bar (Bilik Suara) -->
<header class="bg-white border-bottom shadow-sm sticky-top py-2 px-3 px-md-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= app_logo() ?>" alt="Logo" class="rounded object-fit-contain bg-light p-1" style="width: 38px; height: 38px;">
            <div>
                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?></h6>
                <small class="text-primary fw-semibold"><i class="bi bi-shield-lock-fill me-1"></i>Bilik Suara Elektronik Resmi</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-md-block text-end">
                <div class="small fw-bold text-dark"><?= htmlspecialchars($currentUser['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <small class="text-muted text-uppercase"><?= htmlspecialchars($currentUser['role'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($currentUser['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
            </div>
            <a href="<?= $dashboardUrl ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Apakah Anda yakin ingin keluar dari bilik suara? Pilihan Anda belum tersimpan.')">
                <i class="bi bi-arrow-left me-1"></i> Batal &amp; Kembali
            </a>
        </div>
    </div>
</header>

<div class="container py-4">
    <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8 text-center">
            <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill text-uppercase mb-2">
                <i class="bi bi-shield-lock-fill me-1"></i> Bilik Suara Rahasia & Terverifikasi
            </span>
            <h2 class="display-6 fw-bold text-dark">Surat Suara Elektronik OSIS</h2>
            <p class="text-muted">
                Silakan tentukan pilihan Anda secara bijak, mandiri, dan bertanggung jawab. Pilihan Anda sepenuhnya rahasia dan dilindungi oleh sistem.
            </p>
            <div class="alert alert-warning d-inline-block py-2 px-3 small rounded-pill border-0 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Perhatian: Suara yang telah dikonfirmasi dan dikirim <strong>tidak dapat diubah kembali</strong>.
            </div>
        </div>
    </div>

    <!-- Candidate Cards for Voting -->
    <div class="row g-4 justify-content-center">
        <?php foreach ($candidates as $cand): 
            $photoPasangan = !empty($cand['foto_pasangan']) ? asset('uploads/candidates/' . $cand['foto_pasangan']) : null;
            $photoKetua = !empty($cand['foto_ketua']) ? asset('uploads/candidates/' . $cand['foto_ketua']) : asset('assets/images/default-avatar.svg');
            $photoWakil = !empty($cand['foto_wakil']) ? asset('uploads/candidates/' . $cand['foto_wakil']) : asset('assets/images/default-avatar.svg');
        ?>
        <div class="col-lg-5 col-md-6">
            <div class="candidate-card h-100 shadow-lg border-2 border-primary border-opacity-25" id="vote-card-<?= $cand['id'] ?>">
                <div class="candidate-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-warning text-dark text-uppercase fw-bold px-2 py-1 mb-1">Pilihan</span>
                        <h4 class="text-white mb-0">Pasangan No. <?= sprintf('%02d', $cand['nomor_urut']) ?></h4>
                    </div>
                    <div class="nomor-urut-badge">
                        <?= sprintf('%02d', $cand['nomor_urut']) ?>
                    </div>
                </div>

                <div class="candidate-photo-wrapper">
                    <?php if ($photoPasangan): ?>
                        <img src="<?= $photoPasangan ?>" alt="Paslon <?= $cand['nomor_urut'] ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'">
                    <?php else: ?>
                        <div class="dual-avatar-box">
                            <div class="avatar-half position-relative">
                                <img src="<?= $photoKetua ?>" alt="<?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'" style="object-position: top center; object-fit: cover;">
                                <span class="avatar-side-tag">Calon Ketua</span>
                            </div>
                            <div class="avatar-half position-relative">
                                <img src="<?= $photoWakil ?>" alt="<?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='<?= asset('assets/images/default-avatar.svg') ?>'" style="object-position: top center; object-fit: cover;">
                                <span class="avatar-side-tag">Calon Wakil</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="candidate-body">
                    <div class="text-center mb-3">
                        <h5 class="fw-bold text-dark mb-1">
                            <?= htmlspecialchars($cand['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>
                        </h5>
                        <small class="text-muted d-block mb-1">&amp;</small>
                        <h5 class="fw-bold text-dark mb-2">
                            <?= htmlspecialchars($cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                        </h5>
                        <span class="badge bg-secondary-subtle text-secondary">
                            <?= htmlspecialchars($cand['kelas_ketua'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($cand['kelas_wakil'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div class="vision-quote small flex-grow-1">
                        "<?= htmlspecialchars(mb_strimwidth($cand['visi'], 0, 140, '...'), ENT_QUOTES, 'UTF-8') ?>"
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary w-50 btn-detail-trigger"
                                data-id="<?= $cand['id'] ?>"
                                data-nomor="<?= sprintf('%02d', $cand['nomor_urut']) ?>"
                                data-names="<?= htmlspecialchars($cand['nama_ketua'] . ' & ' . $cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                data-visi="<?= htmlspecialchars($cand['visi'], ENT_QUOTES, 'UTF-8') ?>"
                                data-misi="<?= htmlspecialchars($cand['misi'], ENT_QUOTES, 'UTF-8') ?>"
                                data-proker="<?= htmlspecialchars($cand['program_kerja'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-info-circle me-1"></i> Visi Misi
                        </button>

                        <button type="button"
                                class="btn btn-gold-custom w-50 fw-bold btn-vote-trigger"
                                data-id="<?= $cand['id'] ?>"
                                data-nomor="<?= sprintf('%02d', $cand['nomor_urut']) ?>"
                                data-names="<?= htmlspecialchars($cand['nama_ketua'] . ' & ' . $cand['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>"
                                id="btn-vote-candidate-<?= $cand['id'] ?>">
                            <i class="bi bi-check2-circle me-1"></i> PILIH NO. <?= sprintf('%02d', $cand['nomor_urut']) ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL KONFIRMASI VOTING -->
<div class="modal fade" id="voteConfirmationModal" tabindex="-1" aria-labelledby="modalConfirmTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="modalConfirmTitle">
                    <i class="bi bi-question-circle text-warning me-2"></i> Konfirmasi Pilihan Anda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="p-3 rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex mb-3">
                    <i class="bi bi-envelope-paper-heart-fill display-4"></i>
                </div>

                <h5 class="fw-bold text-dark mb-2">
                    Apakah Anda yakin ingin memilih Pasangan Calon Nomor <span class="text-primary fs-4" id="confirm-candidate-number">01</span>?
                </h5>

                <div class="p-3 rounded-3 bg-light border my-3 fw-semibold text-dark" id="confirm-candidate-names">
                    Nama Pasangan Calon
                </div>

                <p class="small text-muted mb-0">
                    Setelah Anda menekan tombol <strong>"Ya, Saya Yakin"</strong>, suara Anda akan langsung dimasukkan ke dalam kotak suara digital dan tidak dapat diubah kembali.
                </p>

                <!-- Hidden Form to submit vote -->
                <form action="<?= base_url('api/vote.php') ?>" method="POST" id="form-actual-vote" class="d-none">
                    <?= CsrfHelper::getTokenField() ?>
                    <input type="hidden" name="election_id" value="<?= $activeElection['id'] ?>">
                    <input type="hidden" name="candidate_id" id="confirm-candidate-id" value="">
                </form>
            </div>
            <div class="modal-footer bg-light border-0 justify-content-center p-3 gap-2">
                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-bs-dismiss="modal" id="btn-cancel-vote">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-primary-custom px-4 fw-bold" id="btn-confirm-vote-submit" onclick="document.getElementById('form-actual-vote').submit();">
                    <i class="bi bi-check2-circle me-1"></i> Ya, Saya Yakin
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL VISI MISI -->
<div class="modal fade" id="candidateDetailModal" tabindex="-1" aria-labelledby="modalDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modalDetailTitle"><span id="modal-cand-title">Detail Calon</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <h6 class="fw-bold text-primary text-uppercase tracking-wide"><i class="bi bi-eye-fill me-2"></i>Visi</h6>
                    <div class="p-3 rounded bg-light border text-dark" id="modal-cand-visi"></div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-primary text-uppercase tracking-wide"><i class="bi bi-list-check me-2"></i>Misi</h6>
                    <div class="p-3 rounded bg-light border text-dark" id="modal-cand-misi"></div>
                </div>

                <div>
                    <h6 class="fw-bold text-primary text-uppercase tracking-wide"><i class="bi bi-briefcase-fill me-2"></i>Program Kerja Unggulan</h6>
                    <div class="p-3 rounded bg-light border text-dark" id="modal-cand-proker"></div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Dedicated Clean Voting Booth Footer -->
<footer class="bg-white border-top py-3 text-center text-muted small mt-5">
    <div class="container">
        &copy; <?= date('Y') ?> <strong><?= SCHOOL_NAME ?></strong> &bull; Bilik Suara Elektronik OSIS &bull; Dijamin Rahasia (LUBER JURDIL)
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle (Ensured loaded with safety check) -->
<script>
if (typeof bootstrap === 'undefined') {
    var bsScript = document.createElement('script');
    bsScript.src = "<?= asset('assets/js/bootstrap.bundle.min.js') ?>";
    bsScript.onerror = function() {
        var cdnScript = document.createElement('script');
        cdnScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
        document.head.appendChild(cdnScript);
    };
    document.head.appendChild(bsScript);
}
</script>
<!-- Main Interactive JS with cache busting -->
<script src="<?= asset('assets/js/main.js') ?>?v=<?= file_exists(PUBLIC_PATH . '/assets/js/main.js') ? filemtime(PUBLIC_PATH . '/assets/js/main.js') : '1.0.1' ?>"></script>
</body>
</html>
