<?php
// app/Controllers/AuthController.php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Registry.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/CsrfHelper.php';
require_once __DIR__ . '/../Helpers/ValidationHelper.php';

class AuthController {
    public static function handleLogin(): void {
        CsrfHelper::validateOrAbort();

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            set_flash('error', 'Silakan masukkan username/NISN/NIP dan password Anda.');
            header("Location: " . base_url('login.php'));
            exit;
        }

        // Rate Limiting (5 attempts in 5 minutes)
        if (AuthHelper::isRateLimited($username, 5, 300)) {
            LogHelper::log('AUTH_RATE_LIMIT', "Upaya login diblokir karena melebihi batas percobaan: {$username}");
            set_flash('error', 'Terlalu banyak percobaan login yang gagal. Silakan tunggu 5 menit sebelum mencoba kembali.');
            header("Location: " . base_url('login.php'));
            exit;
        }

        $user = User::findByUsername($username);

        // Generic error message to prevent account enumeration
        if (!$user || !password_verify($password, $user['password_hash'])) {
            AuthHelper::recordFailedAttempt($username);
            set_flash('error', 'Username/identitas atau password yang Anda masukkan salah.');
            header("Location: " . base_url('login.php'));
            exit;
        }

        // Account status check
        if ($user['account_status'] === 'pending') {
            set_flash('warning', 'Akun Anda sedang menunggu verifikasi oleh panitia pemilihan/admin sekolah. Silakan hubungi admin jika verifikasi belum selesai.');
            header("Location: " . base_url('login.php'));
            exit;
        }

        if ($user['account_status'] === 'rejected') {
            $reason = !empty($user['rejection_reason']) ? " Alasan: " . htmlspecialchars($user['rejection_reason'], ENT_QUOTES, 'UTF-8') : '';
            set_flash('error', 'Pendaftaran akun Anda ditolak oleh panitia.' . $reason);
            header("Location: " . base_url('login.php'));
            exit;
        }

        if ($user['account_status'] === 'inactive') {
            set_flash('error', 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi panitia OSIS.');
            header("Location: " . base_url('login.php'));
            exit;
        }

        // Successful authentication
        AuthHelper::clearLoginAttempts($username);
        AuthHelper::login($user);

        // Redirect based on role determined from database
        switch ($user['role']) {
            case 'admin':
                header("Location: " . base_url('admin/dashboard.php'));
                break;
            case 'guru':
                header("Location: " . base_url('guru/dashboard.php'));
                break;
            case 'siswa':
            default:
                header("Location: " . base_url('siswa/dashboard.php'));
                break;
        }
        exit;
    }

    public static function handleStudentRegister(): void {
        CsrfHelper::validateOrAbort();

        $nisn            = trim($_POST['nisn'] ?? '');
        $namaLengkap     = trim($_POST['nama_lengkap'] ?? '');
        $kelas           = trim($_POST['kelas'] ?? '');
        $jurusan         = trim($_POST['jurusan'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validations
        if (!ValidationHelper::validateNISN($nisn)) {
            set_flash('error', 'NISN harus terdiri dari 10 digit angka yang valid.');
            header("Location: " . base_url('register.php?type=siswa'));
            exit;
        }

        if (empty($namaLengkap) || empty($kelas) || empty($jurusan)) {
            set_flash('error', 'Semua kolom bertanda bintang wajib diisi.');
            header("Location: " . base_url('register.php?type=siswa'));
            exit;
        }

        if (!empty($email) && !ValidationHelper::validateEmail($email)) {
            set_flash('error', 'Format alamat email tidak valid.');
            header("Location: " . base_url('register.php?type=siswa'));
            exit;
        }

        $pwError = ValidationHelper::validatePassword($password, $confirmPassword);
        if ($pwError) {
            set_flash('error', $pwError);
            header("Location: " . base_url('register.php?type=siswa'));
            exit;
        }

        $res = User::createStudent([
            'nisn'         => $nisn,
            'nama_lengkap' => $namaLengkap,
            'kelas'        => $kelas,
            'jurusan'      => $jurusan,
            'email'        => $email,
            'password'     => $password
        ]);

        if (!$res['success']) {
            set_flash('error', $res['error']);
            header("Location: " . base_url('register.php?type=siswa'));
            exit;
        }

        LogHelper::log('REGISTER_SISWA', "Pendaftaran siswa baru berhasil: NISN {$nisn} ({$namaLengkap})", $res['user_id']);
        set_flash('success', 'Pendaftaran berhasil! Akun Anda berstatus PENDING dan akan segera diverifikasi oleh panitia pemilihan sebelum dapat digunakan untuk voting.');
        header("Location: " . base_url('login.php'));
        exit;
    }

    public static function handleTeacherRegister(): void {
        CsrfHelper::validateOrAbort();

        $identifier      = trim($_POST['identifier'] ?? '');
        $namaLengkap     = trim($_POST['nama_lengkap'] ?? '');
        $mataPelajaran   = trim($_POST['mata_pelajaran'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!ValidationHelper::validateIdentifier($identifier)) {
            set_flash('error', 'NIP / NUPTK / Kode Guru harus terdiri dari minimal 5 karakter alphanumeric.');
            header("Location: " . base_url('register.php?type=guru'));
            exit;
        }

        if (empty($namaLengkap)) {
            set_flash('error', 'Nama lengkap guru wajib diisi.');
            header("Location: " . base_url('register.php?type=guru'));
            exit;
        }

        if (!empty($email) && !ValidationHelper::validateEmail($email)) {
            set_flash('error', 'Format alamat email tidak valid.');
            header("Location: " . base_url('register.php?type=guru'));
            exit;
        }

        $pwError = ValidationHelper::validatePassword($password, $confirmPassword);
        if ($pwError) {
            set_flash('error', $pwError);
            header("Location: " . base_url('register.php?type=guru'));
            exit;
        }

        $res = User::createTeacher([
            'identifier'     => $identifier,
            'nama_lengkap'   => $namaLengkap,
            'mata_pelajaran' => $mataPelajaran,
            'email'          => $email,
            'password'       => $password
        ]);

        if (!$res['success']) {
            set_flash('error', $res['error']);
            header("Location: " . base_url('register.php?type=guru'));
            exit;
        }

        LogHelper::log('REGISTER_GURU', "Pendaftaran guru baru berhasil: {$identifier} ({$namaLengkap})", $res['user_id']);
        set_flash('success', 'Pendaftaran guru berhasil! Akun Anda sedang menunggu verifikasi panitia pemilihan.');
        header("Location: " . base_url('login.php'));
        exit;
    }

    public static function logout(): void {
        AuthHelper::logout();
        set_flash('info', 'Anda telah berhasil keluar dari sistem E-Voting.');
        header("Location: " . base_url('login.php'));
        exit;
    }
}
