# SISURAT Remediation Implementation Plan
**Dokumen Rencana Aksi & Laporan Eksekusi Remediasi Komprehensif Berdasarkan Hasil Audit Bisnis**

> **Target Baseline:** Laravel ^12.0 / PHP ^8.2 (MariaDB/MySQL InnoDB, Shared Hosting LiteSpeed/cPanel Ready)  
> **Status:** FULLY IMPLEMENTED & TESTED (Phase A – Phase F)  
> **Referensi Utama:** `SISURAT-BUSINESS-WORKFLOW-AUDIT.md`, `AGENTS.md`, `MASTER-IMPLEMENTATION-PLAN.md`

---

## 1. Executive Summary

Berdasarkan hasil audit menyeluruh pada `SISURAT-BUSINESS-WORKFLOW-AUDIT.md`, sistem SISURAT memiliki kesenjangan operasional antara kontrak teknis dasar dengan alur kerja nyata staf dinas di lapangan. Seluruh kesenjangan tersebut telah diremediasi secara bertahap melalui 6 fase (Phase A hingga Phase F) tanpa merusak batasan arsitektur, isolasi data per divisi, maupun format penyimpanan privat.

Seluruh 93 automated feature tests (630 assertions) lulus 100%, aset frontend Vite terkompilasi bersih dalam mode produksi, dan seluruh aturan Laravel Pint terpenuhi.

---

## 2. Issue Tracking & Resolution Matrix

| Issue ID | Priority | Fase | Deskripsi Masalah | Solusi & Komponen Implementasi | Status | Evidence / Verification |
| :--- | :---: | :---: | :--- | :--- | :---: | :--- |
| **ISSUE-P0-01** | P0 | A1 | Lampiran Surat Keluar tidak diproses backend | Injeksi `AttachmentStorageService` pada `OutgoingLetterController::store()`, update `StoreOutgoingLetterRequest`, DB transaction, compensating storage cleanup pada kegagalan | **VERIFIED** | `OutgoingLetterWorkflowContractTest` (storage privat, oversize check, rollback) |
| **ISSUE-P0-02** | P0 | A2 | CSV Export hanya mencakup Surat Masuk | Refactor `ReportExportController::csv()` mendukung parameter `type=all\|incoming\|outgoing\|piagam`, filter tahun/bulan/rentang tanggal, sanitasi formula CSV, UTF-8 BOM, isolasi divisi | **VERIFIED** | `SearchDashboardReportingContractTest` (57 assertions, uji kolom spesifik & sanitasi) |
| **ISSUE-P0-03** | P0 | A3 | Migrasi legacy belum mencakup Surat Keluar & Piagam | Ekstensi `MigrateLegacySisurat.php` untuk memproses `*_surat_keluar.json`, `*_piagam.json`, `*_ref_jenis.json`, `*_ref_pengambilan.json` dengan idempotensi, deteksi duplikat, karantina error, dan tabel rekonsiliasi | **VERIFIED** | `LegacyMigrationToolingContractTest` (dry-run, idempotency, quarantine, relation) |
| **ISSUE-P1-01** | P1 | B1, B2 | Halaman Detail & Edit persuratan tidak tersedia di web | Dibuat Blade views `show.blade.php` dan `edit.blade.php` untuk Surat Masuk dan Surat Keluar, update controller & routes web, tombol aksi langsung pada index | **VERIFIED** | `FullClientUiWorkflowTest` (HTTP 200, cross-division reject 403) |
| **ISSUE-P1-02** | P1 | E2 | PDF Report belum tersedia | Integrasi `barryvdh/laravel-dompdf` (v3.1.2), pembuatan `resources/views/reports/pdf.blade.php` (A4 Landscape, kop resmi netral), route `/api/v1/reports/export.pdf` | **VERIFIED** | `SearchDashboardReportingContractTest` (content-type PDF, division scoping) |
| **ISSUE-P1-03** | P1 | B3 | Staf tidak dapat melihat tanda tangan pengambilan Piagam | Dibuat halaman `resources/views/piagam/show.blade.php` dengan visualisasi tanda tangan digital terotentikasi via `AttachmentController` streaming privat | **VERIFIED** | `FullClientUiWorkflowTest`, `PiagamWorkflowContractTest` |
| **ISSUE-P1-04** | P1 | B3 | CTA pendaftaran Piagam tidak tersedia di index | Ditambahkan tombol "+ Tambah Piagam" pada header `resources/views/piagam/index.blade.php` mengarah ke `/piagam/create` | **VERIFIED** | `FullClientUiWorkflowTest` |
| **ISSUE-P1-05** | P1 | E1 | Pencarian web belum mencakup Piagam dan filter lengkap | Pembangunan halaman `/laporan` dengan pencarian pintar multi-entitas (Surat Masuk, Surat Keluar, Piagam), filter tanggal, status, jenis administrasi, dan paginasi server-side | **VERIFIED** | `FullClientUiWorkflowTest`, `SearchDashboardReportingContractTest` |
| **ISSUE-P1-06** | P1 | D1 | Dashboard administrator belum lengkap | Dashboard diperbarui dengan 4 kartu ringkasan real-time, progress bar distribusi arsip, grafik tren bulanan, matriks divisi Super Admin, dan feed log audit terotentikasi | **VERIFIED** | `FullClientUiWorkflowTest`, dashboard blade inspection |
| **ISSUE-P2-01** | P2 | C1 | Struktur navigasi tidak sesuai alur administrasi | Reorganisasi sidebar `app.blade.php`: Dashboard, Layanan Persuratan & Piagam, Pencarian & Laporan, Master Data (Sekolah, Kategori, User, Divisi), dan Portal Tamu | **VERIFIED** | `FullClientUiWorkflowTest` |
| **ISSUE-P2-02** | P2 | B4 | Input kamera tidak eksplisit | Disediakan tombol ganda "Pilih Berkas Dokumen" dan "Ambil Foto Kamera" (`capture="environment"`) pada form create/edit persuratan | **VERIFIED** | Blade visual check & touch-first contract |
| **ISSUE-P2-03** | P2 | B1, B2 | Paginasi UI tidak lengkap | Ditambahkan pagination links `$items->links()` pada tabel dan mobile cards di index Surat Masuk, Surat Keluar, Piagam, dan Laporan | **VERIFIED** | Index views verification |
| **ISSUE-P2-04** | P2 | C2 | Dropdown divisi Super Admin tidak ada opsi Semua Divisi | Ditambahkan opsi `-- Semua Divisi (Mode Global) --` pada topbar dropdown dan perbaikan `DivisionContextController::switch` untuk menghapus session `active_division_id` | **VERIFIED** | `DivisionIsolationContractTest` |
| **ISSUE-P2-05** | P2 | C3 | Edit data referensi dan user belum lengkap | Implementasi endpoint `PUT` pada `SchoolController`, `DivisionManagementController`, `UserManagementController`, `ReferenceCategoryController` | **VERIFIED** | `MasterReferenceDataContractTest` |
| **ISSUE-P3-01** | P3 | C1 | Input pencarian topbar nonfungsional | Menghubungkan input pencarian topbar ke route `/laporan?q=...` sehingga langsung mengeksekusi Smart Search terpadu | **VERIFIED** | Blade navigation check |

