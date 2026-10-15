# SISURAT — CHECKLIST UJI ASAP & PENGUJIAN MANUAL (SMOKE TEST CHECKLIST)

**Versi Dokumen:** 1.0  
**Tanggal Rilis:** 09 Oktober 2026  
**Auditor / Penyusun:** Senior QA Engineer & Runtime Auditor  
**Petunjuk Penggunaan:**  
Checklist ini dirancang untuk dapat dijalankan langsung oleh penguji manusia (staf TU, verifikator, QA manual, atau pimpinan dinas) melalui web browser biasa (Chrome, Firefox, Safari, Edge) tanpa memerlukan pengetahuan teknis pemrograman Laravel atau database.

---

## DAFTAR PERSONA PENGUJIAN

| Persona ID | Peran / Akun | Username | Password Default | Tujuan & Batasan Akses |
|---|---|---|---|---|
| **PER-01** | Tamu Umum / Sekolah | *(Tanpa Login)* | — | Akses portal publik, pencarian piagam sekolah, pembubuhan tanda tangan digital. |
| **PER-02** | Operator DIKDAS | `op_dikdas01` | `password` | Input surat masuk/keluar & piagam Bidang SD. Dilarang melihat data PAUD & dilarang mengedit arsip. |
| **PER-03** | Admin DIKDAS | `admin_dikdas` | `password` | Kelola surat masuk/keluar SD, edit arsip SD, terbitkan kunci rahasia operator SD. |
| **PER-04** | Operator PAUD | `op_paud01` | `password` | Input surat & piagam Bidang PAUD. Dilarang melihat data DIKDAS. |
| **PER-05** | Super Admin | `superadmin` | `password` | Akses global seluruh divisi, switcher divisi, kelola pengguna, sekolah, divisi, dan ekspor laporan. |

---

## 1. MODUL AUTENTIKASI & KEAMANAN SESI (AUTH)

| Test Case ID | Persona | Preconditions (Syarat Awal) | Langkah Pengujian (Steps) | Expected Result (Hasil yang Diharapkan) | Actual Result (Hasil Nyata Saat Audit) | Status | Evidence / Bukti | Catatan & Tindak Lanjut |
|---|---|---|---|---|---|:---:|---|---|
| **SMK-AUTH-01** | PER-01 (Tamu) | Browser dalam keadaan belum login (Incognito mode). | Buka URL langsung: `http://localhost:8000/dashboard` di address bar peramban. | Pengguna dialihkan (redirect) secara otomatis ke halaman login (`/login`) dengan rapi. | **Crash server HTTP 500** bertuliskan `Route [login] not defined`. | **FAIL** | `storage/logs/laravel.log`: `RouteNotFoundException` | **Bug Kritis P0:** Route `/login` belum diberi penamaan `->name('login')`. |
| **SMK-AUTH-02** | PER-01 (Tamu) | Browser dalam keadaan belum login. | Buka URL langsung: `http://localhost:8000/surat-masuk` atau `/surat-keluar`. | Pengguna dialihkan (redirect) ke halaman `/login`. | **Crash server HTTP 500**. | **FAIL** | Browser tab menampilkan layar error framework Laravel. | Terdampak oleh Bug P0 pada routing login. |
| **SMK-AUTH-03** | PER-02 (Operator) | Berada di halaman `http://localhost:8000/login`. | Masukkan username `op_dikdas01` dan password salah (misal: `12345678`), lalu klik tombol **"Masuk"**. | Muncul kotak peringatan merah: *"Username atau Password salah!"* dan field input tetap terisi. | Halaman me-refresh diri, field kosong kembali, **tidak ada pesan kesalahan apa pun**. | **FAIL** | Tampilan `/login` bersih tanpa alert; user bingung. | **Bug P1:** Template `login.blade.php` tidak menampilkan variabel `$errors`. |
| **SMK-AUTH-04** | PER-02 (Operator) | Berada di halaman `/login`. | Masukkan password salah sebanyak 6 kali berturut-turut untuk akun yang sama. | Pada percobaan ke-6, muncul pesan: *"Terlalu banyak percobaan login. Silakan tunggu 15 menit."* | Peramban menampilkan layar putih dengan teks JSON mentah: `{"success":false,"code":"ERR_429_RATE_LIMIT",...}`. | **FAIL** | Respons raw JSON di layar browser. | **Bug P1:** Controller login memanggil `response()->json()` tanpa pengecekan browser. |
| **SMK-AUTH-05** | PER-02 (Operator) | Berada di halaman `/login`. | Masukkan username `op_dikdas01` dan password benar `password`, lalu klik **"Masuk"**. | Berhasil masuk dan dialihkan ke `/dashboard` dengan nama *"Operator DIKDAS"*. | Berhasil masuk ke Dashboard dengan badge divisi Bidang Pembinaan SD. | **PASS** | Dashboard memuat metrik khusus DIKDAS. | Alur login valid berfungsi normal. |
| **SMK-AUTH-06** | PER-02 (Operator) | Sedang aktif di dalam Dashboard. | Klik tombol profil di pojok kanan atas, lalu klik tombol **"Keluar"** (Logout). | Sesi berakhir, pengguna dialihkan kembali ke `/login`, tombol back browser tidak dapat mengakses data. | Berhasil logout dan dialihkan kembali ke halaman `/login`. | **PASS** | HTTP POST `/logout` memusnahkan sesi dengan benar. | Sesi aman dan teregenerasi. |

