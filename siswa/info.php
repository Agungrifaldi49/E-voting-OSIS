<?php
// siswa/info.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Models/Election.php';

AuthHelper::requireRole(['siswa', 'guru']);
$user = AuthHelper::user();
$activeElection = Election::getActive();

$pageTitle = "Informasi Pemilihan - E-Voting OSIS";
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

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: clamp(1.25rem, 4vw, 1.65rem);">Tata Cara &amp; Panduan Pemilu OSIS</h3>
            <p class="text-muted small mb-0">Petunjuk teknis pelaksanaan pemungutan suara elektronik di <?= htmlspecialchars(app_setting('school_name', SCHOOL_NAME), ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>

        <div class="row g-3 g-md-4">
            <div class="col-lg-8">
                <!-- 4 Langkah Mudah Memilih -->
                <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-phone-fill text-primary me-2"></i> 4 Langkah Mudah Memilih dari HP &amp; Laptop</h5>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border h-100">
                                <span class="badge bg-primary text-white fw-bold mb-2">Langkah 1</span>
                                <h6 class="fw-bold text-dark mb-1">Pelajari Visi &amp; Misi</h6>
                                <p class="text-muted small mb-0">Buka menu <strong>"Paslon"</strong> untuk membaca visi, misi, dan program kerja unggulan calon ketua &amp; wakil ketua.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border h-100">
                                <span class="badge bg-primary text-white fw-bold mb-2">Langkah 2</span>
                                <h6 class="fw-bold text-dark mb-1">Masuk Bilik Suara</h6>
                                <p class="text-muted small mb-0">Tekan tombol <strong>"Bilik Suara"</strong> di menu navigasi saat jadwal pemilihan berlangsung.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border h-100">
                                <span class="badge bg-warning text-dark fw-bold mb-2">Langkah 3</span>
                                <h6 class="fw-bold text-dark mb-1">Coblos / Pilih Paslon</h6>
                                <p class="text-muted small mb-0">Tekan tombol <strong>"PILIH"</strong> pada pasangan calon pilihan Anda, lalu periksa konfirmasi final sebelum suara dikirim.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border h-100">
                                <span class="badge bg-success text-white fw-bold mb-2">Langkah 4</span>
                                <h6 class="fw-bold text-dark mb-1">Simpan Bukti Partisipasi</h6>
                                <p class="text-muted small mb-0">Setelah suara masuk, Anda akan mendapatkan <strong>Sertifikat Digital Pemilih</strong> berstempel SAH.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ketentuan Pemilih -->
                <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-2"></i> Ketentuan Pemilih</h5>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small text-muted">
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
                            <span>Pemilih adalah seluruh siswa aktif serta dewan guru (sesuai kebijakan periode aktif) yang terdaftar di DPT resmi.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
                            <span>Setiap pemilih hanya memiliki <strong>1 (satu) hak suara sah</strong> yang dilindungi dari pencoblosan ganda (<em>double voting</em>).</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
                            <span>Pilihan bersifat <strong>LUBER JURDIL</strong> (Langsung, Umum, Bebas, Rahasia, Jujur, dan Adil).</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
                            <span>Sistem dapat diakses dengan nyaman melalui browser smartphone (Chrome, Safari), tablet, maupun laptop.</span>
                        </li>
                    </ul>
                </div>

                <!-- FAQ Siswa & Guru -->
                <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-question-circle text-warning me-2"></i> Pertanyaan yang Sering Diajukan (FAQ)</h5>
                    <div class="accordion accordion-flush" id="faqAccordionSiswa">
                        <div class="accordion-item border-bottom">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button collapsed fw-semibold text-dark px-0 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                    Apakah panitia atau pihak lain bisa melihat siapa yang saya pilih?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#faqAccordionSiswa">
                                <div class="accordion-body px-0 text-muted small">
                                    <strong>Tidak bisa sama sekali.</strong> Sistem menerapkan prinsip <em>Secret Ballot</em> (Asas Kerahasiaan Suara). Tabel kotak suara disimpan tanpa kaitan identitas pemilih, sehingga tidak ada yang dapat melacak paslon pilihan Anda.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-bottom">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed fw-semibold text-dark px-0 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                    Bagaimana jika saya lupa kata sandi (password) akun saya?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordionSiswa">
                                <div class="accordion-body px-0 text-muted small">
                                    Segera hubungi tim helpdesk panitia pemilihan di posko panitia sekolah atau melalui WhatsApp. Admin panitia dapat langsung mereset kata sandi akun Anda melalui panel Data Pemilih.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-bottom">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed fw-semibold text-dark px-0 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                    Bisakah saya mengubah pilihan setelah menekan tombol konfirmasi?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordionSiswa">
                                <div class="accordion-body px-0 text-muted small">
                                    Pilihan yang telah dikonfirmasi dan dimasukkan ke dalam bilik suara elektronik <strong>bersifat final dan tidak dapat diubah kembali</strong> demi menjaga integritas pemilu.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- KARTU JAMINAN KERAHASIAAN SUARA (HIGH-TECH & PRESTISIUS) -->
                <div class="security-guarantee-card mb-4">
                    <div class="watermark-shield">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>

                    <!-- Status Pill & Encryption Badge -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 position-relative" style="z-index: 2;">
                        <span class="badge rounded-pill px-2.5 py-1 fw-bold text-uppercase d-inline-flex align-items-center" style="font-size: 0.65rem; letter-spacing: 0.4px; background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.35);">
                            <span class="live-pulse me-1" style="width: 6px; height: 6px; background: #34d399;"></span>
                            Secret Ballot Enforced
                        </span>
                        <span class="badge rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.65rem; background: rgba(255, 255, 255, 0.1); color: #e2e8f0; border: 1px solid rgba(255, 255, 255, 0.15);">
                            <i class="bi bi-lock-fill text-warning me-1"></i> SHA-256
                        </span>
                    </div>

                    <!-- Header with Glowing Icon & Title -->
                    <div class="d-flex align-items-center gap-3 mb-3 position-relative" style="z-index: 2;">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(14, 165, 233, 0.25) 0%, rgba(16, 185, 129, 0.3) 100%); border: 1px solid rgba(56, 189, 248, 0.4); color: #38bdf8; font-size: 1.45rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-0" style="letter-spacing: -0.01em;">Jaminan Kerahasiaan Suara</h5>
                            <small class="text-white-50" style="font-size: 0.76rem;">Garansi Privasi &amp; Asas Rahasia Pemilu</small>
                        </div>
                    </div>

                    <!-- Visual Decoupled Architecture Diagram -->
                    <div class="p-2.5 rounded-3 mb-3 position-relative" style="z-index: 2; background: rgba(15, 23, 42, 0.5); border: 1px dashed rgba(56, 189, 248, 0.25);">
                        <div class="d-flex flex-wrap align-items-center justify-content-around gap-2 text-center" style="font-size: 0.72rem;">
                            <div class="px-2 py-1.5 rounded flex-grow-1" style="background: rgba(255, 255, 255, 0.08); color: #f1f5f9; min-width: 82px;">
                                <i class="bi bi-person-badge-fill d-block fs-6 text-info mb-0.5"></i>
                                <span>Akun Pemilih</span>
                            </div>
                            <div class="d-flex flex-column align-items-center px-1">
                                <span class="badge rounded-pill bg-danger bg-opacity-25 text-danger border border-danger border-opacity-30 px-2 py-0.5 mb-1" style="font-size: 0.6rem;">
                                    <i class="bi bi-slash-circle me-1"></i> Terputus
                                </span>
                                <span style="font-size: 0.62rem; color: #94a3b8;">Tanpa Relasi DB</span>
                            </div>
                            <div class="px-2 py-1.5 rounded flex-grow-1" style="background: rgba(255, 255, 255, 0.08); color: #f1f5f9; min-width: 82px;">
                                <i class="bi bi-box-seam-fill d-block fs-6 text-warning mb-0.5"></i>
                                <span>Kotak Suara</span>
                            </div>
                        </div>
                    </div>

                    <!-- Structured Security Pillars List -->
                    <div class="d-flex flex-column gap-2 mb-3 position-relative" style="z-index: 2;">
                        <div class="security-pillar-item">
                            <div class="security-pillar-icon" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8;">
                                <i class="bi bi-eye-slash-fill"></i>
                            </div>
                            <div>
                                <strong class="d-block text-white" style="font-size: 0.8rem;">Pemisahan Identitas (Zero-Trace)</strong>
                                <span class="text-white-50" style="font-size: 0.74rem; line-height: 1.35; display: block;">
                                    Tabel kehadiran pemilih terpisah permanen dari surat suara. Pilihan Anda 100% mustahil dilacak ke akun Anda.
                                </span>
                            </div>
                        </div>

                        <div class="security-pillar-item">
                            <div class="security-pillar-icon" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
                                <i class="bi bi-key-fill"></i>
                            </div>
                            <div>
                                <strong class="d-block text-white" style="font-size: 0.8rem;">Verifikasi Token Digital</strong>
                                <span class="text-white-50" style="font-size: 0.74rem; line-height: 1.35; display: block;">
                                    Sertifikat pemilih Anda memiliki kode SHA-256 anonim unik sebagai bukti keabsahan tanpa membocorkan paslon yang dicoblos.
                                </span>
                            </div>
                        </div>

                        <div class="security-pillar-item">
                            <div class="security-pillar-icon" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <strong class="d-block text-white" style="font-size: 0.8rem;">Asas LUBER JURDIL Mutlak</strong>
                                <span class="text-white-50" style="font-size: 0.74rem; line-height: 1.35; display: block;">
                                    Guru, panitia, kepala sekolah, dan tim teknis tidak memiliki akses untuk mengintip surat suara per individu.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Trust Seal Footer -->
                    <div class="pt-2.5 border-top border-white border-opacity-15 d-flex align-items-center justify-content-between position-relative" style="z-index: 2;">
                        <span class="d-inline-flex align-items-center gap-1.5 text-white" style="font-size: 0.72rem;">
                            <i class="bi bi-patch-check-fill text-warning"></i>
                            <span class="text-white-50">Legalitas:</span> <strong>LUBER JURDIL</strong>
                        </span>
                        <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(56, 189, 248, 0.18); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">
                            <i class="bi bi-shield-lock me-1"></i> TERLINDUNGI
                        </span>
                    </div>
                </div>

                <!-- KARTU BANTUAN PANITIA (HELPDESK RESMI) -->
                <div class="card border-0 shadow-sm rounded-4 p-4 position-relative overflow-hidden">
                    <div class="d-flex align-items-center gap-2.5 mb-2">
                        <div class="p-2.5 rounded-circle d-inline-flex flex-shrink-0" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
                            <i class="bi bi-headset fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Bantuan Panitia Pemilihan</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Posko Layanan &amp; Helpdesk Pemilu</small>
                        </div>
                    </div>
                    <p class="small text-muted mb-3 leading-relaxed" style="font-size: 0.8rem;">
                        Mengalami kendala saat login akun, verifikasi data, atau proses pemberian hak suara? Hubungi Panitia Pemilihan OSIS SMK Muthia Harapan Cicalengka.
                    </p>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', app_setting('helpdesk_phone', '081234567890')) ?>" target="_blank" class="btn btn-outline-success btn-sm w-100 fw-bold py-2 shadow-xs d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-whatsapp fs-6"></i> Hubungi Helpdesk via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </main>
    <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>
