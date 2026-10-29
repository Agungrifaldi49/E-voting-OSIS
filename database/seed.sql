-- Seed Data for E-Voting OSIS SMK Muthia Harapan Cicalengka
-- USE `db_evoting_osis`;

-- 1. APP SETTINGS
INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'SMK Muthia Harapan Cicalengka'),
('app_title', 'E-Voting OSIS'),
('school_address', 'Jl. Raya Cicalengka - Majalaya No. 123, Cicalengka, Kab. Bandung'),
('school_phone', '(022) 7949000'),
('school_email', 'info@smkmuthiaharapan.sch.id'),
('contact_person', 'Panitia Pemilu OSIS (Muthia Election Commission)'),
('logo_url', 'assets/images/logo-placeholder.svg')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 2. OFFICIAL REGISTRY (Master Siswa dan Guru Terverifikasi Sekolah)
INSERT INTO `student_registry` (`nisn`, `nama_lengkap`, `kelas`, `jurusan`, `status`) VALUES
('0061234567', 'Muhammad Rizky Pratama', 'XI RPL 1', 'Rekayasa Perangkat Lunak', 'registered'),
('0067654321', 'Aulia Zahra Salsabila', 'XI TKJ 2', 'Teknik Komputer & Jaringan', 'registered'),
('0071122334', 'Fajar Nugraha', 'X AKL 1', 'Akuntansi dan Keuangan Lembaga', 'registered'),
('0079988776', 'Dewi Lestari', 'X OTKP 2', 'Otomatisasi & Tata Kelola Perkantoran', 'unregistered'),
('0065544332', 'Bima Arya Saputra', 'XII RPL 2', 'Rekayasa Perangkat Lunak', 'unregistered'),
('0068877665', 'Fitri Handayani', 'XI BDP 1', 'Bisnis Daring dan Pemasaran', 'unregistered')
ON DUPLICATE KEY UPDATE `nama_lengkap` = VALUES(`nama_lengkap`);

INSERT INTO `teacher_registry` (`employee_identifier`, `nama_lengkap`, `status`) VALUES
('198501152010011001', 'Drs. H. Ahmad Fauzi, M.Pd.', 'registered'),
('199003202015022003', 'Siti Rohmah, S.Kom.', 'unregistered'),
('198811122014011002', 'Budi Santoso, S.Pd.', 'unregistered')
ON DUPLICATE KEY UPDATE `nama_lengkap` = VALUES(`nama_lengkap`);

-- 3. USERS (Admin, Guru, Siswa Aktif, Siswa Pending)
-- Passwords:
-- admin: admin123
-- guru: guru123
-- siswa: siswa123
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `account_status`) VALUES
(1, 'admin', 'admin@smkmuthiaharapan.sch.id', '$2y$10$Je.FrGY.tOpmwpT8h854xea.NW5MPKGGj46lkVR9EkOe2NYccff0a', 'admin', 'active'),
(2, '198501152010011001', 'ahmad.fauzi@smkmuthiaharapan.sch.id', '$2y$10$M6Pr2V0idAFy6PEPWnOBzuqXwb6ddFc3PcGnOpvkoPtps7YjA2zqS', 'guru', 'active'),
(3, '0061234567', 'rizky.pratama@smkmuthiaharapan.sch.id', '$2y$10$8bD6CwdL.J791vlKy0cI/u1dTSkCFSmbjJNhrhSgcBQncQyrnTQ3K', 'siswa', 'active'),
(4, '0067654321', 'aulia.zahra@smkmuthiaharapan.sch.id', '$2y$10$8bD6CwdL.J791vlKy0cI/u1dTSkCFSmbjJNhrhSgcBQncQyrnTQ3K', 'siswa', 'active'),
(5, '0071122334', 'fajar.nugraha@smkmuthiaharapan.sch.id', '$2y$10$8bD6CwdL.J791vlKy0cI/u1dTSkCFSmbjJNhrhSgcBQncQyrnTQ3K', 'siswa', 'pending')
ON DUPLICATE KEY UPDATE `account_status` = VALUES(`account_status`);

-- 4. PROFILES
INSERT INTO `teacher_profiles` (`user_id`, `employee_identifier`, `nama_lengkap`, `mata_pelajaran`) VALUES
(2, '198501152010011001', 'Drs. H. Ahmad Fauzi, M.Pd.', 'Pendidikan Pancasila & Kewarganegaraan')
ON DUPLICATE KEY UPDATE `nama_lengkap` = VALUES(`nama_lengkap`);

INSERT INTO `student_profiles` (`user_id`, `nisn`, `nama_lengkap`, `kelas`, `jurusan`) VALUES
(3, '0061234567', 'Muhammad Rizky Pratama', 'XI RPL 1', 'Rekayasa Perangkat Lunak'),
(4, '0067654321', 'Aulia Zahra Salsabila', 'XI TKJ 2', 'Teknik Komputer & Jaringan'),
(5, '0071122334', 'Fajar Nugraha', 'X AKL 1', 'Akuntansi dan Keuangan Lembaga')
ON DUPLICATE KEY UPDATE `nama_lengkap` = VALUES(`nama_lengkap`);

