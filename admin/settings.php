<?php
// admin/settings.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/LogHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/SettingHelper.php';
require_once dirname(__DIR__) . '/database/reset_for_production.php';

AuthHelper::requireRole('admin');
$pdo = Database::getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfHelper::validateOrAbort();
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'upload_logo') {
        if (!empty($_FILES['website_logo']['name'])) {
            $result = SettingHelper::uploadLogo($_FILES['website_logo']);
            if ($result['success']) {
                set_flash('success', 'Logo resmi website sekolah berhasil diperbarui.');
            } else {
                set_flash('error', $result['error'] ?? 'Gagal mengunggah logo.');
            }
        } else {
            set_flash('warning', 'Pilih file logo terlebih dahulu.');
        }
    } elseif ($formType === 'reset_logo') {
        SettingHelper::resetLogo();
        set_flash('info', 'Logo website berhasil direset ke logo bawaan sistem.');
    } elseif ($formType === 'general_settings') {
        SettingHelper::setMultiple([
            'school_name'   => trim($_POST['school_name'] ?? ''),
            'school_npsn'   => trim($_POST['school_npsn'] ?? ''),
            'app_title'     => trim($_POST['app_title'] ?? ''),
            'app_tagline'   => trim($_POST['app_tagline'] ?? ''),
            'academic_year' => trim($_POST['academic_year'] ?? '')
        ]);
        LogHelper::log('SETTINGS_GENERAL', "Admin memperbarui data identitas sekolah dan aplikasi.");
        set_flash('success', 'Identitas sekolah dan aplikasi berhasil disimpan.');
    } elseif ($formType === 'officials_settings') {
        SettingHelper::setMultiple([
            'kepala_sekolah_nama' => trim($_POST['kepala_sekolah_nama'] ?? ''),
            'kepala_sekolah_nip'  => trim($_POST['kepala_sekolah_nip'] ?? ''),
            'ketua_panitia_nama'  => trim($_POST['ketua_panitia_nama'] ?? ''),
            'ketua_panitia_nip'   => trim($_POST['ketua_panitia_nip'] ?? ''),
            'pembina_osis_nama'   => trim($_POST['pembina_osis_nama'] ?? '')
        ]);
        LogHelper::log('SETTINGS_OFFICIALS', "Admin memperbarui data pejabat penandatangan laporan.");
        set_flash('success', 'Data pejabat penandatangan laporan resmi berhasil diperbarui.');
    } elseif ($formType === 'contact_settings') {
        SettingHelper::setMultiple([
            'school_address'   => trim($_POST['school_address'] ?? ''),
            'school_phone'     => trim($_POST['school_phone'] ?? ''),
            'school_email'     => trim($_POST['school_email'] ?? ''),
            'contact_person'   => trim($_POST['contact_person'] ?? ''),
            'website_url'      => trim($_POST['website_url'] ?? ''),
            'social_instagram' => trim($_POST['social_instagram'] ?? ''),
            'social_youtube'   => trim($_POST['social_youtube'] ?? '')
        ]);
        LogHelper::log('SETTINGS_CONTACT', "Admin memperbarui kontak sekolah dan media sosial.");
        set_flash('success', 'Kontak dan media sosial sekolah berhasil disimpan.');
    } elseif ($formType === 'guidelines_settings') {
        SettingHelper::set('voting_guide', trim($_POST['voting_guide'] ?? ''));
        LogHelper::log('SETTINGS_GUIDE', "Admin memperbarui petunjuk pemilihan di bilik suara.");
        set_flash('success', 'Petunjuk dan tata tertib pemilihan berhasil diperbarui.');
    } elseif ($formType === 'add_announcement') {
        $title = trim($_POST['ann_title'] ?? '');
        $content = trim($_POST['ann_content'] ?? '');

        if ($title && $content) {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, is_active, created_at) VALUES (:title, :content, 1, NOW())");
            $stmt->execute([':title' => $title, ':content' => $content]);
            LogHelper::log('ANNOUNCEMENT_CREATED', "Admin membuat pengumuman: {$title}");
            set_flash('success', 'Pengumuman baru berhasil diterbitkan untuk pemilih.');
        } else {
            set_flash('error', 'Judul dan isi pengumuman wajib diisi.');
        }
    } elseif ($formType === 'toggle_announcement') {
        $annId = (int)($_POST['ann_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute([':id' => $annId]);
        set_flash('info', 'Status tayang pengumuman berhasil diubah.');
    } elseif ($formType === 'delete_announcement') {
        $annId = (int)($_POST['ann_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
        $stmt->execute([':id' => $annId]);
        LogHelper::log('ANNOUNCEMENT_DELETED', "Admin menghapus pengumuman ID: {$annId}");
        set_flash('warning', 'Pengumuman berhasil dihapus.');
    } elseif ($formType === 'homepage_settings') {
        SettingHelper::setMultiple([
            // Top Announcement Ticker
            'hero_announcement_enabled' => isset($_POST['hero_announcement_enabled']) ? '1' : '0',
            'hero_announcement_text'    => trim($_POST['hero_announcement_text'] ?? ''),

            // Hero Section
            'hero_badge_text'           => trim($_POST['hero_badge_text'] ?? ''),
            'hero_title'                => trim($_POST['hero_title'] ?? ''),
            'hero_subtitle'             => trim($_POST['hero_subtitle'] ?? ''),
            'hero_cta_vote_text'        => trim($_POST['hero_cta_vote_text'] ?? ''),
            'hero_cta_cand_text'        => trim($_POST['hero_cta_cand_text'] ?? ''),
            'hero_countdown_title'      => trim($_POST['hero_countdown_title'] ?? ''),
            'hero_countdown_desc'       => trim($_POST['hero_countdown_desc'] ?? ''),

            // Key Stats & Value Proposition (Cards 3 & 4)
            'stat_card_3_title'         => trim($_POST['stat_card_3_title'] ?? ''),
            'stat_card_3_desc'          => trim($_POST['stat_card_3_desc'] ?? ''),
            'stat_card_4_title'         => trim($_POST['stat_card_4_title'] ?? ''),
            'stat_card_4_desc'          => trim($_POST['stat_card_4_desc'] ?? ''),

            // Seksi Kandidat
            'kandidat_section_badge'    => trim($_POST['kandidat_section_badge'] ?? ''),
            'kandidat_section_title'    => trim($_POST['kandidat_section_title'] ?? ''),
            'kandidat_section_subtitle' => trim($_POST['kandidat_section_subtitle'] ?? ''),
            'kandidat_empty_msg'        => trim($_POST['kandidat_empty_msg'] ?? ''),

            // Seksi Agenda Pemilu
            'agenda_section_badge'      => trim($_POST['agenda_section_badge'] ?? ''),
            'agenda_section_title'      => trim($_POST['agenda_section_title'] ?? ''),
            'agenda_section_subtitle'   => trim($_POST['agenda_section_subtitle'] ?? ''),
            'agenda_step1_badge'        => trim($_POST['agenda_step1_badge'] ?? ''),
            'agenda_step1_title'        => trim($_POST['agenda_step1_title'] ?? ''),
            'agenda_step1_desc'         => trim($_POST['agenda_step1_desc'] ?? ''),
            'agenda_step2_badge'        => trim($_POST['agenda_step2_badge'] ?? ''),
            'agenda_step2_title'        => trim($_POST['agenda_step2_title'] ?? ''),
            'agenda_step2_desc'         => trim($_POST['agenda_step2_desc'] ?? ''),
            'agenda_step3_badge'        => trim($_POST['agenda_step3_badge'] ?? ''),
            'agenda_step3_title'        => trim($_POST['agenda_step3_title'] ?? ''),
            'agenda_step3_desc'         => trim($_POST['agenda_step3_desc'] ?? ''),

            // Seksi Tata Cara Memilih
            'guide_section_badge'       => trim($_POST['guide_section_badge'] ?? ''),
            'guide_section_title'       => trim($_POST['guide_section_title'] ?? ''),
            'guide_section_subtitle'    => trim($_POST['guide_section_subtitle'] ?? ''),
            'guide_step1_title'         => trim($_POST['guide_step1_title'] ?? ''),
            'guide_step1_desc'          => trim($_POST['guide_step1_desc'] ?? ''),
            'guide_step2_title'         => trim($_POST['guide_step2_title'] ?? ''),
            'guide_step2_desc'          => trim($_POST['guide_step2_desc'] ?? ''),
            'guide_step3_title'         => trim($_POST['guide_step3_title'] ?? ''),
            'guide_step3_desc'          => trim($_POST['guide_step3_desc'] ?? ''),
            'guide_step4_title'         => trim($_POST['guide_step4_title'] ?? ''),
            'guide_step4_desc'          => trim($_POST['guide_step4_desc'] ?? ''),

            // Seksi Hasil Rekapitulasi
            'hasil_section_badge'       => trim($_POST['hasil_section_badge'] ?? ''),
            'hasil_section_title'       => trim($_POST['hasil_section_title'] ?? ''),
            'hasil_section_subtitle'    => trim($_POST['hasil_section_subtitle'] ?? ''),
            'hasil_hidden_title'        => trim($_POST['hasil_hidden_title'] ?? ''),
            'hasil_hidden_desc'         => trim($_POST['hasil_hidden_desc'] ?? ''),
            'hasil_hidden_note'         => trim($_POST['hasil_hidden_note'] ?? ''),

            // Seksi FAQ & Helpdesk
            'faq_section_badge'         => trim($_POST['faq_section_badge'] ?? ''),
            'faq_section_title'         => trim($_POST['faq_section_title'] ?? ''),
            'faq_section_subtitle'      => trim($_POST['faq_section_subtitle'] ?? ''),
            'faq1_q'                    => trim($_POST['faq1_q'] ?? ''),
            'faq1_a'                    => trim($_POST['faq1_a'] ?? ''),
            'faq2_q'                    => trim($_POST['faq2_q'] ?? ''),
            'faq2_a'                    => trim($_POST['faq2_a'] ?? ''),
            'faq3_q'                    => trim($_POST['faq3_q'] ?? ''),
            'faq3_a'                    => trim($_POST['faq3_a'] ?? ''),
            'helpdesk_title'            => trim($_POST['helpdesk_title'] ?? ''),
            'helpdesk_desc'             => trim($_POST['helpdesk_desc'] ?? ''),
            'helpdesk_phone'            => trim($_POST['helpdesk_phone'] ?? ''),
            'helpdesk_hours'            => trim($_POST['helpdesk_hours'] ?? '')
        ]);
        LogHelper::log('SETTINGS_HOMEPAGE', "Admin memperbarui seluruh konten dan tampilan halaman beranda (Index).");
        set_flash('success', 'Seluruh pengaturan tampilan halaman beranda (Index) berhasil disimpan.');
    } elseif ($formType === 'system_reset_production') {
        $adminPassword = $_POST['admin_password'] ?? '';
        $user = AuthHelper::user();

        // Verify admin password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);
        $currentHash = $stmt->fetchColumn();

        if (!password_verify($adminPassword, $currentHash)) {
            set_flash('error', 'Kata sandi Administrator salah! Pembersihan sistem dibatalkan demi keamanan.');
        } else {
            $options = [
                'clear_registry'   => isset($_POST['clear_registry']),
                'clear_candidates' => isset($_POST['clear_candidates']),
                'clear_elections'  => isset($_POST['clear_elections']),
                'clear_logs'       => isset($_POST['clear_logs'])
            ];
            $result = SystemResetHelper::executeReset($options);
            if ($result['success']) {
                set_flash('success', $result['message']);
            } else {
                set_flash('error', $result['error']);
            }
        }
    }

    $activeTab = $_POST['active_tab'] ?? 'tab-general';
    header("Location: " . base_url('admin/settings.php#' . $activeTab));
    exit;
}

// Fetch current settings & announcements
$settings = SettingHelper::getAll();
$currentLogo = SettingHelper::getLogoUrl();
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY id DESC")->fetchAll();

$pageTitle = "Pengaturan Website Lengkap - Panel Admin";
require_once BASE_PATH . '/app/Views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>
    <?php require_once BASE_PATH . '/app/Views/layouts/admin_sidebar.php'; ?>

    <div class="dashboard-main-area">
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_topbar.php'; ?>
        <main class="dashboard-content" id="main-content">
        <?php require_once BASE_PATH . '/app/Views/layouts/alerts.php'; ?>

        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1">Pengaturan Website &amp; Konfigurasi Sistem</h3>
                <p class="text-muted small mb-0">Kelola logo resmi, identitas sekolah, pejabat penandatangan, kontak, dan pengumuman pemilu OSIS.</p>
            </div>
            <a href="<?= base_url('index.php') ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Pratinjau Halaman Publik
            </a>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills nav-fill bg-white p-2 rounded-4 shadow-sm mb-4 border" id="settingsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="tab-homepage-btn" data-bs-toggle="tab" data-bs-target="#tab-homepage" type="button" role="tab" aria-selected="true">
                    <i class="bi bi-window-fullscreen me-1 text-warning"></i> Kontrol Beranda (Index)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-building me-1"></i> Logo &amp; Identitas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-officials-btn" data-bs-toggle="tab" data-bs-target="#tab-officials" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-pen-fill me-1"></i> Pejabat Laporan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-contact-btn" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-geo-alt-fill me-1"></i> Kontak &amp; Medsos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-guide-btn" data-bs-toggle="tab" data-bs-target="#tab-guide" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-card-text me-1"></i> Aturan Pemilihan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-ann-btn" data-bs-toggle="tab" data-bs-target="#tab-ann" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-megaphone-fill me-1"></i> Pengumuman Panitia
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-danger" id="tab-maintenance-btn" data-bs-toggle="tab" data-bs-target="#tab-maintenance" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-shield-exclamation me-1"></i> Pembersihan &amp; Go-Live
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="settingsTabContent">
            
            <!-- 0. TAB: KONTROL BERANDA (INDEX PUBLIK) -->
            <div class="tab-pane fade show active" id="tab-homepage" role="tabpanel">
                <form action="<?= base_url('admin/settings.php') ?>" method="POST" id="form-homepage-settings">
                    <?= CsrfHelper::getTokenField() ?>
                    <input type="hidden" name="form_type" value="homepage_settings">
                    <input type="hidden" name="active_tab" value="tab-homepage">

                    <!-- Top Studio Header Banner -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-white position-relative overflow-hidden" 
                         style="background: linear-gradient(135deg, #091224 0%, #1e3a8a 50%, #0284c7 100%);">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative" style="z-index: 2;">
                            <div>
                                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-15 border border-white border-opacity-25 text-warning small fw-bold mb-2">
                                    <i class="bi bi-magic me-1"></i> Studio Visual &amp; Kontrol Beranda
                                </div>
                                <h4 class="fw-bold mb-1 text-white">Pusat Kendali Tampilan Beranda (Index)</h4>
                                <p class="mb-0 text-white-50 small" style="max-width: 680px;">
                                    Atur seluruh teks, judul, pengumuman darurat, kartu statistik, jadwal tahapan, panduan memilih, FAQ, dan segel suara di beranda secara terstruktur dan terpusat.
                                </p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= base_url('index.php') ?>" target="_blank" class="btn btn-warning text-dark fw-bold btn-sm px-3 shadow-sm">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Pratinjau Beranda
                                </a>
                                <button type="submit" class="btn btn-light text-primary fw-bold btn-sm px-3 shadow-sm">
                                    <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </div>
                        <div style="position: absolute; right: -30px; top: -30px; width: 160px; height: 160px; background: rgba(245, 158, 11, 0.15); border-radius: 50%; filter: blur(40px); pointer-events: none;"></div>
                    </div>

                    <!-- 2-Column Studio Grid: Left Quick Navigation + Right Studio Cards -->
                    <div class="row g-4">
                        
                        <!-- LEFT COLUMN: Sticky Section Navigator -->
                        <div class="col-lg-3">
                            <div class="studio-nav-wrapper">
                                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                                    <div class="d-flex justify-content-between align-items-center px-2 mb-2">
                                        <span class="small fw-bold text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.8px;">
                                            <i class="bi bi-list-ul me-1"></i> Navigasi Seksi
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size: 0.65rem;">8 Seksi</span>
                                    </div>
                                    <nav class="studio-quick-nav">
                                        <a href="#sec-ticker" class="studio-nav-btn active">
                                            <span class="studio-nav-icon"><i class="bi bi-megaphone-fill text-warning"></i></span>
                                            <span class="text-truncate">1. Top Ticker</span>
                                        </a>
                                        <a href="#sec-hero" class="studio-nav-btn">
                                            <span class="studio-nav-icon"><i class="bi bi-stars text-primary"></i></span>
                                            <span class="text-truncate">2. Hero &amp; Countdown</span>
                                        </a>
                                        <a href="#sec-stats" class="studio-nav-btn">
                                            <span class="studio-nav-icon"><i class="bi bi-bar-chart-fill text-success"></i></span>
                                            <span class="text-truncate">3. Nilai &amp; Statistik</span>
                                        </a>
                                        <a href="#sec-paslon" class="studio-nav-btn">
                                            <span class="studio-nav-icon"><i class="bi bi-people-fill text-info"></i></span>
                                            <span class="text-truncate">4. Pasangan Calon</span>
                                        </a>
                                        <a href="#sec-agenda" class="studio-nav-btn">
                                            <span class="studio-nav-icon"><i class="bi bi-calendar-event-fill text-secondary"></i></span>
                                            <span class="text-truncate">5. Agenda Pemilu</span>
                                        </a>
                                        <a href="#sec-guide" class="studio-nav-btn">
                                            <span class="studio-nav-icon"><i class="bi bi-card-checklist text-primary"></i></span>
                                            <span class="text-truncate">6. Panduan Memilih</span>
                                        </a>
                                        <a href="#sec-results" class="studio-nav-btn">
                                            <span class="studio-nav-icon"><i class="bi bi-pie-chart-fill text-success"></i></span>
                                            <span class="text-truncate">7. Hasil &amp; Segel Suara</span>
                                        </a>
                                        <a href="#sec-faq" class="studio-nav-btn">
                                            <span class="studio-nav-icon"><i class="bi bi-question-circle-fill text-warning"></i></span>
                                            <span class="text-truncate">8. FAQ &amp; Bantuan</span>
                                        </a>
                                    </nav>
                                </div>

                                <!-- Quick Actions Widget -->
                                <div class="card border-0 shadow-sm rounded-4 p-3 bg-light text-center border">
                                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2 text-primary fw-semibold small">
                                        <span class="live-pulse"></span> Sinkronisasi Langsung
                                    </div>
                                    <p class="text-muted small mb-3" style="font-size: 0.78rem;">Setiap perubahan teks yang disimpan langsung tampil di halaman depan tanpa restart sistem.</p>
                                    <button type="submit" class="btn btn-primary-custom btn-sm w-100 fw-bold shadow-sm py-2">
                                        <i class="bi bi-check2-circle me-1"></i> Simpan Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Modern Studio Form Cards -->
                        <div class="col-lg-9">

                            <!-- 1. PENGUMUMAN BERJALAN (URGENT TICKER) -->
                            <div class="studio-card mb-4" id="sec-ticker">
                                <div class="studio-card-header bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-warning-subtle text-warning rounded-3"><i class="bi bi-megaphone-fill fs-5"></i></div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">1. Pengumuman Darurat Berjalan (Top Ticker Banner)</h5>
                                            <small class="text-muted">Pita pengumuman darurat di bagian paling atas beranda.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning-subtle text-dark border border-warning border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-pin-angle me-1"></i> Paling Atas Beranda
                                    </span>
                                </div>
                                <div class="studio-card-body">
                                    <div class="p-3 bg-light rounded-4 border mb-3">
                                        <div class="form-check form-switch fs-6 mb-0 d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-check-label fw-bold text-dark d-block" for="hero_announcement_enabled">Status Bar Pengumuman Darurat</label>
                                                <small class="text-muted">Aktifkan untuk menampilkan pita berjalan berwarna emas di atas navigasi utama.</small>
                                            </div>
                                            <input class="form-check-input ms-3" type="checkbox" role="switch" id="hero_announcement_enabled" name="hero_announcement_enabled" value="1" <?= SettingHelper::get('hero_announcement_enabled', '1') === '1' ? 'checked' : '' ?> style="width: 2.8em; height: 1.4em;">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="hero_announcement_text" class="form-label small fw-semibold text-dark">
                                            <i class="bi bi-chat-left-text me-1 text-warning"></i> Isi Pesan Pengumuman Penting
                                        </label>
                                        <textarea class="form-control" id="hero_announcement_text" name="hero_announcement_text" rows="2" placeholder="Contoh: Pengumuman Resmi: Pastikan seluruh siswa dan guru telah memeriksa status verifikasi DPT sebelum hari pemilihan tiba."><?= htmlspecialchars(SettingHelper::get('hero_announcement_text', 'Pengumuman Resmi: Pastikan seluruh siswa dan guru telah memeriksa status verifikasi DPT sebelum hari pemilihan tiba.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                        <div class="form-text text-muted small mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Jika dikosongkan, sistem secara otomatis mengambil judul pengumuman aktif terbaru dari tabel panitia.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. HERO SECTION -->
                            <div class="studio-card mb-4" id="sec-hero">
                                <div class="studio-card-header bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-primary-subtle text-primary rounded-3"><i class="bi bi-stars fs-5"></i></div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">2. Hero Section (Header Utama &amp; Hitung Mundur)</h5>
                                            <small class="text-muted">Tampilan visual pembuka yang pertama kali dilihat pengunjung.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-window-fullscreen me-1"></i> Layar Utama
                                    </span>
                                </div>
                                <div class="studio-card-body">
                                    
                                    <!-- Sub-Group A: Teks Utama -->
                                    <div class="mb-4 pb-3 border-bottom">
                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-type-h1 me-2 text-primary"></i>A. Teks Utama &amp; Badge Hero</h6>
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label for="hero_badge_text" class="form-label small fw-semibold text-dark">Teks Badge Hero</label>
                                                <input type="text" class="form-control" id="hero_badge_text" name="hero_badge_text" value="<?= htmlspecialchars(SettingHelper::get('hero_badge_text', 'Pesta Demokrasi Siswa • Asas Luber Jurdil'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Pesta Demokrasi Siswa • Asas Luber Jurdil">
                                            </div>
                                            <div class="col-md-7">
                                                <label for="hero_title" class="form-label small fw-semibold text-dark">Judul Utama Hero</label>
                                                <input type="text" class="form-control" id="hero_title" name="hero_title" value="<?= htmlspecialchars(SettingHelper::get('hero_title', 'PEMILIHAN KETUA & WAKIL KETUA OSIS'), ENT_QUOTES, 'UTF-8') ?>" placeholder="PEMILIHAN KETUA & WAKIL KETUA OSIS">
                                            </div>
                                            <div class="col-12">
                                                <label for="hero_subtitle" class="form-label small fw-semibold text-dark">Subjudul / Deskripsi Sambutan</label>
                                                <textarea class="form-control" id="hero_subtitle" name="hero_subtitle" rows="2"><?= htmlspecialchars(SettingHelper::get('hero_subtitle', 'Kenali calon pemimpinmu, pelajari visi dan misinya, dan gunakan hak pilihmu untuk menentukan masa depan OSIS SMK Muthia Harapan Cicalengka.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sub-Group B: Tombol CTA -->
                                    <div class="mb-4 pb-3 border-bottom">
                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cursor-fill me-2 text-warning"></i>B. Tombol Ajakan Bertindak (Call-to-Action)</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="hero_cta_vote_text" class="form-label small fw-semibold text-dark">Label Tombol Masuk Bilik Suara</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="bi bi-check2-square text-warning"></i></span>
                                                    <input type="text" class="form-control" id="hero_cta_vote_text" name="hero_cta_vote_text" value="<?= htmlspecialchars(SettingHelper::get('hero_cta_vote_text', 'Masuk Bilik Suara'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="hero_cta_cand_text" class="form-label small fw-semibold text-dark">Label Tombol Lihat Paslon</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="bi bi-people-fill text-primary"></i></span>
                                                    <input type="text" class="form-control" id="hero_cta_cand_text" name="hero_cta_cand_text" value="<?= htmlspecialchars(SettingHelper::get('hero_cta_cand_text', 'Lihat Pasangan Calon'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sub-Group C: Kotak Hitung Mundur -->
                                    <div>
                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-stopwatch me-2 text-success"></i>C. Kotak Hitung Mundur (Countdown Glass)</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="hero_countdown_title" class="form-label small fw-semibold text-dark">Judul Kotak Hitung Mundur</label>
                                                <input type="text" class="form-control" id="hero_countdown_title" name="hero_countdown_title" value="<?= htmlspecialchars(SettingHelper::get('hero_countdown_title', 'Hitung Mundur Pemilihan'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="hero_countdown_desc" class="form-label small fw-semibold text-dark">Catatan Kaki Kotak Hitung Mundur</label>
                                                <input type="text" class="form-control" id="hero_countdown_desc" name="hero_countdown_desc" value="<?= htmlspecialchars(SettingHelper::get('hero_countdown_desc', 'Pastikan akun Anda telah diverifikasi oleh Panitia Pemilihan sebelum waktu pemilihan berakhir.'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- 3. KARTU STATISTIK & NILAI INTEGRITAS -->
                            <div class="studio-card mb-4" id="sec-stats">
                                <div class="studio-card-header bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-success-subtle text-success rounded-3"><i class="bi bi-bar-chart-fill fs-5"></i></div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">3. Empat Kartu Statistik &amp; Nilai Integritas</h5>
                                            <small class="text-muted">Kartu 1 (Total DPT) &amp; Kartu 2 (Paslon Resmi) otomatis terhitung dari database.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-grid-fill me-1"></i> 4 Kartu KPI
                                    </span>
                                </div>
                                <div class="studio-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="studio-step-box border-start border-4 border-primary h-100">
                                                <div class="d-flex align-items-center gap-2 mb-3">
                                                    <span class="badge bg-primary text-white fw-bold px-2 py-1">Kartu 3</span>
                                                    <h6 class="fw-bold text-dark mb-0">Prinsip Asas Pemilihan</h6>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="stat_card_3_title" class="form-label small fw-semibold text-dark">Judul Kartu 3</label>
                                                    <input type="text" class="form-control" id="stat_card_3_title" name="stat_card_3_title" value="<?= htmlspecialchars(SettingHelper::get('stat_card_3_title', 'Asas LUBER JURDIL'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div>
                                                    <label for="stat_card_3_desc" class="form-label small fw-semibold text-dark">Keterangan Singkat</label>
                                                    <input type="text" class="form-control" id="stat_card_3_desc" name="stat_card_3_desc" value="<?= htmlspecialchars(SettingHelper::get('stat_card_3_desc', 'Langsung, Umum, Bebas, Rahasia, Jujur & Adil'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="studio-step-box border-start border-4 border-success h-100">
                                                <div class="d-flex align-items-center gap-2 mb-3">
                                                    <span class="badge bg-success text-white fw-bold px-2 py-1">Kartu 4</span>
                                                    <h6 class="fw-bold text-dark mb-0">Keamanan &amp; Kerahasiaan</h6>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="stat_card_4_title" class="form-label small fw-semibold text-dark">Judul Kartu 4</label>
                                                    <input type="text" class="form-control" id="stat_card_4_title" name="stat_card_4_title" value="<?= htmlspecialchars(SettingHelper::get('stat_card_4_title', 'Kerahasiaan Terjamin'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div>
                                                    <label for="stat_card_4_desc" class="form-label small fw-semibold text-dark">Keterangan Singkat</label>
                                                    <input type="text" class="form-control" id="stat_card_4_desc" name="stat_card_4_desc" value="<?= htmlspecialchars(SettingHelper::get('stat_card_4_desc', 'Secret Ballot 100% Enkripsi Suara Terlindungi'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. SEKSI PASANGAN CALON (KANDIDAT) -->
                            <div class="studio-card mb-4" id="sec-paslon">
                                <div class="studio-card-header bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-info-subtle text-info rounded-3"><i class="bi bi-people-fill fs-5"></i></div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">4. Seksi Pasangan Calon (Kandidat)</h5>
                                            <small class="text-muted">Header judul dan pesan penampung pada daftar calon ketua &amp; wakil OSIS.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-info-subtle text-info border border-info border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-person-badge me-1"></i> Profil Paslon
                                    </span>
                                </div>
                                <div class="studio-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="kandidat_section_badge" class="form-label small fw-semibold text-dark">Badge Seksi</label>
                                            <input type="text" class="form-control" id="kandidat_section_badge" name="kandidat_section_badge" value="<?= htmlspecialchars(SettingHelper::get('kandidat_section_badge', 'Kandidat Pemimpin'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Kandidat Pemimpin">
                                        </div>
                                        <div class="col-md-8">
                                            <label for="kandidat_section_title" class="form-label small fw-semibold text-dark">Judul Utama Seksi</label>
                                            <input type="text" class="form-control" id="kandidat_section_title" name="kandidat_section_title" value="<?= htmlspecialchars(SettingHelper::get('kandidat_section_title', 'Daftar Pasangan Calon Ketua & Wakil Ketua OSIS'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label for="kandidat_section_subtitle" class="form-label small fw-semibold text-dark">Subjudul / Deskripsi Seksi</label>
                                            <input type="text" class="form-control" id="kandidat_section_subtitle" name="kandidat_section_subtitle" value="<?= htmlspecialchars(SettingHelper::get('kandidat_section_subtitle', 'Pelajari profil, rekam jejak, visi, misi, serta program kerja unggulan sebelum menentukan pilihan terbaik Anda.'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label for="kandidat_empty_msg" class="form-label small fw-semibold text-dark">Pesan Saat Belum Ada Paslon yang Dipublikasikan</label>
                                            <input type="text" class="form-control" id="kandidat_empty_msg" name="kandidat_empty_msg" value="<?= htmlspecialchars(SettingHelper::get('kandidat_empty_msg', 'Belum ada pasangan calon yang dipublikasikan oleh panitia pemilihan untuk periode ini.'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. SEKSI AGENDA & TAHAPAN PEMILU -->
                            <div class="studio-card mb-4" id="sec-agenda">
                                <div class="studio-card-header bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-secondary-subtle text-secondary rounded-3"><i class="bi bi-calendar-event-fill fs-5"></i></div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">5. Seksi Agenda &amp; Tahapan Pelaksanaan Pemilu</h5>
                                            <small class="text-muted">Jadwal resmi 3 langkah pelaksanaan pemilu OSIS di beranda.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-clock-history me-1"></i> Timeline 3 Tahap
                                    </span>
                                </div>
                                <div class="studio-card-body">
                                    <div class="row g-3 mb-4 pb-3 border-bottom">
                                        <div class="col-md-4">
                                            <label for="agenda_section_badge" class="form-label small fw-semibold text-dark">Badge Seksi Agenda</label>
                                            <input type="text" class="form-control" id="agenda_section_badge" name="agenda_section_badge" value="<?= htmlspecialchars(SettingHelper::get('agenda_section_badge', 'Agenda Resmi'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <label for="agenda_section_title" class="form-label small fw-semibold text-dark">Judul Utama Seksi Agenda</label>
                                            <input type="text" class="form-control" id="agenda_section_title" name="agenda_section_title" value="<?= htmlspecialchars(SettingHelper::get('agenda_section_title', 'Jadwal & Tahapan Pemilihan OSIS'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label for="agenda_section_subtitle" class="form-label small fw-semibold text-dark">Subjudul Seksi Agenda</label>
                                            <input type="text" class="form-control" id="agenda_section_subtitle" name="agenda_section_subtitle" value="<?= htmlspecialchars(SettingHelper::get('agenda_section_subtitle', 'Ikuti seluruh tahapan pesta demokrasi sekolah sesuai dengan jadwal yang telah ditetapkan panitia.'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>

                                    <!-- 3 Steps Grid -->
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="studio-step-box border-top border-3 border-primary h-100">
                                                <span class="badge bg-primary text-white fw-bold mb-2">Tahap 01</span>
                                                <div class="mb-2">
                                                    <label class="form-label text-xs fw-semibold">Badge Tahap 1</label>
                                                    <input type="text" class="form-control form-control-sm" name="agenda_step1_badge" value="<?= htmlspecialchars(SettingHelper::get('agenda_step1_badge', 'Tahap 1'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label text-xs fw-semibold">Judul Tahap 1</label>
                                                    <input type="text" class="form-control form-control-sm" name="agenda_step1_title" value="<?= htmlspecialchars(SettingHelper::get('agenda_step1_title', 'Registrasi & Verifikasi Pemilih'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div>
                                                    <label class="form-label text-xs fw-semibold">Deskripsi Tahap 1</label>
                                                    <textarea class="form-control form-control-sm" name="agenda_step1_desc" rows="3"><?= htmlspecialchars(SettingHelper::get('agenda_step1_desc', 'Siswa dan Dewan Guru mendaftarkan akun di portal E-Voting dan diverifikasi langsung oleh admin panitia pemilihan sekolah.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="studio-step-box border-top border-3 border-warning h-100">
                                                <span class="badge bg-warning text-dark fw-bold mb-2">Tahap 02</span>
                                                <div class="mb-2">
                                                    <label class="form-label text-xs fw-semibold">Badge Tahap 2</label>
                                                    <input type="text" class="form-control form-control-sm" name="agenda_step2_badge" value="<?= htmlspecialchars(SettingHelper::get('agenda_step2_badge', 'Tahap 2'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label text-xs fw-semibold">Judul Tahap 2</label>
                                                    <input type="text" class="form-control form-control-sm" name="agenda_step2_title" value="<?= htmlspecialchars(SettingHelper::get('agenda_step2_title', 'Pemungutan Suara (E-Voting)'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div>
                                                    <label class="form-label text-xs fw-semibold">Deskripsi Tahap 2</label>
                                                    <textarea class="form-control form-control-sm" name="agenda_step2_desc" rows="3"><?= htmlspecialchars(SettingHelper::get('agenda_step2_desc', 'Pemilih yang terdaftar dalam DPT menggunakan hak pilihnya di bilik suara elektronik secara rahasia, aman, dan tanpa paksaan.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="studio-step-box border-top border-3 border-dark h-100">
                                                <span class="badge bg-dark text-white fw-bold mb-2">Tahap 03</span>
                                                <div class="mb-2">
                                                    <label class="form-label text-xs fw-semibold">Badge Tahap 3</label>
                                                    <input type="text" class="form-control form-control-sm" name="agenda_step3_badge" value="<?= htmlspecialchars(SettingHelper::get('agenda_step3_badge', 'Tahap 3'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label text-xs fw-semibold">Judul Tahap 3</label>
                                                    <input type="text" class="form-control form-control-sm" name="agenda_step3_title" value="<?= htmlspecialchars(SettingHelper::get('agenda_step3_title', 'Rekapitulasi & Penetapan Paslon'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div>
                                                    <label class="form-label text-xs fw-semibold">Deskripsi Tahap 3</label>
                                                    <textarea class="form-control form-control-sm" name="agenda_step3_desc" rows="3"><?= htmlspecialchars(SettingHelper::get('agenda_step3_desc', 'Penetapan dan pengumuman resmi pasangan calon terpilih Ketua dan Wakil Ketua OSIS berdasarkan perolehan suara terbanyak yang sah.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 6. SEKSI PANDUAN TATA CARA MEMILIH (4 LANGKAH) -->
                            <div class="studio-card mb-4" id="sec-guide">
                                <div class="studio-card-header bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-primary-subtle text-primary rounded-3"><i class="bi bi-card-checklist fs-5"></i></div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">6. Seksi Panduan Tata Cara Memilih (4 Langkah Visual)</h5>
                                            <small class="text-muted">Panduan infografis 4 langkah memilih di beranda.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-compass me-1"></i> Edukasi Pemilih
                                    </span>
                                </div>
                                <div class="studio-card-body">
                                    <div class="row g-3 mb-4 pb-3 border-bottom">
                                        <div class="col-md-4">
                                            <label for="guide_section_badge" class="form-label small fw-semibold text-dark">Badge Seksi Panduan</label>
                                            <input type="text" class="form-control" id="guide_section_badge" name="guide_section_badge" value="<?= htmlspecialchars(SettingHelper::get('guide_section_badge', 'Panduan Pemilih'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <label for="guide_section_title" class="form-label small fw-semibold text-dark">Judul Utama Seksi Panduan</label>
                                            <input type="text" class="form-control" id="guide_section_title" name="guide_section_title" value="<?= htmlspecialchars(SettingHelper::get('guide_section_title', 'Tata Cara Memberikan Hak Suara'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label for="guide_section_subtitle" class="form-label small fw-semibold text-dark">Subjudul Seksi Panduan</label>
                                            <input type="text" class="form-control" id="guide_section_subtitle" name="guide_section_subtitle" value="<?= htmlspecialchars(SettingHelper::get('guide_section_subtitle', '4 langkah mudah menyalurkan hak suara Anda secara cepat, mudah, dan terjamin kerahasiaannya.'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>

                                    <!-- 4 Step Cards Grid -->
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="studio-step-box h-100">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-dark text-white fw-bold">01</span>
                                                    <span class="fw-bold small text-dark">Langkah Pertama</span>
                                                </div>
                                                <input type="text" class="form-control form-control-sm mb-2" name="guide_step1_title" value="<?= htmlspecialchars(SettingHelper::get('guide_step1_title', '1. Masuk & Verifikasi Akun'), ENT_QUOTES, 'UTF-8') ?>">
                                                <textarea class="form-control form-control-sm" name="guide_step1_desc" rows="3"><?= htmlspecialchars(SettingHelper::get('guide_step1_desc', 'Login menggunakan NISN/NIP dan kata sandi yang telah diverifikasi dan disetujui panitia pemilihan.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="studio-step-box h-100">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-dark text-white fw-bold">02</span>
                                                    <span class="fw-bold small text-dark">Langkah Kedua</span>
                                                </div>
                                                <input type="text" class="form-control form-control-sm mb-2" name="guide_step2_title" value="<?= htmlspecialchars(SettingHelper::get('guide_step2_title', '2. Pelajari Visi & Misi'), ENT_QUOTES, 'UTF-8') ?>">
                                                <textarea class="form-control form-control-sm" name="guide_step2_desc" rows="3"><?= htmlspecialchars(SettingHelper::get('guide_step2_desc', 'Cermati program kerja unggulan, gagasan, dan integritas masing-masing pasangan calon.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="studio-step-box h-100">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-dark text-white fw-bold">03</span>
                                                    <span class="fw-bold small text-dark">Langkah Ketiga</span>
                                                </div>
                                                <input type="text" class="form-control form-control-sm mb-2" name="guide_step3_title" value="<?= htmlspecialchars(SettingHelper::get('guide_step3_title', '3. Coblos di Bilik Suara'), ENT_QUOTES, 'UTF-8') ?>">
                                                <textarea class="form-control form-control-sm" name="guide_step3_desc" rows="3"><?= htmlspecialchars(SettingHelper::get('guide_step3_desc', 'Masuk bilik suara digital dan tekan tombol coblos pada nomor urut paslon yang menjadi pilihan hati nurani Anda.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="studio-step-box h-100">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-dark text-white fw-bold">04</span>
                                                    <span class="fw-bold small text-dark">Langkah Keempat</span>
                                                </div>
                                                <input type="text" class="form-control form-control-sm mb-2" name="guide_step4_title" value="<?= htmlspecialchars(SettingHelper::get('guide_step4_title', '4. Konfirmasi & Selesai'), ENT_QUOTES, 'UTF-8') ?>">
                                                <textarea class="form-control form-control-sm" name="guide_step4_desc" rows="3"><?= htmlspecialchars(SettingHelper::get('guide_step4_desc', 'Konfirmasi suara sah Anda. Sistem mencatat suara secara terenkripsi anonim (Secret Ballot).'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 7. SEKSI HASIL REKAPITULASI & SEGEL SUARA -->
                            <div class="studio-card mb-4" id="sec-results">
                                <div class="studio-card-header bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-success-subtle text-success rounded-3"><i class="bi bi-pie-chart-fill fs-5"></i></div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">7. Seksi Hasil Rekapitulasi &amp; Segel Bilik Suara</h5>
                                            <small class="text-muted">Keterangan grafik dan pesan garansi saat hasil sementara disembunyikan panitia.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-shield-lock me-1"></i> Transparansi &amp; Segel
                                    </span>
                                </div>
                                <div class="studio-card-body">
                                    
                                    <!-- A. Header Hasil -->
                                    <div class="mb-4 pb-3 border-bottom">
                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-line me-2 text-primary"></i>A. Judul &amp; Subjudul Seksi Hasil</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="hasil_section_badge" class="form-label small fw-semibold text-dark">Badge Seksi</label>
                                                <input type="text" class="form-control" id="hasil_section_badge" name="hasil_section_badge" value="<?= htmlspecialchars(SettingHelper::get('hasil_section_badge', 'Transparansi Pemilu'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="col-md-8">
                                                <label for="hasil_section_title" class="form-label small fw-semibold text-dark">Judul Utama Seksi</label>
                                                <input type="text" class="form-control" id="hasil_section_title" name="hasil_section_title" value="<?= htmlspecialchars(SettingHelper::get('hasil_section_title', 'Perolehan Suara & Grafik Pemilihan'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="col-12">
                                                <label for="hasil_section_subtitle" class="form-label small fw-semibold text-dark">Subjudul Seksi Hasil</label>
                                                <input type="text" class="form-control" id="hasil_section_subtitle" name="hasil_section_subtitle" value="<?= htmlspecialchars(SettingHelper::get('hasil_section_subtitle', 'Pantau perkembangan perolehan suara sah secara transparan dan akurat langsung dari sistem e-voting.'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- B. Garansi Segel Suara -->
                                    <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="bi bi-lock-fill me-1"></i>Bilik Digembok</span>
                                            <h6 class="fw-bold text-dark mb-0">Tampilan Saat Hasil Sementara Disembunyikan (Mode Segel)</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="hasil_hidden_title" class="form-label small fw-semibold text-dark">Judul Kotak Segel Bilik Suara</label>
                                                <input type="text" class="form-control" id="hasil_hidden_title" name="hasil_hidden_title" value="<?= htmlspecialchars(SettingHelper::get('hasil_hidden_title', 'Hasil Pemilihan Sementara Masih Dirahasiakan'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="hasil_hidden_note" class="form-label small fw-semibold text-dark">Badge / Catatan Pengumuman Resmi</label>
                                                <input type="text" class="form-control" id="hasil_hidden_note" name="hasil_hidden_note" value="<?= htmlspecialchars(SettingHelper::get('hasil_hidden_note', 'Pengumuman resmi akan dipublikasikan setelah seluruh proses pemungutan suara ditutup.'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="col-12">
                                                <label for="hasil_hidden_desc" class="form-label small fw-semibold text-dark">Pernyataan Garansi Panitia Pemilihan</label>
                                                <textarea class="form-control" id="hasil_hidden_desc" name="hasil_hidden_desc" rows="2"><?= htmlspecialchars(SettingHelper::get('hasil_hidden_desc', 'Sesuai dengan ketetapan Panitia Pemilihan OSIS SMK Muthia Harapan Cicalengka, perolehan suara sementara dirahasiakan selama proses pemungutan suara berlangsung untuk menjaga netralitas dan kerahasiaan pemilihan.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- 8. SEKSI FAQ & PUSAT BANTUAN (HELPDESK) -->
                            <div class="studio-card mb-4" id="sec-faq">
                                <div class="studio-card-header bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 bg-warning-subtle text-warning rounded-3"><i class="bi bi-question-circle-fill fs-5"></i></div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">8. Seksi Pertanyaan Umum (FAQ) &amp; Pusat Bantuan (Helpdesk)</h5>
                                            <small class="text-muted">Tanya jawab umum dan kontak posko bantuan panitia di beranda.</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning-subtle text-dark border border-warning border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-headset me-1"></i> Layanan Bantuan
                                    </span>
                                </div>
                                <div class="studio-card-body">
                                    
                                    <!-- A. Header FAQ -->
                                    <div class="row g-3 mb-4 pb-3 border-bottom">
                                        <div class="col-md-4">
                                            <label for="faq_section_badge" class="form-label small fw-semibold text-dark">Badge Seksi FAQ</label>
                                            <input type="text" class="form-control" id="faq_section_badge" name="faq_section_badge" value="<?= htmlspecialchars(SettingHelper::get('faq_section_badge', 'Pusat Informasi & Bantuan'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <label for="faq_section_title" class="form-label small fw-semibold text-dark">Judul Utama Seksi FAQ</label>
                                            <input type="text" class="form-control" id="faq_section_title" name="faq_section_title" value="<?= htmlspecialchars(SettingHelper::get('faq_section_title', 'Pertanyaan yang Sering Diajukan (FAQ)'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label for="faq_section_subtitle" class="form-label small fw-semibold text-dark">Subjudul Seksi FAQ</label>
                                            <input type="text" class="form-control" id="faq_section_subtitle" name="faq_section_subtitle" value="<?= htmlspecialchars(SettingHelper::get('faq_section_subtitle', 'Temukan jawaban atas pertanyaan seputar akun, tata tertib, dan teknis pemilihan ketua OSIS.'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>

                                    <!-- B. FAQ 1, 2, 3 Cards -->
                                    <div class="mb-4 pb-3 border-bottom">
                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-chat-quote-fill me-2 text-primary"></i>Tiga Pertanyaan &amp; Jawaban Utama (FAQ)</h6>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="studio-step-box">
                                                    <span class="badge bg-primary text-white fw-bold mb-2">Pertanyaan 01</span>
                                                    <input type="text" class="form-control mb-2 fw-semibold" name="faq1_q" value="<?= htmlspecialchars(SettingHelper::get('faq1_q', 'Siapa saja yang berhak memberikan suara dalam pemilihan ini?'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Pertanyaan 1">
                                                    <textarea class="form-control" name="faq1_a" rows="2" placeholder="Jawaban 1"><?= htmlspecialchars(SettingHelper::get('faq1_a', 'Seluruh siswa aktif dan dewan guru SMK Muthia Harapan Cicalengka yang telah terdaftar dalam Daftar Pemilih Tetap (DPT) dan akunnya telah diverifikasi oleh panitia pemilihan.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="studio-step-box">
                                                    <span class="badge bg-primary text-white fw-bold mb-2">Pertanyaan 02</span>
                                                    <input type="text" class="form-control mb-2 fw-semibold" name="faq2_q" value="<?= htmlspecialchars(SettingHelper::get('faq2_q', 'Apakah kerahasiaan pilihan saya benar-benar terjamin?'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Pertanyaan 2">
                                                    <textarea class="form-control" name="faq2_a" rows="2" placeholder="Jawaban 2"><?= htmlspecialchars(SettingHelper::get('faq2_a', 'Ya, 100% terjamin. Sistem E-Voting menerapkan prinsip Secret Ballot kriptografis di mana catatan siapa yang Anda pilih diputus total dari identitas akun Anda.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="studio-step-box">
                                                    <span class="badge bg-primary text-white fw-bold mb-2">Pertanyaan 03</span>
                                                    <input type="text" class="form-control mb-2 fw-semibold" name="faq3_q" value="<?= htmlspecialchars(SettingHelper::get('faq3_q', 'Bagaimana jika saya lupa kata sandi atau akun belum diverifikasi?'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Pertanyaan 3">
                                                    <textarea class="form-control" name="faq3_a" rows="2" placeholder="Jawaban 3"><?= htmlspecialchars(SettingHelper::get('faq3_a', 'Segera hubungi tim helpdesk panitia pemilihan melalui WhatsApp atau kunjungi posko panitia di sekolah sebelum batas waktu pemilihan berakhir.'), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- C. Helpdesk Card Inputs -->
                                    <div class="p-3 bg-light rounded-4 border">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <span class="badge bg-success text-white fw-bold"><i class="bi bi-whatsapp me-1"></i>Helpdesk</span>
                                            <h6 class="fw-bold text-dark mb-0">Informasi Kontak Bantuan Panitia</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-dark">Judul Kartu Bantuan</label>
                                                <input type="text" class="form-control" name="helpdesk_title" value="<?= htmlspecialchars(SettingHelper::get('helpdesk_title', 'Pusat Bantuan Panitia Pemilihan'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-dark">Nomor WhatsApp / Telepon Panitia</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white text-success"><i class="bi bi-whatsapp"></i></span>
                                                    <input type="text" class="form-control" name="helpdesk_phone" value="<?= htmlspecialchars(SettingHelper::get('helpdesk_phone', '0812-3456-7890'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: 0812-3456-7890">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-dark">Jam Operasional Layanan</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white text-primary"><i class="bi bi-clock"></i></span>
                                                    <input type="text" class="form-control" name="helpdesk_hours" value="<?= htmlspecialchars(SettingHelper::get('helpdesk_hours', 'Senin - Jumat: 07.30 - 15.30 WIB'), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-dark">Keterangan Layanan Bantuan</label>
                                                <input type="text" class="form-control" name="helpdesk_desc" value="<?= htmlspecialchars(SettingHelper::get('helpdesk_desc', 'Mengalami kendala saat login atau membutuhkan panduan lebih lanjut? Tim panitia siap membantu Anda.'), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div> <!-- Close Right col-lg-9 -->

                    </div> <!-- Close row g-4 -->

                    <!-- Floating Sticky Action Bar at Bottom -->
                    <div class="studio-floating-save-bar d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle">
                                <i class="bi bi-cloud-arrow-up-fill fs-4"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">Simpan Pengaturan Kontrol Beranda</strong>
                                <small class="text-muted">Perubahan langsung aktif seketika setelah disimpan.</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('index.php') ?>" target="_blank" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Pratinjau Beranda
                            </a>
                            <button type="submit" class="btn btn-primary-custom px-4 py-2 fw-bold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Seluruh Pengaturan Beranda
                            </button>
                        </div>
                    </div>

                </form>
            </div>

            <!-- 1. TAB: LOGO & IDENTITAS SEKOLAH -->
            <div class="tab-pane fade" id="tab-general" role="tabpanel">
                <div class="row g-4">
                    <!-- Logo Manager Box -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 text-center">
                            <h5 class="fw-bold text-dark mb-3 text-start"><i class="bi bi-image text-primary me-2"></i>Logo Resmi Website</h5>
                            
                            <div class="p-4 bg-light rounded-4 border d-inline-flex justify-content-center align-items-center mb-3 mx-auto" style="width: 160px; height: 160px;">
                                <img src="<?= $currentLogo ?>" alt="Logo Sekolah" id="logoPreview" class="img-fluid" style="max-height: 120px; object-fit: contain;">
                            </div>

                            <div class="small text-muted mb-3">
                                <span class="badge bg-success-subtle text-success fw-bold px-2 py-1 mb-1">Status: Terpasang</span>
                                <div class="text-xs">Logo ini otomatis tampil di Ikon Tab Browser (Favicon), seluruh Navbar, Sidebar, Surat Suara, Berita Acara, dan Halaman Login.</div>
                            </div>

                            <!-- Upload Form -->
                            <form action="<?= base_url('admin/settings.php') ?>" method="POST" enctype="multipart/form-data" class="mb-2">
                                <?= CsrfHelper::getTokenField() ?>
                                <input type="hidden" name="form_type" value="upload_logo">
                                <input type="hidden" name="active_tab" value="tab-general">
                                <input type="hidden" name="favicon_png_base64" id="favicon_png_base64">

                                <div class="mb-3 text-start">
                                    <label for="website_logo" class="form-label small fw-semibold text-dark">Pilih File Logo Baru</label>
                                    <input type="file" class="form-control form-control-sm" id="website_logo" name="website_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" required onchange="previewSelectedLogo(this)">
                                    <div class="form-text text-xs">Format: PNG transparan, JPG, WEBP, atau SVG (Maks. 3MB).</div>
                                </div>

                                <button type="submit" class="btn btn-primary-custom btn-sm w-100 fw-bold">
                                    <i class="bi bi-cloud-arrow-up me-1"></i> Unggah &amp; Pasang Logo
                                </button>
                            </form>

                            <!-- Reset Logo Button -->
                            <form action="<?= base_url('admin/settings.php') ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset logo ke logo bawaan sistem?')">
                                <?= CsrfHelper::getTokenField() ?>
                                <input type="hidden" name="form_type" value="reset_logo">
                                <input type="hidden" name="active_tab" value="tab-general">
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset ke Logo Default
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- General Identity Form -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-shaded text-primary me-2"></i>Identitas Sekolah &amp; Aplikasi</h5>
                            
                            <form action="<?= base_url('admin/settings.php') ?>" method="POST">
                                <?= CsrfHelper::getTokenField() ?>
                                <input type="hidden" name="form_type" value="general_settings">
                                <input type="hidden" name="active_tab" value="tab-general">

                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="school_name" class="form-label fw-semibold small text-dark">Nama Sekolah</label>
                                        <input type="text" class="form-control" id="school_name" name="school_name" value="<?= htmlspecialchars($settings['school_name'] ?? SCHOOL_NAME, ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="school_npsn" class="form-label fw-semibold small text-dark">NPSN Sekolah</label>
                                        <input type="text" class="form-control" id="school_npsn" name="school_npsn" value="<?= htmlspecialchars($settings['school_npsn'] ?? '69900000', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: 69912345">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="app_title" class="form-label fw-semibold small text-dark">Nama / Judul Aplikasi</label>
                                        <input type="text" class="form-control" id="app_title" name="app_title" value="<?= htmlspecialchars($settings['app_title'] ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="academic_year" class="form-label fw-semibold small text-dark">Tahun Pelajaran / Periode</label>
                                        <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?= htmlspecialchars($settings['academic_year'] ?? '2026/2027', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: 2026/2027">
                                    </div>
                                    <div class="col-12">
                                        <label for="app_tagline" class="form-label fw-semibold small text-dark">Slogan / Tagline Pemilihan</label>
                                        <input type="text" class="form-control" id="app_tagline" name="app_tagline" value="<?= htmlspecialchars($settings['app_tagline'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Suara Demokrasi Pelajar untuk Pemimpin Berkarakter & Berprestasi">
                                    </div>
                                </div>

                                <div class="mt-4 pt-2 border-top">
                                    <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                                        <i class="bi bi-save me-1"></i> Simpan Identitas Sekolah
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. TAB: PEJABAT PENANDATANGAN LAPORAN -->
            <div class="tab-pane fade" id="tab-officials" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-check-fill text-primary me-2"></i>Pejabat Penandatangan Laporan &amp; Berita Acara</h5>
                        <span class="badge bg-info-subtle text-info fw-semibold">Sinkronisasi Otomatis ke Laporan PDF &amp; Cetak</span>
                    </div>
                    <p class="text-muted small mb-4">Nama dan NIP pejabat di bawah ini akan tercantum secara resmi pada lembar penandatanganan hasil rekapitulasi surat suara.</p>

                    <form action="<?= base_url('admin/settings.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="form_type" value="officials_settings">
                        <input type="hidden" name="active_tab" value="tab-officials">

                        <div class="row g-4">
                            <!-- Kepala Sekolah -->
                            <div class="col-lg-6">
                                <div class="p-3 rounded-4 bg-light border">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-fill text-primary me-1"></i> Kepala Sekolah</h6>
                                    <div class="mb-3">
                                        <label for="kepala_sekolah_nama" class="form-label small fw-semibold text-dark">Nama Lengkap &amp; Gelar</label>
                                        <input type="text" class="form-control" id="kepala_sekolah_nama" name="kepala_sekolah_nama" value="<?= htmlspecialchars($settings['kepala_sekolah_nama'] ?? 'H. Dadang Sulaeman, S.Pd., M.M.', ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="mb-0">
                                        <label for="kepala_sekolah_nip" class="form-label small fw-semibold text-dark">NIP / NUPTK</label>
                                        <input type="text" class="form-control" id="kepala_sekolah_nip" name="kepala_sekolah_nip" value="<?= htmlspecialchars($settings['kepala_sekolah_nip'] ?? '197508102003121002', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: 197508102003121002">
                                    </div>
                                </div>
                            </div>

                            <!-- Ketua Panitia Pemilihan -->
                            <div class="col-lg-6">
                                <div class="p-3 rounded-4 bg-light border">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-check-fill text-warning me-1"></i> Ketua Panitia Pemilihan OSIS</h6>
                                    <div class="mb-3">
                                        <label for="ketua_panitia_nama" class="form-label small fw-semibold text-dark">Nama Lengkap &amp; Gelar</label>
                                        <input type="text" class="form-control" id="ketua_panitia_nama" name="ketua_panitia_nama" value="<?= htmlspecialchars($settings['ketua_panitia_nama'] ?? 'Drs. H. Ahmad Fauzi, M.Pd.', ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="mb-0">
                                        <label for="ketua_panitia_nip" class="form-label small fw-semibold text-dark">NIP / Identitas Pegawai</label>
                                        <input type="text" class="form-control" id="ketua_panitia_nip" name="ketua_panitia_nip" value="<?= htmlspecialchars($settings['ketua_panitia_nip'] ?? '198501152010011001', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: 198501152010011001">
                                    </div>
                                </div>
                            </div>

                            <!-- Pembina OSIS -->
                            <div class="col-lg-12">
                                <div class="p-3 rounded-4 bg-light border">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-mortarboard-fill text-success me-1"></i> Pembina OSIS / Kesiswaan</h6>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label for="pembina_osis_nama" class="form-label small fw-semibold text-dark">Nama Lengkap Pembina OSIS</label>
                                            <input type="text" class="form-control" id="pembina_osis_nama" name="pembina_osis_nama" value="<?= htmlspecialchars($settings['pembina_osis_nama'] ?? 'Siti Rahmawati, S.Pd.', ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-2 border-top">
                            <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Pejabat Penandatangan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. TAB: KONTAK & MEDIA SOSIAL -->
            <div class="tab-pane fade" id="tab-contact" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-telephone-inbound-fill text-primary me-2"></i>Kontak Resmi, Alamat &amp; Media Sosial Sekolah</h5>
                    <p class="text-muted small mb-4">Informasi ini ditampilkan pada footer website publik, kartu kontak informasi siswa, serta lembar helpdesk pemilihan.</p>

                    <form action="<?= base_url('admin/settings.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="form_type" value="contact_settings">
                        <input type="hidden" name="active_tab" value="tab-contact">

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="school_address" class="form-label fw-semibold small text-dark">Alamat Lengkap Kampus Sekolah</label>
                                <textarea class="form-control" id="school_address" name="school_address" rows="2" required><?= htmlspecialchars($settings['school_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="school_phone" class="form-label fw-semibold small text-dark">Nomor Telepon / WhatsApp Panitia</label>
                                <input type="text" class="form-control" id="school_phone" name="school_phone" value="<?= htmlspecialchars($settings['school_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: (022) 7949000 atau 08123456789">
                            </div>

                            <div class="col-md-6">
                                <label for="school_email" class="form-label fw-semibold small text-dark">Email Resmi Sekolah / Panitia</label>
                                <input type="email" class="form-control" id="school_email" name="school_email" value="<?= htmlspecialchars($settings['school_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="info@smkmuthiaharapan.sch.id">
                            </div>

                            <div class="col-md-6">
                                <label for="contact_person" class="form-label fw-semibold small text-dark">Penanggung Jawab / Helpdesk OSIS</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?= htmlspecialchars($settings['contact_person'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Kesekretariatan Panitia Pemilu OSIS">
                            </div>

                            <div class="col-md-6">
                                <label for="website_url" class="form-label fw-semibold small text-dark">URL Website Resmi Sekolah</label>
                                <input type="url" class="form-control" id="website_url" name="website_url" value="<?= htmlspecialchars($settings['website_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="https://smkmuthiaharapan.sch.id">
                            </div>

                            <div class="col-md-6">
                                <label for="social_instagram" class="form-label fw-semibold small text-dark">Instagram Resmi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-instagram"></i></span>
                                    <input type="text" class="form-control" id="social_instagram" name="social_instagram" value="<?= htmlspecialchars($settings['social_instagram'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="@smkmuthiaharapan">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="social_youtube" class="form-label fw-semibold small text-dark">Channel YouTube Resmi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-youtube"></i></span>
                                    <input type="text" class="form-control" id="social_youtube" name="social_youtube" value="<?= htmlspecialchars($settings['social_youtube'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="SMK Muthia Harapan Official">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-2 border-top">
                            <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Kontak &amp; Media Sosial
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 4. TAB: ATURAN & PETUNJUK PEMILIHAN -->
            <div class="tab-pane fade" id="tab-guide" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Petunjuk &amp; Tata Tertib Pemilihan</h5>
                    <p class="text-muted small mb-4">Teks petunjuk teknis ini akan dibaca oleh pemilih (siswa &amp; guru) sebelum masuk ke bilik suara elektronik.</p>

                    <form action="<?= base_url('admin/settings.php') ?>" method="POST">
                        <?= CsrfHelper::getTokenField() ?>
                        <input type="hidden" name="form_type" value="guidelines_settings">
                        <input type="hidden" name="active_tab" value="tab-guide">

                        <div class="mb-3">
                            <label for="voting_guide" class="form-label fw-semibold small text-dark">Isi Petunjuk Bilik Suara</label>
                            <textarea class="form-control" id="voting_guide" name="voting_guide" rows="6"><?= htmlspecialchars($settings['voting_guide'] ?? 'Silakan tentukan pilihan Anda secara bijak, mandiri, dan bertanggung jawab. Pilihan Anda sepenuhnya rahasia dan dilindungi oleh enkripsi sistem.', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text small">Teks ini tampil sebagai petunjuk panduan di bagian atas lembar bilik suara.</div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                            <i class="bi bi-save me-1"></i> Simpan Aturan Pemilihan
                        </button>
                    </form>
                </div>
            </div>

            <!-- 5. TAB: PENGUMUMAN PANITIA -->
            <div class="tab-pane fade" id="tab-ann" role="tabpanel">
                <div class="row g-4">
                    <!-- Form Tambah Pengumuman -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-megaphone-fill text-warning me-2"></i>Terbitkan Pengumuman Baru</h5>
                            <form action="<?= base_url('admin/settings.php') ?>" method="POST">
                                <?= CsrfHelper::getTokenField() ?>
                                <input type="hidden" name="form_type" value="add_announcement">
                                <input type="hidden" name="active_tab" value="tab-ann">

                                <div class="mb-3">
                                    <label for="ann_title" class="form-label fw-semibold small text-dark">Judul Pengumuman</label>
                                    <input type="text" class="form-control" id="ann_title" name="ann_title" placeholder="Contoh: Jadwal Pemungutan Suara Bilik 1" required>
                                </div>

                                <div class="mb-3">
                                    <label for="ann_content" class="form-label fw-semibold small text-dark">Isi Pengumuman Lengkap</label>
                                    <textarea class="form-control" id="ann_content" name="ann_content" rows="4" placeholder="Tuliskan informasi penting panitia..." required></textarea>
                                </div>

                                <button type="submit" class="btn btn-gold-custom fw-bold px-4 w-100">
                                    <i class="bi bi-send-fill me-1"></i> Publikasikan Pengumuman
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Daftar Pengumuman -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-list-stars text-primary me-2"></i>Daftar Pengumuman Terbit</h5>
                            
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-2 text-secondary"></i>
                                    Belum ada pengumuman yang diterbitkan oleh panitia.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($announcements as $an): ?>
                                    <div class="list-group-item px-0 py-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($an['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($an['created_at'])) ?> WIB
                                                </small>
                                            </div>
                                            <span class="badge <?= $an['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $an['is_active'] ? 'Aktif Tayang' : 'Disembunyikan' ?>
                                            </span>
                                        </div>
                                        <p class="small text-muted mb-3"><?= nl2br(htmlspecialchars($an['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        
                                        <div class="d-flex gap-2">
                                            <!-- Toggle Status -->
                                            <form action="<?= base_url('admin/settings.php') ?>" method="POST" class="d-inline">
                                                <?= CsrfHelper::getTokenField() ?>
                                                <input type="hidden" name="form_type" value="toggle_announcement">
                                                <input type="hidden" name="ann_id" value="<?= $an['id'] ?>">
                                                <input type="hidden" name="active_tab" value="tab-ann">
                                                <button type="submit" class="btn btn-xs <?= $an['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                                    <i class="bi bi-eye-slash me-1"></i><?= $an['is_active'] ? 'Sembunyikan' : 'Aktifkan' ?>
                                                </button>
                                            </form>

                                            <!-- Delete -->
                                            <form action="<?= base_url('admin/settings.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengumuman ini secara permanen?')">
                                                <?= CsrfHelper::getTokenField() ?>
                                                <input type="hidden" name="form_type" value="delete_announcement">
                                                <input type="hidden" name="ann_id" value="<?= $an['id'] ?>">
                                                <input type="hidden" name="active_tab" value="tab-ann">
                                                <button type="submit" class="btn btn-xs btn-outline-danger">
                                                    <i class="bi bi-trash me-1"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. TAB: PEMELIHARAAN & GO-LIVE (BERSIHKAN DATA PENGUJIAN) -->
            <div class="tab-pane fade" id="tab-maintenance" role="tabpanel">
                <!-- Warning Header -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-danger text-white rounded-circle fs-3 shadow-sm">
                            <i class="bi bi-shield-exclamation"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold text-danger mb-1">Pembersihan Data Pengujian &amp; Persiapan Go-Live Resmi</h4>
                            <p class="text-muted small mb-0">Fitur ini digunakan saat masa uji coba/simulasi selesai dan sistem akan digunakan untuk pemilihan ketua OSIS yang sebenarnya.</p>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Left: Form Pembersihan -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                <i class="bi bi-trash3-fill text-danger me-2"></i>Eksekusi Pembersihan Sistem
                            </h5>

                            <form action="<?= base_url('admin/settings.php') ?>" method="POST" onsubmit="return confirm('PERINGATAN KRUSIAL: Apakah Anda benar-benar yakin ingin membersihkan data pengujian? Tindakan ini tidak dapat dibatalkan.');">
                                <?= CsrfHelper::getTokenField() ?>
                                <input type="hidden" name="form_type" value="system_reset_production">
                                <input type="hidden" name="active_tab" value="tab-maintenance">

                                <div class="p-3 bg-light rounded-3 border mb-3">
                                    <div class="small fw-bold text-dark mb-2">Pilih Data yang Ingin Dibersihkan:</div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="check_mandatory" checked disabled>
                                        <label class="form-check-label small fw-semibold text-dark" for="check_mandatory">
                                            Suara Sah, Partisipasi &amp; Seluruh Akun Pemilih Uji Coba (Siswa &amp; Guru)
                                        </label>
                                        <div class="text-xs text-muted">Wajib dibersihkan agar kotak suara elektronik kembali 0.</div>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="clear_registry" name="clear_registry" value="1" checked>
                                        <label class="form-check-label small fw-semibold text-dark" for="clear_registry">
                                            Kosongkan Master DPT (Data Siswa &amp; Guru Sekolah)
                                        </label>
                                        <div class="text-xs text-muted">Dikosongkan agar panitia siap mengimpor data DPT siswa &amp; guru resmi sekolah.</div>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="clear_candidates" name="clear_candidates" value="1" checked>
                                        <label class="form-check-label small fw-semibold text-dark" for="clear_candidates">
                                            Kosongkan Kandidat Paslon &amp; Hapus File Foto Uji Coba
                                        </label>
                                        <div class="text-xs text-muted">Menghapus paslon simulasi agar panitia dapat menginput paslon resmi yang sebenarnya.</div>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="clear_elections" name="clear_elections" value="1" checked>
                                        <label class="form-check-label small fw-semibold text-dark" for="clear_elections">
                                            Kosongkan Periode Pemilihan Uji Coba
                                        </label>
                                        <div class="text-xs text-muted">Memungkinkan pembuatan periode pemilihan baru dengan jadwal resmi.</div>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="clear_logs" name="clear_logs" value="1" checked>
                                        <label class="form-check-label small fw-semibold text-dark" for="clear_logs">
                                            Bersihkan Riwayat Audit Log &amp; Rate Limits Pengujian
                                        </label>
                                        <div class="text-xs text-muted">Mereset catatan aktivitas sistem agar audit trail bersih untuk pemilu resmi.</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="admin_password" class="form-label small fw-bold text-dark">
                                        <i class="bi bi-key-fill text-danger me-1"></i>Masukkan Password Administrator untuk Konfirmasi
                                    </label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required placeholder="Ketik kata sandi akun admin Anda saat ini">
                                    <div class="form-text text-xs text-danger">Tindakan ini bersifat permanen dan tidak dapat di-undo.</div>
                                </div>

                                <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">
                                    <i class="bi bi-shield-check me-1"></i> Bersihkan Sistem &amp; Siapkan untuk Pemilihan Resmi
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Right: Informasi Keamanan & Apa yang Dipertahankan -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light">
                            <h5 class="fw-bold text-success mb-3 pb-2 border-bottom">
                                <i class="bi bi-shield-lock-fill text-success me-2"></i>Data yang Selalu Aman &amp; Dipertahankan
                            </h5>

                            <div class="d-flex flex-column gap-3 mb-4">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5 mt-1"></i>
                                    <div>
                                        <strong class="text-dark small d-block">Akun Administrator Sistem</strong>
                                        <span class="text-muted small">Username, password, email, dan profil Administrator tidak akan terhapus. Anda tetap bisa langsung login setelah pembersihan.</span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5 mt-1"></i>
                                    <div>
                                        <strong class="text-dark small d-block">Pengaturan Identitas &amp; Logo Sekolah</strong>
                                        <span class="text-muted small">Logo resmi, nama sekolah, NPSN, alamat, kontak, medsos, pejabat penandatangan, dan aturan pemilihan tetap aman utuh.</span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5 mt-1"></i>
                                    <div>
                                        <strong class="text-dark small d-block">Kustomisasi Tampilan Beranda (Index)</strong>
                                        <span class="text-muted small">Seluruh pengaturan teks hero, FAQ, tata cara memilih, dan tata letak yang sudah Anda rapikan tidak akan hilang.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-white rounded-3 border">
                                <span class="badge bg-primary text-uppercase small mb-2">Alternatif CLI (Terminal)</span>
                                <p class="text-muted small mb-1">Pembersihan juga dapat dijalankan langsung via command line dengan perintah:</p>
                                <code class="p-2 bg-dark text-warning rounded d-block text-xs">php database/reset_for_production.php</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SOP / Alur Kerja Pra-Pemilihan Resmi -->
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                        <i class="bi bi-card-checklist text-primary me-2"></i>SOP / Alur Kerja Menuju Hari Pemilihan Resmi (Go-Live Checklist)
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-primary text-white fw-bold mb-2">Langkah 1</span>
                                <h6 class="fw-bold text-dark">Pembersihan Data Uji Coba</h6>
                                <p class="text-muted small mb-0">Gunakan tombol pembersihan di atas untuk mengosongkan seluruh data siswa, guru, suara, dan paslon simulasi.</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-primary text-white fw-bold mb-2">Langkah 2</span>
                                <h6 class="fw-bold text-dark">Impor Master DPT Asli</h6>
                                <p class="text-muted small mb-0">Impor data DPT resmi dari file Excel/CSV sekolah melalui menu <strong>Master Data Siswa</strong> dan <strong>Master Data Guru</strong>.</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-primary text-white fw-bold mb-2">Langkah 3</span>
                                <h6 class="fw-bold text-dark">Buat Periode &amp; Paslon Resmi</h6>
                                <p class="text-muted small mb-0">Buat jadwal pemilihan di menu <strong>Periode Pemilihan</strong>, lalu unggah foto dan visi-misi paslon resmi di <strong>Manajemen Paslon</strong>.</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-primary text-white fw-bold mb-2">Langkah 4</span>
                                <h6 class="fw-bold text-dark">Sosialisasi &amp; Verifikasi Akun</h6>
                                <p class="text-muted small mb-0">Siswa &amp; guru mendaftar akun atau dibagikan akun, lalu diverifikasi oleh admin di menu <strong>Verifikasi Registrasi</strong>.</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-success text-white fw-bold mb-2">Langkah 5 (Hari H)</span>
                                <h6 class="fw-bold text-dark">Pemungutan Suara di Bilik Suara</h6>
                                <p class="text-muted small mb-0">Pemilih memberikan suara secara rahasia (Secret Ballot). Pantau perolehan suara live di proyektor panggung.</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-dark text-white fw-bold mb-2">Langkah 6</span>
                                <h6 class="fw-bold text-dark">Rekapitulasi &amp; Cetak Berita Acara</h6>
                                <p class="text-muted small mb-0">Tutup pemilihan, tetapkan pemenang, dan cetak Berita Acara &amp; Laporan Hasil Pemilihan bertandatangan resmi Kepala Sekolah.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- Close Tab Content -->

        </main>
        <?php require_once BASE_PATH . '/app/Views/layouts/dashboard_footer.php'; ?>

<script>
// Live Image Preview & Square Favicon Canvas Generator for Logo Selection
function previewSelectedLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const dataUrl = e.target.result;
            const preview = document.getElementById('logoPreview');
            if (preview) {
                preview.src = dataUrl;
            }
            const img = new Image();
            img.onload = function () {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 64;
                    canvas.height = 64;
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, 64, 64);
                    const ratio = Math.min(64 / (img.width || 64), 64 / (img.height || 64));
                    const w = Math.round((img.width || 64) * ratio);
                    const h = Math.round((img.height || 64) * ratio);
                    const x = Math.floor((64 - w) / 2);
                    const y = Math.floor((64 - h) / 2);
                    ctx.drawImage(img, x, y, w, h);
                    const hiddenInput = document.getElementById('favicon_png_base64');
                    if (hiddenInput) {
                        hiddenInput.value = canvas.toDataURL('image/png');
                    }
                } catch (err) {}
            };
            img.src = dataUrl;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Preserve active tab on page reload/submit via hash or query param
function activateTabFromUrl() {
    let target = window.location.hash;
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        target = tabParam.startsWith('#') ? tabParam : ('#' + (tabParam.startsWith('tab-') ? tabParam : 'tab-' + tabParam));
    }
    if (target) {
        const triggerEl = document.querySelector(`button[data-bs-target="${target}"]`);
        if (triggerEl) {
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
            // Smooth scroll to tabs on explicit hash
            if (window.location.hash) {
                triggerEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', activateTabFromUrl);
window.addEventListener('hashchange', activateTabFromUrl);

// Studio Quick Nav Click & ScrollSpy
document.addEventListener('DOMContentLoaded', function() {
    const navButtons = document.querySelectorAll('.studio-quick-nav .studio-nav-btn');
    const sections = document.querySelectorAll('.studio-card[id]');
    
    // Smooth navigation click
    navButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
                const targetEl = document.querySelector(targetId);
                if (targetEl) {
                    e.preventDefault();
                    navButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    // ScrollSpy using IntersectionObserver
    if ('IntersectionObserver' in window && sections.length > 0) {
        const observerOptions = {
            root: null,
            rootMargin: '-100px 0px -60% 0px',
            threshold: 0
        };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    if (id) {
                        navButtons.forEach(btn => {
                            if (btn.getAttribute('href') === '#' + id) {
                                navButtons.forEach(b => b.classList.remove('active'));
                                btn.classList.add('active');
                            }
                        });
                    }
                }
            });
        }, observerOptions);

        sections.forEach(sec => observer.observe(sec));
    }
});
</script>
