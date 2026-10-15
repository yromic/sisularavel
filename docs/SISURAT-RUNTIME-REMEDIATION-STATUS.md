# SISURAT Runtime & Integration Remediation Status Report

> **Document Version:** 1.0.0  
> **Date:** 2026-10-09  
> **Role:** Principal Laravel Architect, Senior Full-Stack Engineer, Security Engineer & QA Lead  
> **Baseline Framework:** Laravel 12.69.2 / PHP 8.2.12 / MariaDB 10.4  
> **Target Platform:** LiteSpeed / cPanel Shared Hosting Compatible  

---

## 1. Executive Summary

Laporan ini mendokumentasikan perbaikan menyeluruh terhadap runtime, konsistensi integrasi Web/API, server-side authorization, dan pengalaman pengguna antarmuka Blade pada sistem **SISURAT (Sistem Informasi Surat & Piagam)** Disdikbudpora Kabupaten Semarang.

Perbaikan ini menyelesaikan 12 permasalahan (BUG-001 hingga BUG-012) yang ditemukan dalam audit runtime exploratory tanpa merusak satu pun kontrak API JSON atau alur bisnis yang sudah berjalan. Seluruh 97 pengujian otomatis sebelumnya tetap berstatus **PASS**, dan ditambahkan 12 regression test baru sehingga total pengujian kini mencapai **109 tests (742 assertions) PASS (100%)**.

---

## 2. Issue Register & Remediation Matrix

| Issue ID | Priority | Description & Root Cause | Implementation Resolution | API Contract Tested | Browser HTTP Tested | Regression Test File | Status |
|---|:---:|---|---|:---:|:---:|---|:---:|
| **BUG-001** | P0 | Unauthenticated user visiting protected route caused `Route [login] not defined` (HTTP 500). | Route login diberi nama `->name('login')`, `redirectGuestsTo('/login')` diaktifkan di `bootstrap/app.php`, dan `AuthenticationException` mengarahkan guest ke `/login`. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug001_*` | **VERIFIED** |
| **BUG-002** | P0/P1 | Login failure gave no error message in browser form. | Menambahkan alert `$errors` dan flash messages di `resources/views/auth/login.blade.php`, mempertahankan `old('username')`, password dikosongkan. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug002_*` | **VERIFIED** |
| **BUG-003** | P1 | Login rate limit returned raw JSON 429 to browser. | `AuthenticatedSessionController@store` memeriksa `! $request->expectsJson()` lalu mengembalikan redirect back dengan validation errors dan menit lockout. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug003_*` | **VERIFIED** |
| **BUG-004** | P1 | Portal `/piagam/ambil` dead skeleton tanpa form lengkap, tombol submit, dan canvas. | Membangun komponen reusable `<x-piagam.claim-form>` lengkap dengan 4-step workflow (sekolah, piagam, identitas, signature canvas pad, submit fetch), dipakai di `/piagam/ambil` dan `welcome.blade.php`. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug004_*`, `MobileFirstUiContractTest` | **VERIFIED** |
| **BUG-005** | P1 | Attachment preview returned raw JSON 404/403 for missing file or cross-division. | `AttachmentController@preview` memeriksa browser navigation via headers/HTML accept, mengembalikan HTTP 404/403 Blade view ramah pengguna; API clients tetap menerima JSON `ERR_404_ATTACHMENT` / `ERR_403_DIVISI`. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug005_*`, `AttachmentStorageContractTest` | **VERIFIED** |
| **BUG-006** | P1 | Outgoing letter edit duplicate number returned raw JSON 409 to browser. | `OutgoingLetterController@update` dan `duplicateNumberResponse` memeriksa `! $request->expectsJson()` lalu mengembalikan redirect back dengan validation errors pada field `nomor_surat`. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug006_*` | **VERIFIED** |
| **BUG-007** | P1 | Deactivated account mid-session returned raw JSON 401 to browser. | `EnsureActiveAccount` memeriksa `! $request->expectsJson()`, melakukan logout session dan mengarahkan browser ke `/login` dengan flash error. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug007_*` | **VERIFIED** |
| **BUG-008** | P2 | Operator could see and open Edit Surat URL despite backend 403 mutation denial. | Tombol "Edit Surat" di `incoming/show.blade.php` dan `outgoing/show.blade.php` dibungkus otorisasi role admin (`super_admin`, `division_admin`). Route GET `/edit` menambahkan server-side role check `abort(403)`. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug008_*`, `FullClientUiWorkflowTest` | **VERIFIED** |
| **BUG-009** | P2 | Master user management lacked Edit modal/button in UI. | Menambahkan tombol Edit di tabel, modal interaktif Edit Pengguna di `master/users/index.blade.php`, script `openEditUserModal()` yang mengirim `PUT /api/v1/users/{id}`. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug009_*` | **VERIFIED** |
| **BUG-010** | P2 | Master school management lacked Edit modal/button in UI. | Menambahkan tombol Edit di tabel, modal interaktif Edit Sekolah di `master/schools/index.blade.php`, script `openEditSchoolModal()` yang mengirim `PUT /api/v1/schools/{id}`. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug010_*` | **VERIFIED** |
| **BUG-011** | P2 | Missing attachment remediation feature had no UI on letter detail. | Menambahkan form unggah berkas pengganti (remediasi) di `incoming/show.blade.php` untuk admin jika lampiran missing, dan `remediateAttachment` controller mendukung browser redirect. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug011_*` | **VERIFIED** |
| **BUG-012** | P3 | Double-nested storage paths (`documents/documents/` dan `signatures/signatures/`). | Menerapkan compatibility path fallback di `AttachmentController@preview` untuk mencari file baik dengan prefix lengkap maupun tanpa prefix berulang tanpa memindahkan atau merusak file fisik yang ada. | Yes | Yes | `RuntimeRemediationRegressionTest::test_bug012_*` | **VERIFIED** |

---

## 3. Files Modified & Created

### Modified Core Files
- `routes/web.php`:
  - Menamai route login: `->name('login')`.
  - Menamai route password activate: `->name('password.activate')`.
  - Menamai route ambil piagam: `->name('piagam.ambil')` dan menyuplai data `$schools`.
  - Menambahkan validasi role admin pada route `GET /surat-masuk/{incomingLetter}/edit` dan `GET /surat-keluar/{outgoingLetter}/edit`.
- `bootstrap/app.php`:
  - Menambahkan konfigurasi middleware guest redirect: `$middleware->redirectGuestsTo('/login')`.
  - Memperbaiki `AuthenticationException` agar browser diarahkan ke `/login` via `redirect()->guest('/login')`.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`:
  - Menambahkan browser error redirect pada saat rate limit login terlampaui (`back()->withInput()->withErrors(...)`).
