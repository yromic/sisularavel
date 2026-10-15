# SISURAT Piagam Module Rework: Buku Ekspedisi Digital Pengambilan Piagam

> **Dokumen Arsitektur & Implementasi Resmi**  
> **Target Framework:** Laravel 12.69.2 / PHP 8.2.12 / MariaDB 10.4  
> **Status:** IMPLEMENTED & FULLY VERIFIED (135 automated tests / 860 assertions PASS)  
> **Tanggal:** 9 Oktober 2026  
> **Arsitek & QA Lead:** Principal Laravel Architect, Business Process Engineer & QA Lead

---

## 1. Background

SISURAT merupakan sistem administrasi persuratan dan piagam penghargaan untuk Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga (Disdikbudpora) Kabupaten Semarang. Dalam operasional dinas, penyerahan piagam perlombaan (seperti FLS2N, O2SN, OSN, MAPSI, dsb.) kepada perwakilan sekolah (kepala sekolah, guru pembimbing, staf TU, atau orang tua siswa) memerlukan pencatatan tanda terima sebagai dokumen pertanggungjawaban fisik.

Sebelumnya, modul Piagam dirancang menggunakan paradigma e-commerce/katalog reservasi (pre-registration dua tahap): staf dinas menginput data seluruh piagam terlebih dahulu ke sistem, kemudian tamu datang mencari NPSN dan memilih piagam yang hendak diklaim.

---

## 2. Root Cause Analysis

Penyelidikan mendalam terhadap proses bisnis riil di kantor dinas menemukan ketidaksesuaian fundamental:
1. **Piagam Fisik Sudah Dicetak Sebelum Penyerahan:** Dinas telah mencetak seluruh sertifikat fisik secara manual/massal di luar sistem sebelum jadwal pengambilan.
2. **Beban Administratif Berlebihan:** Mewajibkan staf dinas menginput ratusan hingga ribuan nama siswa, kategori lomba, dan sekolah sebelum penyerahan menciptakan antrean kerja ganda (*double entry*).
3. **Miskonsepsi Domain:** Sistem sebelumnya memperlakukan piagam sebagai *inventaris barang yang harus di-claim*, padahal kebutuhan dinas yang sebenarnya adalah **Buku Ekspedisi Tanda Terima Fisik (Digital Handover Receipt Book)**.
4. **Alur Loket yang Rumit:** Tamu di loket dinas dipaksa melalui 4 tahap wizard (input NPSN -> AJAX fetch -> centang piagam -> input penerima & TTD -> status change). Jika NPSN sekolah tidak terdaftar atau nama siswa salah ketik saat pre-registrasi, piagam fisik tidak dapat diserahkan.

---

## 3. Old vs New Workflow Comparison

| Dimensi | Alur Lama (Katalog Reservasi 2-Tahap) | Alur Baru (Buku Ekspedisi Digital 1-Aksi) |
|---|---|---|
| **Prapendaftaran Staf** | Wajib; staf harus input setiap siswa & lomba | **Tidak Ada**; staf tidak perlu pre-input data piagam |
| **Katalog Piagam** | Ada tabel piagam "Belum Diambil" | **Tidak Ada**; fisik piagam sudah ada di loket dinas |
| **Akses Tamu** | Wizard 4 tahap (NPSN search -> pilih piagam) | **1 Halaman Form** via QR Code / Bitly / CTA Web |
| **Pencarian NPSN** | Wajib; jika gagal, flow terhenti | **Opsional / Datalist**; mendukung nama sekolah bebas |
| **Tanda Tangan** | Tahap 4 setelah memilih ID piagam | **Dibubuhkan langsung pada form yang sama** |
| **Status Record** | Pending / Belum Diambil -> Sudah Diambil | **Langsung berstatus Tercatat Sah (Tanda Terima)** |
| **Prinsip Transaksi** | Multi-step claim of existing row | **One Form, One Submission, One Handover Record** |

---

## 4. New Domain Model

Setiap baris data dalam tabel `piagams` merepresentasikan **satu peristiwa serah terima fisik (handover receipt)**.

### Minimum Data Contract

