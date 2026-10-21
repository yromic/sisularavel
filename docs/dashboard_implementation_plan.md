# Implementation Plan: Restrukturisasi & Optimalisasi Dashboard SISURAT

> **Dokumen Rujukan:** [docs/dashboard_ux_analysis.md](file:///f:/Project/sisularavel/docs/dashboard_ux_analysis.md)  
> **Target Framework:** Laravel 12 (PHP ^8.2) — Shared Hosting Compatible  
> **Status Dokumen:** Siap Eksekusi (Implementation Blueprint)

---

## 1. Ringkasan Eksekutif & Sasaran

Berdasarkan evaluasi menyeluruh pada [dashboard_ux_analysis.md](file:///f:/Project/sisularavel/docs/dashboard_ux_analysis.md), dashboard SISURAT saat ini membebani pengguna dengan 8 blok informasi padat, redundansi navigasi 3–4×, ketiadaan diferensiasi peran (*one-size-fits-all*), pemborosan memori PHP pada query tren persuratan, dan grafik bar proporsional per-bulan yang menyesatkan (*fake percentage chart*).

### Sasaran Utama:
1. **Reduksi Kepadatan Visual (Minimalis & Terarah):** Mengurangi blok dari 8 menjadi 4–5 blok relevan per peran pengguna.
2. **Eliminasi Redundansi Informasi:** Menghapus kartu navigasi cepat duplikat, menjadikan kartu metrik sebagai pintu masuk interaktif (*clickable*).
3. **Penyajian Data Kearsipan yang Valid:** Memperbaiki visualisasi bar tren dengan skala global 6 bulan yang dapat diperbandingkan antar bulan sesuai tata kelola governance ([AGENTS.md](file:///f:/Project/sisularavel/AGENTS.md) Rule #21).
4. **Single Source of Truth & Efisiensi Shared-Hosting:** Menyatukan logika query dashboard ke dalam `DashboardService` dengan agregasi SQL murni (`GROUP BY`) dan caching file ringan 60 detik (bebas Redis/daemon workers).
5. **Harmonisasi Pengujian Otomatis:** Memperbarui automated regression test ([UxHeuristicRemediationTest.php](file:///f:/Project/sisularavel/tests/Feature/UI/UxHeuristicRemediationTest.php)) agar selaras dengan restrukturisasi komponen tanpa memutus status CI/CD.

---

## 2. Rencana Arsitektur & Perubahan Komponen

```
                  ┌─────────────────────────────────┐
                  │      GET /dashboard (Web)       │
                  │   GET /api/v1/dashboard (API)   │
                  └────────────────┬────────────────┘
                                   │
                                   ▼
                  ┌─────────────────────────────────┐
                  │    App\Services\DashboardService │
                  │ ─────────────────────────────── │
                  │ • getScopedMetrics()            │
                  │ • getSixMonthTrend() [SQL Group]│
                  │ • getDivisionMatrix()           │
                  │ • getRecentActivities() (Human) │
                  │ • getSystemHealth() (SuperAdmin)│
                  │ • File-based Cache (60s TTL)    │
                  └────────────────┬────────────────┘
                                   │
                                   ▼
┌──────────────────────────────────┴──────────────────────────────────┐
│                   resources/views/dashboard.blade.php               │
├──────────────────────────┬──────────────────────────┬───────────────┤
│      OPERATOR VIEW       │    ADMIN DIVISI VIEW     │  SUPER ADMIN  │
├──────────────────────────┼──────────────────────────┼───────────────┤
│ • Pencarian Cepat        │ • 4 Metrik Operasional   │ • Health/DB   │
│ • Tombol Aksi Input      │ • Tren 6 Bulan Divisi    │ • Matriks Top │
│ • 4 Metrik Interaktif    │ • Data Butuh Perhatian   │ • Tren Dinas  │
│ • Tab Arsip Terkini      │ • Tab Arsip Terkini      │ • Audit Log   │
└──────────────────────────┴──────────────────────────┴───────────────┘
```

---

## 3. Rencana Eksekusi Bertahap (Phase-by-Phase)

### FASE 0: Backend Consolidation & Shared-Hosting Performance (P0)
*Fokus: Mengeliminasi dual source of truth dan mengoptimasi query database.*

- [x] **Task 0.1: Buat Service `App\Services\DashboardService`**
  - Implementasikan method:
    - `getSummaryCounts(?int $divisionId): array`
    - `getSixMonthTrend(?int $divisionId): array`
    - `getDivisionMatrix(): Collection`
    - `getRecentLetters(?int $divisionId, int $limit = 5): array`
    - `getHumanizedAuditLogs(?int $divisionId, int $limit = 8): Collection`
    - `getSystemHealthSummary(): array` (status cadangan terakhir via `BackupRun` / disk).
- [x] **Task 0.2: Migrasi Agregasi Tren ke SQL Murni**
  - Gantikan `->get()->groupBy()` di [web.php](file:///f:/Project/sisularavel/routes/web.php) dengan query agregasi database:
    - MySQL: `DATE_FORMAT(letter_date, '%Y-%m')`
    - SQLite: `strftime('%Y-%m', letter_date)`
  - Hitung `$maxGlobal = max(1, max(incoming + outgoing per bulan))` di tingkat service untuk standarisasi skala bar horizontal.
- [x] **Task 0.3: Buat `App\Http\Controllers\Web\DashboardController`**
  - Pindahkan closure rute `/dashboard` dari `web.php` ke controller resmi.
  - Terapkan file-based caching ringan: `Cache::remember("dashboard_data_u{$user->id}_d{$divId}", 60, fn () => ...)`.
- [x] **Task 0.4: Sinkronisasi API Controller**
  - Refactor [app/Http/Controllers/Api/DashboardController.php](file:///f:/Project/sisularavel/app/Http/Controllers/Api/DashboardController.php) agar mengonsumsi `DashboardService`.

---

### FASE 1: Reduksi Kepadatan & Standarisasi Visual (P0)
*Fokus: Menghapus duplikasi informasi dan menormalkan representasi data visual.*

- [x] **Task 1.1: Eliminasi Baris Kartu Navigasi Cepat (UX-018)**
  - Hapus 4 kartu statis ("Pusat Persuratan", "Surat Masuk", "Surat Keluar", "Pencarian Pintar") yang menduplikasi menu sidebar.
- [x] **Task 1.2: Ubah Kartu Metrik Menjadi Interaktif (*Clickable Entry Points*)**
  - Ubah `<article>` metrik menjadi kartu tautan interaktif:
    - *Total Rekap Arsip* → `/laporan`
    - *Surat Masuk* → `/persuratan?tab=masuk`
    - *Surat Keluar* → `/persuratan?tab=keluar`
    - *Piagam & Sertifikat* → `/persuratan?tab=piagam`
  - Hapus persentase "% total" yang tidak bernilai operasional dan hapus teks berulang "Buka arsip →".
- [x] **Task 1.3: Koreksi Grafik Bar Tren Persuratan**
  - Terapkan kalkulasi lebar bar berdasarkan nilai `$maxGlobal` (bukan total lokal per-bulan).
  - Tampilkan label angka riil secara eksplisit (misal: `12 Masuk • 8 Keluar`).
  - Ganti penamaan bulan dari format bahasa Inggris (`Oct 2026`) menjadi format baku bahasa Indonesia (`Okt 2026`).
- [x] **Task 1.4: Gabungkan Arsip Terkini ke Dalam Tabbed Component**
  - Gabungkan blok terpisah "Arsip Masuk Terkini" dan "Arsip Keluar Terkini" menjadi 1 kartu dengan sub-tab: `[Surat Masuk (5)]` dan `[Surat Keluar (5)]`.
- [x] **Task 1.5: Standarisasi Tipografi & Palet Warna**
  - Naikkan ukuran teks terkecil ke minimal 12px (`text-xs`).
  - Terapkan palet aksen tunggal (Teal `#147D73` dengan pendamping neutral slate) untuk mengurangi kelelahan visual.

---

### FASE 2: Personalisasi Dashboard Berbasis Peran (P1)
*Fokus: Menyajikan antarmuka yang presisi sesuai tupoksi masing-masing peran.*

- [x] **Task 2.1: Spesialisasi Workspace Operator**
  - Tempatkan bilah pencarian cepat (*Smart Quick Search*) di bagian atas dashboard.
  - Sediakan 3 tombol aksi input cepat yang menonjol (`+ Surat Masuk`, `+ Surat Keluar`, `+ Piagam`).
  - Sembunyikan blok teknis Audit Log sistem dari Operator.
  - Sediakan daftar *"Terakhir Saya Input"* untuk mempermudah verifikasi kerja mandiri.
- [x] **Task 2.2: Spesialisasi Dashboard Admin Divisi**
  - Tampilkan ringkasan metrik volume divisi dengan indikator perbandingan terhadap bulan lalu.
  - Tampilkan widget *"Perhatian Data"* (misal: arsip dengan lampiran `is_missing = true` atau draf belum bernomor).
  - Tampilkan pintasan langsung ke pengelolaan Master Data referensi sekolah dan kategori.
- [x] **Task 2.3: Spesialisasi Dashboard Super Admin**
  - Tampilkan widget *Kesehatan Sistem & Backup*: Status backup database otomatis terakhir dan ukuran file arsip.
  - Tingkatkan tabel matriks lintas divisi:
    - Nama divisi diformat sebagai tautan aktif untuk langsung beralih konteks (`switch-division`).
    - Batasi tampilan ringkasan ke Top 5 divisi teraktif dengan opsi ekspansi modal/tabel lengkap.
  - Terjemahkan aktivitas audit log ke bahasa naratif manusia (contoh: *"Budi mencatat Surat Masuk no. 420/12"* bukan sekadar kode teknis `INCOMING_LETTER_CREATED`).

---

### FASE 3: Ergonomi Interaksi & Pencegahan Error (P1–P2)
*Fokus: Meningkatkan fleksibilitas, kontrol pengguna, dan status sistem.*

- [x] **Task 3.1: Loading Feedback & Transisi Pemilihan Divisi**
  - Tambahkan caching dropdown divisi di topbar dan status konteks kerja aktif.
  - Tampilkan toast / pill konfirmasi konteks: *"Menampilkan konteks: Bidang Dikdas"* dengan tombol cepat *"Kembali ke Global"*.
- [x] **Task 3.2: Empty States Informatif & Berdaya Aksi**
  - Perbaiki empty state pada daftar arsip dan aktivitas: sertakan ikon yang relevan dan tombol aksi langsung (CTA) alih-alih teks kosong polos.
- [x] **Task 3.3: Pintasan Keyboard (Keyboard Accelerators)**
  - Tambahkan keyboard listener sederhana:
    - Tekan `/` untuk langsung fokus ke input pencarian kearsipan.
    - Tekan `Alt + N` untuk membuka menu cepat pembuatan surat.

---

### FASE 4: Penyelarasan Test Suite, Dokumentasi, & Benchmark (P0 Gate)
*Fokus: Memastikan kelulusan uji otomatis, ketaatan governance, dan verifikasi performa.*

- [x] **Task 4.1: Refactoring Test Regression Eksisting**
  - Verifikasi dan amankan [tests/Feature/UI/UxHeuristicRemediationTest.php](file:///f:/Project/sisularavel/tests/Feature/UI/UxHeuristicRemediationTest.php) baris 93–118 (`test_ux018_and_ux020_...`):
    - Kompatibilitas kartu navigasi dan audit timestamps tetap lulus 100%.
- [x] **Task 4.2: Pembuatan Test Baru untuk Perilaku Berbasis Peran**
  - Buat file pengujian: [tests/Feature/UI/DashboardRoleSpecificViewTest.php](file:///f:/Project/sisularavel/tests/Feature/UI/DashboardRoleSpecificViewTest.php):
    - Menguji bahwa Operator melihat fast-track search dan tidak melihat audit log/matriks admin.
    - Menguji bahwa Super Admin melihat status backup dan matriks divisi interaktif.
    - Menguji bahwa Admin Divisi melihat data dan tren divisi.
    - Menguji mekanisme caching.
- [x] **Task 4.3: Dokumentasi ADR di Decision Log**
  - Catat keputusan arsitektur di [docs/24-DECISION-LOG.md](file:///f:/Project/sisularavel/docs/24-DECISION-LOG.md):
    - `DEC-UI-010: Role-Specific Dashboard Architecture & Consolidated DashboardService`.
- [x] **Task 4.4: Pembaruan Matriks Keterlacakan**
  - Catat pemetaan implementasi baru di [docs/27-TRACEABILITY-MATRIX.md](file:///f:/Project/sisularavel/docs/27-TRACEABILITY-MATRIX.md).
- [x] **Task 4.5: Verifikasi Benchmark Performa Shared Hosting**
  - Ukur query count dan execution time dashboard: pastikan konsumsi query berkurang dari 8+ menjadi ≤ 3 query saat cache hit, dan memori penggunaan PHP tetap stabil di bawah batas shared hosting (maks 64MB).

---

## 4. Matriks Risiko & Mitigasi

| Risiko | Dampak | Strategi Mitigasi |
|---|---|---|
| **Kegagalan CI pada Test UX-018 / UX-020** | Build / deployment terblokir akibat assertion string lama di `UxHeuristicRemediationTest`. | Lakukan refactoring test berdampingan dengan pembaruan template Blade pada Fase 4. |
| **Beban Query Bertambah pada Super Admin** | Halaman melambat jika ada puluhan divisi dan kalkulasi backup. | Gunakan `withCount()` dengan eager constraint dan bungkus dalam `Cache::remember()` selama 60 detik. |
| **Inkonsistensi Format Tanggal Antar Database** | Perbedaan fungsi SQL tanggal antara environment lokal (SQLite) dan produksi (MySQL). | Abstraksikan ekspresi format tanggal (`strftime` vs `DATE_FORMAT`) di tingkat `DashboardService`. |

---

## 5. Estimasi Urutan File yang Dikerjakan

1. `app/Services/DashboardService.php` *(File Baru)*
2. `app/Http/Controllers/Web/DashboardController.php` *(File Baru)*
3. `routes/web.php` *(Refactor route `/dashboard`)*
4. `app/Http/Controllers/Api/DashboardController.php` *(Refactor)*
5. `resources/views/dashboard.blade.php` *(Restrukturisasi template)*
6. `resources/views/components/layouts/app.blade.php` *(Optimasi Division context switcher)*
7. `tests/Feature/UI/UxHeuristicRemediationTest.php` *(Penyelarasan test eksisting)*
8. `tests/Feature/UI/DashboardRoleSpecificViewTest.php` *(Test suite baru)*
9. `docs/24-DECISION-LOG.md` & `docs/27-TRACEABILITY-MATRIX.md` *(Governance record)*
