<?php
// app/Helpers/CertificatePdfHelper.php
// Generator Dokumen PDF Resmi Sertifikat & Bukti Partisipasi Pemilihan OSIS (Pure PHP PDF 1.4)

class CertificatePdfHelper
{
    /**
     * Escape string untuk literal teks PDF (...)
     */
    private static function pdfEscape(string $text): string
    {
        // Konversi UTF-8 ke Windows-1252 / ISO-8859-1 agar karakter umum tampil rapi di Type1 Font
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\\(', '\\)', '', ' '],
            $text
        );
    }

    /**
     * Bungkus teks panjang menjadi beberapa baris sesuai batas karakter
     */
    private static function wrapText(string $text, int $maxChars): array
    {
        $wrapped = wordwrap(trim($text), $maxChars, "\n", true);
        return array_filter(array_map('trim', explode("\n", $wrapped)), fn($l) => $l !== '');
    }

    /**
     * Siapkan buffer JPEG RGB untuk logo sekolah agar dapat di-embed ke PDF (/DCTDecode)
     */
    private static function getLogoJpegData(): ?array
    {
        $logoRel = app_setting('logo_url', '');
        $candidates = [];
        if (!empty($logoRel)) {
            $candidates[] = PUBLIC_PATH . '/' . ltrim($logoRel, '/');
        }
        $candidates[] = PUBLIC_PATH . '/assets/images/logo-placeholder-source.png';

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }
            $info = @getimagesize($path);
            if (!$info || empty($info[0]) || empty($info[1])) {
                continue;
            }
            $mime = $info['mime'] ?? '';
            if ($mime === 'image/jpeg') {
                $bytes = @file_get_contents($path);
                if ($bytes !== false && strlen($bytes) > 0) {
                    return [
                        'width'  => (int)$info[0],
                        'height' => (int)$info[1],
                        'data'   => $bytes,
                    ];
                }
            } else {
                // Konversi PNG/GIF ke JPEG cache di atas background putih agar kompatibel dengan PDF /DCTDecode
                $cacheDir = PUBLIC_PATH . '/uploads/settings';
                if (!is_dir($cacheDir)) {
                    @mkdir($cacheDir, 0775, true);
                }
                $cacheJpg = $cacheDir . '/logo_pdf_cache.jpg';
                if (!is_file($cacheJpg) || @filemtime($cacheJpg) < @filemtime($path)) {
                    $psSrc = str_replace("'", "''", $path);
                    $psDst = str_replace("'", "''", $cacheJpg);
                    $psCmd = "Add-Type -AssemblyName System.Drawing; "
                           . "\$src = [System.Drawing.Image]::FromFile('$psSrc'); "
                           . "\$bmp = New-Object System.Drawing.Bitmap(160, 160); "
                           . "\$g = [System.Drawing.Graphics]::FromImage(\$bmp); "
                           . "\$g.Clear([System.Drawing.Color]::White); "
                           . "\$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic; "
                           . "\$g.DrawImage(\$src, 0, 0, 160, 160); "
                           . "\$g.Dispose(); \$src.Dispose(); "
                           . "\$bmp.Save('$psDst', [System.Drawing.Imaging.ImageFormat]::Jpeg); "
                           . "\$bmp.Dispose();";
                    @shell_exec('powershell -NoProfile -NonInteractive -Command "' . $psCmd . '"');
                }
                if (is_file($cacheJpg)) {
                    $cInfo = @getimagesize($cacheJpg);
                    $bytes = @file_get_contents($cacheJpg);
                    if ($cInfo && $bytes !== false && strlen($bytes) > 0) {
                        return [
                            'width'  => (int)$cInfo[0],
                            'height' => (int)$cInfo[1],
                            'data'   => $bytes,
                        ];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Bangun binary PDF 1.4 untuk Sertifikat & Bukti Partisipasi Pemilih
     */
    public static function generatePdfBytes(array $user, array $election, string $votedAt, string $certCode): string
    {
        $schoolName    = strtoupper(app_setting('school_name', SCHOOL_NAME));
        $appTitle      = app_setting('app_title', APP_NAME);
        $schoolAddress = app_setting('school_address', 'Jl. Babakan Peuteuy No.300, Kec. Cicalengka, Kabupaten Bandung, Jawa Barat 40395');
        $ketuaPanitia  = app_setting('ketua_panitia_nama', 'Drs. H. Ahmad Fauzi, M.Pd.');
        $ketuaNip      = app_setting('ketua_panitia_nip', '198501152010011001');
        $kepsekNama    = app_setting('kepala_sekolah_nama', 'H. Dadang Sulaeman, S.Pd., M.M.');
        $kepsekNip     = app_setting('kepala_sekolah_nip', '197508102003121002');

        $isGuru        = (($user['role'] ?? 'siswa') === 'guru');
        $roleLabel     = $isGuru ? 'Dewan Guru / Tenaga Pendidik' : 'Siswa Pemilih Aktif';
        $idLabel       = $isGuru ? 'NIP / ID Pegawai' : 'NISN / Nomor Induk';
        $idValue       = (string)($user['employee_identifier'] ?? $user['username'] ?? '-');
        $unitLabel     = $isGuru ? 'Mata Pelajaran / Unit' : 'Kelas & Kompetensi Keahlian';
        $unitValue     = $isGuru
            ? (string)($user['mata_pelajaran'] ?? 'Dewan Guru')
            : trim((string)($user['kelas'] ?? '') . ' - ' . (string)($user['jurusan'] ?? ''));
        if ($unitValue === '-' || $unitValue === '') {
            $unitValue = '-';
        }

        $namaPemilih   = (string)($user['nama_lengkap'] ?? $user['username'] ?? '-');
        $namaPeriode   = (string)($election['nama_periode'] ?? 'Pemilihan Ketua & Wakil Ketua OSIS');
        $tahunPeriode  = (string)($election['tahun'] ?? date('Y'));
        $waktuVote     = $votedAt ? date('d F Y, H:i:s', strtotime($votedAt)) . ' WIB' : date('d F Y, H:i:s') . ' WIB';
        $tanggalCetak  = date('d F Y, H:i') . ' WIB';
        $docNumber     = sprintf('%04d/CERT-EVOTE/OSIS/%s', (int)($user['id'] ?? 1), date('Y'));

        $logo = self::getLogoJpegData();

        // Ukuran Halaman A4 Portrait: 595.28 x 841.89 pt
        $stream = [];

        // Helper gambar teks rata tengah
        $drawCenteredText = function (float $y, string $font, float $size, string $text, array $rgb = [0.06, 0.09, 0.16]) use (&$stream) {
            $approxWidth = strlen($text) * ($size * 0.50);
            if ($font === 'F2' || $font === 'F4') {
                $approxWidth = strlen($text) * ($size * 0.53);
            }
            $x = max(45.0, (595.28 - $approxWidth) / 2.0);
            $stream[] = sprintf('%.3f %.3f %.3f rg', $rgb[0], $rgb[1], $rgb[2]);
            $stream[] = sprintf('BT /%s %.1f Tf %.2f %.2f Td (%s) Tj ET', $font, $size, $x, $y, self::pdfEscape($text));
        };

        // Helper gambar teks rata kiri
        $drawText = function (float $x, float $y, string $font, float $size, string $text, array $rgb = [0.06, 0.09, 0.16]) use (&$stream) {
            $stream[] = sprintf('%.3f %.3f %.3f rg', $rgb[0], $rgb[1], $rgb[2]);
            $stream[] = sprintf('BT /%s %.1f Tf %.2f %.2f Td (%s) Tj ET', $font, $size, $x, $y, self::pdfEscape($text));
        };

        // 1. Bingkai Sertifikat Ganda (Outer & Inner Border)
        // Background putih bersih
        $stream[] = '1 1 1 rg 0 0 595.28 841.89 re f';
        // Bingkai luar Navy (#0f172a)
        $stream[] = '0.059 0.090 0.165 RG 2.2 w 24 24 547.28 793.89 re S';
        // Bingkai dalam Emas (#d97706)
        $stream[] = '0.851 0.467 0.024 RG 0.9 w 29 29 537.28 783.89 re S';

        // Banner Header Atas Navy (#0f172a)
        $stream[] = '0.059 0.090 0.165 rg 29 798 537.28 14.89 re f';
        // Garis Aksen Emas di bawah banner atas
        $stream[] = '0.961 0.620 0.043 rg 29 795 537.28 3 re f';

        // 2. Kop Surat Resmi Sekolah
        if ($logo !== null) {
            // Gambar logo di sebelah kiri kop surat (x=46, y=724, w=52, h=52)
            $stream[] = 'q 52 0 0 52 46 724 cm /Im1 Do Q';
            $drawText(110, 762, 'F2', 13.5, $schoolName, [0.059, 0.090, 0.165]);
            $drawText(110, 746, 'F2', 10.0, 'KOMISI PEMILIHAN ORGANISASI SISWA INTRA SEKOLAH (OSIS)', [0.145, 0.388, 0.922]);
            $addrLines = self::wrapText($schoolAddress, 78);
            $addrY = 733;
            foreach (array_slice($addrLines, 0, 2) as $al) {
                $drawText(110, $addrY, 'F1', 8.5, $al, [0.392, 0.455, 0.545]);
                $addrY -= 11;
            }
        } else {
            $drawCenteredText(762, 'F2', 14.0, $schoolName, [0.059, 0.090, 0.165]);
            $drawCenteredText(746, 'F2', 10.5, 'KOMISI PEMILIHAN ORGANISASI SISWA INTRA SEKOLAH (OSIS)', [0.145, 0.388, 0.922]);
            $addrLines = self::wrapText($schoolAddress, 85);
            $addrY = 733;
            foreach (array_slice($addrLines, 0, 2) as $al) {
                $drawCenteredText($addrY, 'F1', 8.5, $al, [0.392, 0.455, 0.545]);
                $addrY -= 11;
            }
        }

        // Garis Kop Surat Ganda
        $stream[] = '0.059 0.090 0.165 RG 1.8 w 44 712 m 551.28 712 l S';
        $stream[] = '0.580 0.639 0.722 RG 0.6 w 44 709 m 551.28 709 l S';

        // 3. Judul Sertifikat & Badge Status
        // Pill Badge Hijau Muda
        $stream[] = '0.925 0.992 0.961 rg 0.063 0.725 0.506 RG 1 w 185 676 225.28 20 re B';
        $drawCenteredText(682.5, 'F2', 8.5, 'BUKTI SAH PARTISIPASI PEMILIHAN ELEKTRONIK', [0.020, 0.588, 0.412]);

        $drawCenteredText(652, 'F2', 15.5, 'SERTIFIKAT PARTISIPASI PEMILIH', [0.059, 0.090, 0.165]);
        $drawCenteredText(636, 'F1', 9.5, 'Nomor Dokumen: ' . $docNumber, [0.392, 0.455, 0.545]);

        // Paragraf Pengantar
        $introText = 'Komisi Pemilihan OSIS ' . app_setting('school_name', SCHOOL_NAME) . ' menerangkan bahwa pemilih di bawah ini telah menggunakan hak suaranya secara sah, mandiri, dan rahasia pada pelaksanaan ' . $namaPeriode . ' (' . $tahunPeriode . '):';
        $introLines = self::wrapText($introText, 88);
        $introY = 612;
        foreach ($introLines as $line) {
            $drawText(48, $introY, 'F1', 9.5, $line, [0.20, 0.25, 0.33]);
            $introY -= 13.5;
        }

        // 4. Kotak Tabel Data Identitas Pemilih
        $boxTop = 574;
        $boxHeight = 190;
        $boxBottom = $boxTop - $boxHeight;

        // Background kotak abu sangat muda (#f8fafc) & border (#cbd5e1)
        $stream[] = '0.973 0.980 0.988 rg 0.796 0.835 0.882 RG 1 w 48 ' . $boxBottom . ' 499.28 ' . $boxHeight . ' re B';
        // Header bar kotak identitas
        $stream[] = '0.059 0.090 0.165 rg 48 ' . ($boxTop - 26) . ' 499.28 26 re f';
        $drawText(60, $boxTop - 17, 'F2', 9.5, 'RINCIAN IDENTITAS PEMILIH & REKAM JEJAK PARTISIPASI', [1.0, 1.0, 1.0]);

        $rows = [
            ['Nama Lengkap Pemilih', $namaPemilih, true],
            ['Kategori Hak Akses', $roleLabel, false],
            [$idLabel, $idValue, true],
            [$unitLabel, $unitValue, false],
            ['Periode Pemilihan', $namaPeriode . ' (' . $tahunPeriode . ')', false],
            ['Waktu Pencoblosan Suara', $waktuVote, true],
            ['Status Hak Suara', 'SAH - SUARA TELAH TERCATAT DI KOTAK SUARA DIGITAL', true],
        ];

        $rowY = $boxTop - 45;
        foreach ($rows as $idx => $r) {
            [$label, $val, $isBold] = $r;
            if ($idx > 0) {
                $lineY = $rowY + 11;
                $stream[] = '0.886 0.910 0.941 RG 0.5 w 58 ' . $lineY . ' m 537 ' . $lineY . ' l S';
            }
            $drawText(60, $rowY, 'F2', 9.0, $label, [0.278, 0.333, 0.412]);
            $drawText(215, $rowY, 'F1', 9.0, ':', [0.278, 0.333, 0.412]);

            $valColor = ($idx === 6) ? [0.020, 0.588, 0.412] : [0.059, 0.090, 0.165];
            $valFont  = $isBold ? 'F2' : 'F1';
            $drawText(225, $rowY, $valFont, 9.2, mb_strimwidth($val, 0, 56, '...'), $valColor);
            $rowY -= 22;
        }

        // 5. Kotak Kode Verifikasi Digital & Stempel SAH
        $verifBottom = 312;
        $verifHeight = 56;
        // Kotak Kiri: Kode Verifikasi SHA-256
        $stream[] = '0.996 0.953 0.780 rg 0.961 0.620 0.043 RG 1.2 w 48 ' . $verifBottom . ' 355 ' . $verifHeight . ' re B';
        $drawText(62, $verifBottom + 36, 'F2', 8.5, 'KODE VERIFIKASI SERTIFIKAT DIGITAL (SHA-256 ANONIM):', [0.573, 0.251, 0.055]);
        $drawText(62, $verifBottom + 16, 'F4', 13.0, $certCode, [0.059, 0.090, 0.165]);

        // Kotak Kanan: Stempel Digital SAH • VOTED
        $stream[] = '0.925 0.992 0.961 rg 0.063 0.725 0.506 RG 1.8 w 415 ' . $verifBottom . ' 132.28 ' . $verifHeight . ' re B';
        $stream[] = '0.020 0.588 0.412 RG 0.7 w 419 ' . ($verifBottom + 4) . ' 124.28 ' . ($verifHeight - 8) . ' re S';
        $drawText(435, $verifBottom + 35, 'F2', 11.0, 'SAH / VOTED', [0.020, 0.588, 0.412]);
        $drawText(429, $verifBottom + 21, 'F2', 7.5, 'TERVERIFIKASI SISTEM', [0.059, 0.090, 0.165]);
        $drawText(438, $verifBottom + 10, 'F1', 7.0, 'E-VOTING OSIS RESMI', [0.278, 0.333, 0.412]);

        // 6. Kotak Jaminan Kerahasiaan Suara (Secret Ballot Notice)
        $secBottom = 236;
        $secHeight = 62;
        $stream[] = '0.941 0.965 1.000 rg 0.576 0.773 0.992 RG 1 w 48 ' . $secBottom . ' 499.28 ' . $secHeight . ' re B';
        $drawText(60, $secBottom + 44, 'F2', 9.0, 'JAMINAN ASAS KERAHASIAAN SUARA PENUH (SECRET BALLOT ENFORCED)', [0.114, 0.306, 0.847]);
        $secText = 'Dokumen ini adalah bukti sah kehadiran/partisipasi pemilih. Pilihan pasangan calon yang dicoblos tersimpan secara terenkripsi dan terpisah dari identitas akun pemilih (Zero-Trace Architecture), sehingga dijamin 100% rahasia sesuai asas LUBER JURDIL.';
        $secLines = self::wrapText($secText, 92);
        $secY = $secBottom + 30;
        foreach (array_slice($secLines, 0, 3) as $sl) {
            $drawText(60, $secY, 'F1', 8.2, $sl, [0.20, 0.25, 0.33]);
            $secY -= 11;
        }

        // 7. Blok Tanda Tangan Pengesahan
        $sigTop = 200;
        $drawText(72, $sigTop, 'F1', 9.0, 'Mengetahui & Mengesahkan,', [0.278, 0.333, 0.412]);
        $drawText(72, $sigTop - 14, 'F2', 9.5, 'Ketua Panitia Pemilihan OSIS', [0.059, 0.090, 0.165]);

        $drawText(345, $sigTop, 'F1', 9.0, 'Diterbitkan secara elektronik pada ' . date('d M Y'), [0.278, 0.333, 0.412]);
        $drawText(345, $sigTop - 14, 'F2', 9.5, 'Kepala Sekolah', [0.059, 0.090, 0.165]);

        // Badge Tanda Tangan Elektronik di tengah ruang tanda tangan
        $stream[] = '0.973 0.980 0.988 rg 0.796 0.835 0.882 RG 0.8 w 72 ' . ($sigTop - 56) . ' 175 28 re B';
        $drawText(80, $sigTop - 40, 'F2', 7.5, 'TERVERIFIKASI DIGITAL PANITIA', [0.020, 0.588, 0.412]);
        $drawText(80, $sigTop - 50, 'F1', 7.0, 'Hash: ' . substr($certCode, 0, 18), [0.392, 0.455, 0.545]);

        $stream[] = '0.973 0.980 0.988 rg 0.796 0.835 0.882 RG 0.8 w 345 ' . ($sigTop - 56) . ' 175 28 re B';
        $drawText(353, $sigTop - 40, 'F2', 7.5, 'DISAHKAN SISTEM E-VOTING', [0.145, 0.388, 0.922]);
        $drawText(353, $sigTop - 50, 'F1', 7.0, $appTitle, [0.392, 0.455, 0.545]);

        // Nama & NIP Pejabat
        $drawText(72, $sigTop - 74, 'F2', 9.5, $ketuaPanitia, [0.059, 0.090, 0.165]);
        $stream[] = '0.059 0.090 0.165 RG 0.8 w 72 ' . ($sigTop - 77) . ' m 255 ' . ($sigTop - 77) . ' l S';
        $drawText(72, $sigTop - 89, 'F1', 8.5, 'NIP. ' . $ketuaNip, [0.392, 0.455, 0.545]);

        $drawText(345, $sigTop - 74, 'F2', 9.5, $kepsekNama, [0.059, 0.090, 0.165]);
        $stream[] = '0.059 0.090 0.165 RG 0.8 w 345 ' . ($sigTop - 77) . ' m 535 ' . ($sigTop - 77) . ' l S';
        $drawText(345, $sigTop - 89, 'F1', 8.5, 'NIP. ' . $kepsekNip, [0.392, 0.455, 0.545]);

        // 8. Footer Dokumen
        $stream[] = '0.886 0.910 0.941 RG 0.8 w 48 64 m 547.28 64 l S';
        $drawText(48, 50, 'F3', 7.8, 'Dokumen ini diterbitkan secara otomatis oleh ' . $appTitle . ' - ' . app_setting('school_name', SCHOOL_NAME) . '.', [0.392, 0.455, 0.545]);
        $drawText(48, 39, 'F1', 7.8, 'Dicetak/diunduh pada: ' . $tanggalCetak . '  |  Kode Bukti: ' . $certCode, [0.392, 0.455, 0.545]);

        $contentStream = implode("\n", $stream);

        // Rakit struktur objek PDF 1.4
        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";

        $xObjectRef = ($logo !== null) ? " /XObject << /Im1 9 0 R >>" : "";
        $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R /F4 7 0 R >>" . $xObjectRef . " >> /Contents 8 0 R >>";
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>";
        $objects[7] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold /Encoding /WinAnsiEncoding >>";
        $objects[8] = "<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream";

        if ($logo !== null) {
            $objects[9] = "<< /Type /XObject /Subtype /Image /Width " . $logo['width'] . " /Height " . $logo['height'] . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($logo['data']) . " >>\nstream\n" . $logo['data'] . "\nendstream";
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $num => $objContent) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $num . " 0 obj\n" . $objContent . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $totalObjects = count($objects) + 1;
        $pdf .= "xref\n0 " . $totalObjects . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . $totalObjects . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    /**
     * Kirim output file PDF ke browser (Download langsung atau tampilkan di tab baru siap cetak)
     */
    public static function outputPdf(array $user, array $election, string $votedAt, string $certCode, bool $forceDownload = true): void
    {
        $pdfBytes = self::generatePdfBytes($user, $election, $votedAt, $certCode);
        $cleanId = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($user['employee_identifier'] ?? $user['username'] ?? $user['id']));
        $filename = 'Sertifikat_Partisipasi_OSIS_' . $cleanId . '_' . $certCode . '.pdf';

        if (ob_get_length()) {
            @ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($forceDownload ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBytes));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfBytes;
        exit;
    }
}
