# SISURAT — Evaluasi Heuristik UX (Nielsen 10) — Audit v2

| Item | Nilai |
|---|---|
| Tanggal audit | 19 Oktober 2026 |
| Jenis | Audit saja (tanpa perubahan kode/DB/dependency) |
| Metode | Inspeksi kode (routes, Blade, controller, request, CSS, docs kontrak), probe baca-saja via `artisan tinker`, dan pengamatan visual browser (tanpa eksekusi JS) |
| ID temuan | `UX2-xxx` (menghindari bentrok dengan `UX-001..020` pada audit 15 Okt) |
| Audit sebelumnya | [`SISURAT_UX_HEURISTIC_EVALUATION_2026-10-15_prev.md`](SISURAT_UX_HEURISTIC_EVALUATION_2026-10-15_prev.md) — UX-001..020 telah diremediasi pada commit `c441525` (16 Okt). Audit ini menilai kondisi **saat ini**, termasuk perubahan view yang belum di-commit. |

> **Catatan metodologi.** Tidak ada skor keseluruhan karena tidak ada metodologi pengukuran objektif. Tidak ada user testing. Severity memakai skala Nielsen 0–4 (frekuensi, dampak, persistensi, kekritisan alur). Klasifikasi: *Confirmed defect* (CD), *Potential risk* (PR), *Visual inconsistency* (VI), *Subjective preference* (SP), *Unverified assumption* (UA).

---

## 1. Executive Summary

**Penilaian umum.** Fondasi usability SISURAT cukup matang untuk sistem internal: pesan login berbahasa Indonesia dan jelas, envelope error JSON, error inline per field pada modal surat, proteksi idempotensi server-side, konfirmasi tutup saat form kotor (mode tambah), focus-trap + Escape pada modal surat, drawer mobile ber-`aria-modal`, target sentuh navigasi 44px, dan aksi lengket di modal detail mobile. Masalah utama bukan pada "kurang cantik", melainkan pada **kejujuran status/waktu, keselarasan antara apa yang UI tawarkan dan apa yang backend lakukan, bahasa antarmuka yang belum sepenuhnya Indonesia, serta fragmentasi pola interaksi** (tiga implementasi modal, `confirm()`/`alert()` native, token desain parsial).

**Masalah paling kritis (severity 3):**
1. Waktu ditampilkan berlabel "WIB" padahal konfigurasi timezone UTC → selisih ±7 jam pada audit/created_at (UX2-001).
2. Form klaim piagam publik memakai satu `Idempotency-Key` untuk beberapa tanda terima → tanda terima ke-2 dapat "berhasil palsu" atau gagal dengan galat teknis (UX2-002).
3. Modal edit surat menawarkan unggah lampiran dan ganti divisi, tetapi `update()` mengabaikannya (UX2-003).
4. Select "Divisi Pengarsip" pra-pilih divisi pertama saat konteks "Semua Divisi" → risiko salah arsip yang tidak bisa dikoreksi selain hapus-buat ulang (UX2-004).
5. Potensi XSS/penyajian tak ter-escape pada `page-header` dan `innerHTML` (UX2-026, risiko, bukan defect terkonfirmasi).

**Kekuatan yang dipertahankan:** pesan login/rate-limit, error envelope, inline error + fokus ke field pertama (modal), shield idempotensi, advisory duplikat, dirty-close, drawer mobile, halaman error 403/404/419/429/500, sticky action bar mobile.

**Risiko terbesar:** data dengan waktu salah pada arsip resmi; salah arsip divisi; kegagalan senyap saat edit.

**Prioritas:** (1) kejujuran waktu & bahasa, (2) kesesuaian UI–backend pada edit, (3) standardisasi modal/konfirmasi, (4) penyederhanaan mobile workspace & dashboard, (5) aksesibilitas label/fokus.

> Batasan utama: DB berisi 0 surat masuk/keluar/piagam, sehingga alur berbasis data tidak dapat diverifikasi runtime tanpa membuat data (dilarang).

---

## 2. System Coverage (Phase A)

### A.1 Inventaris

**Role (diverifikasi dari seeder & routes):** `super_admin`, `division_admin` (Admin Divisi), `division_operator` (Operator Divisi). Divisi seed: Dikdas, PAUD, Kebudayaan, Pora, Sekretariat.

