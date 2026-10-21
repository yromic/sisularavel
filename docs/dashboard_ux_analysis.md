# Analisis End-to-End Dashboard SISURAT (Read-Only)

Sumber: [dashboard.blade.php](file:///f:/Project/sisularavel/resources/views/dashboard.blade.php), route `/dashboard` di [web.php](file:///f:/Project/sisularavel/routes/web.php#L112-L179), [app.blade.php](file:///f:/Project/sisularavel/resources/views/components/layouts/app.blade.php), [DashboardController.php](file:///f:/Project/sisularavel/app/Http/Controllers/Api/DashboardController.php). Tidak ada file yang diubah.

## 1. Alur End-to-End

`GET /dashboard` → closure di `web.php` (hitung total, tren, matriks, audit) → `dashboard.blade.php` → layout (sidebar + topbar + query Division lagi).

## 2. Mengapa Dashboard Terasa Padat

| # | Blok | Isi | Super Admin | Admin Divisi | Operator |
|---|------|-----|:-:|:-:|:-:|
| 1 | Page header + 3 tombol aksi | Tambah Masuk/Keluar/Piagam | ✅ | ✅ | ✅ |
| 2 | 4 kartu navigasi cepat | Persuratan, Masuk, Keluar, Pencarian | ✅ | ✅ | ✅ |
| 3 | 4 kartu metrik | Total, Masuk, Keluar, Piagam + % + link | ✅ | ✅ | ✅ |
| 4 | Matriks lintas divisi | Tabel/kartu per divisi | ✅ | ❌ | ❌ |
| 5 | Tren 6 bulan | 6 bar | ✅ | ✅ | ❌ |
| 6 | Surat masuk terkini | 5 baris | ✅ | ✅ | ✅ |
| 7 | Surat keluar terkini | 5 baris | ✅ | ✅ | ✅ |
| 8 | Aktivitas sistem (audit log) | 8 baris | ✅ | ✅ | ✅ |

Total: **8 blok** (Super Admin), 7 (Admin Divisi), 6 (Operator). Penyebab utama:

1. **Duplikasi informasi 3–4 kali.**
   - Jumlah Surat Masuk muncul di kartu navigasi ("N Arsip"), kartu metrik, dan tabel matriks.
   - Tujuan `/persuratan?tab=masuk` ada di: kartu navigasi, link "Buka arsip", link "Lihat semua", dan sidebar.
2. **Kartu navigasi (blok 2) menduplikasi sidebar** (Persuratan dan Pencarian sudah ada di sidebar, bahkan dengan badge "Utama"/"Terpadu").
3. **Satu template untuk semua role**; role hanya menyembunyikan blok, tidak mengubah tujuan halaman.
4. **Tidak ada hierarki prioritas.** Semua kartu berbobot visual sama. Tidak ada yang menjawab "apa yang harus saya lakukan sekarang?".
5. **Metrik bersifat arsip/statis**, bukan operasional. Lebih mirip laporan daripada pusat kerja.
6. **Audit log tampil ke semua role** dengan kode teknis (`event_code`), noise bagi operator.
7. **Teks sangat kecil** (10–11px) dan 5+ warna (teal, emerald, blue, amber, rose) tanpa titik fokus.

## 3. Analisis Per Role

**Super Admin (konteks Global)** — paling padat. Kebutuhan: volume antar divisi dan kesehatan sistem (backup). Masalah: matriks tidak bisa diurutkan, nama divisi bukan tautan, tidak ada status backup. Dropdown divisi di topbar submit otomatis (`onchange`) tanpa konfirmasi/indikator loading.

**Super Admin (konteks 1 divisi)** — tampilan sama dengan Admin Divisi, tetapi matriks lintas divisi tetap muncul sehingga pilihan konteks terasa tidak konsisten.

**Admin Divisi** — butuh beban kerja divisi, kualitas data, pintasan master data. Dashboard hanya memberi angka total dan tren.

**Operator** — butuh input cepat dan cari surat. Mendapat 6 blok, ±4 di antaranya tidak membantu (kartu navigasi, persentase, dua daftar terkini, audit log). Tombol input tertimbun di header.

## 4. Temuan Teknis yang Berdampak UX

| Temuan | Dampak |
|---|---|
| Tren memakai `->get()->groupBy()` (memuat semua model ke memori), [web.php](file:///f:/Project/sisularavel/routes/web.php#L139-L148) | Lambat seiring data tumbuh; mengganggu baseline performa |
| 8+ query per request, tanpa cache | Waktu muat naik di shared hosting |
| Layout menjalankan `Division::find` dan `Division::where()->get()` di setiap halaman | Query berulang di semua halaman |
| Logika dashboard ada di closure `web.php`; `Api/DashboardController` punya logika berbeda (all-time, bukan 6 bulan) | Dua sumber kebenaran, bisa tidak konsisten |
| Lebar bar tren relatif terhadap total bulan itu sendiri (`$max = incoming + outgoing`) | **Bar tidak bisa dibandingkan antar bulan**; menyesatkan (dekat dengan "fake percentage chart", AGENTS.md #21) |
| Persentase "% total" per jenis | Nilai informasi rendah, memakan ruang |

## 5. Heuristic Evaluation

Skala: 🔴 buruk · 🟡 perlu perbaikan · 🟢 baik

| # | Heuristik | Nilai | Temuan |
|---|-----------|:-:|--------|
| 1 | Visibility of System Status | 🟡 | Ada badge divisi/peran dan toast. Tidak ada info "data per kapan", indikator saat ganti divisi, status backup, atau penanda bulan berjalan. |
| 2 | Match System & Real World | 🟡 | Istilah campur: "Rekap Arsip", "Arsip", "Register", "Workspace", "Konteks Global", "Audit Log". Peran mentah (`super_admin`) di log. `SM`/`SK` tidak dijelaskan. Bulan Inggris (`Oct 2026`) dari `format('M Y')`. `event_code` adalah bahasa sistem. |
| 3 | User Control & Freedom | 🟡 | Ganti divisi langsung submit tanpa konfirmasi/undo. Dashboard tidak bisa disesuaikan (blok, rentang periode). |
| 4 | Consistency & Standards | 🟡 | Format tanggal campur (`d/m/Y`, `M Y`, relatif). Tombol "Catat Piagam" vs "Surat Masuk". Tautan ke tujuan sama bertuliskan beda ("Buka arsip", "Buka log", "Lihat Register", "Lihat semua"). Dua gaya kartu mirip tapi fungsi beda. |
| 5 | Error Prevention | 🟡 | Dropdown divisi mudah salah klik (auto-submit) padahal mengubah seluruh data tampil. Tombol "+ Surat Masuk" tidak jelas bahwa ini membuat data baru. |
| 6 | Recognition rather than Recall | 🟡 | Aksi cepat tampil (baik), tetapi angka tidak bisa jadi filter, tidak ada "yang saya input terakhir", tidak ada definisi metrik. |
| 7 | Flexibility & Efficiency | 🔴 | Tidak ada pintasan keyboard, pencarian global, personalisasi, atau filter periode. Pemula dan ahli melihat layar sama. |
| 8 | Aesthetic & Minimalist | 🔴 | Duplikasi 3–4×, 5+ warna, 8 blok setara bobot, teks 10–11px, audit log untuk semua role. |
| 9 | Recognize & Recover from Errors | 🟡 | Ada alert sukses/gagal global dan halaman 403/404/500. Empty state hanya teks tanpa tindakan lanjut; tidak ada penanganan bila satu blok gagal dimuat. |
| 10 | Help & Documentation | 🔴 | Tidak ada tautan bantuan, tooltip definisi metrik, atau onboarding untuk staf non-teknis. |

Aksesibilitas: legenda tren hanya dibedakan warna; detail via `title` hover (tidak ramah sentuh); kontras `#657184` pada 10–11px perlu diuji.

## 6. Rekomendasi ke Depan

> [!NOTE]
> Ini usulan UX/arsitektur, bukan aturan bisnis `VERIFIED`. Item yang butuh status atau alur baru (mis. tindak lanjut, tenggat) harus dicatat di `docs/24-DECISION-LOG.md` dan `docs/25-OPEN-QUESTIONS.md` sebelum dibangun.

### A. Kurangi kepadatan (tanpa data baru)
1. **Hapus 4 kartu navigasi**; sidebar sudah menjalankan fungsinya. Jadikan 4 kartu metrik *clickable*.
2. **Hapus persentase** dan link ulang "Buka arsip →".
3. **Gabung daftar Masuk/Keluar terkini** menjadi satu daftar bertab.
4. **Audit log**: hanya Super Admin/Admin Divisi, dengan kalimat manusia ("Budi menambah surat masuk No. 12"). Operator: "Aktivitas saya".
5. **Matriks divisi**: top 5 + "Lihat semua"; nama divisi jadi tautan ganti konteks.
6. **Teks minimum 12px**, palet 1 aksen + netral; merah/kuning hanya untuk peringatan.

### B. Dashboard berbasis role

| Role | Fokus | Susunan |
|---|---|---|
| Operator | Kerjakan | Pencarian besar → 2–3 tombol input → "Terakhir saya input" → surat terbaru divisi |
| Admin Divisi | Pantau divisi | 4 metrik + banding bulan lalu → tren → data perlu ditinjau (mis. lampiran `is_missing`) → pintasan Master Data |
| Super Admin | Pantau dinas & sistem | Status backup → matriks top N → tren dinas → aktivitas penting |

### C. Data & grafik
- Satu skala bersama untuk bar tren, tampilkan angka, tandai bulan berjalan.
- Tambah label selain warna (pola/teks) dan "Diperbarui: dd/mm/yyyy HH:mm".
- Agregasi SQL `GROUP BY` + cache singkat (±60 detik), aman untuk shared hosting.
- Pindahkan logika ke satu service/controller yang dipakai web dan API.

### D. Per heuristik
| Heuristik | Aksi |
|---|---|
| 1 Status | Indikator memuat saat ganti divisi, timestamp data, status backup |
| 2 Bahasa | Istilah baku ("Surat Masuk", "Surat Keluar", "Piagam"); hindari "Register/Workspace/Konteks"; bulan Indonesia |
| 3 Kontrol | Toast "Beralih ke divisi X · Batalkan" |
| 4 Konsistensi | Satu format tanggal, satu pola tombol "+ Tambah …", satu teks tautan "Lihat semua" |
| 5 Pencegahan | Konfirmasi ringan pada ganti divisi; sorot jelas bila bukan divisi sendiri |
| 6 Recognition | Kartu metrik jadi filter; pencarian dengan saran terbaru |
| 7 Efisiensi | Pintasan `/` (cari) dan `n` (surat baru); bantuan bisa disembunyikan oleh ahli |
| 8 Minimalis | Maks 4–5 blok per role |
| 9 Error | Empty state ber-CTA; blok gagal-muat dengan "Coba lagi" |
| 10 Bantuan | Ikon `?` pada metrik, tautan "Panduan singkat", tur login pertama |

### E. Prioritas

| Prioritas | Item | Upaya |
|---|---|---|
| P0 | Hapus duplikasi (kartu navigasi, %, link ganda); audit log manusiawi dan disembunyikan dari operator | Rendah |
| P0 | Perbaiki skala bar tren; agregasi ke SQL | Rendah–Sedang |
| P1 | Dashboard per role; status backup Super Admin | Sedang |
| P1 | Empty state, bantuan metrik, bulan Indonesia | Rendah |
| P2 | Pintasan keyboard, personalisasi, onboarding | Sedang |
| P3 | Antrean tindak lanjut/tenggat (butuh keputusan stakeholder) | Perlu keputusan |

## 7. Ringkasan

Dashboard padat karena mencoba menjadi **menu navigasi + laporan + log audit** sekaligus, untuk tiga role dengan satu template, dan mengulang informasi yang sama. Perbaikan paling murah dan berdampak: hapus blok navigasi dan duplikasi, buat tampilan per role, dan jadikan angka sebagai pintu masuk (clickable).