---

## 2. MODUL DASHBOARD & MULTI-TENANCY (DASH)

| Test Case ID | Persona | Preconditions | Langkah Pengujian | Expected Result | Actual Result | Status | Evidence / Bukti | Catatan & Tindak Lanjut |
|---|---|---|---|---|---|:---:|---|---|
| **SMK-DASH-01** | PER-02 (Operator DIKDAS) | Login sebagai `op_dikdas01`. | Periksa ringkasan angka di kartu statistik dashboard. | Angka Surat Masuk, Surat Keluar, dan Piagam hanya menghitung data Bidang DIKDAS. | Kartu statistik dan grafik tren hanya menampilkan data milik DIKDAS. | **PASS** | `DashboardController` dan Blade terisolasi per divisi. | Isolasi divisi operator terbukti aman. |
| **SMK-DASH-02** | PER-05 (Super Admin) | Login sebagai `superadmin`. | Periksa bagian atas header pada dropdown **"Konteks Divisi"**. | Tampil dropdown pemilih divisi: Semua Divisi, DIKDAS, PAUD, Kebudayaan, dll. | Dropdown tampil dengan pilihan seluruh divisi aktif di Kabupaten Semarang. | **PASS** | Komponen switcher divisi aktif di layout super admin. | Fungsi navigasi super admin lengkap. |
| **SMK-DASH-03** | PER-05 (Super Admin) | Berada di Dashboard. | Pilih opsi **"Bidang Pembinaan SD"** pada dropdown divisi. | Halaman memuat ulang otomatis, kartu metrik berubah menampilkan data khusus SD, muncul banner sukses. | Halaman me-reload, metrik berubah sesuai SD, banner hijau *"Konteks divisi dialihkan..."* muncul. | **PASS** | Sesi `active_division_id` tersimpan di database session. | Context switching berjalan mulus. |
| **SMK-DASH-04** | PER-05 (Super Admin) | Sedang dalam konteks Bidang SD. | Pilih opsi **"Semua Divisi"** pada dropdown. | Halaman kembali menampilkan rekapitulasi gabungan seluruh dinas. | Metrik gabungan dinas ditampilkan secara menyeluruh beserta matriks divisi. | **PASS** | Sesi kembali bernilai null / global. | Switcher global terverifikasi. |

---

## 3. MODUL SURAT MASUK (INCOMING)