-- 5. ELECTIONS
INSERT INTO `elections` (`id`, `nama_periode`, `tahun`, `deskripsi`, `start_at`, `end_at`, `status`, `is_active`, `teacher_can_vote`, `results_visibility`, `results_published_at`) VALUES
(1, 'Pemilihan Ketua & Wakil Ketua OSIS Periode 2026/2027', '2026/2027', 'Pesta Demokrasi Siswa SMK Muthia Harapan Cicalengka untuk memilih pemimpin OSIS masa bakti 2026/2027.', '2026-10-25 08:00:00', '2026-11-05 16:00:00', 'ongoing', 1, 1, 'realtime', '2026-10-25 08:00:00')
ON DUPLICATE KEY UPDATE `nama_periode` = VALUES(`nama_periode`);

-- 6. CANDIDATES
INSERT INTO `candidates` (`id`, `election_id`, `nomor_urut`, `nama_ketua`, `kelas_ketua`, `foto_ketua`, `nama_wakil`, `kelas_wakil`, `foto_wakil`, `foto_pasangan`, `visi`, `misi`, `program_kerja`, `publication_status`) VALUES
(1, 1, 1, 'Arka Danendra', 'XI RPL 1', NULL, 'Nabila Putri Cahyani', 'XI TKJ 2', NULL, NULL,
'Mewujudkan OSIS SMK Muthia Harapan Cicalengka yang berkarakter, berintegritas tinggi, kreatif, adaptif terhadap perkembangan teknologi digital, serta menjunjung tinggi nilai gotong royong.',
'1. Meningkatkan kedisiplinan dan akhlak mulia seluruh siswa melalui program keagamaan dan pembiasaan positif.\n2. Mengoptimalkan penyaluran minat dan bakat siswa di bidang sains, teknologi, seni, dan olahraga.\n3. Membangun ruang aspirasi siswa yang transparan, komunikatif, dan responsif berbasis digital.\n4. Memperkuat kolaborasi antarekstrakurikuler dalam menyelenggarakan kegiatan inovatif.',
'• Ruang Aspirasi Digital (SMK Voice)\n• Pekan Inovasi dan Kreativitas Siswa (Muthia Fest)\n• Pendampingan Karir dan Kewirausahaan Muda (Muthia Technopreneur)',
'published'),

(2, 1, 2, 'Rendra Bagaskara', 'XI OTKP 1', NULL, 'Syifa Maulida', 'XI AKL 2', NULL, NULL,
'Terwujudnya lingkungan sekolah yang inklusif, kolaboratif, peduli lingkungan, dan berdaya saing global dengan semangat profesionalisme kejuruan.',
'1. Mengembangkan jiwa kepemimpinan yang berakhlak dan berwawasan lingkungan hidup (Green School).\n2. Memfasilitasi kegiatan peningkatan kompetensi kejuruan siswa bersama alumni dan industri.\n3. Menggiatkan bakti sosial dan pengabdian masyarakat sekitar Cicalengka.\n4. Menyelenggarakan kegiatan keakraban antarangkatan secara berkala.',
'• Green School Movement & Eco-Campus Clean-Up\n• Forum Diskusi Kejuruan bersama DUDI & Alumni\n• Muthia Care: Aksi Sosial dan Kepedulian Lingkungan',
'published')
ON DUPLICATE KEY UPDATE `nama_ketua` = VALUES(`nama_ketua`);

-- 7. VOTING ELIGIBILITY (Pemilih Terdaftar untuk Periode 1)
INSERT INTO `voting_eligibility` (`election_id`, `user_id`, `eligibility_status`) VALUES
(1, 2, 'eligible'),
(1, 3, 'eligible'),
(1, 4, 'eligible')
ON DUPLICATE KEY UPDATE `eligibility_status` = VALUES(`eligibility_status`);

-- 8. ANNOUNCEMENTS
INSERT INTO `announcements` (`title`, `content`, `is_active`) VALUES
('Selamat Datang di Portal E-Voting OSIS', 'Gunakan hak suara Anda dengan jujur, adil, dan bertanggung jawab. Pemilihan berlangsung secara online mulai tanggal 25 Oktober hingga 05 November 2026.', 1),
('Petunjuk Pemilihan', 'Pastikan akun Anda sudah berstatus aktif dan terverifikasi oleh Panitia Pemilihan sebelum masuk ke bilik suara elektronik.', 1);

-- 9. ACTIVITY LOGS INITIAL
INSERT INTO `activity_logs` (`user_id`, `action`, `description`, `ip_address`, `user_agent`) VALUES
(1, 'SYSTEM_INIT', 'Sistem E-Voting OSIS SMK Muthia Harapan Cicalengka berhasil diinisialisasi.', '127.0.0.1', 'System Installer');
