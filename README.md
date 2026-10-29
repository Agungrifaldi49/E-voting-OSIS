# E-Voting OSIS - SMK Muthia Harapan Cicalengka

Aplikasi Sistem Informasi Pemilihan Ketua dan Wakil Ketua OSIS Berbasis Web yang aman, demokratis, transparan, dan modern, dibangun menggunakan **PHP Native 8.x**, **MySQL (InnoDB)**, **Bootstrap 5**, **Chart.js**, dan **Vanilla JavaScript (Fetch API)**.

Aplikasi dirancang khusus untuk memfasilitasi pesta demokrasi siswa di **SMK Muthia Harapan Cicalengka** dengan mengedepankan asas **LUBER JURDIL** (Langsung, Umum, Bebas, Rahasia, Jujur, dan Adil).

---

## 🌟 Fitur Utama Sistem

### 1. Keamanan & Integritas Suara (Secret Ballot)
- **Kotak Suara Anonim:** Tabel `votes` dirancang khusus **tanpa menyimpan user_id**, menjamin kerahasiaan pilihan pemilih 100%. Pilihan kandidat tidak dapat dihubungkan kembali ke identitas pemilih.
- **Pencegahan Double Voting:** Menggunakan relasi `UNIQUE (election_id, user_id)` pada tabel `voting_participation` serta transaksi database atomik (`BEGIN TRANSACTION`, `COMMIT`, `ROLLBACK`) sehingga tidak ada pemilih yang dapat memilih lebih dari satu kali, bahkan dalam kondisi serangan konkurensi simultan.
- **Validasi Server-Side Kuat:** Pemeriksaan ketat terhadap jadwal pemilihan (mengikuti zona waktu `Asia/Jakarta`), status keaktifan akun, status DPT, masa berlaku kandidat, dan keabsahan token CSRF.
- **Proteksi Brute Force:** Pembatasan percobaan login (maksimal 5 kali percobaan per 5 menit per identitas/IP) dengan tabel `login_rate_limits`.
- **Proteksi Session:** Menggunakan `session_regenerate_id(true)`, cookie dengan atribut `HttpOnly`, dan `SameSite=Lax`.
- **Upload Foto Aman:** Validasi MIME type menggunakan `finfo`, ekstensi file ketat, penamaan acak terenkripsi (`bin2hex(random_bytes(12))`), dan proteksi `.htaccess` di folder upload untuk mencegah eksekusi skrip PHP.

### 2. Fitur Realtime Tanpa Refresh (Live Polling & Chart.js)
- Grafik interaktif Chart.js yang diperbarui secara otomatis melalui background polling **Fetch API** setiap 3–5 detik ke endpoint `/api/results.php`.
- Performa tinggi dan ramah memori: memperbarui instance grafik yang ada tanpa menghancurkan (*destroy*) elemen kanvas.
- Polling otomatis dijeda saat tab browser tidak aktif (*document.visibilityState === 'hidden'*) dan dilanjutkan kembali saat dibuka.
- **3 Mode Publikasi Hasil:**
  1. **Hasil Disembunyikan (Hidden):** Angka suara dilindungi di tingkat backend, publik tidak dapat mengakses suara melalui API maupun tampilan.
  2. **Hasil Sementara Realtime (Realtime):** Grafik dan persentase dapat dipantau langsung oleh seluruh warga sekolah.
  3. **Hasil Final Resmi (Final):** Pengumuman rekapitulasi akhir penetapan paslon terpilih.

### 3. Tiga Role Pengguna
- **Admin:** Pengelola penuh sistem (18 modul): Dashboard analitik, verifikasi akun pendaftar, kelola siswa & guru, import CSV, kelola periode pemilihan, kelola kandidat, pengaturan DPT, monitoring hasil, publikasi hasil, ekspor laporan berita acara, audit log, dan pengaturan website.
- **Guru:** Akses portal pengajar, profil, melihat visi misi kandidat, status hak suara, serta bilik suara jika diizinkan oleh panitia pada periode aktif.
- **Siswa:** Akses portal pemilih, profil, bilik suara elektronik dengan dialog konfirmasi, tanda bukti partisipasi pemilihan, dan rekapitulasi suara.

---

## 🛠️ Spesifikasi Teknologi