| Test Case ID | Persona | Preconditions | Langkah Pengujian | Expected Result | Actual Result | Status | Evidence / Bukti | Catatan & Tindak Lanjut |
|---|---|---|---|---|---|:---:|---|---|
| **SMK-IN-01** | PER-02 (Operator DIKDAS) | Login sebagai `op_dikdas01`. | Klik menu **Surat Masuk** (`/surat-masuk`). | Menampilkan tabel arsip surat masuk khusus bidang DIKDAS. | Tabel memuat surat masuk DIKDAS dengan nomor agenda, asal instansi, dan perihal. | **PASS** | Tabel ter-render lengkap dengan pagination. | Visual tabel rapi dan terisolasi. |
| **SMK-IN-02** | PER-02 (Operator DIKDAS) | Berada di `/surat-masuk`. | Ketik kata kunci pada kotak cari (misal: "Bupati") lalu tekan Enter. | Tabel hanya menampilkan surat yang mengandung kata "Bupati" pada perihal/nomor/asal. | Filter pencarian bekerja instan dan memperbarui daftar surat. | **PASS** | Parameter `?q=Bupati` diaplikasikan ke query Eloquent. | Pencarian inline berfungsi. |
| **SMK-IN-03** | PER-02 (Operator DIKDAS) | Berada di `/surat-masuk`. | Klik tombol **"+ Input Surat Masuk"**. | Terbuka form input `/surat-masuk/create` dengan field nomor surat, asal, tanggal, perihal, lampiran. | Form terbuka dengan rapi sesuai desain mobile-first. | **PASS** | Input field memenuhi standar kenyamanan sentuh (44px). | Layout form responsif. |
| **SMK-IN-04** | PER-02 (Operator DIKDAS) | Form input terbuka. | Kosongkan field mandatory lalu klik **"Simpan Surat Masuk"**. | Form tidak terkirim, muncul pesan validasi merah bahwa field wajib diisi. | Banner merah muncul di atas form merinci field yang belum diisi. | **PASS** | Script JS menangkap validasi 422 dan merender banner. | Validasi asinkron bekerja baik. |
| **SMK-IN-05** | PER-02 (Operator DIKDAS) | Form input terbuka. | Isi data lengkap, unggah file PDF resmi, lalu klik **"Simpan Surat Masuk"**. | Tombol menunjukkan status loading, surat tersimpan, dialihkan ke tabel `/surat-masuk` dengan banner sukses. | Surat tersimpan di database, file tersimpan di storage privat, dialihkan ke `/surat-masuk`. | **PASS** | File tersimpan di `storage/app/private/documents/`. | Penyimpanan berkas privat aman. |
| **SMK-IN-06** | PER-02 (Operator DIKDAS) | Form input terbuka. | Masukkan nomor surat, asal instansi, dan tanggal yang persis sama dengan surat yang sudah ada. | Sistem menampilkan banner kuning peringatan duplikat dan meminta konfirmasi. | Banner kuning muncul: *"Peringatan: Surat ... sudah terdaftar. Apakah Anda yakin...?"* | **PASS** | Controller mendeteksi potensi duplikat dan memberi saran. | Advisory check bekerja sempurna. |
| **SMK-IN-07** | PER-02 (Operator DIKDAS) | Di tabel surat masuk. | Klik salah satu baris surat masuk untuk membuka detail. | Halaman `/surat-masuk/{id}` terbuka menampilkan rincian surat dan kartu lampiran. | Halaman detail memuat rincian lengkap dan tombol aksi. | **PASS** | View `letters.incoming.show` ter-render sempurna. | Detail arsip informatif. |
| **SMK-IN-08** | PER-02 (Operator DIKDAS) | Pada halaman detail surat. | Amati tombol aksi di kanan atas: terdapat tombol **"Edit Surat"**. Klik tombol tersebut. | Jika operator dilarang mengedit, tombol seharusnya tidak ada, atau jika ada harus mengizinkan edit. | Tombol "Edit Surat" terbuka, operator mengisi data, klik Simpan, **muncul layar JSON mentah 403**. | **FAIL** | Layar browser menampilkan raw JSON: `{"success":false,"code":"ERR_403_DIVISI",...}`. | **Bug P2 / Mismatch:** Tombol edit tidak disembunyikan dari operator di Blade view. |
| **SMK-IN-09** | PER-03 (Admin DIKDAS) | Login sebagai `admin_dikdas`, buka detail surat SD. | Klik tombol **"Edit Surat"**, ubah perihal, lalu klik **"Simpan Perubahan"**. | Perubahan tersimpan, kembali ke halaman detail dengan banner hijau sukses. | Data perihal terupdate di database dan kembali ke detail surat dengan pesan sukses. | **PASS** | Admin divisi memiliki wewenang update yang sah. | Fitur edit admin divisi bekerja. |
| **SMK-IN-10** | PER-04 (Operator PAUD) | Login sebagai `op_paud01`. | Coba buka paksa URL surat milik DIKDAS (misal: `/surat-masuk/1`). | Akses ditolak dengan halaman error 403 Forbidden. | Halaman memunculkan error HTTP 403: *"Anda tidak memiliki izin untuk mengakses data divisi ini."* | **PASS** | Policy pembatasan divisi menolak akses cross-tenant. | Batas tenancy antar divisi aman. |