- `app/Http/Middleware/EnsureActiveAccount.php`:
  - Menambahkan browser redirect ke `/login` dengan pesan flash error saat akun/divisi dinonaktifkan di tengah sesi aktif.
- `app/Http/Controllers/Api/OutgoingLetterController.php`:
  - `duplicateNumberResponse`: mendukung browser redirect back dengan errors `nomor_surat`.
  - `forbiddenDivisionResponse`: mendukung browser `abort(403)`.
- `app/Http/Controllers/Api/IncomingLetterController.php`:
  - `forbiddenDivisionResponse`: mendukung browser `abort(403)`.
  - `remediateAttachment`: mendukung browser redirect back dengan flash success.
- `app/Http/Controllers/Api/AttachmentController.php`:
  - Menambahkan null safety `$user === null`.
  - Menambahkan deteksi navigasi browser `isBrowserNavigation()` untuk merender HTML view 403 / 404 tanpa merusak streaming binary maupun kontrak API JSON.
  - Menambahkan fallback kompatibilitas path file storage (BUG-012).
- `resources/views/auth/login.blade.php`:
  - Menambahkan alert pesan kesalahan validasi, session error, dan session success.
- `resources/views/letters/incoming/show.blade.php`:
  - Membatasi tombol "Edit Surat" hanya untuk `super_admin` dan `division_admin`.
  - Menambahkan form UI Remediasi Berkas Lampiran untuk lampiran berstatus missing.
- `resources/views/letters/outgoing/show.blade.php`:
  - Membatasi tombol "Edit Surat" hanya untuk `super_admin` dan `division_admin`.
- `resources/views/master/users/index.blade.php`:
  - Menambahkan tombol Edit di tabel pengguna dan modal interaktif Edit Pengguna.
- `resources/views/master/schools/index.blade.php`:
  - Menambahkan tombol Edit di tabel sekolah dan modal interaktif Edit Data Sekolah.
- `resources/views/master/divisions/index.blade.php`:
  - Menambahkan tombol Edit di tabel divisi dan modal interaktif Edit Divisi.
- `resources/views/welcome.blade.php`:
  - Menyederhanakan landing page dengan menggunakan komponen `<x-piagam.claim-form>` dan menambahkan tombol CTA ke `/piagam/ambil`.
- `resources/views/piagam/claim.blade.php`:
  - Merender portal publik mandiri lengkap dengan komponen `<x-piagam.claim-form>`.
- `tests/Feature/UI/FullClientUiWorkflowTest.php`:
  - Memperbarui pengujian otorisasi edit surat masuk dan surat keluar sesuai aturan matriks RBAC normatif.

### Newly Created Files
- `resources/views/components/piagam/claim-form.blade.php`: Komponen mandiri form pengambilan piagam 4-step lengkap dengan touch/mouse signature canvas, autocomplete/dropdown sekolah, dan async fetch piagam.
- `resources/views/errors/403.blade.php`: Halaman error 403 ramah pengguna dengan branding SISURAT.
- `resources/views/errors/404.blade.php`: Halaman error 404 ramah pengguna.
- `resources/views/errors/419.blade.php`: Halaman error 419 Page Expired ramah pengguna.
- `resources/views/errors/429.blade.php`: Halaman error 429 Too Many Requests ramah pengguna.
- `resources/views/errors/500.blade.php`: Halaman error 500 Internal Server Error yang aman tanpa membocorkan stack trace.
- `tests/Feature/UI/RuntimeRemediationRegressionTest.php`: Automated regression test suite khusus untuk BUG-001 hingga BUG-012.

---

## 4. Test Suite Execution & Acceptance Results

```text
Test Suite Summary:
-------------------
Baseline Tests:          97 passed
Regression Tests Added:  12 passed
Total Tests Executed:   109 passed
Total Assertions:       742 assertions
Duration:               4.85 seconds
Failures:               0
Errors:                 0
Pint Code Style:        Passed (0 violations)
Vite Production Build:  Success (649ms)
```

Semua 12 bug dari temuan audit runtime exploratory telah diperbaiki di akar masalah arsitekturalnya, lulus verifikasi otomatis, dan mempertahankan integritas data serta isolasi divisi.
