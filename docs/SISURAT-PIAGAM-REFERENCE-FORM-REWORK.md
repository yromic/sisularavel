# SISURAT — Arsitektur & Implementasi Rework Formulir Pengambilan Piagam (Buku Ekspedisi Digital)

**Dokumen Kontrak & Spesifikasi Implementasi:** `docs/SISURAT-PIAGAM-REFERENCE-FORM-REWORK.md`  
**Framework & Runtime:** Laravel 12.69.2 / PHP 8.2.12 / MariaDB 10.4 / Blade / JavaScript  
**Status:** IMPLEMENTED & VERIFIED (143/143 Automated Tests Passed)

---

## 1. Analisis Skema Sebelum Perubahan

Sebelum penyempurnaan ini, modul Piagam mengalami transisi dari sistem lama (dua tahap: pre-registration + klaim publik) menjadi pencatatan langsung serah terima fisik di loket. Namun, pada implementasi awal:
1. Pilihan unit kerja terbatas pada entitas `schools` (Satuan Pendidikan dengan NPSN), sehingga instansi non-sekolah (Kecamatan, Korwilcam, Sekretariat Daerah, Cabang Dinas) tidak memiliki entitas referensi resmi.
2. Field jenis pengambilan (`pickup_category_id`) dan kategori lomba (`competition_category_id`) belum menyimpan snapshot teks permanen (`pickup_category_name`, `competition_category_name`). Akibatnya, jika administrator mengubah nama kategori lomba atau menonaktifkan kategori di kemudian hari, tampilan arsip tanda terima lama berisiko berubah atau menjadi tidak informatif.
3. Form publik belum mendukung fallback input manual yang terintegrasi untuk Divisi Tujuan, Unit Kerja, Jenis Pengambilan, dan Kelas Kompetisi.
4. Input NPSN belum otomatis sinkron dengan sekolah yang dipilih dari master data.

---

## 2. Field Mapping (Business Requirements vs Data Model)

| Business Field (Form Label) | Input Control | Referensi Master | Target Column (`piagams`) | Mekanisme Resolusi & Snapshot |
|---|---|---|---|---|
| **Divisi Tujuan / Bidang Pengampu** | Searchable Select + Manual input | `divisions` (aktif) | `division_id` | Validasi ketat: Manual entry dicocokkan case-insensitive dengan kode/nama divisi aktif resmi. Wajib menghasilkan ID divisi valid; tidak boleh membuat divisi baru. |
| **Nama Pengambil** | Text input (required) | Langsung | `recipient_name` | String input langsung dari penerima di loket. |
| **Posisi / Jabatan** | Text input + Datalist | Preset Jabatan Loket | `recipient_position` | String jabatan (Guru Pendamping, Kepala Sekolah, Staf TU, dll). |
| **Unit Kerja / Asal Instansi** | Searchable Select + Manual input | `schools` (optgroup Sekolah) & `work_units` (optgroup Non-Sekolah) | `school_id`, `work_unit_id`, `school_origin`, `recipient_unit` | Jika memilih sekolah: simpan `school_id`, auto-populate `school_origin` & `npsn`. Jika memilih unit kerja: simpan `work_unit_id` & `recipient_unit`. Jika manual: simpan `school_origin` & `recipient_unit` (FK null). |
| **Nomor Publik (NPSN)** | Numeric text input | Terisi otomatis jika sekolah terpilih | `npsn` | 8 digit angka jika memilih sekolah; opsional/manual jika instansi umum. |
| **Jenis Pengambilan** | Select + Manual input | `pickup_categories` (aktif) | `pickup_category_id`, `pickup_category_name` | Pilihan resmi menyimpan ID + nama kategori; jika manual, ID null dan `pickup_category_name` menyimpan teks manual. |
| **Kelas Kompetisi** | Select + Manual input | `competition_categories` (aktif) | `competition_category_id`, `competition_category_name` | Pilihan resmi menyimpan ID + nama lomba; jika manual, ID null dan `competition_category_name` menyimpan teks manual. |
| **Tahun Acara** | Year input (number) | Default current year | `event_year` | Integer 4 digit (misal: 2026). |
| **Kandidat Prestasi (Siswa)** | Text input (required) | Langsung | `student_name` | String nama siswa/peserta peraih piagam. |
| **Bukti Pengambilan (Tanda Tangan)** | HTML5 Canvas (Touch/Mouse) | Private disk storage | `signature_path` | Base64 PNG di-decode dan disimpan ke private storage `signatures/{division}/{YYYY}/{MM}/{uuid}.png`. Path dicatat di DB. |
| **Waktu Pengambilan** | Server timestamp | Server-side time | `claimed_at`, `handover_at`, `received_date` | Timestamp resmi server saat transaksi disimpan. |

