<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SISURAT — Sistem Administrasi Persuratan & Piagam Disdikbudpora Kab. Semarang</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon/icon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen relative flex flex-col justify-between text-white antialiased overflow-x-hidden bg-slate-950">
    <!-- Fixed Full-Screen Background Image (Menutupi seluruh layar dari header sampai footer) -->
    <div class="fixed inset-0 pointer-events-none"
         style="background-image: url('{{ asset('images/bg-hero.jpg') }}'); background-repeat: no-repeat; background-size: cover; background-position: center center;">
    </div>

    <!-- Fixed Full-Screen Dark Overlay (Lapisan gelap transparan peredam silau gambar) -->
    <div class="fixed inset-0 pointer-events-none"
         style="background: linear-gradient(180deg, rgba(15, 23, 42, 0.88) 0%, rgba(15, 23, 42, 0.78) 50%, rgba(15, 23, 42, 0.94) 100%); backdrop-filter: blur(1.5px); -webkit-backdrop-filter: blur(1.5px);">
    </div>

    <!-- Header / Navbar Publik Transparan -->
    <header class="relative z-30 border-b border-white/10 bg-black/20 backdrop-blur-md sticky top-0 transition-all">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/icon/icon.svg') }}" alt="Logo SISURAT" class="h-10 w-10 shrink-0 object-contain drop-shadow-md" />
                <div>
                    <h1 class="text-base font-bold text-white leading-tight drop-shadow-xs">SISURAT</h1>
                    <p class="text-xs text-slate-300">Disdikbudpora Kab. Semarang</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="/piagam/ambil" class="inline-flex min-h-[44px] items-center gap-2 rounded-md bg-emerald-600 px-4 text-xs font-semibold text-white shadow-md hover:bg-emerald-500 transition-colors">
                    <x-icon name="signature" class="h-4 w-4" />
                    Ambil Piagam
                </a>
                @auth
                    <a href="/dashboard" class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-white/20 bg-white/10 px-4 text-xs font-semibold text-white shadow-xs backdrop-blur-sm hover:bg-white/20 transition-colors">
                        <x-icon name="home" class="h-4 w-4" />
                        Dashboard
                    </a>
                @else
                    <a href="/login" class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-white/20 bg-white/10 px-4 text-xs font-semibold text-white shadow-xs backdrop-blur-sm hover:bg-white/20 transition-colors">
                        <x-icon name="key" class="h-4 w-4 text-emerald-300" />
                        Login Petugas
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="relative z-20 flex-1 flex items-center justify-center px-4 py-12 sm:px-6 sm:py-20">
        <!-- Konten Utama Hero -->
        <div class="mx-auto max-w-4xl text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl drop-shadow-md">
                Sistem Informasi Persuratan & Pengambilan Piagam
            </h2>
            <p class="mt-5 text-sm sm:text-lg text-slate-200 leading-relaxed max-w-2xl mx-auto font-normal drop-shadow-xs">
                Selamat datang di portal pelayanan Disdikbudpora Kabupaten Semarang. Bagi tamu sekolah, guru, atau perwakilan yang hadir di kantor dinas untuk serah terima piagam, silakan buka form bukti tanda terima langsung di bawah ini.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3.5">
                <a href="/piagam/ambil" class="flex min-h-[48px] w-full sm:w-auto items-center justify-center gap-2 rounded-lg bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-950/50 hover:bg-emerald-500 transition-all">
                    <x-icon name="signature" class="h-5 w-5" />
                    Ambil Piagam (Isi Tanda Terima)
                </a>
                <a href="/login" class="flex min-h-[48px] w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-white/25 bg-white/10 px-6 text-sm font-semibold text-white backdrop-blur-md hover:bg-white/20 transition-all">
                    <x-icon name="key" class="h-4 w-4 text-emerald-300" />
                    Login Administrator / Staf
                </a>
            </div>
        </div>
    </main>

    <!-- Footer Transparan -->
    <footer class="relative z-20 border-t border-white/10 bg-black/20 backdrop-blur-sm py-5 text-center text-xs text-slate-300">
        <p>&copy; {{ date('Y') }} Dinas Pendidikan, Kebudayaan, Kepemudaan dan Olahraga Kabupaten Semarang. Seluruh hak cipta dilindungi.</p>
    </footer>
</body>
</html>
