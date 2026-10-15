<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SISURAT — Sistem Administrasi Persuratan & Piagam Disdikbudpora Kab. Semarang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased flex flex-col justify-between">
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

            <div class="flex items-center gap-2">
                <a href="/piagam/ambil" class="inline-flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-700 px-4 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800">
                    <x-icon name="signature" class="h-4 w-4" />
                    Ambil Piagam
                </a>
                @auth
                    <a href="/dashboard" class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50">
                        <x-icon name="home" class="h-4 w-4" />
                        Dashboard
                    </a>
                @else
                    <a href="/login" class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        <x-icon name="key" class="h-4 w-4 text-slate-500" />
                        Login Petugas
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex items-center justify-center px-4 py-12 sm:px-6 sm:py-20">
        <div class="mx-auto max-w-3xl text-center">
            <span class="inline-flex rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-bold text-emerald-800 tracking-wide uppercase">
                Layanan Administrasi & Pelayanan Tamu
            </span>
            <h2 class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">
                Sistem Informasi Persuratan & Pengambilan Piagam
            </h2>
            <p class="mt-4 text-sm sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                Selamat datang di portal pelayanan Disdikbudpora Kabupaten Semarang. Bagi tamu sekolah, guru, atau perwakilan yang hadir di kantor dinas untuk serah terima piagam, silakan buka form bukti tanda terima langsung di bawah ini.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="/piagam/ambil" class="flex min-h-[48px] w-full sm:w-auto items-center justify-center gap-2 rounded-lg bg-emerald-700 px-6 text-sm font-bold text-white shadow-sm hover:bg-emerald-800 transition-colors">
                    <x-icon name="signature" class="h-5 w-5" />
                    Ambil Piagam (Isi Tanda Terima)
                </a>
                <a href="/login" class="flex min-h-[48px] w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition-colors">
                    <x-icon name="key" class="h-4 w-4 text-slate-500" />
                    Login Administrator / Staf
                </a>
            </div>

            <!-- Loket Information Card -->
            <div class="mt-12 rounded-xl border border-slate-200 bg-white p-6 text-left shadow-xs grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-emerald-50 p-2 text-emerald-700">
                        <x-icon name="building" class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase">Loket Pelayanan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Disdikbudpora Kab. Semarang, Jl. Gatot Subroto No. 20 Ungaran.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-emerald-50 p-2 text-emerald-700">
                        <x-icon name="signature" class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase">Bukti Digital Langsung</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Tanda tangan digital langsung di layar sebagai bukti serah terima fisik.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-emerald-50 p-2 text-emerald-700">
                        <x-icon name="lock" class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase">Arsip Aman Terenkripsi</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Tersimpan dalam buku ekspedisi digital yang terverifikasi dan terlindungi.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} Dinas Pendidikan, Kebudayaan, Kepemudaan dan Olahraga Kabupaten Semarang. Seluruh hak cipta dilindungi.</p>
    </footer>
</body>
</html>