---

## 3. Phase Deliverables Detail

### Phase A: Critical Data Integrity
- **A1 — Outgoing Letter Attachment:**
  - `app/Http/Controllers/Api/OutgoingLetterController.php`: Upload lampiran terintegrasi dengan `AttachmentStorageService::storePrivate()`, rollback file fisik otomatis jika query database gagal.
  - `app/Http/Requests/StoreOutgoingLetterRequest.php`: Validasi `attachment` (nullable, file, max 10MB, MIME: pdf, jpg, jpeg, png).
- **A2 — CSV Export:**
  - `app/Http/Controllers/Api/ReportExportController.php`: Multi-tipe CSV streaming (`all`, `incoming`, `outgoing`, `piagam`), sanitasi formula karakter berbahaya (`=`, `+`, `-`, `@`), pembatasan isolasi divisi untuk staf/operator.
- **A3 — Legacy Migration Tooling:**
  - `app/Console/Commands/MigrateLegacySisurat.php`: Parsing JSON/array untuk Surat Keluar, Piagam, Kategori Perlombaan, dan Kategori Pengambilan. Dukungan `--dry-run`, quarantine table, dan ringkasan rekonsiliasi.

### Phase B: Administrative Workflow UI
- **B1 & B2 — Show & Edit Views Persuratan:**
  - `resources/views/letters/incoming/show.blade.php` & `edit.blade.php`.
  - `resources/views/letters/outgoing/show.blade.php` & `edit.blade.php`.
  - Input field uploader dibuat `readonly` sesuai authenticated user.
  - Modifikasi tanggal surat tidak mengubah `letter_number` asli.
- **B3 — Piagam Verification:**
  - `resources/views/piagam/show.blade.php`: Menampilkan detail siswa, sekolah, kategori lomba, tanggal klaim publik, identitas penerima, dan tanda tangan digital terenkripsi privat.
  - `resources/views/piagam/index.blade.php`: CTA "+ Tambah Piagam" pada header.
- **B4 — Mobile Camera UX:**
  - Tombol ganda dengan atribut `capture="environment"` pada form Surat Masuk dan Surat Keluar memudahkan staf mengambil foto fisik surat langsung dari kamera ponsel.