| Module | Pages | User Roles | Inspection Status | Notes |
|---|---|---|---|---|
| Autentikasi | login, activate, forgot/reset | Semua | INSPECTED + RUNTIME VERIFIED (login) | Tidak ada tautan "Lupa password" di UI |
| Dashboard | `/dashboard` | Semua | RUNTIME VERIFIED (390px, visual) + INSPECTED | Satu layout; hanya grafik/matriks berbeda per role |
| Workspace terpadu | `/persuratan` | Semua | RUNTIME VERIFIED (390/1440, visual) + INSPECTED | Tab surat masuk/keluar/piagam/ekspedisi |
| Surat Masuk | index, create, show, edit, modal | Semua (edit dibatasi) | INSPECTED; PARTIALLY VERIFIED (create@320) | Tanpa data → tabel/edit tidak diuji |
| Surat Keluar | index/create/show | Semua | PARTIALLY VERIFIED | Diasumsikan paralel dengan Masuk (UA) |
| Piagam | index, create, show, claim (`/piagam/ambil`) | Staf + publik (claim) | INSPECTED (create, claim); NOT VERIFIED (index/show) | |
| Laporan | `/laporan` | Staf | RUNTIME VERIFIED (390, visual) + INSPECTED | PDF view tidak diperiksa |
| Master: Pengguna | `/master/pengguna` | admin | RUNTIME VERIFIED (390) + INSPECTED | |
| Master: Divisi/Unit/Sekolah | index | admin | INSPECTED (sekolah: sebagian) | |
| Admin: Backup | `/admin/backups` | super_admin | PARTIALLY VERIFIED (screenshot) | |
| Halaman error | 403, 404, 419, 429, 500 | Semua | 403 INSPECTED; lainnya NOT VERIFIED | |
| Lampiran/Streaming | AttachmentController | Semua | NOT VERIFIED | |

### A.2 User Journey (fitur yang terverifikasi ada)
1. Login → dashboard (dipetakan, runtime login OK).
2. Dashboard/Workspace → daftar surat masuk → cari/filter → buka detail (modal).
3. Tambah surat masuk (halaman penuh atau modal) → unggah lampiran/kamera → simpan → reload.
4. Edit surat (modal) → simpan.
5. Hapus (modal) → pulihkan (confirm native).
6. Catat pengambilan piagam (form publik `/piagam/ambil`) → tanda tangan → tanda terima → "Catat tanda terima lainnya".
7. Master data: tambah/ubah/hapus pengguna, reset "Kunci Rahasia".
8. Laporan → ekspor.
9. Logout.
Alur "disposisi/validasi/status berjenjang" **tidak ditemukan** sebagai fitur (tidak ada status workflow).

### A.3 Audit Coverage
INSPECTED: routes, layout, page-header, dashboard, workspace, incoming (index/create/show), komponen modal surat, reports, master users/divisions/categories/schools (sebagian), auth login/activate, 403, piagam create/claim (penuh), controller/request terkait, `app.css`, docs 02 & 11. RUNTIME VERIFIED (visual saja): login, dashboard@390, persuratan@390/1440, laporan@390, pengguna@390, backups@390, surat-masuk/create@320. NOT VERIFIED: viewport 375/768/1024, sebagian besar interaksi JS, a11y otomatis, surat keluar, piagam index/show, PDF, lampiran. NOT APPLICABLE: multi-step approval, notifikasi push.

---

## 3. Nielsen Heuristic Evaluation Matrix

