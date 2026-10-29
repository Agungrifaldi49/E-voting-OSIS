<?php
// tests/test_delete_student_teacher.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Models/User.php';
require_once dirname(__DIR__) . '/app/Models/Registry.php';
require_once dirname(__DIR__) . '/app/Helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/Helpers/CsrfHelper.php';

echo "=== MEMULAI TEST FITUR HAPUS SISWA & DEWAN GURU ===\n\n";

$pdo = Database::getConnection();

// Mock Admin Session
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'admin_test',
    'role' => 'admin',
    'nama_lengkap' => 'Admin Penguji'
];

// --- TEST 1: Proteksi Keamanan (Admin tidak boleh bisa dihapus) ---
echo "[TEST 1] Menguji proteksi akun admin agar tidak dapat dihapus...\n";
$adminUser = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
if ($adminUser) {
    $res = User::deleteUser((int)$adminUser['id']);
    if (!$res['success']) {
        echo "  [PASS] Akun admin aman dan tidak dapat dihapus: " . $res['error'] . "\n";
    } else {
        echo "  [FAIL] Akun admin terhapus secara tidak aman!\n";
        exit(1);
    }
} else {
    echo "  [SKIP] Tidak ada akun admin di database.\n";
}

// --- TEST 2: Buat data dummy siswa dan uji coba hapus ---
echo "\n[TEST 2] Menguji hapus data siswa pemilih...\n";
$dummyNisn = '9999888877';
// Pastikan registry tersedia
$pdo->prepare("DELETE FROM users WHERE username = :u")->execute([':u' => $dummyNisn]);
$pdo->prepare("DELETE FROM student_registry WHERE nisn = :n")->execute([':n' => $dummyNisn]);

$pdo->prepare("INSERT INTO student_registry (nisn, nama_lengkap, kelas, jurusan, status) VALUES (:n, 'Siswa Test Hapus', 'XII', 'RPL', 'unregistered')")
    ->execute([':n' => $dummyNisn]);

$studentCreate = User::createStudent([
    'nisn' => $dummyNisn,
    'nama_lengkap' => 'Siswa Test Hapus',
    'kelas' => 'XII',
    'jurusan' => 'RPL',
    'password' => 'password123'
]);

if (!$studentCreate['success']) {
    echo "  [FAIL] Gagal membuat siswa test: " . ($studentCreate['error'] ?? '') . "\n";
    exit(1);
}

$studentId = $studentCreate['user_id'];
echo "  [OK] Siswa test berhasil dibuat (ID: {$studentId}, NISN: {$dummyNisn})\n";

// Periksa bahwa status di student_registry menjadi 'registered'
$regStatus = $pdo->query("SELECT status FROM student_registry WHERE nisn = '{$dummyNisn}'")->fetchColumn();
echo "  [OK] Status student_registry saat ini: '{$regStatus}'\n";

// Sekarang hapus siswa tersebut menggunakan User::deleteUser
$delRes = User::deleteUser($studentId);
if (!$delRes['success']) {
    echo "  [FAIL] Gagal menghapus siswa test: " . ($delRes['error'] ?? '') . "\n";
    exit(1);
}
echo "  [PASS] User::deleteUser() berhasil menghapus siswa!\n";

// Verifikasi bahwa user sudah tidak ada di tabel users
$userCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE id = {$studentId}")->fetchColumn();
if ((int)$userCheck === 0) {
    echo "  [PASS] Data di tabel users terhapus bersih.\n";
} else {
    echo "  [FAIL] Data di tabel users masih ada!\n";
    exit(1);
}

// Verifikasi bahwa profile di student_profiles ikut terhapus
$profileCheck = $pdo->query("SELECT COUNT(*) FROM student_profiles WHERE user_id = {$studentId}")->fetchColumn();
if ((int)$profileCheck === 0) {
    echo "  [PASS] Data di tabel student_profiles terhapus bersih (CASCADE).\n";
} else {
    echo "  [FAIL] Data di tabel student_profiles masih ada!\n";
    exit(1);
}

// Verifikasi bahwa status student_registry direset menjadi 'unregistered'
$regStatusAfter = $pdo->query("SELECT status FROM student_registry WHERE nisn = '{$dummyNisn}'")->fetchColumn();
if ($regStatusAfter === 'unregistered') {
    echo "  [PASS] Status student_registry berhasil direset kembali menjadi 'unregistered'.\n";
} else {
    echo "  [FAIL] Status student_registry tidak direset (nilai: '{$regStatusAfter}').\n";
    exit(1);
}

// Bersihkan dummy registry
$pdo->prepare("DELETE FROM student_registry WHERE nisn = :n")->execute([':n' => $dummyNisn]);

// --- TEST 3: Buat data dummy guru dan uji coba hapus ---
echo "\n[TEST 3] Menguji hapus data dewan guru...\n";
$dummyNip = 'GURU_TEST_999';
$pdo->prepare("DELETE FROM users WHERE username = :u")->execute([':u' => $dummyNip]);
$pdo->prepare("DELETE FROM teacher_registry WHERE employee_identifier = :nip")->execute([':nip' => $dummyNip]);

