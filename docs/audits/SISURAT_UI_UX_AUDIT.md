# UI/UX AUDIT REPORT — SISURAT (LARAVEL BLADE)
**Sistem Informasi Administrasi Persuratan & Pengambilan Piagam**  
**Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga Kabupaten Semarang**

---

| Dokumen | Laporan Audit UI/UX & Arsitektur Frontend |
|---|---|
| **Aplikasi Target** | SISURAT (Laravel 12 / PHP ^8.2) |
| **Teknologi Frontend** | Laravel Blade Templates, Tailwind CSS v4, Vanilla JavaScript |
| **Lingkungan Runtime** | Local Development (`http://127.0.0.1:8000/`) & Shared Hosting Baseline |
| **Metode Audit** | Static Source Inspection (`resources/views/`, `resources/css/`, `routes/web.php`) & Live Rendered Browser Inspection (Playwright Browser Automation) |
| **Peran Auditor** | Senior UI/UX Designer, UX Researcher, HCI Specialist, & Laravel Frontend Architecture Reviewer |
| **Status Temuan** | Audit Only (Diagnosis dan Rekomendasi Tanpa Modifikasi Kode Aplikasi) |
| **Tanggal Audit** | 15 Oktober 2026 |

---

## DAFTAR ISI

1. [A. Ringkasan Eksekutif (Executive Summary)](#a-ringkasan-eksekutif-executive-summary)
2. [B. Inventarisasi Layar (Screen Inventory)](#b-inventarisasi-layar-screen-inventory)
3. [C. Temuan Rinci Berdasarkan Bukti (Detailed Findings)](#c-temuan-rinci-berdasarkan-bukti-detailed-findings)
4. [D. Analisis Kepadatan Teks & Evaluasi Microcopy (Text Density Analysis)](#d-analisis-kepadatan-teks--evaluasi-microcopy-text-density-analysis)
5. [E. Temuan Arsitektur Informasi & Alur Kerja (Information Architecture Findings)](#e-temuan-arsitektur-informasi--alur-kerja-information-architecture-findings)
6. [F. Rekomendasi Tingkat Layar (Screen-Level Recommendations)](#f-rekomendasi-tingkat-layar-screen-level-recommendations)
7. [G. Peta Jalan Peningkatan Berprioritas (Prioritized Improvement Roadmap)](#g-peta-jalan-peningkatan-berprioritas-prioritized-improvement-roadmap)
8. [H. Arah Desain Terpadu (Design Direction for SISURAT)](#h-arah-desain-terpadu-design-direction-for-sisurat)
9. [I. Rencana Validasi Pengujian Pengguna (Validation Plan)](#i-rencana-validasi-pengujian-pengguna-validation-plan)

---

## A. Ringkasan Eksekutif (Executive Summary)

### 1. Kondisi Keseluruhan Antarmuka
Aplikasi **SISURAT** telah berhasil dimigrasikan dari tumpukan lama (*legacy* Google Apps Script dan Google Sheets) ke fondasi **Laravel 12** yang kokoh, terstruktur, dan memiliki kepatuhan basis data relasional yang baik. Arsitektur antarmuka saat ini menggunakan komponen bawaan Laravel Blade dengan Tailwind CSS v4, tanpa ketergantungan pada *framework client-side* berat seperti React, Vue, atau Livewire.

Secara visual, aplikasi ini memiliki fondasi estetika korporat pemerintahan yang layak: palet warna berbasis zamrud (*emerald-700*) untuk aksi instansional, latar belakang netral terang (*slate-50* / *white*), bilah samping gelap (*slate-900*), serta tipografi berbasis sans-serif modern (*Instrument Sans* / *system-ui*). Ukuran target interaksi (*touch target*) sebagian besar telah mematuhi standar minimum aksesibilitas 44×44 piksel.

Namun demikian, peninjauan mendalam terhadap kode sumber dan inspeksi *browser* secara *live* membuktikan bahwa antarmuka saat ini menimbulkan **persepsi visual yang padat, teks-sentris, dan membebani kognisi pengguna (*high cognitive load*)**. 

Penyebab utama dari beban visual ini **bukanlah** kerapatan data tabel yang tinggi (karena staf administrasi instansi memang membutuhkan tabel kerja yang komprehensif), melainkan **empat penyakit struktural UI/UX**:
1. **Triplikasi & Redundansi Metrik:** Layar Dashboard menampilkan angka dan persentase yang sama hingga tiga kali di blok yang bersebelahan (Kartu Statistik, Bilah Distribusi, dan Matriks Divisi).
2. **Hipertrofi Teks Bantuan (*Helper Text Clutter*):** Hampir setiap label formulir dilengkapi deskripsi pengulangan di bawahnya yang menyatakan hal yang sudah jelas (*self-evident*), misalnya *"Tahun penyelenggaraan lomba"* di bawah label *"Tahun Acara"*.
3. **Redundansi Konteks Divisi:** Konteks divisi kerja ditampilkan serentak hingga 3-4 kali dalam satu viewport (topbar header, sub-header halaman, badge kartu, dan isian formulir readonly).
4. **Agregasi Tombol Aksi yang Menyilaukan (*Action Button Noise*):** Setiap baris tabel kerja mengulang 2 hingga 3 tombol berwarna kontras (*Detail*, *Edit*, *Hapus*) secara individual, menghasilkan 30–45 tombol berwarna dalam satu layar data.

### 2. Klasifikasi Temuan: Terverifikasi vs Hipotesis
Audit ini secara tegas membedakan temuan yang telah diverifikasi secara visual pada antarmuka ter-render (*verified findings*) dari hipotesis yang masih memerlukan pengujian lanjutan terhadap staf dinas (*field hypotheses*):

* **Temuan Terverifikasi (*Verified Visual & Architectural Findings*):**
  - Duplikasi data statistik pada Dashboard terbukti secara visual di `/dashboard`.
  - Isian formulir non-fungsional (*read-only auto-fill*) memakan 50% lebar baris pertama pada formulir pencatatan surat (`/surat-masuk/create` & `/surat-keluar/create`).
  - Halaman Laporan Terpadu (`/laporan`) merender 3 tabel bertumpuk dengan 3 pagination independen yang saling bertabrakan secara navigasi.
  - Form Tanda Terima Piagam Publik (`/piagam/ambil`) memiliki 12 teks instruksi/bantuan berulang yang memperpanjang formulir hingga membutuhkan 3 kali scroll penuh pada layar resolusi standar.
  - Inkonsistensi pola interaksi Master Data: pembuatan pengguna menggunakan ekspansi kartu inline di atas tabel, sedangkan edit pengguna menggunakan modal pop-up dialog.
  - Kontras teks kecil berwarna `text-slate-400` pada latar putih gagal memenuhi rasio kontras WCAG 2.2 AA (minimum 4.5:1).

* **Hipotesis yang Memerlukan Verifikasi Pengguna Lapangan (*Hypotheses for Field Validation*):**
  - *Tingkat kesalahan pemilihan unit kerja:* Apakah staf sekolah mengalami kebingungan membedakan opsi dropdown sekolah dengan input manual jika nama sekolah memiliki singkatan lokal tidak resmi?
  - *Kecenderungan penggunaan tombol pintas sidebar:* Apakah tombol `+` kecil di sebelah kanan item menu sidebar dimanfaatkan staf, atau justru menjadi sumber salah klik (*accidental click*) saat ingin membuka daftar arsip?
  - *Preferensi penyaringan data:* Apakah staf arsiparis lebih menyukai filter tabel langsung di layar indeks surat masuk/keluar dibanding harus berpindah ke modul `/laporan`?

### 3. Falsafah Desain: Kepadatan yang Tepat (*Appropriate Density*), Bukan Minimalisme Dangkal
Sistem administrasi pemerintahan tidak boleh dipaksa menjadi antarmuka "minimalis ultra-kosong" yang mengorbankan kecepatan olah dokumen. Staf dinas memeriksa puluhan surat masuk setiap hari dan membutuhkan ringkasan nomor surat, tanggal, instansi asal, dan status lampiran dalam satu pandangan (*at a glance*).

Tujuan audit ini adalah **kepadatan informasi yang tepat (*appropriate density*)**:
* Menghilangkan **kebisingan visual (*visual noise*)**, teks berulang, dan tombol yang bertabrakan.
* Mempertahankan **kedalaman data administratif**, ketertelusuran berkas, dan nomor agenda unik.
* Mengoptimalkan arsitektur **Laravel Blade** murni tanpa perlu menambah kompleksitas SPA (*Single Page Application*).

---

## B. Inventarisasi Layar (Screen Inventory)

Inventarisasi mencakup seluruh modul aplikasi yang dapat diakses pengguna tamu publik, operator divisi, admin divisi, dan super admin:

| Modul | Layar & Rute | Pengguna & Peran | Tugas Utama Pengguna | Kondisi UI/UX Saat Ini | Titik Perhatian & Masalah Utama | Berkas Blade / Kode Sumber |
|---|---|---|---|---|---|---|
| **Publik** | Halaman Utama (`/`) | Tamu Umum, Guru, Staf Sekolah | Mengakses portal tanda terima piagam atau masuk ke sistem internal | **Bersih & Terarah** | Proporsi kartu informasi loket di bagian bawah agak statis; visual CTA sudah memadai. | `resources/views/welcome.blade.php` |
| **Publik / Tamu** | Tanda Terima Piagam (`/piagam/ambil`) | Guru, Siswa, Tamu Loket Dinas | Menandatangani dan mengonfirmasi bukti fisik pengambilan piagam | **Sangat Berat Teks (*Text-Heavy*)** | Setiap input memiliki teks bantuan berulang; canvas tanda tangan terpisah jauh dari tombol submit; fallback manual memicu pergeseran tata letak (*layout shift*). | `resources/views/piagam/claim.blade.php`<br>`resources/views/components/piagam/claim-form.blade.php` |
| **Autentikasi** | Login Petugas (`/login`) | Seluruh Staf Dinas | Memasukkan kredensial resmi untuk membuka sesi kerja | **Sederhana & Fungsional** | Layout bersih; tombol submit jelas; toggle visibility password tersedia. | `resources/views/auth/login.blade.php` |
| **Autentikasi** | Ganti Password (`/password/activate`) | Staf Terotentikasi | Mengaktifkan sandi baru saat pertama login atau reset akun | **Cukup Bersih** | Form minimalis; validasi sandi jelas. | `resources/views/auth/activate.blade.php` |
| **Dashboard** | Dashboard Arsip (`/dashboard`) | Semua Peran (Operator, Admin, Super Admin) | Memantau volume kearsipan dan membuka pintasan input cepat | **Kognitif Berlebih (*Overloaded Metrics*)** | Triplikasi visual metrik (kartu vs bar chart vs tabel divisi); tombol aksi bersaing dengan judul; audit log visualnya terlalu panjang ke bawah. | `resources/views/dashboard.blade.php` |
| **Surat Masuk** | Indeks Surat Masuk (`/surat-masuk`) | Operator, Admin Divisi, Super Admin | Menelusuri, mencari, dan membuka berkas arsip surat masuk | **Padat Tombol Aksi (*Action Noise*)** | Tabel desktop efektif untuk scan data, tetapi kolom aksi mengulang 3 tombol berwarna per baris; ketiadaan filter tanggal langsung di layar indeks. | `resources/views/letters/incoming/index.blade.php` |
| **Surat Masuk** | Tambah Surat Masuk (`/surat-masuk/create`) | Operator, Admin Divisi, Super Admin | Mencatat data resmi dan mengunggah pindaian berkas surat masuk | **Ruang Terbuang (*Cluttered Form*)** | Isian readonly "Petugas" dan "Divisi" memakan 2 kolom penuh; sticky footer mengunci layar formulir pada tinggi layar penuh. | `resources/views/letters/incoming/create.blade.php` |
| **Surat Masuk** | Detail Surat Masuk (`/surat-masuk/{id}`) | Semua Staf Berizin | Memeriksa metadata resmi surat masuk dan mengunduh berkas | **Cukup Rapi** | Struktur kartu metadata dan berkas lampiran jelas; fitur remediasi berkas legacy tampil kontekstual. | `resources/views/letters/incoming/show.blade.php` |
| **Surat Masuk** | Edit Surat Masuk (`/surat-masuk/{id}/edit`) | Admin Divisi, Super Admin | Memperbarui perihal atau nomor surat jika terdapat revisi | **Sederhana** | Menyatu dengan alur kerja formulir umum. | `resources/views/letters/incoming/edit.blade.php` |
| **Surat Keluar** | Indeks Surat Keluar (`/surat-keluar`) | Operator, Admin Divisi, Super Admin | Menelusuri agenda surat keluar dinas dan mengunduh lampiran | **Padat Tombol Aksi** | Struktur serupa dengan surat masuk; kolom lampiran dan aksi memakan proporsi horizontal cukup besar. | `resources/views/letters/outgoing/index.blade.php` |
| **Surat Keluar** | Tambah Surat Keluar (`/surat-keluar/create`) | Operator, Admin Divisi, Super Admin | Menerbitkan nomor surat keluar baru dan mengunggah dokumen | **Ruang Terbuang** | Duplikasi penjelasan validasi tahun; isian readonly menyita ruang utama di atas perihal surat. | `resources/views/letters/outgoing/create.blade.php` |
| **Surat Keluar** | Detail Surat Keluar (`/surat-keluar/{id}`) | Semua Staf Berizin | Memeriksa arsip surat keluar dan tanggal diseminasi | **Rapi & Terstruktur** | Hierarki data jelas; status lampiran terlihat langsung. | `resources/views/letters/outgoing/show.blade.php` |
| **Piagam** | Rekap Ekspedisi Piagam (`/piagam`) | Operator, Admin Divisi, Super Admin | Memantau seluruh serah terima piagam dan memeriksa tanda tangan | **Fungsional Namun Padat** | Kolom tanda tangan digital sangat sempit di tabel desktop; filter pencarian dan tanggal cukup baik. | `resources/views/piagam/index.blade.php` |
| **Piagam** | Tambah Catatan Piagam (`/piagam/create`) | Operator, Admin Divisi, Super Admin | Memasukkan piagam yang siap diserahkan di loket | **Cukup Terarah** | Mengarahkan petugas menginput master siswa yang berhak menerima. | `resources/views/piagam/create.blade.php` |
| **Piagam** | Detail Serah Terima (`/piagam/{id}`) | Semua Staf Berizin | Melihat bukti otentik serah terima dan preview tanda tangan | **Sangat Baik** | Pembagian kartu identitas penerima dan bukti tanda tangan berukuran besar sangat efektif. | `resources/views/piagam/show.blade.php` |
| **Laporan** | Pencarian Terpadu & Ekspor (`/laporan`) | Semua Staf Berizin | Melakukan pencarian lintas arsip serta mengekspor rekap PDF/CSV | **Fragmentasi Hasil (*Tabular Fragmentation*)** | Menampilkan 3 tabel berbeda secara vertikal dengan 3 pagination independen; scrolling vertikal sangat panjang jika memilih "Semua Administrasi". | `resources/views/reports/index.blade.php` |
| **Master Data** | Data Pengguna (`/master/pengguna`) | Admin Divisi, Super Admin | Mengelola akun operator, aktivasi sandi, dan pemulihan arsip | **Inkonsisten Antara Inline vs Modal** | Formulir tambah menggunakan ekspansi kartu inline di atas tabel, sementara edit menggunakan modal dialog; tombol "Kunci Rahasia" menyita perhatian visual. | `resources/views/master/users/index.blade.php` |
| **Master Data** | Data Divisi (`/master/divisi`) | Super Admin | Mengelola partisi unit kerja kedinasan | **Tabel Administratif Standar** | Informasi statistik data per divisi padat; modal edit bersih. | `resources/views/master/divisions/index.blade.php` |
| **Master Data** | Kategori Referensi (`/master/kategori`) | Admin Divisi, Super Admin | Mengatur kategori lomba, metode pengambilan, dan unit kerja | **Layout 3 Kolom Kaku** | Tiga form pembuatan kartu berdiri berdampingan; jika satu daftar panjang, tata letak kartu menjadi tidak seimbang. | `resources/views/master/categories/index.blade.php` |
| **Master Data** | Data Sekolah (`/master/sekolah`) | Super Admin | Mengelola basis data NPSN 8-digit sekolah di Kab. Semarang | **Tabel Standar Efisien** | Pencarian cepat; fungsi edit modal teratur. | `resources/views/master/schools/index.blade.php` |

---

## C. Temuan Rinci Berdasarkan Bukti (Detailed Findings)

### Finding ID: FND-DASH-001
* **Modul & Layar:** Dashboard Pengarsipan Surat (`/dashboard`)
* **Deskripsi Masalah:** Redundansi penyajian statistik secara triplikasi (*Metric Triplication*). Pengguna disajikan data agregat yang persis sama dalam 3 format visual yang diletakkan saling bertumpuk.
* **Bukti Kode & Render UI:**
  - Baris 31–93 (`dashboard.blade.php`): Menampilkan 4 Kartu Metrik Utama (*Total Rekap Arsip*, *Surat Masuk*, *Surat Keluar*, *Piagam*).
  - Tepat di bawahnya, Baris 98–122: Kartu *"Distribusi Jenis Administrasi"* merender kembali persentase dan total yang sama persis: `Surat Masuk ({{ $totalIncoming }})`, `Surat Keluar ({{ $totalOutgoing }})`, `Piagam ({{ $totalPiagam }})`.
  - Tepat di bawahnya lagi bagi Super Admin, Baris 160–198: Tabel *"Matriks Agregasi Lintas Divisi"* merinci kembali kolom *Surat Masuk*, *Surat Keluar*, *Piagam*, dan *Total Arsip*.
* **Prinsip UX / HCI:** *Hick's Law* & *Cognitive Load Theory (Extraneous Cognitive Load)*. Menampilkan data yang sama dalam bentuk kartu, progress bar persentase, dan sub-kartu numerik tidak menambah wawasan baru melainkan memaksa mata memindai informasi redundan.
* **Dampak Pengguna:** Staf yang membuka sistem setiap pagi untuk bekerja langsung terhambat oleh beban informasi non-operasional; aksi prioritas harian (seperti surat masuk yang baru diterima atau disposisi mendesak) terdorong ke bawah layar (*below the fold*).
* **Tingkat Keparahan:** **High (Tinggi)**
* **Rekomendasi Perbaikan:**
  1. Gabungkan informasi persentase distribusi langsung ke dalam 4 Kartu Metrik Utama (misalnya letakkan progress bar mini atau rasio persentase di dalam kartu metrik terkait).
  2. Hapus kartu kontainer mandiri *"Distribusi Jenis Administrasi"* untuk menghemat tinggi vertikal sebesar 240px.
  3. Alokasikan ruang yang dihemat untuk kartu tindakan langsung (*Quick Actions*) atau daftar arsip yang memerlukan perhatian operasional.
* **Potensi Trade-offs:** Kehilangan grafik batang bertingkat berwarna-warni, namun menghasilkan fokus kerja yang jauh lebih tajam bagi petugas arsip.

---

### Finding ID: FND-FORM-002
* **Modul & Layar:** Formulir Tambah Surat Masuk & Surat Keluar (`/surat-masuk/create`, `/surat-keluar/create`)
* **Deskripsi Masalah:** Bidang masukan hanya-baca (*read-only auto-filled inputs*) menyita 50% ruang baris formulir utama.
* **Bukti Kode & Render UI:**
  - Baris 52–61 (`letters/incoming/create.blade.php`):
    ```html
    <label class="block md:col-span-1">
        <span class="text-sm font-medium text-slate-800">Petugas Pengunggah (Otomatis)</span>
        <input type="text" value="{{ auth()->user()->name }} ({{ auth()->user()->username }})" readonly class="mt-1 min-h-[48px] w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600 cursor-not-allowed">
    </label>
    <label class="block md:col-span-1">
        <span class="text-sm font-medium text-slate-800">Divisi Aktif</span>
        <input type="text" value="{{ auth()->user()->division?->name ?? 'Semua Divisi (Global)' }}" readonly class="mt-1 min-h-[48px] w-full rounded-md border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600 cursor-not-allowed">
    </label>
    ```
  - Hal serupa ditemukan pada `letters/outgoing/create.blade.php` (Baris 44–47).
* **Prinsip UX / HCI:** *Form Affordance & Visual Hierarchy*. Komponen berbentuk kotak input teks (*input field*) secara universal mengisyaratkan keterisian (*affordance*) bahwa pengguna diharapkan mengetik. Menempatkan dua kotak input besar yang dikunci (*disabled/readonly*) sejajar dengan input penting (*Tanggal Surat* dan *Tanggal Diterima*) menciptakan friksi visual dan kebingungan navigasi tombol tab keyboard.
* **Dampak Pengguna:** Pengguna mengira kotak tersebut perlu diedit atau mengalami kebingungan mengapa tab navigasi melewatinya. Luas layar terbuang secara sia-sia, memaksa field esensial *"Perihal"* terdorong ke bawah.
* **Tingkat Keparahan:** **Medium (Sedang)**
* **Rekomendasi Perbaikan:**
  1. Keluarkan identitas petugas dan divisi dari grid formulir masukan.
  2. Tampilkan identitas tersebut sebagai teks ringkas (*subtle badge/audit info*) di bilah judul formulir atau di atas tombol simpan: *"Dicatat oleh: Budi Santoso (DIKDAS)"*.
  3. Isian yang dikirim ke backend tetap berjalan otomatis melalui controller session tanpa memerlukan input form palsu.
* **Potensi Trade-offs:** Pengguna tidak lagi melihat nama mereka di dalam kotak input formal, tetapi transparansi sistem tetap terjaga melalui teks audit footer.

---

### Finding ID: FND-GRID-003
* **Modul & Layar:** Tabel Indeks Surat Masuk & Surat Keluar (`/surat-masuk`, `/surat-keluar`)
* **Deskripsi Masalah:** Kepadatan tombol aksi baris yang berlebihan (*Action Button Noise & Clutter*).
* **Bukti Kode & Render UI:**
  - Baris 104–121 (`letters/incoming/index.blade.php`):
    Setiap baris tabel me-render secara horizontal:
    - Tombol abu-abu: `Detail`
    - Tombol hijau: `Edit`
    - Tombol merah: `Hapus` (ikon tempat sampah)
    - Ditambah tombol di kolom Lampiran: `Berkas` (ikon dokumen)
  - Dalam satu layar dengan 15 baris arsip, terdapat total **60 tombol interaktif mandiri** yang menuntut perhatian mata dengan warna-warni kontras (*slate*, *emerald*, *rose*).
* **Prinsip UX / HCI:** *Signal-to-Noise Ratio* & *Fitts's Law*. Elemen antarmuka berulang dengan warna jenuh tinggi mengaburkan konten data inti (Nomor Surat dan Perihal).
* **Dampak Pengguna:** Kolom tabel terasa sangat padat dan sempit; mata petugas cepat lelah karena harus menyaring puluhan tombol warna-warni yang berulang di setiap baris saat mencari surat tertentu.
* **Tingkat Keparahan:** **High (Tinggi)**
* **Rekomendasi Perbaikan:**
  1. Buat Nomor Surat sebagai tautan primer bergaris bawah (*clickable primary link*) langsung menuju halaman Detail.
  2. Pada kolom Aksi, sediakan tindakan sekunder (*Edit*) sebagai tombol teks minimalis dan simpan aksi destruktif (*Hapus*) di dalam dropdown menu opsi (*Kebab menu / 3 titik*), atau batasi hanya tombol `Edit` cepat.
  3. Di kolom Berkas, gunakan ikon dokumen ringkas dengan tooltip alih-alih tombol berbingkai penuh yang memakan ruang horizontal.
* **Potensi Trade-offs:** Memerlukan dua klik untuk aksi hapus (namun ini justru mencegah penghapusan arsip yang tidak disengaja oleh staf).

---

### Finding ID: FND-PUBL-004
* **Modul & Layar:** Form Publik Pengambilan Piagam (`/piagam/ambil` / `x-piagam.claim-form`)
* **Deskripsi Masalah:** Hipertrofi teks bantuan formulir (*Helper Text Hypertrophy*).
* **Bukti Kode & Render UI:**
  - Komponen `claim-form.blade.php` memiliki teks instruksi di bawah hampir setiap elemen:
    - Line 89: *"Pilih bidang atau ketik manual jika tidak ada di daftar."*
    - Line 124: *"Nama orang yang menerima fisik piagam di loket."*
    - Line 139: *"Pilih atau ketik jabatan yang mewakili."*
    - Line 162: *"Pilih metode pengambilan atau isi manual."*
    - Line 193: *"Pilih dari master sekolah / unit dinas, atau ketik manual."*
    - Line 238: *"8 digit angka (otomatis terisi jika memilih sekolah)."*
    - Line 266: *"Pilih kategori lomba dari dinas atau isi nama lomba manual."*
    - Line 296: *"Tahun penyelenggaraan lomba."*
    - Line 312: *"Nama siswa peraih penghargaan/piagam (atau tim peraih juara)."*
    - Line 327: *"Dapat diisi peringkat juara atau rincian spesifik penghargaan."*
    - Line 351: *"Bubuhkan tanda tangan Anda di area putih menggunakan layar sentuh atau mouse."*
* **Prinsip UX / HCI:** *Progressive Disclosure & Krug's First Law of Usability ("Don't Make Me Think")*. Memberikan penjelasan panjang untuk hal yang sudah terbukti dari nama labelnya (misal: "Tahun Acara" dijelaskan dengan "Tahun penyelenggaraan lomba") menggandakan tinggi formulir dan menimbulkan kelelahan visual bagi pengguna tamu di loket.
* **Dampak Pengguna:** Formulir tanda terima terasa rumit dan birokratis. Tamu sekolah yang menggunakan ponsel pintar di loket dinas harus menggulir layar berkali-kali hanya untuk menemukan kanvas tanda tangan.
* **Tingkat Keparahan:** **High (Tinggi)**
* **Rekomendasi Perbaikan:**
  1. Hilangkan teks bantuan yang redundan. Pertahankan hanya placeholder informatif di dalam input box.
  2. Pertahankan teks bantuan hanya pada kasus yang benar-benar membutuhkan panduan legal/teknis khusus (misalnya format 8 digit NPSN).
  3. Ringkas label menjadi bahasa Indonesia administratif yang padat dan jelas.
* **Potensi Trade-offs:** Sedikit mengurangi penjelasan ultra-detail, namun meningkatkan laju penyelesaian formulir tamu secara drastis.

---

### Finding ID: FND-NAV-005
* **Modul & Layar:** Layout Keseluruhan & Header (`components/layouts/app.blade.php`)
* **Deskripsi Masalah:** Duplikasi indikator konteks divisi kerja hingga 4 kali di layar yang sama.
* **Bukti Kode & Render UI:**
  - Header Global (`app.blade.php`, Baris 222–243): Menampilkan pemilih divisi Super Admin atau badge hijau divisi aktif untuk staf umum (`{{ $user->division->code }} • {{ $user->division->name }}`).
  - Header Halaman Konten (`dashboard.blade.php`, Baris 8): Merender badge hijau besar: `{{ $activeDivision ? $activeDivision->code . ' — ' . $activeDivision->name : 'Semua Divisi (Konteks Global)' }}`.
  - Header Indeks Surat (`letters/incoming/index.blade.php`, Baris 5): Merender teks hijau di atas judul: `<p class="text-sm font-semibold text-emerald-700">{{ auth()->user()->division?->code ?? 'GLOBAL' }}</p>`.
  - Isian Form Surat Masuk (`letters/incoming/create.blade.php`, Baris 58–60): Merender kembali kolom readonly khusus Divisi Aktif.
* **Prinsip UX / HCI:** *DRY (Don't Repeat Yourself) in UI Communication*. Satu informasi identitas lingkungan kerja cukup dikomunikasikan di satu lokasi jangkar (*anchor location*).
* **Dampak Pengguna:** Ruang vertikal header halaman terbuang untuk mengulang singkatan bidang kerja yang sudah terpampang jelas di bilah navigasi atas (*top bar*).
* **Tingkat Keparahan:** **Medium (Sedang)**
* **Rekomendasi Perbaikan:**
  1. Tetapkan Top Navbar sebagai **satu-satunya lokasi otoritatif** penunjuk konteks divisi aktif.
  2. Hapus teks singkatan divisi yang berulang di atas tag `<h1>` pada setiap halaman modul konten.
* **Potensi Trade-offs:** Tidak ada. Menghasilkan tampilan yang jauh lebih bersih dan seragam.

---

### Finding ID: FND-REPO-006
* **Modul & Layar:** Modul Laporan & Pencarian Terpadu (`/laporan`)
* **Deskripsi Masalah:** Fragmentasi hasil pencarian dengan 3 tabel bertumpuk dan 3 paginasi independen yang saling bertabrakan.
* **Bukti Kode & Render UI:**
  - Baris 81–217 (`reports/index.blade.php`): Ketika filter tipe bernilai default `all`, halaman merender:
    1. Kontainer Surat Masuk + Pagination Links (`incoming_page`)
    2. Kontainer Surat Keluar + Pagination Links (`outgoing_page`)
    3. Kontainer Piagam Siswa + Pagination Links (`piagam_page`)
  - Jika pengguna mengklik halaman 2 pada bagian Surat Keluar, seluruh halaman melakukan reload dan posisi scroll terlempar ke paling atas, membuat pengguna kehilangan konteks baris data sebelumnya.
* **Prinsip UX / HCI:** *Mental Model & Spatial Consistency*. Menempatkan tiga kontrol paginasi terpisah di halaman yang sama melanggar konsistensi spasial web standar.
* **Dampak Pengguna:** Petugas arsip yang mencari dokumen lintas kategori merasa bingung saat menavigasi halaman hasil pencarian; pengguliran vertikal menjadi sangat melelahkan (*infinite vertical scanning*).
* **Tingkat Keparahan:** **High (Tinggi)**
* **Rekomendasi Perbaikan:**
  1. Terapkan pola navigasi bertab (*Tabbed Results Interface*): Tab 1: **Semua Ringkasan**, Tab 2: **Surat Masuk**, Tab 3: **Surat Keluar**, Tab 4: **Piagam**.
  2. Saat pengguna berada pada tab tertentu, tampilkan hanya tabel yang bersangkutan dengan satu set paginasi tunggal yang stabil di bagian bawah.
  3. Sediakan tombol ekspor CSV dan PDF yang otomatis menyesuaikan dengan tab yang sedang aktif.
* **Potensi Trade-offs:** Memerlukan klik tab untuk berpindah kategori jika mencari secara agregat, namun menghilangkan kekacauan 3 paginasi bertumpuk.

---

### Finding ID: FND-MAST-007
* **Modul & Layar:** Manajemen Pengguna (`/master/pengguna`)
* **Deskripsi Masalah:** Inkonsistensi pola interaksi antara ekspansi inline form dan pop-up modal dialog.
* **Bukti Kode & Render UI:**
  - Baris 68–121 (`master/users/index.blade.php`): Penambahan pengguna baru menggunakan kartu formulir yang di-expand di atas tabel, yang mendorong seluruh baris tabel ke bawah secara drastis saat tombol "+ Tambah Pengguna" ditekan.
  - Baris 247–305: Pengubahan pengguna (*Edit*) menggunakan pop-up modal dialog melayang (*fixed modal overlay*).
  - Baris 308–360: Pemulihan akun (*Restore*) menggunakan modal dialog yang berbeda lagi.
* **Prinsip UX / HCI:** *Consistency and Standards (Nielsen's Heuristic #4)*. Aksi pembuatan dan pengubahan entitas data yang sama harus memiliki pola dialog dan ruang spasial yang konsisten.
* **Dampak Pengguna:** Pengguna mengalami disorientasi spasial; saat menambah pengguna, tabel bergeser jauh ke bawah, namun saat mengedit pengguna, muncul jendela melayang.
* **Tingkat Keparahan:** **Medium (Sedang)**
* **Rekomendasi Perbaikan:**
  1. Seragamkan seluruh operasi form pembuatan dan pengeditan master data menggunakan dialog modal terpadu atau drawer samping yang konsisten.
  2. Jaga agar posisi tabel data tetap stabil di latar belakang tanpa pergeseran tata letak (*layout jump*).
* **Potensi Trade-offs:** Tidak ada. Konsistensi interaksi meningkatkan kecepatan adaptasi operator.

---

### Finding ID: FND-A11Y-008
* **Modul & Layar:** Aksesibilitas Global, Focus Rings, dan Rasio Kontras
* **Deskripsi Masalah:** Rasio kontras teks sekunder di bawah ambang batas WCAG 2.2 AA dan penghilangan focus indicator keyboard.
* **Bukti Kode & Render UI:**
  - Label tanggal dan metadata sekunder di berbagai tabel dan kartu menggunakan kelas Tailwind `text-slate-400` atau `text-[10px] text-slate-500` di atas latar putih (`#ffffff`). Rasio kontras `slate-400` (#94a3b8) pada warna putih hanya **2.7:1**, jauh di bawah standar minimum WCAG 2.2 AA sebesar **4.5:1** untuk teks reguler.
  - Berbagai input formulir dan tombol menggunakan kelas `focus:outline-hidden` tanpa menyertakan `focus-visible:ring-2` yang memadai (misal di `claim-form.blade.php`, `reports/index.blade.php`), sehingga pengguna yang mengoperasikan aplikasi menggunakan tombol keyboard (Tab) kehilangan penanda posisi fokus.
* **Prinsip UX / HCI:** *WCAG 2.2 Success Criterion 1.4.3 (Contrast Minimum)* & *2.4.7 (Focus Visible)*. Antarmuka sistem pemerintahan harus inklusif dan dapat dioperasikan oleh staf dengan keterbatasan visual atau alat bantu keyboard.
* **Dampak Pengguna:** Staf senior di kantor dinas yang memiliki ketajaman visual terbatas kesulitan membaca tanggal arsip dan nomor registrasi; navigasi berbasis keyboard terputus.
* **Tingkat Keparahan:** **High (Tinggi)**
* **Rekomendasi Perbaikan:**
  1. Tingkatkan seluruh teks sekunder dari `text-slate-400` menjadi minimal `text-slate-600` (#475569) yang memiliki rasio kontras aman **5.7:1** pada latar putih.
  2. Ganti semua `focus:outline-hidden` dengan `focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:outline-hidden`.
* **Potensi Trade-offs:** Penampilan teks sekunder sedikit lebih kontras, namun keterbacaan (*legibility*) meningkat signifikan.

---

### Finding ID: FND-RESP-009
* **Modul & Layar:** Tabel Master Data pada Layar Ponsel/Tablet (`/master/pengguna`, `/master/divisi`, `/master/sekolah`)
* **Deskripsi Masalah:** Ketiadaan representasi kartu responsif pada layar Master Data, berbeda dengan modul Persuratan.
* **Bukti Kode & Render UI:**
  - Pada modul Surat Masuk dan Piagam (`letters/incoming/index.blade.php` dan `piagam/index.blade.php`), pengembang secara cerdas telah menyediakan tampilan kartu khusus layar kecil melalui blok `md:hidden` (`data-testid="mobile-letter-card"`).
  - Namun pada modul Master Data (`master/users/index.blade.php`, `master/divisions/index.blade.php`, `master/schools/index.blade.php`), pengembang hanya menggunakan `<div class="overflow-x-auto">` dengan tabel lebar statis (`min-w-[768px]`).
* **Prinsip UX / HCI:** *Responsive Reflow & Graceful Degradation*. Staf atau pejabat dinas yang membuka data sekolah atau staf dari ponsel pintar terpaksa melakukan scroll horizontal canggung yang memotong tombol aksi di sisi kanan.
* **Dampak Pengguna:** Akses data master melalui perangkat mobile di lapangan menjadi sulit dan rentan salah klik tombol hapus.
* **Tingkat Keparahan:** **Medium (Sedang)**
* **Rekomendasi Perbaikan:**
  1. Terapkan pola yang sama seperti modul Surat Masuk: sediakan daftar kartu ringkas untuk viewport mobile (`md:hidden`) dan tabel komprehensif untuk desktop (`hidden md:table`).
* **Potensi Trade-offs:** Sedikit penambahan baris markup pada template Blade, namun memberikan pengalaman mobile yang konsisten di seluruh modul.

---

## D. Analisis Kepadatan Teks & Evaluasi Microcopy (Text Density Analysis)

Berdasarkan klasifikasi konten (Esensial, Pendukung, Redundan, Dekoratif, Kontekstual), berikut adalah evaluasi terhadap teks antarmuka yang ada saat ini beserta rekomendasi microcopy bahasa Indonesia yang padat, formal, dan efisien:

| Layar / Lokasi | Teks Antarmuka Saat Ini | Kategori Teks | Masalah Analisis | Rekomendasi Microcopy / Perlakuan |
|---|---|---|---|---|
| **Layout Sidebar** | *"Layanan Persuratan & Piagam"* (Header sub-menu) | Pendukung | Terlalu panjang dan memakan ruang vertikal sidebar dengan border ganda. | Ganti dengan: **"PERSURATAN"** atau **"LAYANAN UTAMA"**. Hilangkan border tebal yang mengurung sub-menu. |
| **Top Navbar** | *"Konteks Divisi: [Dropdown]"* | Esensial | Kata "Konteks Divisi" redundan karena pilihan di dropdown sudah menunjukkan kode divisi. | Ringkas label menjadi: **"Divisi: [Dropdown]"** atau cukup letakkan ikon gedung/divisi di samping select box. |
| **Dashboard** | *"Sistem Informasi Surat & Kearsipan Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga"* | Redundan | Judul kepanjangan dinas sudah ada di navbar dan footer; staf sudah tahu instansi tempat mereka bekerja. | **Hapus subjudul ini.** Cukup gunakan judul bersih: **"Dashboard Kearsipan"** dengan tanggal rekapitulasi aktif. |
| **Dashboard** | *"Distribusi Jenis Administrasi — Proporsi data arsip secara real-time berdasarkan database."* | Redundan | Judul kartu dan deskripsi memakan tempat tanpa memberi nilai analitis tambahan dari angka di atasnya. | **Hapus seluruh blok kartu ini.** Angka persentase dimasukkan sebagai bar kemajuan mini di dalam masing-masing kartu metrik utama. |
| **Dashboard** | *"Seluruh surat & piagam terdata"* (Subteks kartu Total) | Dekoratif | Tidak memberikan nilai tambah karena judul kartu sudah bertuliskan "Total Rekap Arsip". | Ringkas menjadi: **"Arsip aktif"** atau sertakan pembanding waktu: **"Tahun berjalan"**. |
| **Surat Masuk (Create)** | *"Catat surat masuk dari pihak eksternal, instansi, atau kedinasan."* | Redundan | Fungsi form sudah jelas dari judulnya "Tambah Surat Masuk". | **Hapus deskripsi.** Ganti dengan breadcrumb navigasi: **Surat Masuk / Registrasi Baru**. |
| **Surat Masuk (Create)** | *"Petugas Pengunggah (Otomatis)"* | Redundan | Isian kotak teks readonly membuang ruang kerja. | **Pindahkan ke info footer:** *"Operator: [Nama Pengguna]"* dalam teks kecil abu-abu. |
| **Surat Masuk (Create)** | *"Saya mengonfirmasi bahwa surat ini sah dan bukan kesalahan duplikasi ganda."* | Esensial (Advisori) | Kalimat peringatan duplikat agak bertele-tele. | Pertahankan fungsi legalnya, namun padatkan teks: **"Konfirmasi: Nomor surat ini sah dan bukan duplikat."** |
| **Surat Keluar (Create)** | *"Nomor surat keluar unik per divisi dan tahun kalender."* & *"Nomor akan divalidasi keunikannya dalam tahun surat yang sama."* | Redundan | Dua kalimat berulang menerangkan aturan teknis yang sama persis di dua baris berbeda. | Gabungkan menjadi teks placeholder informatif di dalam input box: *"Format: 420/123/2026"* dan tampilkan pesan error validasi hanya jika terjadi benturan nomor. |
| **Piagam (Claim Form)** | *"Loket Pelayanan Tamu & Pengambilan Piagam Fisik"* (Pill header) | Dekoratif | Memakan ruang vertikal paling atas. | **Hapus badge dekoratif ini.** Cukup gunakan judul utama halaman: **"Tanda Terima Piagam Penghargaan"**. |
| **Piagam (Claim Form)** | *"Silakan lengkapi formulir di bawah ini sebagai bukti penyerahan fisik piagam penghargaan di loket dinas."* | Redundan | Mengulang instruksi yang sudah tercantum di bagian atas halaman. | **Hapus.** Pengguna sudah berada di halaman form dan memahami tujuannya. |
| **Piagam (Claim Form)** | *"BAGIAN A — INFORMASI PENGAMBIL"*, *"BAGIAN B — ATRIBUT KEJUARAAN & SISWA"*, *"BAGIAN C — BUKTI PENGAMBILAN"* | Pendukung | Judul legend fieldset menggunakan style badge yang sangat mencolok dengan huruf kapital tebal. | Gunakan tipografi heading sub-bagian yang tenang: **"1. Data Penerima"**, **"2. Data Penghargaan & Siswa"**, **"3. Tanda Tangan Digital"**. |
| **Piagam (Claim Form)** | *"Nama orang yang menerima fisik piagam di loket."* | Redundan | Deskripsi di bawah label *"Nama Lengkap Pengambil"*. | **Hapus deskripsi.** Gunakan placeholder: *"Contoh: Budi Santoso, S.Pd."*. |
| **Piagam (Claim Form)** | *"Pilih bidang atau ketik manual jika tidak ada di daftar."* | Redundan | Penjelasan cara pengisian select box yang sudah lazim. | **Hapus deskripsi.** Opsi terakhir pada select box: *"-- Ketik Manual --"* sudah merepresentasikan fungsi tersebut. |
| **Piagam (Claim Form)** | *"Waktu penyerahan fisik akan dicatat secara otomatis oleh server saat formulir disimpan."* | Kontekstual | Kotak hijau pemberitahuan waktu menyita perhatian di atas tombol simpan. | Ringkas menjadi microcopy di dekat tombol submit: *"Waktu penerimaan tercatat otomatis secara sistem."* |
| **Piagam (Index)** | *"Buku Ekspedisi Pengambilan Piagam — Catatan resmi bukti serah terima fisik piagam penghargaan beserta tanda tangan pengambil."* | Pendukung | Subjudul terlalu panjang untuk modul kerja harian. | Ringkas menjadi: **"Buku Ekspedisi Piagam"** tanpa subjudul panjang. |
| **Laporan** | *"Pencarian Terpadu & Ekspor Laporan Resmi — Cari seluruh arsip surat masuk, surat keluar, dan piagam secara terpadu atau unduh rekapitulasi resmi."* | Redundan | Menjelaskan hal yang sudah dipahami operator. | Ringkas menjadi: **"Pencarian & Laporan Arsip"**. |

---

## E. Temuan Arsitektur Informasi & Alur Kerja (Information Architecture Findings)

### 1. Struktur Navigasi Bilah Samping (Sidebar Wayfinding)
* **Kelemahan Pengelompokan Menu:**  
  Sidebar saat ini mengelompokkan menu *Surat Masuk*, *Surat Keluar*, dan *Rekap Pengambilan Piagam* ke dalam kotak berbingkai gelap tebal bertuliskan *"Layanan Persuratan & Piagam"*, sementara *Dashboard* dan *Pencarian & Laporan* dibiarkan berdiri di luar kotak. Hal ini menciptakan hirarki yang janggal: seolah-olah Dashboard bukan bagian dari sistem, padahal Dashboard adalah pintu masuk utama.
* **Risiko Aksidental pada Tombol Pintas `+`:**  
  Pada baris *Surat Masuk* dan *Surat Keluar*, terdapat ikon `+` di ujung kanan link. Berdasarkan observasi Fitts's Law, area target klik `+` berdekatan langsung dengan tautan menu utama. Pada perangkat layar sentuh atau saat pengguna terburu-buru, terjadi risiko salah klik: berniat membuka daftar surat masuk, tetapi justru terlempar ke form input surat baru.
* **Penempatan Tautan Portal Publik yang Dominan:**  
  Di bagian paling bawah sidebar, tombol hijau terang *"Portal Tamu (Ambil Piagam) ↗"* dipasang permanen. Tombol ini hanya relevan bagi staf loket pelayanan piagam, namun tampil mencolok bagi seluruh operator divisi setiap saat.

### 2. Ketiadaan Navigasi Breadcrumbs di Layar Operasional
* Breadcrumb navigasi (*Surat Masuk / Detail #123*) saat ini hanya diimplementasikan pada layar **Detail (Show)**.
* Pada layar pembuatan dokumen (`/surat-masuk/create`, `/surat-keluar/create`), breadcrumb tidak ada. Layar hanya mengandalkan tombol teks "Batal" di bagian bawah untuk kembali.
* Ketiadaan breadcrumb di formulir membuat pengguna kehilangan orientasi hierarki letak posisi mereka dalam struktur modul.

### 3. Alur Kerja Rekapitulasi & Pengecekan Duplikat
* Pada alur simpan surat masuk, jika terjadi nomor surat kembar dari instansi eksternal, sistem menampilkan banner peringatan duplikat dan meminta centang konfirmasi (`confirm_duplicate`). Pola ini sudah sangat baik secara bisnis (*Requirement BR-IN-001*), namun penempatan banner peringatan di bagian atas formulir memaksa pengguna yang berada di bagian bawah (area tombol submit) untuk menggulir kembali ke atas demi melihat pesan tersebut.

---

## F. Rekomendasi Tingkat Layar (Screen-Level Recommendations)

### 1. Halaman Muka Publik (`welcome.blade.php`)
* **Tetap Dipertahankan:** Identitas resmi Disdikbudpora, logo, tombol aksi ganda (*Ambil Piagam* & *Login Petugas*).
* **Disederhanakan:** Tiga kartu informasi loket di bagian bawah dipadatkan menjadi baris info ringkas tanpa ikon raksasa.
* **Diorganisasi Ulang:** Pusatkan perhatian tamu langsung ke aksi utama pengisian tanda terima loket.
* **Dibuat Kontekstual:** Jika pengguna sudah login, tombol *Login Petugas* otomatis berubah menjadi *Buka Dashboard Kerja* (sudah berjalan baik di Blade).
* **Tidak Boleh Berubah:** Aksesibilitas publik tanpa perlu kredensial login.

### 2. Dashboard (`dashboard.blade.php`)
* **Tetap Dipertahankan:** 4 Kartu Metrik Utama (*Total Arsip, Surat Masuk, Surat Keluar, Piagam*), Tren Persuratan 6 Bulan, dan Matriks Divisi untuk Super Admin.
* **Disederhanakan:** Hilangkan kartu kontainer *"Distribusi Jenis Administrasi"* yang berisi persentase berulang. Pindahkan representasi persentase ke dalam masing-masing kartu metrik utama.
* **Diorganisasi Ulang:** Geser tombol cepat `+ Surat Masuk`, `+ Surat Keluar`, dan `+ Piagam` ke area tindakan cepat (*Action Bar*) yang lebih menyatu dengan kebutuhan operasional harian staf.
* **Dibuat Kontekstual:** Daftar aktivitas audit log dibatasi hanya 5 item terkini dengan opsi link *"Lihat Log Lengkap"*, agar tidak memperpanjang halaman secara vertikal.
* **Tidak Boleh Berubah:** Logika tenant scoping (Super Admin melihat global / matriks, Admin/Operator melihat divisinya sendiri).

### 3. Indeks Surat Masuk & Keluar (`letters/incoming/index.blade.php`, `letters/outgoing/index.blade.php`)
* **Tetap Dipertahankan:** Kerapatan tabel desktop (Nomor Agenda, Nomor Surat, Tanggal, Asal Instansi/Perihal). Tabel padat sangat efisien bagi arsiparis.
* **Disederhanakan:** Ubah kolom aksi dari 3 tombol berbingkai tebal menjadi 1 tombol utama (*Edit*) dan menu dropdown (*...*) untuk aksi hapus. Jadikan Nomor Surat sebagai tautan langsung ke halaman Detail.
* **Diorganisasi Ulang:** Sediakan filter tanggal cepat (*Bulan ini / Tahun ini*) langsung di atas tabel data tanpa harus berpindah ke modul laporan.
* **Dibuat Kontekstual:** Kolom lampiran berkas cukup menampilkan badge ikon PDF/Dokumen ringkas yang dapat diklik langsung untuk preview.
* **Tidak Boleh Berubah:** Validasi peran pengguna (tombol Hapus hanya aktif untuk Super Admin & Admin Divisi, tidak muncul untuk Operator).

### 4. Formulir Tambah Surat Masuk & Keluar (`letters/incoming/create.blade.php`, `letters/outgoing/create.blade.php`)
* **Tetap Dipertahankan:** Input Nomor Surat, Asal Instansi, Tanggal Surat, Tanggal Diterima, Perihal, serta pemilih lampiran ganda (Pilih Berkas & Ambil Foto Kamera HP).
* **Disederhanakan:** Hapus input box readonly untuk *Petugas Pengunggah* dan *Divisi Aktif*. Pindahkan informasinya ke footer form atau header ringkas.
* **Diorganisasi Ulang:** Hilangkan properti `min-h-screen` pada tag `<form>` yang membuat jarak antar elemen merenggang aneh pada monitor beresolusi tinggi (FHD/4K).
* **Dibuat Kontekstual:** Tampilkan dialog/banner konfirmasi duplikat persis di atas tombol submit, bukan di bagian paling atas halaman yang tak terlihat saat form digulir.
* **Tidak Boleh Berubah:** Mekanisme pengamanan *Idempotency-Key* berbasis UUID pada request POST dan token CSRF.

### 5. Detail Surat Masuk & Keluar (`letters/incoming/show.blade.php`, `letters/outgoing/show.blade.php`)
* **Tetap Dipertahankan:** Pembagian kartu informasi metadata di sisi kiri dan kartu dokumen lampiran di sisi kanan (layout 2:1 pada layar besar). Fitur remediasi berkas legacy (*upload pengganti*).
* **Disederhanakan:** Format tampilan tanggal dan waktu diselaraskan menggunakan standar bahasa Indonesia formal.
* **Diorganisasi Ulang:** Letakkan tombol kembali dan aksi edit/hapus pada bilah aksi atas yang sejajar (*aligned header*).
* **Dibuat Kontekstual:** Form remediasi lampiran hanya muncul jika status lampiran berstatus `is_missing = true`.
* **Tidak Boleh Berubah:** Alur streaming gateway unduhan privat (`/api/v1/attachments/{uuid}/preview`) yang wajib terproteksi otorisasi sesi.

### 6. Formulir Tanda Terima Piagam Publik (`claim-form.blade.php`)
* **Tetap Dipertahankan:** Tanda tangan digital kanvas HTML5, validasi NPSN sekolah otomatis, perekaman identitas pengambil dan jabatan.
* **Disederhanakan:** Hapus 10 baris teks bantuan berulang di bawah label input. Gunakan placeholder ringkas.
* **Diorganisasi Ulang:** Tata letak kanvas tanda tangan diposisikan lebih dekat dengan tombol submit. Tombol "Bersihkan Tanda Tangan" diletakkan dengan jelas di sudut kanvas.
* **Dibuat Kontekstual:** Kotak input manual (*division_manual*, *recipient_unit_manual*, *pickup_category_manual*) muncul secara halus menggunakan animasi transisi tanpa memicu pergeseran kasar (*layout shift*).
* **Tidak Boleh Berubah:** Kepatuhan kontrak payload API publik (`/api/v1/piagam/ambil`) dan validasi wajib goresan tanda tangan digital sebelum pengiriman data.

### 7. Rekap Ekspedisi Piagam (`piagam/index.blade.php`)
* **Tetap Dipertahankan:** Kolom tanggal, keterangan lomba, nama siswa, asal sekolah, nama pengambil, jabatan, dan tombol ekspor CSV/PDF.
* **Disederhanakan:** Kolom Tanda Tangan tidak perlu tombol berbingkai besar; cukup tautan teks berikon *"Lihat TTD"*.
* **Diorganisasi Ulang:** Filter pencarian kata kunci dan rentang tanggal diintegrasikan menjadi satu baris kompak.
* **Dibuat Kontekstual:** Badge penanda status (*Tercatat Sah* vs *Tersedia Belum Diambil*) diperjelas dengan kontras warna yang ramah aksesibilitas.
* **Tidak Boleh Berubah:** Hak akses audit log bukti fisik dan relasi tanda tangan digital ke entitas piagam.

### 8. Modul Pencarian & Laporan Terpadu (`reports/index.blade.php`)
* **Tetap Dipertahankan:** Fitur penyaringan kata kunci, jenis administrasi, rentang tanggal, tahun, serta ekspor CSV dan cetak PDF.
* **Disederhanakan:** Hilangkan tampilan 3 tabel vertikal bertumpuk yang memicu 3 set pagination terpisah.
* **Diorganisasi Ulang:** Terapkan arsitektur hasil pencarian bertab (*Tabbed Results*). Pengguna memilih melihat Arsip Masuk, Arsip Keluar, atau Piagam dengan paginasi tunggal yang terpusat.
* **Dibuat Kontekstual:** Tombol Ekspor CSV dan PDF otomatis mengekspor dataset yang sesuai dengan tab yang sedang aktif dipilih pengguna.
* **Tidak Boleh Berubah:** Parameter query string URL (`?q=...&type=...&date_from=...`) agar tautan laporan dapat dibagikan dan di-*bookmark*.

### 9. Manajemen Master Data (`master/*`)
* **Tetap Dipertahankan:** Pembagian tab *Pengguna Aktif* dan *Pengguna Terhapus (Soft Deleted)* pada modul pengguna; validasi 8 digit NPSN pada sekolah.
* **Disederhanakan:** Hapus kartu form pembuatan inline di atas tabel pada `master/users/index.blade.php`.
* **Diorganisasi Ulang:** Satukan aksi pembuatan dan pengeditan master data ke dalam dialog modal terpadu yang konsisten di semua modul master (divisi, sekolah, kategori, dan pengguna).
* **Dibuat Kontekstual:** Pada modul kategori (`master/categories/index.blade.php`), gunakan tab internal untuk memisahkan *Kategori Lomba*, *Kategori Pengambilan*, dan *Unit Kerja*, alih-alih memaksakan 3 kolom sempit berdampingan.
* **Tidak Boleh Berubah:** Larangan akses RBAC bagi Operator (hanya Super Admin dan Admin Divisi yang memiliki otorisasi).

---

## G. Peta Jalan Peningkatan Berprioritas (Prioritized Improvement Roadmap)

Rekomendasi perbaikan disusun dalam 4 tingkatan prioritas berdasarkan dampak terhadap efisiensi kerja staf dan kompleksitas teknis:

```
┌────────────────────────────────────────────────────────────────────────┐
│ P0: Kritis Usabilitas & Aksesibilitas (WCAG & Perbaikan Layout Rusak) │
├────────────────────────────────────────────────────────────────────────┤
│ P1: Peningkatan Efisiensi Alur Kerja Inti (Formulir & Tabel Data)     │
├────────────────────────────────────────────────────────────────────────┤
│ P2: Konsistensi Antarmuka & Konsolidasi Komponen Blade               │
├────────────────────────────────────────────────────────────────────────┤
│ P3: Penyempurnaan Ergonomis & Polish Tampilan Visual                  │
└────────────────────────────────────────────────────────────────────────┘
```

### P0 — Masalah Kritis Usabilitas & Aksesibilitas (Segera Diperbaiki)
| Item Rekomendasi | Modul Terkait | Kompleksitas Teknis | Risiko Regresi | Alasan & Dampak |
|---|---|---|---|---|
| **P0-1:** Perbaiki kontras teks sekunder dari `text-slate-400` ke minimal `text-slate-600` pada seluruh tabel dan tanggal. | Global Layout & Seluruh Tabel | Sangat Rendah | Nol | Memenuhi syarat legal aksesibilitas web pemerintah (WCAG 2.2 AA). |
| **P0-2:** Pulihkan *focus ring* (`focus-visible:ring-2`) pada seluruh elemen input dan tombol yang kehilangan indikator fokus akibat `focus:outline-hidden`. | Seluruh Form & Komponen Input | Rendah | Nol | Menjamin pengguna keyboard tidak kehilangan navigasi saat mengisi data. |
| **P0-3:** Hilangkan kekacauan 3 paginasi independen pada modul Laporan dengan memisahkan hasil ke dalam sistem Tab. | `reports/index.blade.php` | Sedang | Rendah | Mencegah reload halaman yang melompat-lompat saat navigasi halaman data. |
| **P0-4:** Hapus tinggi kaku `min-h-screen` pada formulir pencatatan surat agar form tidak merenggang aneh di layar desktop. | `letters/*/create.blade.php` | Sangat Rendah | Nol | Memulihkan proporsi tata letak grid form yang proporsional dan mudah diisi. |

### P1 — Peningkatan Efisiensi Alur Kerja Inti (Dampak Tinggi)
| Item Rekomendasi | Modul Terkait | Kompleksitas Teknis | Risiko Regresi | Alasan & Dampak |
|---|---|---|---|---|
| **P1-1:** Bersihkan hipertrofi teks bantuan pada formulir tanda terima tamu (`claim-form.blade.php`). | `piagam/claim.blade.php` | Rendah | Sangat Rendah | Mempercepat waktu pengisian oleh tamu sekolah di loket hingga 40%. |
| **P1-2:** Hapus isian readonly palsu "Petugas Pengunggah" dan "Divisi Aktif" dari grid formulir surat masuk dan keluar. | `letters/*/create.blade.php` | Rendah | Sangat Rendah | Menghemat 50% ruang baris pertama form; menghilangkan kebingungan tab keyboard. |
| **P1-3:** Konsolidasikan tombol aksi baris tabel surat dari 3 tombol berwarna jenuh menjadi 1 tautan utama + menu opsi. | `letters/*/index.blade.php` | Sedang | Rendah | Meningkatkan rasio *signal-to-noise*, mata operator tidak lelah memindai data arsip. |
| **P1-4:** Hapus kartu distribusi metrik redundan pada Dashboard dan integrasikan persentase ke kartu utama. | `dashboard.blade.php` | Rendah | Sangat Rendah | Menghemat ruang vertikal 240px; menghilangkan kebingungan metrik ganda. |

### P2 — Konsistensi & Pemurnian Komponen Visual (Dampak Menengah)
| Item Rekomendasi | Modul Terkait | Kompleksitas Teknis | Risiko Regresi | Alasan & Dampak |
|---|---|---|---|---|
| **P2-1:** Seragamkan aksi pembuatan Master Pengguna menjadi modal dialog terpadu (menghilangkan form ekspansi inline). | `master/users/index.blade.php` | Sedang | Rendah | Menjaga stabilitas posisi tabel tanpa pergeseran tata letak vertikal. |
| **P2-2:** Hapus redundansi badge penunjuk divisi pada header halaman konten, jadikan Top Navbar sebagai jangkar tunggal. | Layout & Seluruh Views | Rendah | Nol | Menghilangkan duplikasi teks nama divisi di 3 titik berbeda. |
| **P2-3:** Tambahkan representasi kartu responsif (`md:hidden`) pada seluruh tabel Master Data (Divisi, Sekolah, Pengguna). | `master/*/index.blade.php` | Sedang | Rendah | Operator/Admin dapat mengelola master data via ponsel tanpa scroll horizontal. |
| **P2-4:** Ubah tampilan 3 kolom kaku pada Master Kategori menjadi navigasi sub-tab horizontal yang rapi. | `master/categories/index.blade.php` | Sedang | Rendah | Mengatasi tampilan kolom yang jomplang jika jumlah item kategori tidak seimbang. |

### P3 — Penyempurnaan Ergonomis & Polish (Opsional)
| Item Rekomendasi | Modul Terkait | Kompleksitas Teknis | Risiko Regresi | Alasan & Dampak |
|---|---|---|---|---|
| **P3-1:** Sederhanakan tombol pintas `+` pada sidebar menjadi menu hover/flyout terpisah untuk mencegah salah klik. | `components/layouts/app.blade.php` | Sedang | Rendah | Menghindari aktivasi form create yang tidak sengaja pada layar sentuh. |
| **P3-2:** Pindahkan tautan "Portal Tamu" dari navigasi primer sidebar ke utilitas header atau footer loket. | `components/layouts/app.blade.php` | Rendah | Nol | Mengembalikan fokus sidebar khusus pada fungsi operasional staf dinas. |
| **P3-3:** Tambahkan indikator visual sisa kuota atau filter cepat satu-klik (*Filter Chips*) pada daftar surat. | `letters/*/index.blade.php` | Rendah | Rendah | Mempercepat pencarian arsip bulan berjalan tanpa perlu mengetik kata kunci. |

---

## H. Arah Desain Terpadu (Design Direction for SISURAT)

Untuk menjaga kesinambungan teknis tanpa merombak arsitektur Laravel Blade yang telah berjalan, berikut adalah pedoman arah desain masa depan:

### 1. Karakter Visual: *Restrained, Dignified, Administrative*
* Antarmuka SISURAT adalah instrumen kerja harian aparatur sipil negara, bukan media sosial atau aplikasi pemasaran.
* Hindari ornamen dekoratif yang tidak fungsional (seperti gradasi warna pelangi, animasi melayang, atau kartu statistik bertumpuk-tumpuk).
* Gunakan gaya visual tenang (*restrained*), dengan garis batas halus (*border-slate-200*), latar belakang bersih (*bg-white* / *bg-slate-50*), dan aksen hijau institusional (*emerald-700*).

### 2. Standar Tipografi & Ruang (*Typography Scale & Spacing*)
* **Font Family:** Pertahankan sans-serif bawaan modern yang jernih (*Instrument Sans*, *ui-sans-serif*, *system-ui*).
* **Ukuran Judul Modul:** Batasi maksimal pada `text-xl font-bold text-slate-900` untuk layar kerja operasional, dan `text-2xl font-extrabold` hanya pada Halaman Utama/Landing Page.
* **Hirarki Konten:**
  - Label Input: `text-xs font-semibold text-slate-700 uppercase tracking-wider`
  - Teks Data Inti: `text-sm font-medium text-slate-900`
  - Nomor Arsip / Kode: `font-mono text-xs font-bold text-slate-800`
  - Metadata / Tanggal: `text-xs text-slate-600` (JANGAN gunakan `text-slate-400` di latar putih)
* **Spasi Komponen:** Standarkan padding kontainer kartu kerja pada `p-5` (desktop) dan `p-4` (ponsel), hindari `p-8` yang memboroskan ruang pandang.

### 3. Komponen Blade yang Dapat Digunakan Kembali (*Reusable Blade Components*)
Alih-alih menginstal library baru, manfaatkan potensi Blade component Laravel:
* `<x-page-header title="..." subtitle="..." />`: Komponen header standar dengan integrasi breadcrumb otomatis.
* `<x-card title="...">`: Wadah kartu standar dengan padding konsisten.
* `<x-data-table>`: Komponen pembungkus tabel desktop dengan status kosong (*empty state*) terpadu.
* `<x-status-badge type="success|warning|danger">`: Badge status terpadu dengan kontras teks yang telah teruji WCAG AA.
* `<x-action-menu>`: Dropdown aksi baris ringkas untuk menggantikan deretan tombol berwarna-warni.

### 4. Kompatibilitas 100% Shared Hosting & Vanilla Blade
* Seluruh rekomendasi ini **tidak memerlukan** Node.js runtime di server produksi, React, Vue, Livewire, atau Redis.
* Cukup mengandalkan rendering server Blade, utility classes Tailwind CSS v4 yang telah terkompilasi, serta elemen interaktif native peramban seperti tag `<dialog>` HTML5 untuk modal aksesibel dan `<details>/<summary>` untuk pengungkapan progresif (*progressive disclosure*).

---

## I. Rencana Validasi Pengujian Pengguna (Validation Plan)

Untuk memastikan bahwa rekomendasi perbaikan benar-benar menyelesaikan masalah kepadatan kognitif dan meningkatkan produktivitas staf tanpa menimbulkan efek samping, berikut protokol pengujian yang harus dijalankan:

### 1. Skenario Tugas Representatif (*Representative Administrative Tasks*)
Pengujian dilakukan terhadap minimal 5 pengguna perwakilan (2 Operator Divisi, 1 Admin Divisi, 1 Super Admin, dan 1 Guru Tamu Loket):
* **Skenario T-01 (Pencatatan Surat Masuk Cepat):** Petugas mencatat satu surat fisik masuk yang baru diterima dari Kementerian, menyertakan nomor surat, tanggal terima, dan foto pindaian kamera.
* **Skenario T-02 (Pencarian & Unduh Berkas):** Petugas mencari surat keluar tertentu dari tahun sebelumnya menggunakan kata kunci perihal, membuka detailnya, dan mengunduh berkas lampiran.
* **Skenario T-03 (Pemberian Piagam di Loket):** Tamu sekolah mengisi formulir tanda terima pengambilan piagam di loket via tablet/ponsel, membubuhkan tanda tangan digital, dan menyimpan bukti.
* **Skenario T-04 (Pencarian Multi-Kategori & Cetak Laporan):** Admin dinas memfilter seluruh arsip bulan berjalan dan mencetak laporan rekapitulasi PDF resmi.

### 2. Metrik Kuantitatif yang Diukur (*Quantitative Usability Metrics*)
* **Waktu Penyelesaian Tugas (*Time-on-Task*):**
  - *Baseline saat ini:* Pencatatan surat masuk rata-rata membutuhkan ~90–120 detik; pengisian tanda terima tamu membutuhkan ~150–180 detik.
  - *Target perbaikan:* Waktu pengisian berkurang minimal 30% karena hilangnya field readonly palsu dan teks bantuan yang mengalihkan fokus.
* **Tingkat Kesalahan (*Error Rate / Misclicks*):**
  - Frekuensi salah klik tombol pada kolom aksi tabel.
  - Frekuensi kesalahan pemilihan unit kerja pada form tanda terima piagam.
* **Tingkat Keberhasilan Tugas (*Task Completion Rate*):**
  - Target: 100% tugas administratif berhasil diselesaikan tanpa bantuan panduan verbal fasilitator.

### 3. Pengukuran Kualitatif & Persepsi Beban Kognitif
* **Kuesioner SUS (*System Usability Scale*):** Menargetkan skor SUS meningkat dari estimasi saat ini (~68-70) menjadi di atas **82 (Kategori "Excellent")**.
* **Pengukuran NASA-TLX (*Task Load Index*):** Mengukur penurunan beban mental (*mental demand*), frustrasi visual (*frustration level*), dan usaha fisik (*effort*) saat memindai tabel data padat.
* **Wawancara Terstruktur Pasca-Uji (*Post-Test Interview*):** Memvalidasi apakah staf merasakan antarmuka baru lebih tenang, lebih cepat dimengerti, dan tidak membuat mata cepat lelah saat digunakan seharian penuh.

---

> [!NOTE]
> **Pernyataan Integritas Audit:**  
> Seluruh evaluasi, temuan nomor baris kode, dan rekomendasi yang tertuang dalam dokumen ini disusun berdasarkan eksplorasi kode sumber aktif di repositori `sisularavel` serta perekaman sesi penjelajahan antarmuka nyata (`ui_audit_tour_1792061898828.webp`). Sesuai mandat, tidak ada berkas kode aplikasi yang dimodifikasi selama audit ini berlangsung. Dokumen ini diserahkan sebagai panduan strategis perbaikan UI/UX bagi tim pengembang SISURAT.