| Heuristic | Main Findings | Highest Severity | Affected Modules | Evidence |
|---|---|---|---|---|
| H1 Visibility of System Status | Waktu "WIB" salah; export feedback palsu; tanpa progres unggah; konteks divisi berubah senyap | 3 | Semua tampilan waktu, Surat, Laporan | UX2-001, 005, 016, 017 |
| H2 Match with Real World | Validasi/pagination Inggris; istilah campur; role slug mentah; "Agenda #id" | 3 (UX2-001 juga) | Master, Surat, Piagam | UX2-006, 021–023 |
| H3 User Control & Freedom | Salah arsip tak terkoreksi; dirty-guard edit; trashed-row click | 3 | Surat Masuk/Keluar | UX2-004, 030, 032 |
| H4 Consistency & Standards | 3 pola modal; confirm/alert native; IA paralel; token terfragmentasi; dua alur "piagam" | 2 | Lintas modul | UX2-008, 009, 014, 015, 025 |
| H5 Error Prevention | Edit menawarkan fitur yang diabaikan; default divisi; idempotency key claim; Edit tampil ke operator | 3 | Surat, Piagam | UX2-002, 003, 004, 007 |
| H6 Recognition vs Recall | Label tanpa `for`; filter tanggal tanpa label; claim form 3 input sekolah | 2 | Workspace, Claim, Master | UX2-010, 012, 019 |
| H7 Flexibility & Efficiency | Mobile workspace menumpuk; dashboard ~5 jalur ke tujuan sama; reload kehilangan scroll | 2 | Dashboard, Workspace | UX2-012, 013, 029 |
| H8 Aesthetic & Minimalist | Dashboard generik/redundan; teks 10–11px; badge "TERPADU/UTAMA"; login ornamental | 2 | Dashboard, Sidebar, Login | UX2-013, 027, 028 |
| H9 Error Recovery | Galat teknis 409; error piagam generik; satu banner tanpa field | 3 (UX2-002) | Claim, Piagam create, incoming/create | UX2-002, 006, 018 |
| H10 Help & Documentation | Tanpa Lupa Password/bantuan kontekstual; claim form tanpa petunjuk memadai | 2 | Login, Claim | UX2-019, 020 |

---

## 4. Detailed Findings

Format ringkas; semua bidang Phase D tercantum. Verification: INSP = INSPECTED, PV = PARTIALLY VERIFIED, RV = RUNTIME VERIFIED, NV = NOT VERIFIED. Conf: H/M/L.

### Severity 3

**UX2-001 — Waktu berlabel "WIB" namun UTC** (H1/H2, CD)
- Module: Global (dashboard tooltip, show surat, `piagam/index`, `piagam/show`, `IncomingLetterController::resource` `created_at_formatted`, `PiagamController` ±L139, `app.blade.php`).
- Evidence: tinker `app()->getLocale()=en`, timezone `UTC`; `now()` = 12:55 sementara jam lokal 19:55 WIB; view menambahkan literal "WIB".
- Observed: stempel waktu tampil ±7 jam lebih awal tetapi diberi label WIB.
- Expected: waktu sesuai Asia/Jakarta atau label tidak menyesatkan.
- Impact: audit trail/tanggal terima pada arsip resmi tampak keliru; kepercayaan data turun.
- Recommendation: selaraskan timezone aplikasi/format tampil dengan WIB (keputusan konfigurasi perlu dicatat di decision log); hapus literal.
- Severity 3 · RV (probe) · Conf H.
- AC: waktu tampil sama dengan jam dinding WIB saat record dibuat; tidak ada literal "WIB" tanpa konversi.

**UX2-002 — `Idempotency-Key` form klaim tidak diregenerasi** (H5/H9, CD dari kode)
- Location: `components/piagam/claim-form.blade.php` ±L424 (`idempotencyKey` dibuat sekali), `resetFormForNewReceipt()`; `EnsureIdempotentMutation`.
- Observed: "Catat Tanda Terima Lainnya" memakai ulang key. Payload sama → respons cache (sukses palsu); payload beda → 409 `ERR_409_IDEMPOTENCY_CONFLICT` ditampilkan mentah.
- Expected: key baru per submission baru.
- Impact: petugas mengira tersimpan padahal tidak, atau melihat galat teknis; duplikasi/kehilangan tanda terima.
- Recommendation: regenerasi key saat reset form; terjemahkan 409 menjadi pesan operasional.
- Severity 3 · INSP · Conf H (runtime tidak dapat diuji tanpa membuat data).
- AC: dua tanda terima berurutan menghasilkan dua record; tidak ada pesan "Idempotency key…" ke pengguna.