| Komponen | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Backend** | PHP Native (PHP 8.1.x / 8.x) | Arsitektur MVC sederhana tanpa framework eksternal |
| **Database** | MySQL / MariaDB (InnoDB) | Foreign keys, unique constraints, transactions |
| **Koneksi DB** | PDO (PHP Data Objects) | Prepared statements untuk proteksi SQL Injection |
| **Frontend** | HTML5, CSS3, Bootstrap 5.3.3 | Desain responsif (Mobile, Tablet, Desktop) |
| **Ikon** | Bootstrap Icons v1.11.3 | CDN resmi |
| **Grafik** | Chart.js v4.4.1 | Visualisasi batang perolehan suara realtime |
| **JavaScript** | Vanilla JavaScript (ES6+) | Fetch API, DOM manipulation, countdown timer |
| **Timezone** | `Asia/Jakarta` (WIB) | Konsisten di database, backend, dan frontend |
| **Environment** | XAMPP for Windows | Apache 2.4 & MariaDB 10.4 |

---

## 📂 Struktur Direktori Proyek

```
c:/xampp/htdocs/E-vooting OSIS/
│
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php          # Login, registrasi siswa/guru, logout
│   │   ├── AdminController.php         # Verifikasi, statistik, import CSV
│   │   ├── ElectionController.php      # Periode pemilihan, pengaturan DPT
│   │   ├── CandidateController.php     # CRUD paslon & upload foto
│   │   ├── VotingController.php        # Bilik suara & transaksi voting
│   │   └── ResultController.php        # Endpoint API hasil & ekspor CSV
│   │
│   ├── Models/
│   │   ├── User.php                    # Model pengguna & profil
│   │   ├── Election.php                # Model periode & status waktu
│   │   ├── Candidate.php               # Model paslon, visi & misi
│   │   ├── Vote.php                    # Model voting atomik & rekapitulasi
│   │   └── Registry.php                # Model master data & CSV import
│   │
│   ├── Helpers/
│   │   ├── AuthHelper.php              # Session, role check, rate limiting
│   │   ├── CsrfHelper.php              # Token CSRF proteksi form
│   │   ├── ValidationHelper.php        # Validasi NISN, password, foto, CSV
│   │   └── LogHelper.php               # Audit log aktivitas sistem
│   │
│   └── Views/
│       ├── layouts/                    # Header, navbar, footer, sidebars, alerts
│       ├── auth/                       # Halaman autentikasi
│       ├── admin/                      # Template admin
│       ├── guru/                       # Template guru
│       └── siswa/                      # Template siswa & bilik suara
│
├── config/
│   ├── app.php                         # Pengaturan timezone, session, konstanta
│   └── database.php                    # Koneksi PDO MySQL
│
├── database/
│   ├── schema.sql                      # Struktur tabel, relasi, indeks & constraint
│   └── seed.sql                        # Data awal admin, paslon, dan pemilih uji coba
│
├── public/
│   ├── index.php                       # Halaman utama publik
│   ├── login.php                       # Formulir masuk satu pintu
│   ├── register.php                    # Formulir registrasi siswa & guru
│   ├── voting.php                      # Bilik suara pemilihan
│   ├── results.php                     # Halaman hasil & grafik perolehan
│   ├── assets/                         # CSS, JS, logo SVG, template CSV
│   └── uploads/candidates/             # Tempat penyimpanan foto calon (.htaccess secured)
│
├── api/
│   ├── results.php                     # Endpoint JSON perolehan suara realtime
│   ├── participation.php               # Endpoint JSON statistik DPT & partisipasi
│   └── vote.php                        # Endpoint penerima request suara
│
├── admin/                              # Halaman modul dashboard admin
├── guru/                               # Halaman modul dashboard dewan guru
├── siswa/                              # Halaman modul dashboard siswa
├── storage/logs/                       # Log internal error aplikasi
├── tests/
│   ├── run_tests.php                   # Test suite otomatis (24 unit test)
│   ├── test_endpoints.php              # Pengujian status HTTP endpoint
│   └── test_auth_flow.php              # Pengujian alur login sesi HTTP
│
├── .htaccess                           # Proteksi keamanan Apache
└── README.md                           # Dokumentasi lengkap sistem
```

---

## 🚀 Petunjuk Instalasi di XAMPP Windows

Ikuti langkah-langkah berikut secara berurutan:

### Langkah 1: Siapkan XAMPP
Pastikan Anda telah menginstal XAMPP (disarankan versi PHP 8.1 atau yang lebih baru) di komputer Windows Anda.

### Langkah 2: Tempatkan Proyek di htdocs
Pastikan folder proyek berada di:
```
C:\xampp\htdocs\E-vooting OSIS
```

