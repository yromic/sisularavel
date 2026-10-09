<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Pengambilan Piagam & Pelayanan Tamu — Disdikbudpora Kab. Semarang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <!-- Header / Navbar Publik -->
    <header class="border-b border-slate-200 bg-white/95 sticky top-0 z-20 backdrop-blur-xs">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-700 font-bold text-white shadow-xs">
                    SI
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 leading-tight">SISURAT</h1>
                    <p class="text-xs text-slate-500">Disdikbudpora Kab. Semarang</p>
                </div>
            </div>

            <div>
                @auth
                    <a href="/dashboard" class="inline-flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800">
                        <x-icon name="home" class="h-4 w-4" />
                        Masuk ke Dashboard
                    </a>
                @else
                    <a href="/login" class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        <x-icon name="key" class="h-4 w-4 text-slate-500" />
                        Login Petugas / Staf
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="border-b border-slate-200 bg-gradient-to-b from-white to-slate-100/80 px-4 py-10 sm:px-6 sm:py-14 text-center">
        <div class="mx-auto max-w-3xl">
            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 tracking-wide uppercase">
                Layanan Mandiri Tamu & Sekolah
            </span>
            <h2 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                Portal Pengambilan Piagam Penghargaan
            </h2>
            <p class="mt-3 text-sm sm:text-base text-slate-600 leading-relaxed">
                Silakan pilih sekolah asal Anda, pilih piagam penghargaan yang akan diambil, dan bubuhkan tanda tangan digital bukti serah terima resmi di bawah ini.
            </p>
        </div>
    </section>

    <!-- Main Claim Form Container -->
    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <!-- Success Banner -->
        <div id="claim-success-banner" class="hidden mb-6 rounded-lg border border-emerald-300 bg-emerald-50 p-5 text-emerald-900 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="rounded-full bg-emerald-600 p-1 text-white">
                    <x-icon name="check" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="font-bold text-base text-emerald-950">Pengambilan Piagam Berhasil Dicatat!</h3>
                    <p class="mt-1 text-sm text-emerald-800" id="claim-success-message">Tanda tangan bukti serah terima telah tersimpan aman dalam sistem SISURAT.</p>
                    <button type="button" onclick="location.reload()" class="mt-3 inline-flex min-h-[44px] items-center rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white hover:bg-emerald-800">
                        Ambil Piagam Lainnya
                    </button>
                </div>
            </div>
        </div>

        <div id="claim-form-card" class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
            <div id="form-error-banner" class="hidden mb-6 rounded-md border border-rose-300 bg-rose-50 p-4 text-sm text-rose-800"></div>

            <form id="public-claim-form" data-testid="piagam-claim-form" method="post" action="/api/v1/piagam/ambil" class="space-y-6">
                @csrf
                <input type="hidden" name="piagam_id" id="piagam_id" required>
                <input type="hidden" name="signature_canvas" id="signature_canvas" required>

                <!-- STEP 1: PILIH SEKOLAH -->
                <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">1</span>
                        <h3 class="font-semibold text-slate-900 text-sm">Pilih Sekolah Asal</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label for="school_select" class="block text-xs font-semibold text-slate-700">Daftar Sekolah Terdaftar *</label>
                            <select id="school_select" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden" onchange="handleSchoolSelected(this.value)">
                                <option value="">-- Pilih Nama Sekolah Anda --</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->npsn }}" data-name="{{ $school->name }}">{{ $school->name }} (NPSN: {{ $school->npsn }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="npsn" class="block text-xs font-semibold text-slate-700">NPSN Sekolah (8 Digit) *</label>
                            <input name="npsn" id="npsn" maxlength="8" pattern="\d{8}" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 font-mono text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: 20320340" required onchange="fetchPiagamsByNpsn(this.value)">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: PILIH PIAGAM SISWA -->
                <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">2</span>
                        <h3 class="font-semibold text-slate-900 text-sm">Pilih Piagam yang Diambil</h3>
                    </div>

                    <div id="piagam-loading" class="hidden py-4 text-center text-xs text-slate-500">
                        Memuat daftar piagam yang tersedia untuk sekolah ini...
                    </div>

                    <div id="piagam-list-container" class="space-y-2 mt-2">
                        <p class="py-4 text-center text-xs text-slate-500" id="piagam-placeholder">
                            Silakan pilih sekolah asal di atas untuk melihat piagam yang dapat diambil.
                        </p>
                    </div>
                </div>

                <!-- STEP 3: IDENTITAS PENGAMBIL (TAMU) -->
                <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">3</span>
                        <h3 class="font-semibold text-slate-900 text-sm">Identitas Tamu / Pengambil</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="recipient_name" class="block text-xs font-semibold text-slate-700">Nama Lengkap Pengambil *</label>
                            <input name="recipient_name" id="recipient_name" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: Budi Santoso, S.Pd." required>
                        </div>
                        <div>
                            <label for="recipient_position" class="block text-xs font-semibold text-slate-700">Jabatan / Status *</label>
                            <input name="recipient_position" id="recipient_position" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: Guru Pendamping / Wali" required>
                        </div>
                        <div>
                            <label for="recipient_unit" class="block text-xs font-semibold text-slate-700">Asal Instansi / Unit Kerja *</label>
                            <input name="recipient_unit" id="recipient_unit" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-emerald-700 focus:outline-hidden" placeholder="Contoh: SMP N 1 Ungaran" required>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: TANDA TANGAN DIGITAL -->
                <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">4</span>
                            <h3 class="font-semibold text-slate-900 text-sm">Tanda Tangan Digital Pengambil *</h3>
                        </div>
                        <button type="button" id="btn-clear-sig" class="min-h-[44px] rounded-md border border-slate-300 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-100">
                            Bersihkan Tanda Tangan
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mb-2">Gunakan jari tangan Anda (pada layar sentuh) atau mouse komputer untuk membubuhkan tanda tangan resmi.</p>

                    <div class="rounded-md border-2 border-dashed border-slate-300 bg-white p-1">
                        <canvas id="sig-pad" data-testid="signature-pad" class="w-full h-44 bg-white touch-none cursor-crosshair rounded"></canvas>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" id="btn-submit-claim" class="flex min-h-[52px] w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-6 font-bold text-white text-base shadow-sm hover:bg-emerald-800 transition-colors focus:outline-hidden disabled:opacity-50">
                        <x-icon name="check" class="h-5 w-5" />
                        Konfirmasi & Ambil Piagam
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-16 border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} Dinas Pendidikan, Kebudayaan, Kepemudaan dan Olahraga Kabupaten Semarang. Hak Cipta Dilindungi.</p>
    </footer>

    <!-- Script: Canvas & Claim Logic -->
    <script>
        const canvas = document.getElementById('sig-pad');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let hasDrawn = false;

        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#0f172a';
        }

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        function getCanvasPos(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDraw(e) {
            e.preventDefault();
            isDrawing = true;
            hasDrawn = true;
            const pos = getCanvasPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const pos = getCanvasPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }

        function stopDraw() {
            isDrawing = false;
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDraw);

        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stopDraw);

        document.getElementById('btn-clear-sig').addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasDrawn = false;
        });

        function handleSchoolSelected(npsn) {
            document.getElementById('npsn').value = npsn;
            const select = document.getElementById('school_select');
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.name) {
                document.getElementById('recipient_unit').value = selectedOpt.dataset.name;
            }
            if (npsn) {
                fetchPiagamsByNpsn(npsn);
            }
        }

        async function fetchPiagamsByNpsn(npsn) {
            if (!npsn || npsn.length !== 8) return;
            const container = document.getElementById('piagam-list-container');
            const loading = document.getElementById('piagam-loading');
            loading.classList.remove('hidden');
            container.innerHTML = '';

            try {
                const res = await fetch(`/api/v1/public/piagams?npsn=${encodeURIComponent(npsn)}`);
                const json = await res.json();
                loading.classList.add('hidden');

                if (json.data && json.data.length > 0) {
                    let html = '<div class="space-y-2">';
                    json.data.forEach((p, idx) => {
                        html += `
                            <label class="flex items-center gap-3 p-3 rounded-md border border-slate-300 bg-white hover:border-emerald-600 cursor-pointer">
                                <input type="radio" name="selected_piagam" value="${p.id}" ${idx === 0 ? 'checked' : ''} onchange="document.getElementById('piagam_id').value = this.value" class="h-4 w-4 text-emerald-700">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">${p.student_name}</p>
                                    <p class="text-xs text-slate-500">${p.competition_category} &bull; Tahun ${p.competition_year}</p>
                                </div>
                            </label>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                    document.getElementById('piagam_id').value = json.data[0].id;
                } else {
                    container.innerHTML = `
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-center text-xs text-amber-900">
                            Tidak ada piagam belum diambil yang terdaftar untuk NPSN ${npsn}.
                        </div>
                    `;
                }
            } catch (err) {
                loading.classList.add('hidden');
                container.innerHTML = '<p class="text-xs text-rose-600 text-center py-2">Gagal mengambil daftar piagam sekolah.</p>';
            }
        }

        // Handle Submit
        document.getElementById('public-claim-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const errBanner = document.getElementById('form-error-banner');
            errBanner.classList.add('hidden');
            errBanner.textContent = '';

            if (!hasDrawn) {
                errBanner.classList.remove('hidden');
                errBanner.textContent = 'Harap bubuhkan tanda tangan digital Anda pada area yang disediakan.';
                return;
            }

            const piagamId = document.getElementById('piagam_id').value;
            if (!piagamId) {
                errBanner.classList.remove('hidden');
                errBanner.textContent = 'Harap pilih piagam siswa yang akan diambil.';
                return;
            }

            const sigData = canvas.toDataURL('image/png');
            document.getElementById('signature_canvas').value = sigData;

            const submitBtn = document.getElementById('btn-submit-claim');
            const origText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Menyimpan Tanda Terima...';

            const payload = {
                piagam_id: parseInt(piagamId),
                npsn: document.getElementById('npsn').value,
                recipient_name: document.getElementById('recipient_name').value,
                recipient_position: document.getElementById('recipient_position').value,
                recipient_unit: document.getElementById('recipient_unit').value,
                signature_canvas: sigData
            };

            try {
                const response = await fetch('/api/v1/piagam/ambil', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Idempotency-Key': crypto.randomUUID(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (response.ok) {
                    document.getElementById('claim-form-card').classList.add('hidden');
                    const successBanner = document.getElementById('claim-success-banner');
                    successBanner.classList.remove('hidden');
                    document.getElementById('claim-success-message').textContent =
                        `Piagam telah berhasil diserahkan kepada ${data.data.recipient_name} (${data.data.recipient_position}). Bukti tanda tangan tersimpan.`;
                } else {
                    errBanner.classList.remove('hidden');
                    errBanner.textContent = data.message || 'Gagal memproses pengambilan piagam. Periksa isian form.';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origText;
                }
            } catch (err) {
                errBanner.classList.remove('hidden');
                errBanner.textContent = 'Terjadi gangguan koneksi internet. Silakan coba kembali.';
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
            }
        });
    </script>
</body>
</html>