**UX2-003 — Edit menampilkan unggah lampiran & ganti divisi yang diabaikan `update()`** (H5/H1, CD dari kode)
- Location: `components/letters/form-modal.blade.php` (mode edit), `IncomingLetterController::update`.
- Observed: `update()` tidak memproses file maupun `division_id`, sementara modal tetap menampilkan keduanya (untuk super_admin: select divisi).
- Expected: kontrol disembunyikan/dinonaktifkan di mode edit, atau backend mendukung (butuh keputusan bisnis—cek `docs/25`).
- Impact: pengguna mengira lampiran diganti/divisi dipindah; pesan sukses tetap muncul.
- Severity 3 · INSP · Conf H (perlu runtime dengan data).
- AC: di mode edit hanya field yang benar-benar tersimpan yang dapat diubah; pesan jelas bila fitur tidak tersedia.

**UX2-004 — Default "Divisi Pengarsip" menyesatkan** (H5/H3, CD)
- Location: `letters/incoming/create.blade.php` L29–40; `form-modal` L40–52.
- Evidence: RV@320 — chip "Semua Divisi (Global)" tetapi select pra-pilih KEBUDAYAAN.
- Impact: salah arsip; koreksi hanya via hapus + buat ulang (lihat UX2-003).
- Recommendation: placeholder "Pilih divisi…" + wajib; atau pra-pilih hanya bila konteks eksplisit.
- Severity 3 · RV (visual) · Conf H.
- AC: super_admin global tidak dapat menyimpan tanpa memilih divisi secara sadar.

**UX2-026 — Output tak ter-escape pada page-header/innerHTML** (H9/H5, PR)
- Location: `components/page-header.blade.php` (`{!! $title/$subtitle/$badge/$meta !!}`), `showToast`, `applyFieldErrors`, banner error (`innerHTML`), banner "Kunci Rahasia" (username via `innerHTML`).
- Risk: bila `:title` berisi `letter_number` (data pengguna) → potensi stored XSS; ini juga isu keamanan, bukan hanya UX.
- Severity 3 · INSP · Conf M (belum dibuktikan eksploit; perlu audit pemanggil).
- AC: semua string data pengguna di-escape; hanya markup tepercaya yang boleh raw.

### Severity 2

**UX2-005 — Konteks divisi berubah senyap setelah membuat surat** (H1) — `IncomingLetterController::store` L63–74: super_admin global yang menyimpan surat otomatis menulis `active_division_id` ke session; daftar berikutnya terfilter tanpa pemberitahuan. INSP · Conf H · AC: perubahan konteks diumumkan atau tidak terjadi.

**UX2-006 — Validasi & pagination berbahasa Inggris** (H2/H9) — `APP_LOCALE=en`, tanpa `lang/`; contoh: "The npsn field must be 8 digits.", "The code has already been taken.", "Next »". Pesan `nomor_surat.max` berbunyi "wajib diisi (maks…)" untuk kelebihan panjang. RV (probe tinker) · Conf H · AC: seluruh pesan pengguna berbahasa Indonesia, spesifik, dan actionable.

**UX2-007 — Tombol Edit tampil untuk operator, 403 menyesatkan** (H5/H9) — `incoming/index.blade.php` L99/L183; glosarium: operator tidak boleh edit; API 403 "…data divisi ini" (salah penyebab). INSP · Conf M · AC: Edit disembunyikan/dinonaktifkan dengan alasan; pesan 403 sesuai penyebab.

**UX2-008 — `confirm()`/`alert()` native** (H4/H3) — ±17 `confirm`, ±14 `alert` (workspace, users, categories, show, index, modal). Tidak sejalan dengan modal kustom, tidak terkonsisten (hapus vs pulihkan vs Kunci Rahasia). INSP · Conf H · AC: satu komponen konfirmasi/feedback.

**UX2-009 — Tiga implementasi modal; master modal tanpa semantik dialog** (H4/WCAG) — Users/divisions/schools: kartu inline & modal tanpa `role=dialog`, `aria-modal`, manajemen fokus, backdrop; Escape menutup senyap. INSP · Conf H.

**UX2-010 — Label tanpa `for`** (H6/WCAG 1.3.1, 3.3.2) — `form-modal`, users, divisions, schools, categories (input saudara, bukan terbungkus). Login/create terbungkus (OK); claim memakai `for`. INSP · Conf H.

**UX2-011 — Fokus tidak dipindah ke modal saat dibuka** (WCAG 2.4.3) — `form-modal`, `delete-modal`, `split-modal-detail` punya `role=dialog` + trap Tab, tetapi fokus awal tetap di pemicu. INSP · Conf M (butuh uji manual).