| Field | Tipe | Nullable | Keterangan |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | Auto-increment Primary Key |
| `uuid` | CHAR(36) | No | Unique public reference |
| `division_id` | BIGINT UNSIGNED | No | Foreign Key ke tabel `divisions` |
| `piagam_description` | TEXT | Yes | Deskripsi piagam, lomba, dan nama penerima sertifikat |
| `student_name` | VARCHAR(150) | No | Nama siswa / keterangan piagam utama |
| `school_id` | BIGINT UNSIGNED | Yes | Relasi ke master sekolah (jika terdaftar) |
| `school_origin` | VARCHAR(255) | Yes | Nama sekolah atau instansi pengambil |
| `recipient_name` | VARCHAR(150) | Yes | Nama lengkap orang yang mengambil piagam |
| `recipient_position` | VARCHAR(100) | Yes | Jabatan (Guru, KS, Staf TU, Perwakilan) |
| `recipient_unit` | VARCHAR(150) | Yes | Unit kerja / instansi pengambil |
| `competition_year` | INT | Yes | Tahun perolehan lomba |
| `created_at` | TIMESTAMP | No | Tanggal & jam penyerahan fisik |
| `updated_at` | TIMESTAMP | No | Tanggal update sistem |

---

## 5. Database Migration Strategy

Dilakukan melalui migration tambahan tanpa menghapus tabel, kolom historis, atau data foreign key legacy:

- **File Migration:** `database/migrations/2026_10_09_152000_simplify_piagams_for_handover_receipts.php`
- **Perubahan Skema:**
  - Menambahkan kolom `school_origin` (VARCHAR 255, nullable).
  - Menambahkan kolom `piagam_description` (TEXT, nullable).
  - Mengubah kolom `school_id`, `competition_category_id`, dan `competition_year` menjadi `nullable` melalui statement SQL yang kompatibel dengan MariaDB/MySQL tanpa perlu dependensi doctrine/dbal tambahan.
- **Rollback Safety:** Method `down()` mengembalikan constraint dengan aman.

---

## 6. Routes & Controllers

### Route Map

| Method | URI | Controller Action | Middleware | Deskripsi |
|---|---|---|---|---|
| `GET` | `/` | Blade View | `web` | Landing page publik; tombol CTA **Ambil Piagam** mengarah ke `/piagam/ambil`. |
| `GET` | `/piagam/ambil` | `PublicPiagamController@showClaimForm` | `web` | Formulir tanda terima satu halaman untuk publik/tamu loket. |
| `POST` | `/api/v1/piagam/ambil` | `PublicPiagamController@claim` | `api`, `throttle:60,1`, `idempotent` | Endpoint submission tanda terima dan tanda tangan digital. |
| `GET` | `/piagam` | `PiagamController@index` | `web`, `auth`, `active.account` | Buku Ekspedisi Digital Pengambilan Piagam (Staf internal). |
| `GET` | `/piagam/{id}` | `PiagamController@show` | `web`, `auth`, `active.account` | Detail tanda terima dan pratinjau tanda tangan digital privat. |
| `GET` | `/api/v1/attachments/{uuid}/preview` | `AttachmentController@preview` | `web/api`, `auth`, `active.account` | Gateway streaming privat untuk berkas tanda tangan PNG. |

---

## 7. Public Form Design (`/piagam/ambil`)

Formulir dirancang **Mobile-First** khusus untuk layar sentuh ponsel tamu yang memindai QR code di loket dinas:
- **Komponen Blade:** `resources/views/components/piagam/claim-form.blade.php`
- **Field yang Disediakan:**
  1. *Keterangan Piagam / Lomba / Siswa:* Textarea responsif (wajib).
  2. *Asal Sekolah / Instansi:* Input dengan HTML5 `<datalist>` dari database sekolah dinas, atau ketik manual (wajib).
  3. *NPSN:* Input numerik opsional (`inputmode="numeric"`).
  4. *Bidang Pengampu Piagam:* Dropdown pemilihan bidang dinas penyerah (DIKDAS, PAUD, dsb.).
  5. *Nama Lengkap Pengambil:* Input teks (wajib).
  6. *Jabatan Pengambil:* Input dengan opsi datalist (Guru Pendamping, Kepala Sekolah, TU, Wali Murid, dsb.).
  7. *Unit Kerja / Instansi:* Input teks opsional.
  8. *Tanda Tangan Digital:* HTML5 Canvas (`data-testid="signature-pad"`, class `min-h-[180px]`) mendukung pointer, touch, dan mouse events dengan pencegahan scroll layar, resolusi high-DPI (*devicePixelRatio*), tombol bersihkan, dan validasi goresan non-kosong.
- **Client Submission:** Mengirim JSON via Fetch API dengan `X-CSRF-TOKEN` dan `Idempotency-Key` (UUIDv4 unik per sesi form). Tombol submit memicu state loading spinner dan dinonaktifkan untuk mencegah klik ganda.

---

## 8. Internal Register (`/piagam`) — Buku Ekspedisi Digital