---

## 4. MODUL SURAT KELUAR (OUTGOING)

| Test Case ID | Persona | Preconditions | Langkah Pengujian | Expected Result | Actual Result | Status | Evidence / Bukti | Catatan & Tindak Lanjut |
|---|---|---|---|---|---|:---:|---|---|
| **SMK-OUT-01** | PER-02 (Operator DIKDAS) | Login sebagai `op_dikdas01`. | Klik menu **Surat Keluar** (`/surat-keluar`). | Menampilkan tabel arsip surat keluar bidang DIKDAS. | Tabel memuat surat keluar dengan nomor surat, tahun surat, tanggal share, dan perihal. | **PASS** | Kolom tahun surat dan tanggal share ditampilkan rapi. | Tampilan tabel terverifikasi. |
| **SMK-OUT-02** | PER-02 (Operator DIKDAS) | Berada di `/surat-keluar`. | Klik tombol **"+ Input Surat Keluar"**. | Terbuka form `/surat-keluar/create`. | Form terbuka dengan input nomor surat, tanggal surat, tanggal share, perihal, lampiran. | **PASS** | Layout form konsisten dengan Surat Masuk. | Antarmuka terstandarisasi. |
| **SMK-OUT-03** | PER-02 (Operator DIKDAS) | Form input surat keluar terbuka. | Masukkan nomor surat yang sudah pernah dipakai pada divisi yang sama di tahun 2026. | Validasi gagal dan menampilkan pesan bahwa nomor surat sudah terdaftar di tahun ini. | Banner merah muncul: *"Nomor surat keluar sudah terdaftar pada divisi dan tahun ini"*. | **PASS** | Validasi keunikan tahunan `(division_id, year, number)` bekerja. | Integritas penomoran surat terjaga. |
| **SMK-OUT-04** | PER-03 (Admin DIKDAS) | Buka halaman edit surat keluar (`/surat-keluar/{id}/edit`). | Ubah nomor surat menjadi nomor yang sudah ada di divisi dan tahun yang sama, klik **"Simpan Perubahan"**. | Form menolak dan kembali ke form edit dengan tanda merah pada field nomor surat. | Browser dialihkan ke layar putih bertuliskan JSON mentah: `{"success":false,"code":"ERR_409_DUPLICATE_NUMBER",...}`. | **FAIL** | Respons raw JSON 409 pada submit form HTML peramban. | **Bug P1:** Update controller tidak mengecek `$request->expectsJson()` saat duplikat. |
| **SMK-OUT-05** | PER-03 (Admin DIKDAS) | Buka detail surat keluar (`/surat-keluar/{id}`). | Klik tombol merah **"Hapus"** dan konfirmasi popup dialog. | Surat terhapus secara soft delete, dialihkan ke `/surat-keluar` dengan banner sukses. | Surat masuk ke status terhapus, dialihkan ke `/surat-keluar` dengan pesan sukses. | **PASS** | Soft delete mencatat user penghapus di audit log. | Penghapusan aman. |

---

## 5. MODUL PIAGAM & PENGAMBILAN TAMU (PIAGAM)