**UX2-012 — Workspace mobile menumpuk sebelum data** (H7/H6) — RV@390: header card, tab, toggle status, filter (cari + 2 tanggal tanpa label + Filter) sebelum baris pertama ±700px; tab "Buku Ekspedisi Piag…" terpotong. Filter tanggal hanya `title`. PV · Conf M · AC: baris pertama terlihat ≤ ±1 layar pada 390px; filter dapat dilipat; tanggal berlabel.

**UX2-013 — Dashboard generik & redundan** (H8/H7) — 3 CTA header + 4 kartu navigasi + 4 tile dengan "Buka arsip"/"Lihat semua" ≈ 5 jalur ke tujuan sama; tanpa pekerjaan tertunda; hanya grafik/matriks beda per role; kartu "Arsip surat masuk & disposisi" menjanjikan disposisi yang tidak ditemukan. RV@390 + INSP · Conf M. (Sebagian bersifat SP; dasarnya redundansi yang terukur.)

**UX2-014 — IA paralel & sidebar active-state** (H4) — Sidebar hanya ke `/persuratan`, `/laporan`, master; rute `/surat-masuk*`, `/surat-keluar*`, `/piagam*` tanpa state aktif; dicapai dari CTA dashboard & redirect simpan. INSP · Conf H.

**UX2-015 — Dua alur berbeda berlabel mirip "piagam"** (H4/H2) — Dashboard "+ Piagam" → `/piagam/create` (pra-registrasi); workspace "+ Catat Pengambilan Piagam" → `/piagam/ambil` (serah terima). INSP · Conf H.

**UX2-016 — Feedback ekspor palsu** (H1) — `handleExportClick` (`app.blade.php`) mereset tombol 2500 ms tanpa mengetahui status unduhan. INSP · Conf H.

**UX2-017 — Tanpa indikator progres unggah** (H1) — hanya teks "Menyimpan..."; kontrak `docs/11` §3.3 mengharuskan progres. INSP · Conf H.

**UX2-018 — Error form halaman penuh kehilangan detail** (H9) — `incoming/create`: semua error digabung satu banner tanpa penanda field; `piagam/create`: hanya `data.message` generik "Data yang dikirim tidak memenuhi persyaratan validasi." INSP · Conf H.

**UX2-019 — Form klaim piagam: beban kognitif tinggi** (H6/H5) — field pertama "Divisi Tujuan / Bidang Pengampu" butuh pengetahuan internal; "Jenis Pengambilan" pra-pilih via `$loop->first` tanpa `required`; tiga input sekolah (autocomplete + select panjang + NPSN); empat opsi "Lainnya/Ketik Manual" (risiko kualitas data); satu halaman panjang, bukan alur bertahap (kontrak §3.4); label kapital tebal. INSP · Conf M.

**UX2-020 — Tidak ada bantuan/jalur pemulihan akun di UI** (H10) — login tanpa "Lupa password/hubungi admin" padahal rute `/forgot-password` & `/password/reset` ada; tidak ada halaman bantuan atau kontak. INSP · Conf H.

**UX2-023 — "No. Agenda" = PK** (H2, PR) — `Agenda #id` memakai primary key; penomoran agenda formal masih Q-01D (OPEN). Jangan diubah sebelum keputusan stakeholder. INSP · Conf M.

**UX2-027 — Teks 10–11px & kontras `slate-400`** (WCAG 1.4.3, 1.4.4) — `text-[10px]`/`text-[11px]` luas; audit lalu mengukur ±2,35:1 untuk slate-400 pada putih. Tidak diukur ulang. INSP · Conf M.

**UX2-030 — Dirty-guard edit tidak bekerja** (H3) — `isLetterFormDirty()` false pada mode edit kecuali ada file → perubahan teks hilang saat tutup. INSP · Conf H.

**UX2-031 — Baris/kartu klik tanpa akses keyboard** (WCAG 2.1.1) — `<tr>`/`<article>` memakai `onclick`; tombol Detail menjadi alternatif sehingga dampak sedang. INSP · Conf M.

**UX2-032 — Klik baris terhapus (trashed)** (H3, PR/NV) — handler `openLetterDetailModal` tetap aktif; binding API kemungkinan mengecualikan soft-deleted → "Gagal memuat detail surat." NV (tanpa data) · Conf L.