### Langkah 3: Jalankan Apache dan MySQL
1. Buka **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache**.
3. Klik tombol **Start** pada modul **MySQL**.

### Langkah 4: Buat Database dan Impor Skema
1. Buka browser dan buka phpMyAdmin di: `http://localhost/phpmyadmin/`
2. Buat database baru bernama: `db_evoting_osis` dengan collation `utf8mb4_unicode_ci`.
3. Klik tab **Import**, pilih file `database/schema.sql` lalu klik **Go**.
4. Ulangi import untuk file `database/seed.sql` untuk memasukkan data awal.

*Atau melalui PowerShell / Command Prompt:*
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS db_evoting_osis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Get-Content "c:\xampp\htdocs\E-vooting OSIS\database\schema.sql" -Raw | C:\xampp\mysql\bin\mysql.exe -u root db_evoting_osis
Get-Content "c:\xampp\htdocs\E-vooting OSIS\database\seed.sql" -Raw | C:\xampp\mysql\bin\mysql.exe -u root db_evoting_osis
```

### Langkah 5: Sesuaikan Konfigurasi Database (Jika Diperlukan)
Buka file `config/database.php`. Jika Anda menggunakan konfigurasi standar XAMPP (user: `root`, tanpa password), tidak ada yang perlu diubah:
```php
private const DB_HOST = '127.0.0.1';
private const DB_PORT = '3306';
private const DB_NAME = 'db_evoting_osis';
private const DB_USER = 'root';
private const DB_PASS = '';
```

### Langkah 6: Buka Aplikasi di Browser
Buka browser dan akses alamat berikut:
```
http://localhost/E-vooting%20OSIS/
```
Aplikasi akan langsung menampilkan halaman beranda resmi dengan informasi pemilihan aktif, hitung mundur, dan daftar pasangan calon.

---

## 🔑 Akun Bawaan untuk Uji Coba

Sistem telah dilengkapi data akun awal untuk memudahkan pengujian seluruh role:

| Role | Username / Identifier | Password | Status Akun | Catatan |
| :--- | :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | **Active** | Akses penuh ke seluruh panel admin |
| **Guru** | `198501152010011001` | `guru123` | **Active** | Drs. H. Ahmad Fauzi, M.Pd. |
| **Siswa 1** | `0061234567` | `siswa123` | **Active** | Muhammad Rizky Pratama (XI RPL 1) |
| **Siswa 2** | `0067654321` | `siswa123` | **Active** | Aulia Zahra Salsabila (XI TKJ 2) |
| **Siswa 3 (Pending)** | `0071122334` | `siswa123` | **Pending** | Fajar Nugraha (Untuk menguji verifikasi admin) |

---

## 🧪 Menjalankan Pengujian Otomatis

Aplikasi ini dilengkapi test runner mandiri yang menguji skenario autentikasi, integritas transaksi database, pencegahan double voting, dan kerahasiaan suara secara langsung.

Jalankan perintah berikut di PowerShell atau Command Prompt:
```bash
php tests/run_tests.php
```

Hasil pengujian otomatis akan menampilkan:
- 8 Test Autentikasi (Registrasi, Unik NISN, Hash Password, Approval Admin)
- 7 Test Voting & Pencegahan Double Voting (DPT check, Transaksi, Constraint DB)
- 2 Test Secret Ballot (Pengecekan isolasi data pemilih dari suara)
- 5 Test Realtime Endpoint & Mode Publikasi
- 2 Test Administrasi & Multi-periode

Semua skenario pengujian telah diuji dan menghasilkan status **24 PASSED, 0 FAILED**.

---

## 📋 Panduan Alur Penggunaan Aplikasi

### 1. Pendaftaran Pemilih
1. Siswa atau Guru membuka halaman [Registrasi](http://localhost/E-vooting%20OSIS/register.php).
2. Memasukkan NISN (10 digit) atau NIP guru, nama lengkap, kelas, jurusan, dan password.
3. Akun yang baru didaftarkan akan berstatus **Pending**.

### 2. Verifikasi oleh Admin
1. Admin login di [Halaman Login](http://localhost/E-vooting%20OSIS/login.php) dengan username `admin` dan password `admin123`.
2. Buka menu **Verifikasi Registrasi**.
3. Admin meninjau data pendaftar dan mencocokkannya dengan data master resmi.
4. Klik tombol **Setujui** untuk mengaktifkan akun, atau klik **Tolak** dengan memasukkan alasan penolakan.

### 3. Penetapan DPT (Daftar Pemilih Tetap)
1. Buka menu **Pengaturan Hak Pilih (DPT)** di panel admin.
2. Klik tombol **"Sinkronkan DPT dari Akun Aktif"**. Seluruh siswa (dan guru jika diizinkan) akan otomatis terdaftar sebagai pemilih sah untuk periode aktif.

### 4. Pelaksanaan Pemungutan Suara
1. Pemilih (Siswa/Guru) login ke akun masing-masing.
2. Klik menu **Bilik Suara / Voting**.
3. Klik tombol **"PILIH"** pada pasangan calon yang diinginkan.
4. Muncul dialog modal konfirmasi: *"Apakah Anda yakin ingin memilih pasangan calon nomor XX?"*.
5. Klik **"Ya, Saya Yakin"**.
6. Sistem memproses suara melalui transaksi database yang aman dan mengarahkan ke halaman sertifikat partisipasi.

### 5. Monitoring Realtime & Publikasi
1. Hasil perolehan suara dapat dipantau langsung di menu **Monitoring & Publikasi Hasil**.
2. Admin dapat mengubah mode publikasi menjadi:
   - **Hidden:** Suara dirahasiakan selama pemilihan.
   - **Realtime:** Grafik di halaman utama publik otomatis terupdate setiap 3.5 detik.
   - **Final:** Rekapitulasi suara resmi diumumkan.

### 6. Cetak Berita Acara & Ekspor Laporan
1. Buka menu **Laporan Pemilihan**.
2. Klik tombol **"Cetak / Simpan PDF"** untuk mencetak berita acara resmi lengkap dengan kolom tanda tangan Ketua Panitia dan Kepala Sekolah.
3. Klik tombol **"Unduh Laporan CSV"** untuk mengekspor rekapitulasi ke format Excel/Spreadsheet yang aman dari celah *CSV formula injection*.

---

## 🔧 Panduan Troubleshooting

| Masalah | Penyebab Umum | Solusi |
| :--- | :--- | :--- |
| **Gagal koneksi database (Error 500)** | MySQL belum dijalankan di XAMPP | Buka XAMPP Control Panel dan pastikan modul **MySQL** berwarna hijau (Running). Pastikan nama database `db_evoting_osis` telah dibuat. |
| **Foto kandidat tidak muncul** | Izin folder upload atau file belum diunggah | Folder `public/uploads/candidates/` otomatis dibuat. Pastikan ekstensi gambar adalah JPG, PNG, atau WEBP maksimal 2MB. Jika gambar tidak diunggah, avatar fallback SVG resmi akan otomatis ditampilkan. |
| **Session sering logout sendiri** | Pengaturan cookie browser | Pastikan cookie diizinkan di browser Anda. Aplikasi menggunakan session cookie standar dengan masa berlaku 24 jam. |
| **Spasi pada URL folder XAMPP** | Nama folder `E-vooting OSIS` memiliki spasi | Helper `base_url()` telah diprogram untuk otomatis melakukan URL encoding (`/E-vooting%20OSIS/`) sehingga seluruh tautan dan asset berjalan sempurna di Apache. |
| **CSRF Token Invalid saat submit** | Halaman terbuka terlalu lama sebelum dikirim | Muat ulang (*refresh*) halaman untuk memperbarui token CSRF sebelum mengirimkan formulir. |

---

## 🛡️ Catatan Batasan Anonimitas & Kepatuhan LUBER

Sistem ini menerapkan pemisahan logis dan fisik antara data kehadiran/partisipasi (`voting_participation`) dan data surat suara sah (`votes`):
1. Tabel `voting_participation` mencatat **siapa** yang telah memilih, tanpa mencatat paslon mana yang dipilih.
2. Tabel `votes` mencatat **surat suara** masuk per kandidat, tanpa mencatat siapa yang memasukkan suara tersebut.
3. Tidak ada endpoint atau tampilan antarmuka yang mengaitkan akun pemilih dengan pilihan paslonnya.

Untuk menjamin netralitas dan kerahasiaan absolut pada pemilihan berskala besar di lingkungan sekolah, disarankan agar pemilihan dilakukan secara serentak di laboratorium komputer sekolah atau melalui jaringan intranet lokal sekolah.

---

**Panitia Pemilihan OSIS SMK Muthia Harapan Cicalengka**  
*Masa Bakti 2026/2027*