---

## 3. Perubahan Database

Dibuat migration: `database/migrations/2026_10_09_155000_create_work_units_and_enhance_piagams_references.php`

### A. Tabel Baru: `work_units`
```sql
CREATE TABLE `work_units` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_work_units_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### B. Penambahan Kolom pada `piagams`
- `work_unit_id` (BIGINT UNSIGNED NULL, Foreign Key ke `work_units.id` ON DELETE SET NULL).
- `npsn` (VARCHAR(20) NULL) untuk persistensi NPSN instansi/sekolah pada level record.
- `pickup_category_name` (VARCHAR(150) NULL) untuk text snapshot jenis pengambilan.
- `competition_category_name` (VARCHAR(150) NULL) untuk text snapshot kelas kompetisi.

---

## 4. Reference Integration & Master Management

1. **Model `WorkUnit` (`app/Models/WorkUnit.php`):**
   - Mendukung soft status `is_active`.
   - Relasi `piagams()` (HasMany).
2. **Kelola Referensi (`resources/views/master/categories/index.blade.php`):**
   - Layout diperluas menjadi grid 3-kolom:
     1. Kategori Perlombaan (Lomba)
     2. Kategori Pengambilan Piagam
     3. Unit Kerja / Instansi Non-Sekolah (Baru)
   - Dilengkapi modal Create & Edit, badge status aktif, aksi toggle status/delete.
3. **Endpoint & Controller (`ReferenceCategoryController.php`):**
   - Ditambahkan `storeWorkUnit`, `updateWorkUnit`, `destroyWorkUnit`.
   - Route Web: `POST /master/kategori/unit-kerja`, `PUT /master/kategori/unit-kerja/{workUnit}`, `DELETE /master/kategori/unit-kerja/{workUnit}`.
   - Endpoint API: `GET /api/v1/reference/work-units`.
4. **Seeder Data (`WorkUnitSeeder.php`):**
   - Disiapkan data default: Korwilcam Bidang Pendidikan (berbagai kecamatan), Kantor Cabang Dinas, Sekretariat Daerah.

---

## 5. Manual Input Behavior & Historical Integrity

### A. Fallback Input Manual
Setiap dropdown (Divisi, Unit Kerja, Jenis Pengambilan, Kelas Kompetisi) memiliki opsi **"-- Lainnya / Ketik Manual --"**.
- Ketika opsi manual dipilih, antarmuka JavaScript secara reaktif memunculkan field input teks dengan styling khusus amber border.
- Nilai manual dikirim bersama payload form.
- Nilai manual **TIDAK PERNAH** dimasukkan secara otomatis ke dalam tabel master referensi (mencegah master data pollution dari formulir publik).

### B. Keamanan & Integritas Divisi Tujuan
Sesuai aturan isolasi tenant dinas:
- Pengunjung publik tidak dapat membuat divisi baru.
- Jika pengguna mengetik teks manual untuk divisi (misal: "dikdas" atau "bidang pembinaan sd"), backend `PublicClaimPiagamRequest` melakukan normalisasi dan pencocokan string case-insensitive terhadap divisi aktif resmi.
- Jika cocok secara unik, `division_id` resmi digunakan.
- Jika tidak cocok atau ambigu, request ditolak dengan pesan validasi ramah: *"Nama divisi manual tidak cocok dengan divisi resmi dinas. Silakan pilih bidang resmi yang tersedia."*

### C. Text Snapshot & Historical Immutability
Untuk mengantisipasi perubahan data master di masa depan:
- `pickup_category_name` dan `competition_category_name` disimpan permanen di tabel `piagams`.
- Model `Piagam` menyediakan accessor pintar:
  - `pickup_display_name`: memprioritaskan snapshot `pickup_category_name`, lalu nama relasi `pickupCategory->name`, lalu label default loket.
  - `competition_display_name`: memprioritaskan snapshot `competition_category_name`, lalu relasi `competitionCategory->name`.
  - `unit_display_name`: memprioritaskan relasi `workUnit->name`, lalu `school->name`, lalu teks `recipient_unit` atau `school_origin`.
- Ketika kategori referensi diubah namanya atau dihapus oleh admin, riwayat tanda terima lama tetap menampilkan nama aslinya sesuai saat transaksi dibuat.

---

## 6. Berkas yang Diubah & Dibuat

1. `database/migrations/2026_10_09_155000_create_work_units_and_enhance_piagams_references.php` *(Baru)*
2. `database/seeders/WorkUnitSeeder.php` *(Baru)*
3. `database/seeders/DatabaseSeeder.php` *(Menyertakan WorkUnitSeeder)*
4. `app/Models/WorkUnit.php` *(Baru)*
5. `app/Models/Piagam.php` *(Update relasi, fillable, dan accessors display name)*
6. `app/Http/Controllers/Api/ReferenceCategoryController.php` *(CRUD handler WorkUnit)*
7. `app/Http/Requests/PublicClaimPiagamRequest.php` *(Resolusi komprehensif referensi & manual fallback)*
8. `app/Http/Controllers/Api/PublicPiagamController.php` *(Penyimpanan tanda terima langsung + rollback proteksi)*
9. `resources/views/components/piagam/claim-form.blade.php` *(Rework formulir Bagian A, B, C, canvas signature & interaksi)*
10. `resources/views/master/categories/index.blade.php` *(UI Kelola Referensi 3-kolom)*
11. `resources/views/piagam/index.blade.php` *(Buku ekspedisi digital dengan display accessor)*
12. `resources/views/piagam/show.blade.php` *(Detail tanda terima lengkap atribut baru)*
13. `resources/views/reports/pdf.blade.php` *(Ekspor PDF mengadopsi fallback snapshot)*
14. `app/Http/Controllers/Api/ReportExportController.php` *(Ekspor CSV mengadopsi fallback snapshot)*
15. `app/Http/Controllers/Api/SearchController.php` *(Pencarian pintar mencari field referensi & manual)*
16. `routes/web.php` *(Route master work unit & data binding GET /piagam/ambil)*
17. `tests/Feature/Piagam/DigitalExpeditionBookHandoverContractTest.php` *(Suite pengujian 21 skenario)*

---

## 7. Security & Privacy Review

- **Public Route Protection:** Endpoint `/piagam/ambil` dan `POST /api/v1/piagam/ambil` dapat diakses tamu tanpa autentikasi, namun dilindungi:
  - Rate limiting (30 requests/minute).
  - Validasi ketat server-side melalui `PublicClaimPiagamRequest`.
  - Header `Idempotency-Key` untuk mencegah double-submission saat koneksi tidak stabil.
- **Private Signature Storage:** Berkas tanda tangan digital disimpan pada private disk (`storage/app/private_signatures`), bukan `public/`.
- **Authorized Streaming Gateway:** Preview tanda tangan hanya dapat diakses melalui route `/api/v1/piagam/{piagam}/signature` yang diautentikasi oleh policy `PiagamPolicy` dan isolasi divisi.
- **Storage Consistency & Rollback:** Jika transaksi database gagal setelah gambar tanda tangan ditulis ke disk, berkas staging langsung dihapus secara otomatis.
- **Tenant Isolation:** Operator hanya dapat melihat tanda terima piagam milik divisinya sendiri. Super Admin dapat melihat lintas divisi melalui context switcher.

---

## 8. Regression Tests & Verification Results

Semua 143 automated tests dalam repository berjalan dan **100% LULUS** (899 assertions).

### Spesifik Pengujian Fitur Rework Piagam (`DigitalExpeditionBookHandoverContractTest`):
1. `test_guest_can_access_piagam_ambil_without_login` — **PASS**
2. `test_landing_page_displays_cta_button_and_no_embedded_claim_form` — **PASS**
3. `test_guest_can_submit_handover_receipt_directly_without_pre_registration` — **PASS**
4. `test_handover_receipt_submission_rejects_missing_mandatory_fields` — **PASS**
5. `test_handover_receipt_submission_rejects_blank_or_invalid_signature` — **PASS**
6. `test_handover_receipt_submission_is_idempotent` — **PASS**
7. `test_recorded_receipt_appears_in_internal_digital_expedition_book` — **PASS**
8. `test_authorized_staff_can_view_receipt_detail_and_signature_preview` — **PASS**
9. `test_guest_is_forbidden_from_internal_expedition_book` — **PASS**
10. `test_operator_from_other_division_is_forbidden_from_viewing_receipt_detail` — **PASS**
11. `test_dashboard_metrics_counts_recorded_handover_receipts` — **PASS**
12. `test_smart_search_finds_recorded_handover_by_recipient_or_school` — **PASS**
13. `test_export_csv_includes_recorded_handover_data` — **PASS**
14. `test_public_form_renders_actual_active_references` — **PASS**
15. `test_submission_with_reference_selection_stores_relations_and_snapshots` — **PASS**
16. `test_submission_with_manual_entries_stores_snapshots_without_master_pollution` — **PASS**
17. `test_submission_rejects_invalid_or_inactive_reference_ids` — **PASS**
18. `test_submission_rejects_invalid_or_ambiguous_manual_division` — **PASS**
19. `test_submission_accepts_valid_unique_manual_division_and_resolves_id` — **PASS**
20. `test_historical_record_preserves_original_labels_when_reference_is_renamed_or_deleted` — **PASS**
21. `test_admin_can_manage_work_units_via_reference_endpoint` — **PASS**

---

## 9. Browser & Mobile UX Testing

- **Layout Mobile-First:** Komponen formulir didesain dengan kontainer responsif dan padding yang nyaman pada viewport layar sentuh smartphone.
- **Canvas Signature Handling:**
  - Mendukung PointerEvents, TouchEvents, dan MouseEvents.
  - Memiliki `touch-action: none` untuk mencegah scrolling browser yang tidak disengaja saat membubuhkan tanda tangan.
  - Menggunakan rasio `window.devicePixelRatio` untuk resolusi tajam pada layar retina/High-DPI.
  - Otomatis mempertahankan goresan tanda tangan saat terjadi penyesuaian ukuran viewport/rotasi layar.
- **Anti Double-Submit:**
  - Tombol submit menampilkan animasi spinner dan menonaktifkan diri (`disabled`) saat pengiriman sedang berlangsung.
- **Status UAT Browser:** Dilengkapi dengan manual smoke test checklist pada loket dinas.

---

## 10. Kompatibilitas Legacy Data

- Record piagam legacy tidak dihapus maupun dimodifikasi strukturnya secara destruktif.
- Kolom baru bersifat nullable sehingga kompatibel penuh dengan data historis.
- Accessor `pickup_display_name`, `competition_display_name`, dan `unit_display_name` memiliki fallback cerdas sehingga record lama tetap terformat rapi pada antarmuka dan laporan.

---

## 11. Status Checkpoint Implementasi

- [x] **Checkpoint A:** Audit model dan referensi (selesai).
- [x] **Checkpoint B:** Database migration & backend service (selesai).
- [x] **Checkpoint C:** Form publik mobile-first Bagian A, B, C dengan canvas tanda tangan (selesai).
- [x] **Checkpoint D:** Kelola Referensi untuk Unit Kerja & integrasi form publik (selesai).
- [x] **Checkpoint E:** Internal Register, Detail, Dashboard, Smart Search, CSV & PDF (selesai).
- [x] **Checkpoint F:** Regression testing & code formatting Pint (selesai, 143 test pass, 0 linter errors).

---

## 12. Known Limitations & Rekomendasi Operasional

1. **Jaringan Loket:** Pengambilan piagam di loket fisik membutuhkan koneksi internet pada perangkat tamu atau loket dinas untuk mengakses `/piagam/ambil`.
2. **Kualitas Resolusi QR Code:** Disarankan mencetak stiker QR Code loket dengan ukuran minimal 8x8 cm agar mudah dipindai oleh semua tipe kamera ponsel tamu.