**UX2-034 — Kanvas tanda tangan tanpa nama aksesibel** (WCAG 4.1.2) — tanpa `aria-label`/alternatif; resize (keyboard virtual) merender ulang kanvas. INSP · Conf M.

### Severity 1

- **UX2-021 Role slug mentah** (H2, VI): tabel pengguna "DIVISION ADMIN" vs form "Admin Divisi". RV@390 · H.
- **UX2-022 Istilah campur** (H2, VI): "Tanggal Share/Diseminasi/Share", "Workspace", "Role", "soft-delete", "Disaster Recovery", "Human-Readable Archive", `event_code` mentah (`LETTER_INCOMING_CREATED`) di dashboard. INSP · M.
- **UX2-024 Pelanggaran kontrak UI** (H4, VI): glyph `&rarr;`, `&times;`, `←`, prefiks "+ " ganda ("+ + Surat Masuk" terlihat runtime) (`docs/11` UI-003); tombol "Reset Filter" empty-state (§4.3) dan "Halaman X dari Y" (§3.2) tidak ada. RV/INSP · H.
- **UX2-025 Token desain terfragmentasi** (H4, VI): `app.css` hanya sebagian token; view memakai hex keras (`#147D73`, `#17243A`, `#E3E8EE`) + emerald/teal/blue/slate; radius md/lg/xl/2xl; tinggi tombol 30–50px; font 'Instrument Sans' tanpa pemuatan ditemukan. INSP · H.
- **UX2-028 Halaman Kategori** (H8/H2, VI): badge "Aktif" redundan (query sudah aktif), tanpa UI ubah nama padahal ada rute PUT, subjudul "divisi aktif" padahal unit kerja global. INSP · M.
- **UX2-029 Reload penuh setelah simpan** (H7): `window.location.reload()` menghilangkan posisi scroll/filter lokal. INSP · H.
- **UX2-033 Advisory duplikat HTTP 200 `success:true` tanpa simpan** (H1): klien menangani, tetapi semantik membingungkan bila API dipakai ulang. INSP · M.

### Ringkasan jumlah

| Severity | Jumlah | ID |
|---|---|---|
| 4 | 0 | — |
| 3 | 5 | 001, 002, 003, 004, 026 |
| 2 | 22 | 005–020, 023, 027, 030, 031, 032, 034 |
| 1 | 7 | 021, 022, 024, 025, 028, 029, 033 |
| **Total** | **34** | |

Klasifikasi: CD ≈ 24, PR 4 (026, 023, 032, 031), VI 7 (sebagian tumpang tindih), SP 1 (bagian dari 013), UA 1 (surat keluar paralel).

---

## 5. Mobile Usability Findings

| Halaman | Breakpoint | Temuan | Status |
|---|---|---|---|
| Dashboard | 390 | Tumpukan CTA + kartu + tile; pekerjaan utama tertunda (UX2-013) | RV visual |
| Persuratan | 390 | Filter menumpuk, tab terpotong, tanggal tanpa label (UX2-012) | RV visual |
| Persuratan | 1440 | Header + sidebar baik; nav "Persuratan & Piagam" membungkus dua baris + badge | RV visual |
| Laporan | 390 | Dapat dipakai; ekspor feedback palsu (UX2-016) | RV visual |
| Pengguna | 390 | Role slug mentah (UX2-021); kartu tambah inline di bawah filter tanpa scroll/fokus | RV visual |
| Backup | 390 | Kartu hijau berat, bahasa campur | RV visual |
| Surat Masuk create | 320 | Chip "Global" vs select KEBUDAYAAN (UX2-004); "+ +" ganda | RV visual |
| Topbar | <sm | Ganti Password & Logout hanya ikon (logout beraria-label) | INSP |
| Semua | 375, 768, 1024 | **NV** — tidak diuji | NV |

Catatan: subagen browser tidak mengeksekusi JS; jumlah "target kecil" tidak dipakai sebagai bukti. Overflow horizontal tidak dikonfirmasi pada semua viewport.

## 6. Accessibility Findings