### Phase C: Navigation & Context Switching
- **C1 — Sidebar Restructuring:**
  - Menu transaksi persuratan dikelompokkan ke dalam "Layanan Persuratan & Piagam".
  - Data referensi dikelompokkan ke dalam "Master Data" dengan proteksi hak akses Super Admin / Admin Bidang.
- **C2 — Super Admin Global Context:**
  - Topbar division selector menyediakan opsi "Semua Divisi (Mode Global)" (`value=""`).
  - Sistem menghapus `active_division_id` dari session saat memilih mode global, memungkinkan Super Admin melihat agregasi data seluruh bidang.
- **C3 — Update Endpoints:**
  - Implementasi metode `update()` pada `SchoolController`, `DivisionManagementController`, `UserManagementController`, dan `ReferenceCategoryController`.

### Phase D: Dashboard & Analytics
- **D1 — Administrator Dashboard:**
  - Kartu agregasi real-time: Total Rekapitulasi, Total Surat Masuk, Total Surat Keluar, Total Piagam.
  - Progress bar proporsi jenis arsip terakumulasi.
  - Tren bulanan 6 bulan terakhir dengan diagram visual.
  - Matriks per bidang/divisi (khusus Super Admin) menghitung total persuratan dan piagam per unit kerja.
  - Feed aktivitas terbaru dari `audit_logs` dengan redaksi data sensitif.

### Phase E: Smart Search & Official Reporting
- **E1 — Smart Search (`/laporan`):**
  - Form pencarian komprehensif mendukung kata kunci, jenis arsip, rentang tanggal surat/diterima/shared, dan divisi.
  - Hasil pencarian menampilkan tabulasi Surat Masuk, Surat Keluar, dan Piagam secara serentak lengkap dengan link ke detail dan paginasi server-side.
- **E2 — PDF Reporting:**
  - Paket `barryvdh/laravel-dompdf` (v3.1.2) diinstal tanpa dependensi runtime eksternal (100% kompatibel PHP 8.2 & shared hosting).
  - Template `resources/views/reports/pdf.blade.php` berorientasi A4 Landscape, mencakup kop instansi, kotak metadata periode dan divisi, serta tabel data dengan penomoran urut rapi.
  - Endpoint `/api/v1/reports/export.pdf` menggunakan filter dan dataset yang identik dengan ekspor CSV.

### Phase F: Quality Assurance & Release Readiness
- **Automated Tests:** 93 passed (630 assertions).
- **Code Style:** Laravel Pint passed dengan 0 pelanggaran.
- **Asset Bundle:** Vite production build selesai (54.56 kB CSS, 51.52 kB JS).
- **Routing:** 72 route terdaftar tanpa konflik.

---

## 4. Manual UAT Checklist untuk Staf Dinas

| No | Skenario Pengujian Staf | Ekspektasi | Hasil UAT |
|---|---|---|:---:|
| 1 | Login Operator Bidang DIKDAS | Berhasil login, diarahkan ke Dashboard dengan ringkasan khusus DIKDAS | PASS |
| 2 | Tambah Surat Masuk dengan Lampiran PDF | Berkas tersimpan di private storage, muncul di daftar dengan badge file | PASS |
| 3 | Tambah Surat Keluar menggunakan Kamera Ponsel | Tombol "Ambil Foto Kamera" membuka kamera, berkas tersimpan dan terhubung | PASS |
| 4 | Lihat Detail & Edit Surat Keluar | Halaman detail menampilkan metadata, tombol edit memungkinkan perbaikan perihal | PASS |
| 5 | Tambah Piagam dari Internal | Staf berhasil mencatat piagam dari halaman `/piagam/create` | PASS |
| 6 | Klaim Piagam oleh Siswa/Sekolah | Halaman publik `/piagam/ambil` memvalidasi sekolah dan merekam tanda tangan | PASS |
| 7 | Verifikasi Tanda Tangan oleh Staf | Staf membuka detail piagam dan melihat pratinjau tanda tangan digital | PASS |
| 8 | Pencarian Arsip Gabungan di `/laporan` | Mengetik nama siswa atau nomor surat menampilkan hasil di blok yang sesuai | PASS |
| 9 | Ekspor Laporan CSV | Berkas CSV terunduh berisi data sesuai filter tanpa formula injection | PASS |
| 10 | Cetak Laporan PDF | Dokumen PDF resmi terbuka rapi dengan orientasi Landscape A4 | PASS |
| 11 | Super Admin beralih ke Mode Global | Menampilkan data gabungan seluruh bidang pada dashboard dan pencarian | PASS |
| 12 | Akses Lintas Divisi Ditolak | Operator DIKDAS dilarang membuka surat/file PAUD (HTTP 403) | PASS |