Halaman internal yang diakses staf dinas bertindak sebagai **Buku Ekspedisi Digital**:
- **Judul:** Buku Ekspedisi Pengambilan Piagam
- **Dua Tampilan Responsif:**
  - *Mobile (< 768px):* Kartu ringkasan (`data-testid="mobile-piagam-card"`) lengkap dengan badge bidang, waktu pengambilan, nama pengambil, status bukti TTD, dan tombol detail.
  - *Desktop (>= 768px):* Tabel padat (`data-testid="desktop-piagam-grid"`) menampilkan Tanggal & Jam, Keterangan Piagam, Asal Sekolah, Nama Pengambil, Jabatan/Unit Kerja, Divisi, Bukti TTD, dan Aksi.
- **Filter:** Pencarian teks instan (Keterangan, Siswa, Sekolah, Pengambil) serta filter rentang tanggal (*Date From* / *Date To*).
- **Integritas Record:** Catatan tanda terima yang sudah tersimpan tidak menyediakan tombol hapus sewenang-wenang. Pratinjau tanda tangan dilindungi otorisasi divisi.

---

## 9. Division Attribution Strategy

Karena endpoint publik `/piagam/ambil` dapat diakses oleh tamu umum tanpa login:
1. Bidang pengampu dipilih oleh tamu melalui dropdown resmi (`division_id`) yang divalidasi ke tabel `divisions` di database.
2. Tamu juga dapat diarahkan menggunakan URL parameter (misal `?bidang=DIKDAS` dari QR Code loket bidang spesifik) yang secara otomatis memilih bidang terkait.
3. Server memvalidasi keberadaan `division_id` aktif sebelum record dibuat.
4. Isolasi divisi berlaku penuh saat data diakses secara internal: Operator Bidang A dilarang (HTTP 403) melihat tanda terima milik Bidang B.

---

## 10. Signature Storage & Compensating Rollback

- **Private Disk Storage:** Seluruh tanda tangan disimpan secara biner di disk privat (`storage/app/private_signatures/signatures/{DIV_CODE}/{uuid}.png`). Tidak pernah disimpan di folder `public/`.
- **Database Transaction & Compensating Rollback:**
  ```php
  $path = 'signatures/'.$division->code.'/'.Str::uuid().'.png';
  $stagedPath = null;
  try {
      $result = DB::transaction(function () use (...) {
          Storage::disk('private_signatures')->put($path, $binary);
          $stagedPath = $path;
          // Insert Piagam & Attachment record...
      });
  } catch (\Throwable $e) {
      if ($stagedPath && Storage::disk('private_signatures')->exists($stagedPath)) {
          Storage::disk('private_signatures')->delete($stagedPath);
      }
      throw $e;
  }
  ```
- **Streaming Gateway:** Berkas gambar tanda tangan hanya dapat diakses melalui controller terautentikasi `AttachmentController@preview` dengan Policy pengecekan tenant divisi.

---

## 11. Reporting, Search & Dashboard Integration

1. **Dashboard Analytics (`/api/v1/dashboard`):** Menghitung total tanda terima pengambilan yang tercatat sah pada divisi aktif.
2. **Smart Archive Search (`/api/v1/search`):** Menelusuri record piagam berdasarkan `piagam_description`, `student_name`, `recipient_name`, `school_origin`, dan nama relasi sekolah.
3. **CSV Export (`/api/v1/reports/export.csv`):** Mengekspor data serah terima fisik (Waktu, Keterangan, Sekolah, Penerima, Jabatan, Divisi) dengan formula-cell sanitization (`SEC-004`). Tidak memasukkan string Base64 tanda tangan mentah.
4. **PDF Export (`/api/v1/reports/export.pdf`):** Menghasilkan dokumen cetak resmi Buku Ekspedisi berlogo dinas lengkap dengan daftar penerimaan piagam.

---

## 12. Legacy Compatibility & Data Reconciliation

Tiga kategori data dikelola secara harmonis:
1. **Legacy Handover Receipts (dari Google Sheets `{KODE}_piagam`):** Baris data impor dengan nama pengambil dan tautan Drive lama tetap dapat dicari dan dibuka. Jika tautan Drive hilang, ditandai sebagai `is_missing = true` dengan graceful degradation.
2. **Existing Laravel Completed Claims:** Record yang sebelumnya telah selesai diklaim tetap utuh dengan tanda tangan digital tersimpan di disk privat.
3. **Existing Laravel Unclaimed Registrations:** Record lama yang didaftarkan tanpa penerima diberi label status `"Belum Diambil (Tersedia)"` di Buku Ekspedisi tanpa dihapus otomatis dari database.

---

## 13. Security Controls