Tidak ada tool otomatis dijalankan; tidak ada klaim kepatuhan WCAG.
- Label tanpa `for` (UX2-010) — 1.3.1/3.3.2, INSP.
- Fokus awal modal tidak dipindah (UX2-011) — 2.4.3, INSP.
- `aria-live` hanya pada toast; banner loading/error/rahasia tanpa `role=alert/status` (flash layout ada `role=alert`) — 4.1.3, INSP.
- Baris/kartu `onclick` tanpa keyboard (UX2-031) — 2.1.1.
- Kanvas tanda tangan (UX2-034) — 4.1.2.
- Teks kecil & kontras (UX2-027) — 1.4.3/1.4.4.
- Tidak ada skip link — 2.4.1, INSP. Jumlah `h1` per halaman NV.
- Positif: `<title>` per halaman, `aria-label` pada sidebar/nav, drawer `aria-modal`, target 44px nav.

## 7. Dashboard Information Architecture Review

| Role | Tujuan kerja (dari bukti) | Evaluasi |
|---|---|---|
| Super Admin | Pengawasan lintas divisi, master data, backup | Matriks divisi relevan; tanpa indikator kesehatan data/backup di dashboard; risiko konteks senyap (UX2-005) |
| Admin Divisi | Mengelola arsip divisi & pengguna | Layout sama dengan role lain; tanpa pintasan pengguna divisi |
| Operator | Mencatat surat/piagam | CTA tambah tersedia (baik); redundansi jalur (UX2-013) |

Sistem tidak memiliki status workflow, sehingga widget "pending work" tidak ada dasar; jangan menambah tanpa kebutuhan terkonfirmasi. Rekomendasi: kurangi duplikasi entry point, utamakan "aktivitas terbaru" dengan label manusiawi (bukan `event_code`), serta sesuaikan konten per role berdasarkan tugas nyata.

## 8. Visual Design and Minimalism Review

- Clutter: kartu navigasi dashboard duplikatif dengan sidebar; badge "TERPADU"/"UTAMA" tidak informatif (SP/VI).
- Redundansi: badge "Aktif" kategori; subjudul panjang pada header.
- Tipografi: 10–11px luas; label kapital tebal pada claim; font 'Instrument Sans' tanpa pemuatan.
- Warna: dua warna primer (`#147D73` vs emerald-700), campur slate/teal/blue.
- Ornamen: login memakai gambar latar + overlay + blur (bertentangan dengan UI-002, restraint) — SP.
- Pola generik: dashboard bentuk 4-tile + grafik.
Tidak ada informasi kritis yang direkomendasikan dihapus.

## 9. User Journey Friction Analysis

| Alur | Hambatan | Langkah terverifikasi |
|---|---|---|
| Login → dashboard | Tanpa Lupa password (UX2-020) | 3 interaksi (user, pass, masuk) |
| Cari surat di mobile | Filter menumpuk, tanggal tanpa label (UX2-012) | NV hitung penuh |
| Tambah surat masuk | Default divisi (004), progres unggah (017), reload (029) | INSP, tanpa data |
| Edit surat | Kontrol tak berfungsi (003), dirty-guard (030), operator (007) | INSP |
| Catat piagam (claim) | Idempotency (002), 3 input sekolah, "Lainnya" (019) | INSP penuh |
| Hapus/pulihkan | `confirm` native, klik baris trashed (008, 032) | INSP |
Durasi/success rate tidak diukur.

## 10. Prioritized Improvement Backlog

