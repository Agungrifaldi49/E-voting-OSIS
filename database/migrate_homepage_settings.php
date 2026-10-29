<?php
// database/migrate_homepage_settings.php
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

$homepageDefaults = [
    // Top Urgent Ticker
    'hero_announcement_enabled' => '1',
    'hero_announcement_text'    => 'Pengumuman Resmi: Pastikan seluruh siswa dan guru telah memeriksa status verifikasi DPT sebelum hari pemilihan berakhir.',

    // Hero Section
    'hero_badge_text'           => 'Pesta Demokrasi Siswa • Asas Luber Jurdil',
    'hero_title'                => 'PEMILIHAN KETUA & WAKIL KETUA OSIS',
    'hero_subtitle'             => 'Kenali calon pemimpinmu, pelajari visi dan misinya, dan gunakan hak pilihmu untuk menentukan masa depan OSIS SMK Muthia Harapan Cicalengka.',
    'hero_cta_vote_text'        => 'Masuk Bilik Suara',
    'hero_cta_cand_text'        => 'Lihat Pasangan Calon',
    'hero_countdown_title'      => 'Hitung Mundur Pemilihan',
    'hero_countdown_desc'       => 'Pastikan akun Anda telah diverifikasi oleh Panitia Pemilihan sebelum waktu pemilihan berakhir.',

    // Key Stats / Values (Cards 3 & 4)
    'stat_card_3_title'         => 'Asas LUBER JURDIL',
    'stat_card_3_desc'          => 'Langsung, Umum, Bebas, Rahasia, Jujur & Adil',
    'stat_card_4_title'         => 'Kerahasiaan Terjamin',
    'stat_card_4_desc'          => 'Secret Ballot 100% Enkripsi Suara Terlindungi',

    // Section Kandidat
    'kandidat_section_badge'    => 'Kandidat Pemimpin',
    'kandidat_section_title'    => 'Daftar Pasangan Calon Ketua & Wakil Ketua OSIS',
    'kandidat_section_subtitle' => 'Pelajari profil, rekam jejak, visi, misi, serta program kerja unggulan sebelum menentukan pilihan terbaik Anda.',
    'kandidat_empty_msg'        => 'Belum ada pasangan calon yang dipublikasikan oleh panitia pemilihan untuk periode ini.',

    // Section Agenda
    'agenda_section_badge'      => 'Agenda Resmi',
    'agenda_section_title'      => 'Jadwal & Tahapan Pemilihan OSIS',
    'agenda_section_subtitle'   => 'Ikuti seluruh tahapan pesta demokrasi sekolah sesuai dengan jadwal yang telah ditetapkan panitia.',
    'agenda_step1_badge'        => 'Tahap 1',
    'agenda_step1_title'        => 'Registrasi & Verifikasi Pemilih',
    'agenda_step1_desc'         => 'Siswa dan Dewan Guru mendaftarkan akun di portal E-Voting dan diverifikasi langsung oleh admin panitia pemilihan sekolah.',
    'agenda_step2_badge'        => 'Tahap 2',
    'agenda_step2_title'        => 'Pemungutan Suara (E-Voting)',
    'agenda_step2_desc'         => 'Pemilih yang terdaftar dalam DPT menggunakan hak pilihnya di bilik suara elektronik secara rahasia, aman, dan tanpa paksaan.',
    'agenda_step3_badge'        => 'Tahap 3',
    'agenda_step3_title'        => 'Rekapitulasi & Penetapan Paslon',
    'agenda_step3_desc'         => 'Penetapan dan pengumuman resmi pasangan calon terpilih Ketua dan Wakil Ketua OSIS berdasarkan perolehan suara terbanyak yang sah.',

    // Section Tata Cara Memilih (4 Steps)
    'guide_section_badge'       => 'Panduan Pemilih',
    'guide_section_title'       => 'Tata Cara Memberikan Hak Suara',
    'guide_section_subtitle'    => '4 langkah mudah menyalurkan hak suara Anda secara cepat, mudah, dan terjamin kerahasiaannya.',
    'guide_step1_title'         => '1. Masuk & Verifikasi Akun',
    'guide_step1_desc'          => 'Login menggunakan NISN/NIP dan kata sandi yang telah diverifikasi dan disetujui panitia pemilihan.',
    'guide_step2_title'         => '2. Pelajari Visi & Misi',
    'guide_step2_desc'          => 'Cermati program kerja unggulan, gagasan, dan integritas masing-masing pasangan calon.',
    'guide_step3_title'         => '3. Coblos di Bilik Suara',
    'guide_step3_desc'          => 'Masuk bilik suara digital dan tekan tombol coblos pada nomor urut paslon yang menjadi pilihan hati nurani Anda.',
    'guide_step4_title'         => '4. Konfirmasi & Selesai',
    'guide_step4_desc'          => 'Konfirmasi suara sah Anda. Sistem mencatat suara secara terenkripsi anonim (Secret Ballot).',

    // Section Hasil Rekapitulasi
    'hasil_section_badge'       => 'Transparansi Pemilu',
    'hasil_section_title'       => 'Perolehan Suara & Grafik Pemilihan',
    'hasil_section_subtitle'    => 'Pantau perkembangan perolehan suara sah secara transparan dan akurat langsung dari sistem e-voting.',
    'hasil_hidden_title'        => 'Hasil Pemilihan Sementara Masih Dirahasiakan',
    'hasil_hidden_desc'         => 'Sesuai dengan ketetapan Panitia Pemilihan OSIS SMK Muthia Harapan Cicalengka, perolehan suara sementara dirahasiakan selama proses pemungutan suara berlangsung untuk menjaga netralitas dan kerahasiaan pemilihan.',
    'hasil_hidden_note'         => 'Pengumuman resmi akan dipublikasikan setelah seluruh proses pemungutan suara ditutup.',

    // Section FAQ & Helpdesk
    'faq_section_badge'         => 'Pusat Informasi & Bantuan',
    'faq_section_title'         => 'Pertanyaan yang Sering Diajukan (FAQ)',
    'faq_section_subtitle'      => 'Temukan jawaban atas pertanyaan seputar akun, tata tertib, dan teknis pemilihan ketua OSIS.',
    'faq1_q'                    => 'Siapa saja yang berhak memberikan suara dalam pemilihan ini?',
    'faq1_a'                    => 'Seluruh siswa aktif dan dewan guru SMK Muthia Harapan Cicalengka yang telah terdaftar dalam Daftar Pemilih Tetap (DPT) dan akunnya telah diverifikasi oleh panitia pemilihan.',
    'faq2_q'                    => 'Apakah kerahasiaan pilihan saya benar-benar terjamin?',
    'faq2_a'                    => 'Ya, 100% terjamin. Sistem E-Voting menerapkan prinsip Secret Ballot kriptografis di mana catatan siapa yang Anda pilih diputus total dari identitas akun Anda.',
    'faq3_q'                    => 'Bagaimana jika saya lupa kata sandi atau akun belum diverifikasi?',
    'faq3_a'                    => 'Segera hubungi tim helpdesk panitia pemilihan melalui WhatsApp atau kunjungi posko panitia di sekolah sebelum batas waktu pemilihan berakhir.',
    'helpdesk_title'            => 'Pusat Bantuan Panitia Pemilihan',
    'helpdesk_desc'             => 'Mengalami kendala saat login atau membutuhkan panduan lebih lanjut? Tim panitia siap membantu Anda.',
    'helpdesk_phone'            => '0812-3456-7890',
    'helpdesk_hours'            => 'Senin - Jumat: 07.30 - 15.30 WIB'
];

$stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = COALESCE(setting_value, VALUES(setting_value))");
$inserted = 0;
foreach ($homepageDefaults as $k => $v) {
    $stmt->execute([':k' => $k, ':v' => $v]);
    $inserted++;
}

echo "[SUCCESS] Migrated and verified {$inserted} homepage settings in app_settings table.\n";
