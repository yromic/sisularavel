# PANDUAN PENGGUNAAN & SERAH TERIMA SISTEM SISURAT
**Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga (Disdikbudpora) Kabupaten Semarang**

---

## 1. Daftar Akun Pengguna Awal (*Default Seed Accounts*)

Aplikasi telah dilengkapi dengan master seeder awal yang mencakup seluruh bidang/divisi kerja di lingkungan Disdikbudpora.

> **Kata Sandi Bawaan untuk Semua Akun:** `password`  
> *(Sangat disarankan segera diganti setelah login pertama melalui menu profil).*

| Peran (*Role*) | Username | Nama Akun | Divisi / Bidang Kerja | Batas Hak Akses |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin` | Super Admin SISURAT | *Global (Semua Bidang)* | Akses seluruh divisi, ganti konteks divisi aktif, kelola sekolah & divisi. |
| **Admin DIKDAS** | `admin_dikdas` | Admin DIKDAS | Bidang Pembinaan SD | Kelola surat masuk/keluar SD, terbitkan kunci rahasia operator SD. |
| **Operator DIKDAS** | `op_dikdas01` | Operator DIKDAS | Bidang Pembinaan SD | Input surat masuk, surat keluar, dan piagam Bidang SD. |
| **Admin PAUD** | `admin_paud` | Admin PAUD | Bidang PAUD dan PNF | Kelola surat masuk/keluar PAUD & PNF, kelola operator PAUD. |
| **Operator PAUD** | `op_paud01` | Operator PAUD | Bidang PAUD dan PNF | Input surat & piagam Bidang PAUD dan PNF. |
| **Admin Kebudayaan**| `admin_budaya` | Admin Kebudayaan | Bidang Kebudayaan | Kelola arsip dan piagam lomba kebudayaan. |
| **Operator Budaya** | `op_budaya01` | Operator Kebudayaan | Bidang Kebudayaan | Input surat & piagam Bidang Kebudayaan. |
| **Admin Pora** | `admin_pora` | Admin Pemuda & Olahraga | Bidang Pemuda & Olahraga | Kelola arsip dan piagam kejuaraan olahraga (POPDA, dll). |
| **Operator Pora** | `op_pora01` | Operator Pemuda & Olahraga| Bidang Pemuda & Olahraga | Input surat & piagam Bidang Olahraga & Kepemudaan. |
| **Admin Sekretariat**| `admin_sekretariat` | Admin Sekretariat | Sekretariat Dinas | Kelola surat umum & kedinasan sekretariat. |
| **Operator Sekretariat**| `op_sekretariat01`| Operator Sekretariat | Sekretariat Dinas | Input surat masuk/keluar umum dinas. |

---

## 2. Alur Operasional Utama

### 2.1 Masuk Sistem & Beralih Konteks Divisi
1. Buka halaman login di `/login`.
2. Masukkan username dan password.
3. Setelah berhasil masuk, pengguna akan diarahkan ke **Dashboard** (`/dashboard`).
4. Khusus akun **Super Admin**, terdapat pemilih divisi di bilah atas (*top bar*) untuk beralih konteks kerja (misal: memeriksa arsip Bidang Pembinaan SD, lalu beralih ke Bidang Kebudayaan).

### 2.2 Pencatatan Surat Masuk via Ponsel / Komputer
1. Buka menu **Surat Masuk** (`/surat-masuk`).
2. Ketuk tombol **+ Tambah** (`/surat-masuk/create`).
3. Isi Nomor Surat, Tanggal Surat, Tanggal Diterima, Asal Surat, dan Perihal.
4. Lampirkan foto atau dokumen berkas surat (kamera ponsel atau file PDF).
5. Ketuk **Simpan Surat**. Tombol akan otomatis beralih ke status *"Menyimpan..."* dan menonaktifkan klik berulang untuk mencegah data kembar (*duplicate submission protection*).

### 2.3 Penomoran & Pencatatan Surat Keluar
1. Buka menu **Surat Keluar** (`/surat-keluar`).
2. Ketuk tombol **+ Tambah** (`/surat-keluar/create`).
3. Masukkan nomor surat keluar, tanggal surat, dan tanggal share.
4. Sistem secara otomatis menerapkan aturan penomoran unik per divisi dan tahun kalender (`letter_year`). Jika nomor kembar dalam tahun yang sama terdeteksi, sistem akan menampilkan peringatan validasi ramah pengguna.

### 2.4 Pendaftaran & Pengambilan Piagam Lomba
1. **Pendaftaran Piagam (Internal Operator):**
   - Operator membuka menu **Piagam** (`/piagam`) & ketuk **+ Tambah** (`/piagam/create`).
   - Pilih NPSN sekolah asal, nama lengkap siswa, kategori lomba, dan tahun penyelenggaraan.
2. **Pengambilan Piagam (Portal Publik untuk Tamu/Sekolah):**
   - Sekolah membuka tautan publik: `/piagam/ambil`.
   - Masukkan 8 digit NPSN dan nama perwakilan pengambil.
   - Bubuhkan tanda tangan langsung menggunakan jari/stylus pada kanvas digital sentuh.
   - Ketuk **Klaim Piagam**. Tanda tangan akan disimpan secara aman dan terenkripsi pada direktori privat dinas.

### 2.5 Pencarian Terpadu & Unduh Laporan
1. Buka menu **Pencarian & Laporan** (`/laporan`).
2. Ketik kata kunci (nomor surat, asal instansi, nama sekolah, perihal).
3. Hasil surat masuk, surat keluar, dan piagam akan tampil serentak.
4. Ketuk **Unduh Rekap CSV** untuk mendapatkan file rekap data yang sudah diamankan dari ancaman *formula injection*.

### 2.6 Penanganan Akun Migrasi Legacy (Aktivasi Path B)
1. Akun hasil migrasi dari sistem lama memiliki status wajib ganti password (`must_reset_password = true`).
2. Administrator Divisi membuka menu **Master Data > Pengguna** (`/master/pengguna`).
3. Ketuk tombol **Kunci Rahasia (Path B)** pada akun operator yang bersangkutan.
4. Berikan kode rahasia sementara tersebut kepada operator.
5. Saat operator login, sistem akan otomatis mengarahkan ke form **Aktivasi Kata Sandi** (`/password/activate`) untuk menetapkan kata sandi baru mereka sendiri.

---

## 3. Langkah Menjalankan Aplikasi di Server Lokal / Produksi

### Menjalankan Server Lokal (Development):
```bash
# 1. Jalankan migrasi dan seeder awal
php artisan migrate --seed

# 2. Kompilasi aset frontend
npm run build

# 3. Jalankan server lokal
php artisan serve
```
Aplikasi dapat dibuka di browser: `http://127.0.0.1:8000/login`.

### Menjalankan Migrasi Data dari Spreadsheet Lama:
```bash
# Uji coba tanpa mengubah database (Dry-Run):
php artisan sisurat:migrate-legacy --dry-run

# Eksekusi migrasi data riil:
php artisan sisurat:migrate-legacy
```
Laporan rekonsiliasi dan baris data karantina akan tersimpan di folder `storage/migration/`.
