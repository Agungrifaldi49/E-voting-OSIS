<?php
// app/Controllers/ResultController.php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/ValidationHelper.php';
require_once __DIR__ . '/../Models/Election.php';
require_once __DIR__ . '/../Models/Candidate.php';
require_once __DIR__ . '/../Models/Vote.php';

class ResultController {
    /**
     * API JSON Endpoint untuk Realtime Polling Chart.js
     */
    public static function apiResults(): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $electionId = (int)($_GET['election_id'] ?? 0);
        if (!$electionId) {
            $active = Election::getActive();
            $electionId = $active ? (int)$active['id'] : 0;
        }

        if (!$electionId) {
            echo json_encode(['success' => false, 'message' => 'Periode pemilihan tidak ditemukan.']);
            exit;
        }

        $election = Election::getById($electionId);
        if (!$election) {
            echo json_encode(['success' => false, 'message' => 'Periode pemilihan tidak ditemukan.']);
            exit;
        }

        // Cek Publikasi Hasil
        $isAdmin = (AuthHelper::check() && AuthHelper::role() === 'admin');
        $visibility = $election['results_visibility'] ?? 'hidden';

        if ($visibility === 'hidden' && !$isAdmin) {
            echo json_encode([
                'success'    => false,
                'visibility' => 'hidden',
                'message'    => 'Hasil perolehan suara belum dipublikasikan oleh panitia pemilihan.'
            ]);
            exit;
        }

        // Ambil data hasil voting
        $resultsData = Vote::getResults($electionId);
        $turnout = Vote::getParticipationStats($electionId);

        echo json_encode([
            'success'             => true,
            'visibility'          => $visibility,
            'is_admin'            => $isAdmin,
            'election'            => [
                'id'           => $election['id'],
                'nama_periode' => $election['nama_periode'],
                'tahun'        => $election['tahun'],
                'status'       => $election['computed_status']
            ],
            'total_suara_sah'     => $resultsData['total_suara_sah'],
            'candidates'          => $resultsData['candidates'],
            'turnout'             => $turnout,
            'updated_at'          => $resultsData['updated_at'],
            'updated_at_formatted'=> $resultsData['updated_at_formatted']
        ]);
        exit;
    }

    /**
     * API JSON Endpoint untuk Realtime Partisipasi (Admin)
     */
    public static function apiParticipation(): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $electionId = (int)($_GET['election_id'] ?? 0);
        if (!$electionId) {
            $active = Election::getActive();
            $electionId = $active ? (int)$active['id'] : 0;
        }

        if (!$electionId) {
            echo json_encode(['success' => false, 'message' => 'Periode tidak ditemukan.']);
            exit;
        }

        $turnout = Vote::getParticipationStats($electionId);
        echo json_encode([
            'success' => true,
            'turnout' => $turnout
        ]);
        exit;
    }

    /**
     * Ekspor Laporan Resmi Pemilihan ke CSV (Aman dari CSV Formula Injection)
     */
    public static function exportCsv(int $electionId): void {
        AuthHelper::requireRole('admin');

        $election = Election::getById($electionId);
        if (!$election) {
            set_flash('error', 'Periode pemilihan tidak ditemukan.');
            header("Location: " . base_url('admin/reports.php'));
            exit;
        }

        $results = Vote::getResults($electionId);
        $turnout = Vote::getParticipationStats($electionId);

        $filename = "Laporan_Hasil_Evoting_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $election['tahun']) . "_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Microsoft Excel
        fputs($output, "\xEF\xBB\xBF");

        // Header Laporan
        fputcsv($output, [ValidationHelper::sanitizeCsvValue('LAPORAN HASIL PEMILIHAN KETUA & WAKIL KETUA OSIS')]);
        fputcsv($output, [ValidationHelper::sanitizeCsvValue(SCHOOL_NAME)]);
        fputcsv($output, [ValidationHelper::sanitizeCsvValue('Periode: ' . $election['nama_periode'])]);
        fputcsv($output, [ValidationHelper::sanitizeCsvValue('Tahun Ajaran: ' . $election['tahun'])]);
        fputcsv($output, [ValidationHelper::sanitizeCsvValue('Waktu Pelaksanaan: ' . $election['start_at'] . ' s.d. ' . $election['end_at'])]);
        fputcsv($output, [ValidationHelper::sanitizeCsvValue('Tanggal Unduh Laporan: ' . date('d F Y, H:i:s') . ' WIB')]);
        fputcsv($output, []); // Empty line

        // Statistik Partisipasi
        fputcsv($output, ['RINGKASAN PARTISIPASI PEMILIH']);
        fputcsv($output, ['Kategori', 'Jumlah', 'Persentase']);
        fputcsv($output, ['Total Pemilih Berhak (DPT)', $turnout['total_eligible'], '100%']);
        fputcsv($output, ['Total Menggunakan Hak Suara', $turnout['total_voted'], $turnout['turnout_pct'] . '%']);
        fputcsv($output, ['Total Belum Memilih / Golput', $turnout['total_unvoted'], ($turnout['total_eligible'] > 0 ? round(($turnout['total_unvoted'] / $turnout['total_eligible']) * 100, 2) : 0) . '%']);
        fputcsv($output, ['Total Suara Sah Masuk', $results['total_suara_sah'], '-']);
        fputcsv($output, []);

        // Rekapitulasi Pasangan Calon
        fputcsv($output, ['REKAPITULASI PEROLEHAN SUARA PASANGAN CALON']);
        fputcsv($output, ['Nomor Urut', 'Calon Ketua OSIS', 'Kelas Ketua', 'Calon Wakil Ketua OSIS', 'Kelas Wakil', 'Perolehan Suara', 'Persentase']);

        foreach ($results['candidates'] as $c) {
            fputcsv($output, [
                ValidationHelper::sanitizeCsvValue($c['nomor_urut']),
                ValidationHelper::sanitizeCsvValue($c['nama_ketua']),
                ValidationHelper::sanitizeCsvValue($c['kelas_ketua']),
                ValidationHelper::sanitizeCsvValue($c['nama_wakil']),
                ValidationHelper::sanitizeCsvValue($c['kelas_wakil']),
                ValidationHelper::sanitizeCsvValue($c['total_suara']),
                ValidationHelper::sanitizeCsvValue($c['persentase'] . '%')
            ]);
        }

        fputcsv($output, []);
        fputcsv($output, [ValidationHelper::sanitizeCsvValue('*Catatan: Data laporan ini bersifat sah dan rahasia. Pilihan individu pemilih dirahasiakan sepenuhnya oleh sistem.')]);

        fclose($output);
        exit;
    }
}