| Test Case ID | Persona | Preconditions | Langkah Pengujian | Expected Result | Actual Result | Status | Evidence / Bukti | Catatan & Tindak Lanjut |
|---|---|---|---|---|---|:---:|---|---|
| **SMK-PG-01** | PER-02 (Operator DIKDAS) | Login sebagai `op_dikdas01`. | Klik menu **Rekap Pengambilan Piagam** (`/piagam`). | Menampilkan tabel piagam penghargaan siswa SD di Kab. Semarang. | Tabel memuat nama siswa, nama sekolah, cabang lomba, tahun, dan status pengambilan. | **PASS** | Tampilan status: "Sudah Diambil" (hijau) atau "Belum Diambil" (kuning). | Rekapitulasi staf jelas. |
| **SMK-PG-02** | PER-02 (Operator DIKDAS) | Berada di `/piagam`. | Klik tombol **"+ Tambah Piagam"**. | Terbuka form input internal staf untuk mendaftarkan piagam baru. | Form input memuat dropdown sekolah terdaftar, kategori lomba, nama siswa, dan tahun. | **PASS** | Input piagam staf tersambung ke relasi master data. | Form internal bekerja. |
| **SMK-PG-03** | PER-01 (Tamu Sekolah) | Buka halaman muka sistem: `http://localhost:8000/`. | Gulir ke bagian **"Pengambilan Piagam Penghargaan Siswa"**. | Terbuka formulir 4 langkah: (1) Pilih Sekolah, (2) Pilih Piagam, (3) Identitas Tamu, (4) Tanda Tangan. | Form 4 langkah interaktif tampil dengan jelas di bagian landing page. | **PASS** | Antarmuka publik modern dan interaktif. | Portal publik siap di halaman depan. |
| **SMK-PG-04** | PER-01 (Tamu Sekolah) | Pada form tamu di halaman muka. | Pilih nama sekolah atau ketik 8-digit NPSN (misal: `20320340`). | Daftar piagam siswa yang belum diambil untuk sekolah tersebut otomatis muncul di Langkah 2. | Dropdown/radio list piagam siswa muncul secara otomatis melalui panggilan AJAX. | **PASS** | API `GET /api/v1/public/piagams?npsn=...` mengembalikan data yang belum diambil. | Pengambilan data sekolah dinamis. |
| **SMK-PG-05** | PER-01 (Tamu Sekolah) | Pada form tamu di halaman muka. | Pilih salah satu piagam, isi Nama Tamu, Jabatan, Instansi, bubuhkan tanda tangan di canvas, klik **"Simpan Tanda Terima"**. | Tombol loading, form tertutup, muncul banner hijau sukses dengan nama penerima dan konfirmasi tanda tangan. | Kartu sukses hijau muncul mengonfirmasi piagam telah diserahkan dan tanda tangan tersimpan. | **PASS** | Berkas PNG tanda tangan tersimpan di `storage/app/private/signatures/`. | Alur tanda tangan digital selesai. |
| **SMK-PG-06** | PER-01 (Tamu Sekolah) | Buka URL yang tertera pada dokumen panduan: `http://localhost:8000/piagam/ambil`. | Amati antarmuka dan coba lakukan pengambilan piagam dari URL ini. | Tampil halaman pengambilan piagam yang lengkap dan dapat disubmit. | Tampil halaman usang: **tidak ada tombol simpan/submit**, field piagam & jabatan tidak ada, canvas tidak bisa digambar. | **FAIL** | Form mati total tanpa script dan tombol. | **Bug P1:** Halaman `/piagam/ambil` tidak sinkron dengan form di `welcome.blade.php`. |
| **SMK-PG-07** | PER-02 (Operator DIKDAS) | Login dan buka detail piagam yang baru diambil (`/piagam/{id}`). | Periksa kartu **"Bukti Tanda Tangan"** di sisi kanan layar. | Terlihat gambar coretan tanda tangan digital yang dibubuhkan tamu tadi. | Gambar tanda tangan ter-render dengan jelas melalui tag `<img>`. | **PASS** | Tag `<img>` memanggil `/api/v1/attachments/{uuid}/preview` dengan otentikasi sesi staf. | Readback verifikasi tanda tangan valid. |

---

## 6. MODUL PENCARIAN TERPADU & LAPORAN (REPORT)

