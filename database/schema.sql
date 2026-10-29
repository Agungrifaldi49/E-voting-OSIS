-- Database Schema for E-Voting OSIS SMK Muthia Harapan Cicalengka
-- Target Database: db_evoting_osis
-- Character Set: utf8mb4 / utf8mb4_unicode_ci
-- Engine: InnoDB

-- Jika menggunakan phpMyAdmin di hosting cPanel, pilih database Anda terlebih dahulu lalu klik Import.
-- CREATE DATABASE IF NOT EXISTS `db_evoting_osis` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `db_evoting_osis`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `login_rate_limits`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `votes`;
DROP TABLE IF EXISTS `voting_participation`;
DROP TABLE IF EXISTS `voting_eligibility`;
DROP TABLE IF EXISTS `candidates`;
DROP TABLE IF EXISTS `elections`;
DROP TABLE IF EXISTS `student_registry`;
DROP TABLE IF EXISTS `teacher_registry`;
DROP TABLE IF EXISTS `student_profiles`;
DROP TABLE IF EXISTS `teacher_profiles`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS TABLE
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `full_name` VARCHAR(128) NULL,
  `email` VARCHAR(128) NULL,
  `avatar` VARCHAR(255) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'guru', 'siswa') NOT NULL DEFAULT 'siswa',
  `account_status` ENUM('pending', 'active', 'rejected', 'inactive') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. STUDENT PROFILES
CREATE TABLE `student_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `nisn` VARCHAR(32) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(128) NOT NULL,
  `kelas` VARCHAR(32) NOT NULL,
  `jurusan` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TEACHER PROFILES
CREATE TABLE `teacher_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `employee_identifier` VARCHAR(64) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(128) NOT NULL,
  `mata_pelajaran` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_teacher_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. OFFICIAL STUDENT REGISTRY (Master Data Sekolah untuk validasi pendaftaran)
CREATE TABLE `student_registry` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nisn` VARCHAR(32) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(128) NOT NULL,
  `kelas` VARCHAR(32) NOT NULL,
  `jurusan` VARCHAR(64) NOT NULL,
  `status` ENUM('unregistered', 'registered') NOT NULL DEFAULT 'unregistered',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_student_reg_nisn` (`nisn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. OFFICIAL TEACHER REGISTRY (Master Data Guru Sekolah untuk validasi pendaftaran)
CREATE TABLE `teacher_registry` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_identifier` VARCHAR(64) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(128) NOT NULL,
  `status` ENUM('unregistered', 'registered') NOT NULL DEFAULT 'unregistered',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_teacher_reg_id` (`employee_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ELECTIONS (Periode Pemilihan)
CREATE TABLE `elections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_periode` VARCHAR(128) NOT NULL,
  `tahun` VARCHAR(16) NOT NULL,
  `deskripsi` TEXT NULL,
  `start_at` DATETIME NOT NULL,
  `end_at` DATETIME NOT NULL,
  `status` ENUM('draft', 'scheduled', 'ongoing', 'closed', 'results_published') NOT NULL DEFAULT 'draft',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `teacher_can_vote` TINYINT(1) NOT NULL DEFAULT 0,
  `results_visibility` ENUM('hidden', 'realtime', 'final') NOT NULL DEFAULT 'hidden',
  `results_published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_election_active` (`is_active`),
  INDEX `idx_election_dates` (`start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. CANDIDATES (Pasangan Calon Ketua & Wakil Ketua OSIS)
CREATE TABLE `candidates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `election_id` INT NOT NULL,
  `nomor_urut` INT NOT NULL,
  `nama_ketua` VARCHAR(128) NOT NULL,
  `kelas_ketua` VARCHAR(64) NOT NULL,
  `foto_ketua` VARCHAR(255) NULL,
  `nama_wakil` VARCHAR(128) NOT NULL,
  `kelas_wakil` VARCHAR(64) NOT NULL,
  `foto_wakil` VARCHAR(255) NULL,
  `foto_pasangan` VARCHAR(255) NULL,
  `visi` TEXT NOT NULL,
  `misi` TEXT NOT NULL,
  `program_kerja` TEXT NULL,
  `publication_status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_candidates_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_election_nomor_urut` (`election_id`, `nomor_urut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. VOTING ELIGIBILITY (Daftar Pemilih Tetap per Periode)
CREATE TABLE `voting_eligibility` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `election_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `eligibility_status` ENUM('eligible', 'ineligible') NOT NULL DEFAULT 'eligible',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_eligibility_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eligibility_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_eligibility_election_user` (`election_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. VOTING PARTICIPATION (Audit Partisipasi Pemilih - Mencegah Double Voting)
CREATE TABLE `voting_participation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `election_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `voted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_participation_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participation_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_participation_election_user` (`election_id`, `user_id`),
  INDEX `idx_participation_election` (`election_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. VOTES (Kotak Suara Anonim - Suara Sah OSIS - TIDAK MENYIMPAN USER_ID)
CREATE TABLE `votes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `election_id` INT NOT NULL,
  `candidate_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_votes_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_votes_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  INDEX `idx_votes_election_candidate` (`election_id`, `candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. ANNOUNCEMENTS
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. ACTIVITY LOGS (Audit Log Administratif)
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(64) NOT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_logs_action` (`action`),
  INDEX `idx_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. APP SETTINGS (Pengaturan Logo, Nama Sekolah, Kontak, dll.)
CREATE TABLE `app_settings` (
  `setting_key` VARCHAR(64) PRIMARY KEY,
  `setting_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. LOGIN RATE LIMITS (Proteksi Brute Force)
CREATE TABLE `login_rate_limits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(128) NOT NULL,
  `attempt_time` INT NOT NULL,
  INDEX `idx_rate_limits` (`identifier`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
