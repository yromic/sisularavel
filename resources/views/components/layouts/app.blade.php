<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SISURAT' }} — Disdikbudpora Kab. Semarang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950 antialiased font-sans">
    @auth
        @php
            $user = auth()->user();
            $activeDivisionId = session('active_division_id', $user->division_id);
            $currentDivision = $activeDivisionId ? \App\Models\Division::find($activeDivisionId) : null;
        @endphp

        <div class="flex min-h-screen">
            <!-- ============================================================ -->
            <!-- DESKTOP SIDEBAR (PRIMARY NAVIGATION)                         -->
            <!-- ============================================================ -->
            <aside class="hidden w-64 shrink-0 flex-col justify-between border-r border-slate-800 bg-slate-900 text-slate-200 md:flex">
                <div class="flex flex-col">
                    <!-- Brand / Logo Header -->
                    <div class="flex items-center gap-3 border-b border-slate-800 px-5 py-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600 font-bold text-white shadow-sm">
                            SI
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-base font-bold tracking-tight text-white">SISURAT</span>
                                <span class="rounded bg-emerald-950 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-400 border border-emerald-800">v1.3</span>
                            </div>
                            <p class="text-[11px] text-slate-400">Disdikbudpora Kab. Semarang</p>
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <nav class="space-y-4 px-3 py-4 text-sm" aria-label="Navigasi Utama">
                        <!-- Dashboard -->
                        <div>
                            <a href="/dashboard" class="flex min-h-[44px] items-center gap-3 rounded-md px-3 font-medium transition-colors {{ request()->is('dashboard') ? 'bg-emerald-600 font-semibold text-white shadow-xs' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <x-icon name="home" class="h-5 w-5" />
                                <span>Dashboard</span>
                            </a>
                        </div>

                        <!-- 1 NAVIGASI: LAYANAN PERSURATAN & PIAGAM -->
                        <div class="rounded-lg border border-slate-800/80 bg-slate-950/40 p-2">
                            <div class="px-2 py-1">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Layanan Persuratan & Piagam</p>
                            </div>
                            <div class="mt-1 space-y-1">
                                <!-- Surat Masuk -->
                                <div class="flex items-center justify-between rounded-md transition-colors {{ request()->is('surat-masuk*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <a href="/surat-masuk" class="flex min-h-[44px] flex-1 items-center gap-2.5 px-3">
                                        <x-icon name="mail" class="h-4 w-4" />
                                        <span>Surat Masuk</span>
                                    </a>
                                    <a href="/surat-masuk/create" class="flex min-h-[44px] min-w-[40px] items-center justify-center text-slate-400 hover:text-emerald-400" title="Input Surat Masuk">
                                        <x-icon name="plus" class="h-4 w-4" />
                                    </a>
                                </div>

                                <!-- Surat Keluar -->
                                <div class="flex items-center justify-between rounded-md transition-colors {{ request()->is('surat-keluar*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <a href="/surat-keluar" class="flex min-h-[44px] flex-1 items-center gap-2.5 px-3">
                                        <x-icon name="send" class="h-4 w-4" />
                                        <span>Surat Keluar</span>
                                    </a>
                                    <a href="/surat-keluar/create" class="flex min-h-[44px] min-w-[40px] items-center justify-center text-slate-400 hover:text-emerald-400" title="Input Surat Keluar">
                                        <x-icon name="plus" class="h-4 w-4" />
                                    </a>
                                </div>

                                <!-- Rekap Pengambilan Piagam (Staff Log) -->
                                <a href="/piagam" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 transition-colors {{ request()->is('piagam*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <x-icon name="award" class="h-4 w-4" />
                                    <span>Rekap Pengambilan Piagam</span>
                                </a>
                            </div>
                        </div>

                        <!-- Pencarian & Laporan -->
                        <div>
                            <a href="/laporan" class="flex min-h-[44px] items-center gap-3 rounded-md px-3 font-medium transition-colors {{ request()->is('laporan*') ? 'bg-emerald-600 font-semibold text-white shadow-xs' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <x-icon name="file-text" class="h-5 w-5" />
                                <span>Pencarian & Laporan</span>
                            </a>
                        </div>

                        <!-- Master Data (Super Admin & Division Admin) -->
                        @if ($user->role === 'super_admin' || $user->role === 'division_admin')
                            <div class="pt-2">
                                <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Master Data</p>
                                <div class="mt-1 space-y-1">
                                    <a href="/master/divisi" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 font-medium transition-colors {{ request()->is('master/divisi*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                        <x-icon name="building" class="h-4 w-4" />
                                        <span>Data Divisi</span>
                                    </a>
                                    <a href="/master/pengguna" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 font-medium transition-colors {{ request()->is('master/pengguna*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                        <x-icon name="users" class="h-4 w-4" />
                                        <span>Data Pengguna</span>
                                    </a>
                                    <a href="/master/kategori" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 font-medium transition-colors {{ request()->is('master/kategori*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                        <x-icon name="settings" class="h-4 w-4" />
                                        <span>Kategori Referensi</span>
                                    </a>
                                    <a href="/master/sekolah" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 font-medium transition-colors {{ request()->is('master/sekolah*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                        <x-icon name="building" class="h-4 w-4" />
                                        <span>Data Sekolah</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </nav>
                </div>

                <!-- Sidebar Bottom: Portal Tamu Link -->
                <div class="border-t border-slate-800 p-3">
                    <a href="/" target="_blank" class="flex min-h-[44px] items-center justify-between rounded-md border border-emerald-700/50 bg-emerald-950/40 px-3 text-xs font-semibold text-emerald-400 hover:bg-emerald-900/50">
                        <span class="flex items-center gap-2">
                            <x-icon name="signature" class="h-4 w-4" />
                            Portal Tamu (Ambil Piagam)
                        </span>
                        <x-icon name="external-link" class="h-3.5 w-3.5" />
                    </a>
                </div>
            </aside>

            <!-- ============================================================ -->
            <!-- MOBILE DRAWER SIDEBAR                                        -->
            <!-- ============================================================ -->
            <div id="mobile-sidebar-drawer" class="fixed inset-0 z-50 hidden md:hidden" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" onclick="closeMobileSidebar()"></div>
                <div class="fixed inset-y-0 left-0 flex w-72 flex-col justify-between border-r border-slate-800 bg-slate-900 p-4 text-slate-200 shadow-xl">
                    <div class="flex flex-col">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-bold text-white">SISURAT</span>
                                <span class="rounded bg-emerald-950 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-400">v1.3</span>
                            </div>
                            <button type="button" onclick="closeMobileSidebar()" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md text-slate-400 hover:text-white" aria-label="Tutup menu">
                                <x-icon name="x" class="h-6 w-6" />
                            </button>
                        </div>

                        <nav class="mt-4 space-y-3 text-sm">
                            <a href="/dashboard" class="flex min-h-[44px] items-center gap-3 rounded-md px-3 font-medium {{ request()->is('dashboard') ? 'bg-emerald-600 font-semibold text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <x-icon name="home" class="h-5 w-5" /> Dashboard
                            </a>

                            <div class="rounded-lg border border-slate-800/80 bg-slate-950/40 p-2">
                                <p class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-400">Layanan Persuratan & Piagam</p>
                                <div class="mt-1 space-y-1">
                                    <div class="flex items-center justify-between rounded-md {{ request()->is('surat-masuk*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300' }}">
                                        <a href="/surat-masuk" class="flex min-h-[44px] flex-1 items-center gap-2.5 px-3">
                                            <x-icon name="mail" class="h-4 w-4" /> Surat Masuk
                                        </a>
                                        <a href="/surat-masuk/create" class="flex min-h-[44px] min-w-[40px] items-center justify-center text-slate-400 hover:text-emerald-400">+</a>
                                    </div>
                                    <div class="flex items-center justify-between rounded-md {{ request()->is('surat-keluar*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300' }}">
                                        <a href="/surat-keluar" class="flex min-h-[44px] flex-1 items-center gap-2.5 px-3">
                                            <x-icon name="send" class="h-4 w-4" /> Surat Keluar
                                        </a>
                                        <a href="/surat-keluar/create" class="flex min-h-[44px] min-w-[40px] items-center justify-center text-slate-400 hover:text-emerald-400">+</a>
                                    </div>
                                    <a href="/piagam" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 {{ request()->is('piagam*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300' }}">
                                        <x-icon name="award" class="h-4 w-4" /> Rekap Piagam
                                    </a>
                                </div>
                            </div>

                            <a href="/laporan" class="flex min-h-[44px] items-center gap-3 rounded-md px-3 font-medium {{ request()->is('laporan*') ? 'bg-emerald-600 font-semibold text-white' : 'text-slate-300' }}">
                                <x-icon name="file-text" class="h-5 w-5" /> Pencarian & Laporan
                            </a>

                            @if ($user->role === 'super_admin' || $user->role === 'division_admin')
                                <div class="pt-2 border-t border-slate-800">
                                    <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Master Data</p>
                                    <a href="/master/divisi" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 {{ request()->is('master/divisi*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300' }}">
                                        <x-icon name="building" class="h-4 w-4" /> Data Divisi
                                    </a>
                                    <a href="/master/pengguna" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 {{ request()->is('master/pengguna*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300' }}">
                                        <x-icon name="users" class="h-4 w-4" /> Data Pengguna
                                    </a>
                                    <a href="/master/kategori" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 {{ request()->is('master/kategori*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300' }}">
                                        <x-icon name="settings" class="h-4 w-4" /> Kategori
                                    </a>
                                    <a href="/master/sekolah" class="flex min-h-[44px] items-center gap-2.5 rounded-md px-3 {{ request()->is('master/sekolah*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300' }}">
                                        <x-icon name="building" class="h-4 w-4" /> Data Sekolah
                                    </a>
                                </div>
                            @endif
                        </nav>
                    </div>

                    <div class="border-t border-slate-800 pt-3">
                        <a href="/" target="_blank" class="flex min-h-[44px] items-center justify-between rounded-md border border-emerald-700/50 bg-emerald-950/40 px-3 text-xs font-semibold text-emerald-400">
                            <span>Portal Tamu (Ambil Piagam)</span>
                            <x-icon name="external-link" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- MAIN CONTENT WRAPPER                                         -->
            <!-- ============================================================ -->
            <div class="flex flex-1 flex-col min-w-0">
                <!-- ======================================================== -->
                <!-- TOP NAVBAR: RESTRICTED TO CONTEXT, PASSWORD & LOGOUT      -->
                <!-- ======================================================== -->
                <header class="flex min-h-[56px] items-center justify-between border-b border-slate-200 bg-white px-4 py-2 shadow-xs sm:px-6">
                    <!-- Left: Mobile Menu Toggle & Division Context -->
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="openMobileSidebar()" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-slate-300 text-slate-700 md:hidden" aria-label="Buka menu sidebar">
                            <x-icon name="menu" class="h-6 w-6" />
                        </button>

                        <!-- Division Switcher / Badge -->
                        @if ($user->role === 'super_admin')
                            <form method="post" action="/api/v1/session/switch-division" class="flex items-center gap-2">
                                @csrf
                                <span class="hidden text-xs font-semibold text-slate-500 sm:inline">Konteks Divisi:</span>
                                <select name="division_id" class="min-h-[44px] rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-800 focus:border-emerald-700 focus:outline-hidden" onchange="this.form.submit()">
                                    @foreach (\App\Models\Division::where('status', 'active')->orderBy('name')->get() as $div)
                                        <option value="{{ $div->id }}" @selected($currentDivision?->id === $div->id)>
                                            {{ $div->code }} — {{ $div->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <div class="flex items-center gap-1.5 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs font-bold text-emerald-800">
                                <span>{{ $user->division?->code ?? 'SEKRETARIAT' }}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="font-medium text-slate-700">{{ $user->division?->name ?? 'Dinas' }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Right: User Identity, Ganti Password, and Logout -->
                    <div class="flex items-center gap-2 sm:gap-3">
                        <!-- User Name & Role Pill -->
                        <div class="hidden text-right sm:block">
                            <p class="text-xs font-bold text-slate-900">{{ $user->name }}</p>
                            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ str_replace('_', ' ', $user->role) }}</p>
                        </div>

                        <!-- Ganti Password Link -->
                        <a href="/password/activate" class="flex min-h-[44px] items-center gap-1.5 rounded-md border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900" title="Ganti kata sandi akun">
                            <x-icon name="lock" class="h-4 w-4 text-slate-500" />
                            <span class="hidden sm:inline">Ganti Password</span>
                        </a>

                        <!-- Logout Button -->
                        <form method="post" action="/logout" class="inline">
                            @csrf
                            <button type="submit" class="flex min-h-[44px] items-center gap-1.5 rounded-md border border-rose-200 bg-rose-50 px-3 text-xs font-semibold text-rose-700 hover:bg-rose-100" aria-label="Keluar dari sistem" title="Keluar">
                                <x-icon name="log-out" class="h-4 w-4" />
                                <span class="hidden sm:inline">Logout</span>
                            </button>
                        </form>
                    </div>
                </header>

                <!-- Page Content Body -->
                <main class="flex-1 overflow-y-auto">
                    @if (session('success'))
                        <div class="mx-4 mt-4 flex items-center gap-2 rounded-md border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 md:mx-8">
                            <x-icon name="check" class="h-5 w-5 text-emerald-700" />
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mx-4 mt-4 flex items-center gap-2 rounded-md border border-rose-300 bg-rose-50 p-3 text-sm text-rose-900 md:mx-8">
                            <x-icon name="alert-triangle" class="h-5 w-5 text-rose-700" />
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <script>
            function openMobileSidebar() {
                document.getElementById('mobile-sidebar-drawer').classList.remove('hidden');
            }
            function closeMobileSidebar() {
                document.getElementById('mobile-sidebar-drawer').classList.add('hidden');
            }
        </script>
    @else
        <!-- Guest Viewport Layout (Login, Claim Piagam, etc.) -->
        <main class="min-h-screen">
            {{ $slot }}
        </main>
    @endauth
</body>
</html>