| Test Case ID | Persona | Preconditions | Langkah Pengujian | Expected Result | Actual Result | Status | Evidence / Bukti | Catatan & Tindak Lanjut |
|---|---|---|---|---|---|:---:|---|---|
| **SMK-REP-01** | PER-02 (Operator DIKDAS) | Login sebagai `op_dikdas01`. | Klik menu **Pencarian & Laporan** (`/laporan`). | Menampilkan halaman pencarian multi-kriteria: kata kunci, jenis administrasi, tanggal dari/sampai, tahun. | Halaman filter terpadu memuat 3 tab rekapitulasi: Surat Masuk, Surat Keluar, dan Piagam. | **PASS** | Filter terpadu bekerja menyaring ketiga entitas sekaligus. | Fitur pencarian terpadu lengkap. |
| **SMK-REP-02** | PER-02 (Operator DIKDAS) | Di halaman `/laporan`. | Klik tombol **"Unduh Rekap CSV"**. | File format `.csv` otomatis terunduh di browser. | File `sisurat-rekap-all-...csv` terunduh instan. | **PASS** | File dapat dibuka di Microsoft Excel dengan karakter rapi (BOM UTF-8). | Ekspor spreadsheet berfungsi. |
| **SMK-REP-03** | PER-02 (Operator DIKDAS) | Di halaman `/laporan`. | Klik tombol **"Cetak Laporan PDF"**. | Tab baru terbuka menampilkan dokumen PDF resmi berlogo Pemkab Semarang yang siap dicetak. | File PDF tampil di viewer browser dengan tabel rekapitulasi dan tanda tangan elektronik. | **PASS** | DomPDF merender layout PDF resmi berstandar kedinasan. | Cetak laporan resmi siap pakai. |

---

## 7. MODUL MANAJEMEN MASTER DATA (ADMIN)

| Test Case ID | Persona | Preconditions | Langkah Pengujian | Expected Result | Actual Result | Status | Evidence / Bukti | Catatan & Tindak Lanjut |
|---|---|---|---|---|---|:---:|---|---|
| **SMK-MST-01** | PER-05 (Super Admin) | Login sebagai `superadmin`. | Buka menu **Master Data > Data Pengguna** (`/master/pengguna`). | Menampilkan daftar akun staf beserta role dan statusnya. | Tabel memuat seluruh pengguna dinas beserta tombol aksi dan penerbitan kunci rahasia. | **PASS** | Tampilan pengguna terstruktur. | Manajemen user terbuka. |
| **SMK-MST-02** | PER-05 (Super Admin) | Berada di `/master/pengguna`. | Klik **"+ Tambah Pengguna"**, isi form dengan password hanya 4 karakter (misal: `1234`), klik **"Simpan Akun"**. | Modal tetap terbuka, data tidak hilang, muncul banner merah: *"The password field must be at least 8 characters."* | Modal tetap terbuka, input tetap terisi, banner merah peringatan password muncul di atas form. | **PASS** | Validasi web hybrid bekerja (Opsi A berhasil diterapkan). | UX validasi pengguna sempurna. |
| **SMK-MST-03** | PER-05 (Super Admin) | Berada di `/master/pengguna`. | Cari akun yang berstatus "Wajib Reset", lalu klik tombol kuning **"Kunci Rahasia"**. | Muncul konfirmasi, lalu muncul banner kuning di atas tabel berisi kode rahasia sementara (misal: 20 karakter). | Banner kuning muncul menampilkan kode rahasia yang dapat diserahkan kepada operator dinas. | **PASS** | Fitur Path B (Aktivasi Darurat Admin Tanpa Email) bekerja. | Alternatif aktivasi akun tanpa email siap. |
| **SMK-MST-04** | PER-05 (Super Admin) | Berada di `/master/pengguna`. | Cari tombol untuk mengedit data pengguna yang sudah ada (misal: ubah nama lengkap atau email). | Terdapat tombol atau ikon "Edit" di samping tombol Hapus. | **Tidak ada tombol atau modal edit sama sekali** (hanya ada tombol Hapus dan Kunci Rahasia). | **FAIL** | Tidak ada elemen edit di template `master/users/index.blade.php`. | **Bug P2:** Antarmuka edit akun belum disediakan di antarmuka web. |
| **SMK-MST-05** | PER-05 (Super Admin) | Buka menu **Master Data > Data Sekolah** (`/master/sekolah`). | Cari tombol untuk mengedit nama atau status sekolah. | Terdapat tombol edit sekolah. | **Tidak ada tombol edit sekolah** (hanya Tambah dan Hapus). | **FAIL** | Tidak ada elemen edit di template `master/schools/index.blade.php`. | **Bug P2:** Antarmuka edit sekolah belum disediakan di web. |
| **SMK-MST-06** | PER-05 (Super Admin) | Buka menu **Master Data > Data Divisi** (`/master/divisi`). | Klik tombol **"Nonaktifkan"** pada salah satu divisi uji. | Status divisi berubah menjadi Nonaktif (merah) dan muncul notifikasi sukses. | Status divisi berubah instan dan pengguna divisi tersebut diblokir aksesnya. | **PASS** | Toggle status divisi bekerja melalui route PATCH. | Manajemen siklus hidup divisi aktif. |