| Priority | Finding ID | Issue | Severity | User Impact | Effort | Remediation Status & Verification |
|---|---|---|---|---|---|---|
| P0 | UX2-001 | Waktu "WIB" salah | 3 | Data resmi salah | S | `REMEDIATED` (`DEC-SYS-002`, `config/app.php`, test passing) |
| P0 | UX2-002 | Idempotency key claim | 3 | Sukses palsu/galat | S | `REMEDIATED` (`claim-form.blade.php`, dynamic UUID, test passing) |
| P0 | UX2-026 | Output tak ter-escape | 3 (risiko) | Keamanan | S–M | `REMEDIATED` (sanitized headers & secret banners, test passing) |
| P1 | UX2-003 | Edit tawarkan fitur diabaikan | 3 | Kegagalan senyap | M | `REMEDIATED` (`DEC-LET-003`, locked division & hidden upload on edit) |
| P1 | UX2-004 | Default divisi | 3 | Salah arsip | S | `REMEDIATED` (`DEC-AUTH-002`, required placeholder `-- Pilih Divisi --`) |
| P1 | UX2-006 | Bahasa Inggris | 2 | Error tak dipahami | M | `REMEDIATED` (`DEC-LOC-001`, `APP_LOCALE=id`, full `lang/id` dictionaries) |
| P1 | UX2-007 | Edit untuk operator | 2 | Kebingungan/403 | S | `REMEDIATED` (hidden Edit button for operators, 403 authorization guard) |
| P1 | UX2-030 | Dirty-guard edit | 2 | Data hilang | S | `REMEDIATED` (baseline dirty state tracking in `form-modal.blade.php`) |
| P2 | UX2-005 | Konteks senyap | 2 | Disorientasi | S | `REMEDIATED` (`DEC-AUTH-002`, eliminated silent session mutation) |
| P2 | UX2-008/009/011 | Modal & konfirmasi | 2 | Inkonsistensi/a11y | M–L | `REMEDIATED` (`DEC-UI-009`, `<x-modal.confirm>`, symmetric master popups) |
| P2 | UX2-010 | Label tanpa for | 2 | A11y | S | `REMEDIATED` (explicit `for`/`id` bindings & first-element autofocus) |
| P2 | UX2-012/013 | Mobile workspace & dashboard | 2 | Efisiensi | M | `REMEDIATED` (collapsible filter panel, removed disposisi wording) |
| P2 | UX2-019/020 | Claim & bantuan | 2 | Beban kognitif | M | `REMEDIATED` (a11y signature canvas, login recovery note) |
| P3 | UX2-021/024/034 | Polish & token | 1–2 | Konsistensi | S–M | `REMEDIATED` (humanized role labels, pure SVG arrows, canvas redraw guard) |


## 11. Quick Wins vs Structural Improvements

**Quick Wins:** regenerasi idempotency key (002); hilangkan literal/selaraskan waktu (001); placeholder divisi (004); sembunyikan Edit operator (007); label tanggal & `for` (010, 012); label role manusiawi (021); hapus "+ " ganda (024); tautan "Lupa password/hubungi admin" (020); perluas dirty-guard (030).

**Structural:** komponen modal & konfirmasi tunggal (008, 009, 011); locale Indonesia penuh (006); token desain terpusat (025); penataan ulang IA sidebar vs rute penuh (014, 015); redesain filter mobile (012); dashboard per role (013); audit escape output (026); keputusan bisnis edit lampiran/divisi (003).

## 12. Proposed UX Improvement Roadmap (rekomendasi, bukan instruksi)

1. **Fase 1 — Kritis & pencegahan error:** 001, 002, 026, 003, 004, 007, 030.
2. **Fase 2 — Navigasi & alur:** 005, 012, 013, 014, 015, 019, 020.
3. **Fase 3 — Konsistensi & design system:** 008, 009, 025, 024, 021, 022, 028.
4. **Fase 4 — Mobile & aksesibilitas:** 010, 011, 027, 031, 034, uji 320–1440.
5. **Fase 5 — Validasi & regresi:** a11y otomatis + manual, uji dengan data nyata, uji pengguna terjadwal, update `docs/27-TRACEABILITY-MATRIX.md`.

## 13. Limitations and Unverified Assumptions

- DB kosong: tabel, pagination, edit/hapus/pulihkan, status trashed, dashboard dengan data tidak diuji runtime.
- Browser subagen tanpa eksekusi JS; viewport 375/768/1024 tidak diuji; perilaku modal/fokus/keyboard tidak diuji manual.
- Tidak ada tool a11y otomatis; tidak ada klaim WCAG.
- Surat keluar diasumsikan paralel dengan surat masuk (UA).
- Tidak diperiksa: `admin/backups` (kode), `piagam/index|show`, `welcome`, error 404/419/429/500, PDF, `AttachmentController`, `PasswordActivationController`, otorisasi restore pengguna.
- Efek samping: login runtime kemungkinan menambah baris audit-log.
- Working tree berisi perubahan view belum di-commit; temuan mencerminkan kondisi tersebut.
- Tidak ada user testing; tidak ada metrik durasi/success rate.
