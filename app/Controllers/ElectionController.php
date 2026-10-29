<?php
// app/Controllers/ElectionController.php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/CsrfHelper.php';
require_once __DIR__ . '/../Helpers/LogHelper.php';
require_once __DIR__ . '/../Models/Election.php';

class ElectionController {
    public static function handleSave(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        $id = (int)($_POST['election_id'] ?? 0);
        $nama = trim($_POST['nama_periode'] ?? '');
        $tahun = trim($_POST['tahun'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $startAt = trim($_POST['start_at'] ?? '');
        $endAt = trim($_POST['end_at'] ?? '');
        $status = trim($_POST['status'] ?? 'draft');
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        $teacherCanVote = !empty($_POST['teacher_can_vote']) ? 1 : 0;
        $resultsVisibility = trim($_POST['results_visibility'] ?? 'hidden');

        if (empty($nama) || empty($tahun) || empty($startAt) || empty($endAt)) {
            set_flash('error', 'Semua kolom bertanda bintang wajib diisi.');
            header("Location: " . base_url('admin/elections.php'));
            exit;
        }

        if (strtotime($endAt) <= strtotime($startAt)) {
            set_flash('error', 'Waktu selesai harus lebih besar daripada waktu mulai.');
            header("Location: " . base_url('admin/elections.php'));
            exit;
        }

        $data = [
            'nama_periode'       => $nama,
            'tahun'              => $tahun,
            'deskripsi'          => $deskripsi,
            'start_at'           => $startAt,
            'end_at'             => $endAt,
            'status'             => $status,
            'is_active'          => $isActive,
            'teacher_can_vote'   => $teacherCanVote,
            'results_visibility' => $resultsVisibility
        ];

        if ($id > 0) {
            Election::update($id, $data);
            LogHelper::log('ELECTION_UPDATED', "Admin memperbarui periode pemilihan ID {$id}: '{$nama}'.");
            set_flash('success', "Periode pemilihan '{$nama}' berhasil diperbarui.");
        } else {
            $newId = Election::create($data);
            LogHelper::log('ELECTION_CREATED', "Admin membuat periode pemilihan baru ID {$newId}: '{$nama}'.");
            set_flash('success', "Periode pemilihan '{$nama}' berhasil dibuat.");
        }

        header("Location: " . base_url('admin/elections.php'));
        exit;
    }

    public static function handleSetActive(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        $id = (int)($_POST['election_id'] ?? 0);
        if ($id > 0 && Election::setActive($id)) {
            LogHelper::log('ELECTION_ACTIVATED', "Admin menetapkan periode ID {$id} sebagai pemilihan aktif.");
            set_flash('success', 'Periode pemilihan aktif berhasil diubah.');
        } else {
            set_flash('error', 'Gagal mengubah periode pemilihan aktif.');
        }

        header("Location: " . base_url('admin/elections.php'));
        exit;
    }

    public static function handleSetVisibility(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        $id = (int)($_POST['election_id'] ?? 0);
        $vis = trim($_POST['results_visibility'] ?? 'hidden');

        if ($id > 0 && in_array($vis, ['hidden', 'realtime', 'final'], true)) {
            Election::setResultsVisibility($id, $vis);
            LogHelper::log('RESULTS_VISIBILITY_CHANGED', "Admin mengubah publikasi hasil periode ID {$id} menjadi '{$vis}'.");
            set_flash('success', "Pengaturan publikasi hasil berhasil diperbarui ke mode: " . strtoupper($vis));
        } else {
            set_flash('error', 'Opsi visibilitas tidak valid.');
        }

        header("Location: " . base_url('admin/results.php'));
        exit;
    }

    public static function handleSyncEligibility(): void {
        AuthHelper::requireRole('admin');
        CsrfHelper::validateOrAbort();

        $id = (int)($_POST['election_id'] ?? 0);
        $election = Election::getById($id);
        if (!$election) {
            set_flash('error', 'Periode pemilihan tidak ditemukan.');
            header("Location: " . base_url('admin/elections.php'));
            exit;
        }

        $includeTeachers = (bool)$election['teacher_can_vote'];
        $count = Election::syncEligibleVoters($id, $includeTeachers);

        LogHelper::log('ELIGIBILITY_SYNCED', "Admin menyinkronkan daftar pemilih tetap untuk periode ID {$id}. Ditambahkan: {$count} pemilih.");
        set_flash('success', "Sinkronisasi berhasil! Sebanyak <strong>{$count}</strong> pemilih aktif telah dimasukkan ke daftar pemilih tetap periode ini.");

        header("Location: " . base_url('admin/voters.php?election_id=' . $id));
        exit;
    }
}