$pdo->prepare("INSERT INTO teacher_registry (employee_identifier, nama_lengkap, status) VALUES (:nip, 'Guru Test Hapus, S.Pd.', 'unregistered')")
    ->execute([':nip' => $dummyNip]);

$teacherCreate = User::createTeacher([
    'identifier' => $dummyNip,
    'nama_lengkap' => 'Guru Test Hapus, S.Pd.',
    'mata_pelajaran' => 'Teknologi Informasi',
    'password' => 'password123'
]);

if (!$teacherCreate['success']) {
    echo "  [FAIL] Gagal membuat guru test: " . ($teacherCreate['error'] ?? '') . "\n";
    exit(1);
}

$teacherId = $teacherCreate['user_id'];
echo "  [OK] Guru test berhasil dibuat (ID: {$teacherId}, NIP: {$dummyNip})\n";

// Sekarang hapus guru tersebut menggunakan User::deleteUser
$delTeacherRes = User::deleteUser($teacherId);
if (!$delTeacherRes['success']) {
    echo "  [FAIL] Gagal menghapus guru test: " . ($delTeacherRes['error'] ?? '') . "\n";
    exit(1);
}
echo "  [PASS] User::deleteUser() berhasil menghapus dewan guru!\n";

// Verifikasi di users
$teacherUserCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE id = {$teacherId}")->fetchColumn();
if ((int)$teacherUserCheck === 0) {
    echo "  [PASS] Data di tabel users terhapus bersih.\n";
} else {
    echo "  [FAIL] Data di tabel users masih ada!\n";
    exit(1);
}

// Verifikasi di teacher_profiles
$tProfileCheck = $pdo->query("SELECT COUNT(*) FROM teacher_profiles WHERE user_id = {$teacherId}")->fetchColumn();
if ((int)$tProfileCheck === 0) {
    echo "  [PASS] Data di tabel teacher_profiles terhapus bersih.\n";
} else {
    echo "  [FAIL] Data di tabel teacher_profiles masih ada!\n";
    exit(1);
}

// Verifikasi status teacher_registry direset menjadi 'unregistered'
$tRegStatusAfter = $pdo->query("SELECT status FROM teacher_registry WHERE employee_identifier = '{$dummyNip}'")->fetchColumn();
if ($tRegStatusAfter === 'unregistered') {
    echo "  [PASS] Status teacher_registry berhasil direset kembali menjadi 'unregistered'.\n";
} else {
    echo "  [FAIL] Status teacher_registry tidak direset (nilai: '{$tRegStatusAfter}').\n";
    exit(1);
}

// Bersihkan dummy registry guru
$pdo->prepare("DELETE FROM teacher_registry WHERE employee_identifier = :nip")->execute([':nip' => $dummyNip]);

// --- TEST 4: Verifikasi Elemen UI Tombol Hapus & Modal di File admin/students.php & admin/teachers.php ---
echo "\n[TEST 4] Memeriksa keberadaan Tombol Hapus dan Modal Konfirmasi di file PHP...\n";
$studentsCode = file_get_contents(dirname(__DIR__) . '/admin/students.php');
$teachersCode = file_get_contents(dirname(__DIR__) . '/admin/teachers.php');

$studentChecks = [
    'value="delete"'                => 'Hidden input action=delete di form modal',
    'btn-outline-danger'            => 'Tombol Hapus warna merah outline',
    'deleteStudentModal'            => 'ID Modal Konfirmasi Hapus Siswa',
    'openDeleteStudentModal'        => 'Handler JS pemanggil modal konfirmasi siswa',
    'User::deleteUser($userId)'     => 'Pemanggilan backend User::deleteUser()'
];

foreach ($studentChecks as $needle => $label) {
    if (strpos($studentsCode, $needle) !== false) {
        echo "  [PASS] admin/students.php memiliki: {$label}\n";
    } else {
        echo "  [FAIL] admin/students.php TIDAK memiliki: {$label}\n";
        exit(1);
    }
}

$teacherChecks = [
    'value="delete"'                => 'Hidden input action=delete di form modal',
    'btn-outline-danger'            => 'Tombol Hapus warna merah outline',
    'deleteTeacherModal'            => 'ID Modal Konfirmasi Hapus Guru',
    'openDeleteTeacherModal'        => 'Handler JS pemanggil modal konfirmasi guru',
    'User::deleteUser($userId)'     => 'Pemanggilan backend User::deleteUser()'
];

foreach ($teacherChecks as $needle => $label) {
    if (strpos($teachersCode, $needle) !== false) {
        echo "  [PASS] admin/teachers.php memiliki: {$label}\n";
    } else {
        echo "  [FAIL] admin/teachers.php TIDAK memiliki: {$label}\n";
        exit(1);
    }
}

echo "\n=== SELURUH TEST FITUR HAPUS SISWA & GURU BERHASIL 100% (PASS)! ===\n";
