# 29. Panduan Konfigurasi OAuth Google Drive untuk SISURAT Backup

> **Tujuan Dokumen:** Panduan langkah demi langkah bagi Administrator Sistem untuk menghubungkan modul backup SISURAT ke Google Drive resmi instansi/pribadi.  
> **Dasar Kebijakan:** Mengikuti prinsip *Least-Privilege* (`drive.file`) dan arsitektur backup pada [`docs/backup.md`](file:///f:/Project/sisularavel/docs/backup.md) serta [`AGENTS.md`](file:///f:/Project/sisularavel/AGENTS.md).

---

## 1. Ikhtisar Kebutuhan Kredensial

Untuk mengaktifkan pengunggahan otomatis arsip terenkripsi ke Google Drive, file `.env` pada sistem SISURAT membutuhkan 4 parameter utama berikut:

```dotenv
# ==============================================================================
# SISURAT GOOGLE DRIVE BACKUP CONFIGURATION
# ==============================================================================
BACKUP_REMOTE_ENABLED=true
BACKUP_REMOTE_PROVIDER=google_drive

GOOGLE_DRIVE_CLIENT_ID=xxxxxxxxxxxx-xxxxxxxxxxxxxxxx.apps.googleusercontent.com
GOOGLE_DRIVE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx
GOOGLE_DRIVE_REFRESH_TOKEN=1//0xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
GOOGLE_DRIVE_BACKUP_FOLDER_ID=1aBcDeFgHiJkLmNoPqRsTuVwXyZ12345

# Opsional: Ukuran chunk upload (default 1MB = 1048576, kelipatan 256KB)
GOOGLE_DRIVE_CHUNK_SIZE=1048576
GOOGLE_DRIVE_MAX_RETRIES=3
```

---

## 2. Langkah 1: Buat Google Cloud Project & Aktifkan Drive API

1. Buka [Google Cloud Console](https://console.cloud.google.com/) dan login menggunakan akun Google instansi / akun pengelola arsip.
2. Klik menu pemilih project di bagian atas, lalu klik **"New Project"** (Project Baru).
   - **Project Name:** `SISURAT Backup Storage`
   - **Organization:** Pilih organisasi instansi (atau *No organization* jika akun mandiri).
   - Klik **Create**.
3. Pastikan project yang baru dibuat telah dipilih di bilah atas.
4. Buka menu **APIs & Services** > **Library** (Pustaka).
5. Pada kotak pencarian, ketik `Google Drive API` lalu tekan Enter.
6. Klik pada hasil **Google Drive API**, kemudian klik tombol biru **Enable** (Aktifkan).

---

## 3. Langkah 2: Konfigurasi OAuth Consent Screen (Layar Izin)

1. Buka menu **APIs & Services** > **OAuth consent screen** (Layar persetujuan OAuth).
2. Pilih **User Type**:
   - **Internal:** Pilih ini jika menggunakan Google Workspace instansi/sekolah (Disarankan! Refresh token berlaku permanen dan tidak perlu verifikasi Google).
   - **External:** Pilih ini jika menggunakan akun Gmail biasa (`@gmail.com`).
   - Klik **Create**.
3. Isi informasi dasar aplikasi:
   - **App name:** `SISURAT Backup System`
   - **User support email:** Masukkan email Anda / administrator.
   - **Developer contact information:** Masukkan email administrator.
   - Klik **Save and Continue**.
4. **Scopes (Cakupan Izin):**
   - Klik **Add or Remove Scopes**.
   - Pada kolom filter/search, cari atau masukkan scope:
     ```text
     https://www.googleapis.com/auth/drive.file
     ```
   - > [!IMPORTANT]
     > **Prinsip Least-Privilege:**  
     > Gunakan scope `.../auth/drive.file`, **JANGAN** gunakan `.../auth/drive` penuh. Scope `drive.file` memastikan SISURAT hanya memiliki akses ke file arsip yang diunggah oleh sistem ini sendiri dan tidak dapat membaca file pribadi lain di Google Drive instansi.
   - Klik **Update** lalu klik **Save and Continue**.
5. **Test Users (Khusus jika User Type = External):**
   - Klik **Add Users**, masukkan alamat Gmail yang akan digunakan sebagai wadah Google Drive backup.
   - Klik **Save and Continue**.

> [!CAUTION]
> **Peringatan Token Kedaluwarsa 7 Hari (Testing Mode):**  
> Jika User Type Anda adalah **External** dan status publishing masih **"Testing"**, Google membatasi masa berlaku `refresh_token` hanya selama **7 hari**.  
> **Tindakan Wajib:** Di halaman *OAuth consent screen*, klik tombol **"Publish App"** agar statusnya berubah menjadi **In Production** sehingga refresh token berlaku permanen. (Abaikan peringatan perlunya verifikasi karena aplikasi ini hanya digunakan secara internal untuk backup instansi Anda sendiri).

---

## 4. Langkah 3: Buat OAuth 2.0 Client ID

1. Buka menu **APIs & Services** > **Credentials** (Kredensial).
2. Klik **+ Create Credentials** di bagian atas, lalu pilih **OAuth client ID**.
3. Pada pilihan **Application type**, pilih **Web application**.
4. **Name:** `SISURAT Backup Client`.
5. Pada bagian **Authorized redirect URIs** (URI Pengalihan Resmi), klik **+ Add URI** dan masukkan:
   ```text
   https://developers.google.com/oauthplayground
   ```
   *(URL Google OAuth Playground resmi ini digunakan satu kali saja untuk menukarkan izin menjadi Refresh Token permanen secara mudah).*
6. Klik **Create**.
7. Jendela pop-up akan menampilkan:
   - **Client ID** (misal: `123456789-abcdefg.apps.googleusercontent.com`)
   - **Client Secret** (misal: `GOCSPX-abcdefghijklmn123456`)
   - *Salin dan simpan kedua nilai ini sementara di catatan teks aman Anda.*

---

## 5. Langkah 4: Dapatkan Permanent Refresh Token via OAuth Playground

1. Buka [Google OAuth 2.0 Playground](https://developers.google.com/oauthplayground/) di browser Anda.
2. Di pojok kanan atas, klik **ikon roda gigi (OAuth 2.0 Configuration)**:
   - Centang kotak **Use your own OAuth credentials**.
   - Masukkan **OAuth Client ID** yang Anda dapatkan pada Langkah 3.
   - Masukkan **OAuth Client secret** yang Anda dapatkan pada Langkah 3.
   - Tutup pop-up konfigurasi.
3. Di panel sebelah kiri (**Step 1: Select & Authorize APIs**):
   - Scroll ke bagian paling bawah ke input teks: **"Input your own scopes"**.
   - Masukkan scope resmi SISURAT:
     ```text
     https://www.googleapis.com/auth/drive.file
     ```
   - Klik tombol biru **Authorize APIs**.
4. Browser akan mengarahkan ke halaman login Google:
   - Pilih akun Google yang akan dijadikan tempat penyimpanan arsip backup.
   - Jika muncul peringatan *"Google hasn't verified this app"*, klik **Advanced** > **Go to SISURAT Backup System (unsafe)**.
   - Klik **Continue / Allow** untuk memberikan izin akses.
5. Anda akan dikembalikan ke halaman OAuth Playground pada **Step 2: Exchange authorization code for tokens**:
   - Klik tombol biru **Exchange authorization code for tokens**.
6. Perhatikan panel Step 2:
   - Cari kolom **Refresh token**.
   - Nilainya biasanya berawalan `1//0...` (panjang sekitar 60–100 karakter).
   - Salin nilai **Refresh token** ini! *(Ini adalah kunci otentikasi permanen SISURAT).*

---

## 6. Langkah 5: Buat Folder Backup di Google Drive & Dapatkan Folder ID

1. Buka [Google Drive](https://drive.google.com/) menggunakan akun yang sama.
2. Buat folder baru khusus untuk menampung backup SISURAT, misalnya bernama:
   `SISURAT_BACKUPS`
3. Masuk ke dalam folder tersebut (klik dua kali).
4. Perhatikan alamat URL di bilah navigasi browser Anda:
   ```text
   https://drive.google.com/drive/folders/1s7Kj9LmNoPqRsTuVwXyZ_0123456789a
   ```
5. String alfanumerik setelah `/folders/` adalah **Folder ID** Anda!
   - Contoh Folder ID: `1s7Kj9LmNoPqRsTuVwXyZ_0123456789a`.
   - Salin ID folder ini.

---

## 7. Langkah 6: Masukkan Konfigurasi ke File `.env`

Buka file [`.env`](file:///f:/Project/sisularavel/.env) pada root proyek SISURAT, lalu tambahkan atau sesuaikan variabel berikut:

```dotenv
# Aktifkan Remote Backup
BACKUP_REMOTE_ENABLED=true
BACKUP_REMOTE_PROVIDER=google_drive

# Kredensial OAuth dari Google Cloud Console
GOOGLE_DRIVE_CLIENT_ID="123456789-abcdefg.apps.googleusercontent.com"
GOOGLE_DRIVE_CLIENT_SECRET="GOCSPX-abcdefghijklmn123456"

# Refresh Token dari OAuth Playground
GOOGLE_DRIVE_REFRESH_TOKEN="1//04xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"

# ID Folder Khusus Backup di Google Drive
GOOGLE_DRIVE_BACKUP_FOLDER_ID="1s7Kj9LmNoPqRsTuVwXyZ_0123456789a"

# Opsi Chunk Size (Default: 1MB = 1048576 byte)
GOOGLE_DRIVE_CHUNK_SIZE=1048576
GOOGLE_DRIVE_MAX_RETRIES=3
```

Simpan file `.env`.

---

## 8. Langkah 7: Pengujian Langsung (Live Verification Test)

Setelah file `.env` diisi, Anda dapat melakukan uji coba end-to-end secara langsung melalui command line:

### 1. Jalankan Backup dan Langsung Unggah ke Google Drive:
```bash
php artisan sisurat:backup --upload
```
*Sistem akan:*
- Memeriksa ruang disk & database (Preflight).
- Mengunci snapshot transaksi & menyusun dump SQL + berkas privat.
- Membuat `manifest.json` & checksum SHA-256.
- Mengompresi bundle dan mengenkripsi menggunakan **AES-256-GCM**.
- Menginisiasi Resumable Upload Session ke Google Drive.
- Mengunggah berkas secara chunked (1 MB per potongan).
- Memverifikasi keberadaan berkas di Google Drive dan mencatat `remote_file_id` ke tabel `backup_runs`.

### 2. Verifikasi Integritas & Ketersediaan Berkas di Google Drive:
```bash
php artisan sisurat:backup:verify --remote
```
*Sistem akan:*
- Mengecek checksum lokal file `.enc`.
- Memanggil Google Drive API untuk memvalidasi bahwa file ID remote benar-benar ada di cloud, ukurannya cocok dengan metadata lokal, dan file tidak berada di keranjang sampah (*trashed*).

### 3. Simulasi Uji Restore Kering (Dry-Run Integrity Verification):
```bash
php artisan sisurat:restore --verify-only
```
*Sistem akan:*
- Mengambil arsip backup lokal terbaru.
- Mendekripsi secara aman di direktori temporary menggunakan kunci pemulihan.
- Memverifikasi header `SISURAT_GCM1\0` dan tag autentikasi AEAD 128-bit.
- Memverifikasi 100% hash SHA-256 di `checksums.sha256`.
- Memvalidasi isi SQL dump dan struktur berkas tanpa menyentuh database aktif.
- Menghapus kembali file sementara (*clean zero-trace*).

---

## 9. Troubleshooting & FAQ

| Gejala Masalah | Penyebab Utama | Solusi |
| :--- | :--- | :--- |
| **Error 401 Unauthorized saat upload** | Refresh token salah atau expired (7 hari pada testing mode) | Buat ulang refresh token di OAuth Playground atau klik "Publish App" di Google Cloud Console. |
| **Error 403 Access Not Configured** | Google Drive API belum diaktifkan di Google Cloud | Buka Google Cloud Console > APIs & Services > Library > aktifkan Google Drive API. |
| **Error 404 File / Folder Not Found** | `GOOGLE_DRIVE_BACKUP_FOLDER_ID` salah atau folder berada di akun lain | Pastikan ID folder disalin dari URL folder milik akun Google yang sama dengan yang diizinkan. |
| **Upload Lambat / Timeout di Hosting** | Koneksi shared hosting terbatas | Sistem secara otomatis memecah file menjadi chunk 1 MB (`GOOGLE_DRIVE_CHUNK_SIZE=1048576`) dan melakukan retry hingga 3 kali jika koneksi tersendat. |