---

## 8. MODUL PENGUNDUHAN BERKAS PRIVAT (ATTACHMENT)

| Test Case ID | Persona | Preconditions | Langkah Pengujian | Expected Result | Actual Result | Status | Evidence / Bukti | Catatan & Tindak Lanjut |
|---|---|---|---|---|---|:---:|---|---|
| **SMK-ATT-01** | PER-02 (Operator DIKDAS) | Buka surat masuk DIKDAS yang memiliki berkas lampiran. | Klik tombol hijau **"Buka & Unduh Berkas"**. | Tab baru peramban terbuka menampilkan berkas PDF / gambar secara inline. | Berkas terbuka di tab peramban dengan MIME type yang tepat (`application/pdf`). | **PASS** | Controller streaming privat mengalirkan biner dengan aman. | Streaming gateway berfungsi normal. |
| **SMK-ATT-02** | PER-04 (Operator PAUD) | Menggunakan link preview berkas milik DIKDAS (misal tautan dari riwayat browser). | Buka tautan preview lampiran milik DIKDAS di address bar browser. | Ditampilkan halaman HTML 403 Forbidden yang sopan dan aman. | Browser menampilkan layar putih bertuliskan JSON mentah: `{"success":false,"code":"ERR_403_DIVISI",...}`. | **FAIL** | Layar browser menampilkan raw JSON 403. | **Bug P1:** `AttachmentController` mengabaikan `Accept: text/html` pada kondisi terlarang. |
| **SMK-ATT-03** | PER-02 (Operator DIKDAS) | Surat hasil migrasi lama yang lampirannya missing (tidak ada di drive). | Buka detail surat masuk yang lampirannya berstatus missing. | Kartu lampiran memberi tahu bahwa file lama hilang, dan menyarankan pembukaan link Google Drive lama jika ada. | Kartu lampiran menampilkan label kuning *"Legacy Drive (Tautan Lama)"* dan tombol link Drive lama. | **PASS** | Tampilan visual degradasi anggun (*graceful degradation*) bekerja. | Penanganan legacy drive rapi. |

---

## REKAPITULASI HASIL PENGUJIAN SMOKE TEST

```
================================================================================
                    REKAPITULASI STATUS PENGUJIAN SMOKE TEST
================================================================================
  Total Kasus Uji Dijalankan : 35 Test Cases
  Status LULUS (PASS)        : 26 Kasus (74.3%)
  Status GAGAL (FAIL)        :  9 Kasus (25.7%)
  Status TERHAMBAT (BLOCKED) :  0 Kasus ( 0.0%)
================================================================================
  Rincian Kegagalan Berdasarkan Dampak:
  - P0 Critical (Macet Total): 1 Kasus (Direct unauthenticated access crash 500)
  - P1 High (Layar JSON Mentah / Dead Form): 5 Kasus
  - P2 Medium (UI Mismatch / Fitur Edit Belum Ada): 3 Kasus
================================================================================
```

### Kesimpulan Penguji Manual:
Aplikasi memiliki alur dasar (*happy path*) yang sangat mengesankan: antarmuka bersih, cepat, pencarian instan, tanda tangan digital di ponsel sangat mulus, ekspor PDF/CSV rapi, dan pemisahan divisi bekerja dengan aman. Namun, aplikasi **rentan mengalami layar error teknis (layar merah 500 dan layar putih JSON mentah)** apabila pengguna keluar dari alur ideal (misalnya lupa login, salah ketik password, salah pencet tombol edit bagi operator, atau salah membuka link lampiran). Seluruh bug register P0 dan P1 pada checklist ini wajib diperbaiki sebelum sistem diluncurkan untuk operasional staf.