- **CSRF & Session Protection:** Menggunakan token CSRF aktif untuk sesi web.
- **Rate Limiting:** Dibatasi 60 request per menit pada public endpoint (`throttle:60,1`).
- **Mutation Idempotency:** Header `Idempotency-Key` wajib disertakan dan dicatat dalam tabel `idempotency_keys` untuk mencegah pencatatan ganda akibat tap ganda atau gangguan jaringan seluler.
- **Canvas Payload Validation:** Memvalidasi format Base64 PNG/JPEG dan memeriksa integritas biner agar tidak menyimpan berkas kosong atau corrupt.
- **Division Access Policy:** Enforce server-side 403 Forbidden pada akses data antar bidang.
- **Audit Logging:** Event `PIAGAM_HANDOVER_RECORDED` dicatat ke tabel `audit_logs` secara otomatis dengan ID record dan nama penerima.

---

## 14. Automated Regression Tests

Diverifikasi melalui suite pengujian otomatis lengkap:

| Test File | Fokus Pengujian | Jumlah Test | Status |
|---|---|---|---|
| `DigitalExpeditionBookHandoverContractTest.php` | Pengujian positif & negatif alur baru Buku Ekspedisi | 13 tests | **PASS** |
| `MobileFirstUiContractTest.php` | Mobile-first viewport, canvas touch, layout | 4 tests | **PASS** |
| `FullClientUiWorkflowTest.php` | Alur UI end-to-end lengkap, RBAC, tenant isolation | 16 tests | **PASS** |
| `MasterReferenceDataContractTest.php` | Pengujian master data sekolah & kategori | 6 tests | **PASS** |
| `SearchDashboardReportingContractTest.php` | Dashboard counts, search, CSV & PDF exports | 7 tests | **PASS** |
| `DivisionIsolationContractTest.php` | Tenant isolation antar divisi dinas | 6 tests | **PASS** |
| **Total Test Suite Repository** | **Seluruh modul SISURAT** | **135 tests (860 assertions)** | **100% PASS** |

---

## 15. Manual UAT Checklist (Loket Pengambilan)

| No | Langkah Uji | Hasil yang Diharapkan | Status |
|---|---|---|---|
| 1 | Buka landing page `/` pada smartphone | Tampil tombol **Ambil Piagam**; tidak ada form klaim 4 tahap yang membingungkan | Terverifikasi |
| 2 | Klik **Ambil Piagam** (atau scan QR Bitly) | Mengarah ke `/piagam/ambil`, form terbuka tanpa login | Terverifikasi |
| 3 | Isi Keterangan, Sekolah, dan Identitas Penerima | Input responsif, datalist sekolah memudahkan pengisian | Terverifikasi |
| 4 | Coba klik simpan tanpa tanda tangan | Muncul pesan peringatan: tanda tangan wajib dibubuhkan | Terverifikasi |
| 5 | Goreskan tanda tangan di canvas dan simpan | Tombol menampilkan spinner, tanda terima tersimpan, layar sukses muncul | Terverifikasi |
| 6 | Login sebagai staf dinas di `/piagam` | Data baru langsung muncul di baris teratas Buku Ekspedisi | Terverifikasi |
| 7 | Klik tombol Detail / Lihat TTD | Gambar tanda tangan tampil jelas melalui streaming privat | Terverifikasi |
| 8 | Login sebagai staf divisi lain | Akses detail tanda terima tersebut diblokir dengan HTTP 403 Forbidden | Terverifikasi |

---

## 16. Known Limitations

- **Otentikasi Fisik di Loket:** QR Code dan Bitly didistribusikan secara fisik di meja loket dinas. Karena URL publik dapat diakses lewat internet, sistem mengandalkan rate limiter dan audit log. Verifikasi fisik penerima tetap dilakukan oleh petugas loket yang menyerahkan piagam secara langsung.
- **Pencocokan Otomatis NPSN:** Jika pengambil mengetikkan nama sekolah secara manual tanpa memilih dari datalist, record tetap tersimpan sah dengan kolom `school_origin`, namun `school_id` bernilai `null`.

---

## 17. Release Readiness & Approval

- **Database:** Non-destructive migration diaplikasikan dan teruji.
- **Backend:** Endpoint controller, model, request, dan storage service selaras dengan arsitektur Laravel 12.
- **Frontend:** Komponen Blade responsif, build Vite berhasil tanpa eror (`npm run build` sukses).
- **Testing:** 135 tests lulus tanpa catatan kegagalan (`vendor/bin/pint` lolos formatting).
- **Rekomendasi Rilis:** **APPROVED FOR PRODUCTION DEPLOYMENT**.
