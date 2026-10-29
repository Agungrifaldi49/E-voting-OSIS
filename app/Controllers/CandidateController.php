<?php
// app/Controllers/CandidateController.php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/CsrfHelper.php';
require_once __DIR__ . '/../Helpers/LogHelper.php';
require_once __DIR__ . '/../Helpers/ValidationHelper.php';
require_once __DIR__ . '/../Models/Candidate.php';
require_once __DIR__ . '/../Models/Election.php';

class CandidateController {
    public static function handleSave(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        $id           = (int)($_POST['candidate_id'] ?? 0);
        $electionId   = (int)($_POST['election_id'] ?? 0);
        $nomorUrut    = (int)($_POST['nomor_urut'] ?? 0);
        $namaKetua    = trim($_POST['nama_ketua'] ?? '');
        $kelasKetua   = trim($_POST['kelas_ketua'] ?? '');
        $namaWakil    = trim($_POST['nama_wakil'] ?? '');
        $kelasWakil   = trim($_POST['kelas_wakil'] ?? '');
        $visi         = trim($_POST['visi'] ?? '');
        $misi         = trim($_POST['misi'] ?? '');
        $programKerja = trim($_POST['program_kerja'] ?? '');
        $pubStatus    = trim($_POST['publication_status'] ?? 'published');

        if (!$electionId || !$nomorUrut || empty($namaKetua) || empty($kelasKetua) || empty($namaWakil) || empty($kelasWakil) || empty($visi) || empty($misi)) {
            set_flash('error', 'Semua kolom bertanda bintang wajib diisi.');
            header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
            exit;
        }

        // Check if number is already taken
        if (Candidate::isNumberTaken($electionId, $nomorUrut, $id ?: null)) {
            set_flash('error', "Nomor urut {$nomorUrut} sudah digunakan oleh pasangan calon lain dalam periode ini.");
            header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
            exit;
        }

        // Handle photo uploads
        $fotoKetua = null;
        $fotoWakil = null;
        $fotoPasangan = null;

        if (!empty($_FILES['foto_ketua']['name'])) {
            $up = ValidationHelper::handlePhotoUpload($_FILES['foto_ketua'], UPLOAD_DIR);
            if (!$up['success']) {
                set_flash('error', "Foto Ketua: " . $up['error']);
                header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
                exit;
            }
            $fotoKetua = $up['filename'];
        }

        if (!empty($_FILES['foto_wakil']['name'])) {
            $up = ValidationHelper::handlePhotoUpload($_FILES['foto_wakil'], UPLOAD_DIR);
            if (!$up['success']) {
                set_flash('error', "Foto Wakil: " . $up['error']);
                header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
                exit;
            }
            $fotoWakil = $up['filename'];
        }

        if (!empty($_FILES['foto_pasangan']['name'])) {
            $up = ValidationHelper::handlePhotoUpload($_FILES['foto_pasangan'], UPLOAD_DIR);
            if (!$up['success']) {
                set_flash('error', "Foto Pasangan: " . $up['error']);
                header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
                exit;
            }
            $fotoPasangan = $up['filename'];
        }

        $data = [
            'election_id'        => $electionId,
            'nomor_urut'         => $nomorUrut,
            'nama_ketua'         => $namaKetua,
            'kelas_ketua'        => $kelasKetua,
            'nama_wakil'         => $namaWakil,
            'kelas_wakil'        => $kelasWakil,
            'visi'               => $visi,
            'misi'               => $misi,
            'program_kerja'      => $programKerja,
            'publication_status' => $pubStatus
        ];

        if ($fotoKetua) $data['foto_ketua'] = $fotoKetua;
        if ($fotoWakil) $data['foto_wakil'] = $fotoWakil;
        if ($fotoPasangan) $data['foto_pasangan'] = $fotoPasangan;

        if ($id > 0) {
            Candidate::update($id, $data);
            LogHelper::log('CANDIDATE_UPDATED', "Admin memperbarui kandidat No. {$nomorUrut} ({$namaKetua} & {$namaWakil}).");
            set_flash('success', "Data pasangan calon nomor urut {$nomorUrut} berhasil diperbarui.");
        } else {
            $newId = Candidate::create($data);
            LogHelper::log('CANDIDATE_CREATED', "Admin menambahkan kandidat baru ID {$newId}: No. {$nomorUrut} ({$namaKetua} & {$namaWakil}).");
            set_flash('success', "Pasangan calon nomor urut {$nomorUrut} berhasil didaftarkan.");
        }

        header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
        exit;
    }

    public static function handleDelete(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        $id = (int)($_POST['candidate_id'] ?? 0);
        $electionId = (int)($_POST['election_id'] ?? 0);

        $cand = Candidate::getById($id);
        if (!$cand) {
            set_flash('error', 'Kandidat tidak ditemukan.');
            header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
            exit;
        }

        if (Candidate::hasVotes($id)) {
            set_flash('error', 'Kandidat tidak dapat dihapus karena sudah memiliki suara sah yang tercatat dalam pemilihan.');
            header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
            exit;
        }

        if (Candidate::delete($id)) {
            LogHelper::log('CANDIDATE_DELETED', "Admin menghapus kandidat No. {$cand['nomor_urut']} ({$cand['nama_ketua']} & {$cand['nama_wakil']}).");
            set_flash('success', "Pasangan calon nomor urut {$cand['nomor_urut']} berhasil dihapus.");
        } else {
            set_flash('error', 'Gagal menghapus kandidat.');
        }

        header("Location: " . base_url("admin/candidates.php?election_id={$electionId}"));
        exit;
    }
}
