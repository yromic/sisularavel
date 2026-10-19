<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SISURAT' }} — Disdikbudpora Kab. Semarang</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon/icon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F7F8FA] text-[#17243A] antialiased font-sans">
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
            <aside class="hidden w-64 shrink-0 flex-col justify-between border-r border-[#23344E] bg-[#17243A] text-slate-200 md:flex" aria-label="Navigasi Utama Desktop">
                <div class="flex flex-col">
                    <!-- Brand Header -->
                    <div class="flex items-center gap-3 border-b border-[#23344E] px-5 py-4">
                        <img src="{{ asset('images/icon/icon.svg') }}" alt="Logo SISURAT" class="h-10 w-10 shrink-0 object-contain drop-shadow-xs" />
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-base font-bold tracking-tight text-white">SISURAT</span>
                                <span class="rounded bg-teal-950/80 px-1.5 py-0.5 text-[10px] font-semibold text-teal-300 border border-teal-800/80">v1.3</span>
                            </div>
                            <p class="truncate text-[11px] text-slate-400">Disdikbudpora Kab. Semarang</p>
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <nav class="space-y-4 px-3 py-4 text-sm" aria-label="Menu Utama">
                        <!-- Dashboard Link -->
                        <div>
                            <a href="/dashboard" 
                               class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400 {{ request()->is('dashboard') ? 'bg-[#147D73] font-semibold text-white shadow-xs' : 'text-slate-300 hover:bg-[#23344E]/60 hover:text-white' }}">
                                <x-icon name="home" class="h-5 w-5 shrink-0" />
                                <span>Dashboard</span>
                            </a>
                        </div>

                        <!-- Pencarian Pintar (Fitur Utama Tepat di Bawah Dashboard) -->
                        <div>
                            <a href="/laporan" 
                               class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400 {{ request()->is('laporan*') || request()->is('pencarian*') ? 'bg-[#147D73] font-semibold text-white shadow-xs' : 'text-slate-200 hover:bg-[#23344E]/60 hover:text-white' }}">
                                <x-icon name="search" class="h-5 w-5 shrink-0 text-teal-400" />
                                <span class="font-semibold">Pencarian Pintar</span>
                                <span class="ml-auto rounded bg-emerald-500/20 px-1.5 py-0.5 text-[9px] font-bold text-teal-300 uppercase">Utama</span>
                            </a>
                        </div>

                        <!-- 1. PERSURATAN & PIAGAM (Required group for tests & operations) -->
                        <div class="space-y-1">
                            <div class="px-3 pt-2 pb-1">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Layanan Persuratan & Piagam</p>
                            </div>
                            <div class="space-y-0.5">
                                <!-- Persuratan & Piagam (Konsol Terpadu Cepat) -->
                                <a href="/persuratan" 
                                   class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400 {{ request()->is('persuratan*') ? 'bg-[#147D73] font-semibold text-white shadow-xs' : 'text-slate-300 hover:bg-[#23344E]/60 hover:text-white' }}">
                                    <x-icon name="layers" class="h-4 w-4 shrink-0 text-teal-400" />
                                    <span>Persuratan & Piagam</span>
                                    <span class="ml-auto rounded bg-teal-500/20 px-1.5 py-0.5 text-[9px] font-bold text-teal-300 uppercase">Terpadu</span>
                                </a>
                            </div>
                        </div>

                        <!-- 3. MASTER DATA (Super Admin & Division Admin) -->
                        @if ($user->role === 'super_admin' || $user->role === 'division_admin')
                            <div class="space-y-1 pt-2">
                                <div class="px-3 pb-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Master Data</p>
                                </div>
                                <div class="space-y-0.5">
                                    <a href="/master/divisi" 
                                       class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400 {{ request()->is('master/divisi*') ? 'bg-[#147D73]/25 font-semibold text-teal-300 border-l-2 border-teal-400' : 'text-slate-300 hover:bg-[#23344E]/60 hover:text-white' }}">
                                        <x-icon name="building" class="h-4 w-4 shrink-0 text-slate-400" />
                                        <span>Data Divisi</span>
                                    </a>
                                    <a href="/master/pengguna" 
                                       class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400 {{ request()->is('master/pengguna*') ? 'bg-[#147D73]/25 font-semibold text-teal-300 border-l-2 border-teal-400' : 'text-slate-300 hover:bg-[#23344E]/60 hover:text-white' }}">
                                        <x-icon name="users" class="h-4 w-4 shrink-0 text-slate-400" />
                                        <span>Data Pengguna</span>
                                    </a>
                                    <a href="/master/kategori" 
                                       class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400 {{ request()->is('master/kategori*') ? 'bg-[#147D73]/25 font-semibold text-teal-300 border-l-2 border-teal-400' : 'text-slate-300 hover:bg-[#23344E]/60 hover:text-white' }}">
                                        <x-icon name="settings" class="h-4 w-4 shrink-0 text-slate-400" />
                                        <span>Kategori Referensi</span>
                                    </a>
                                    <a href="/master/sekolah" 
                                       class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400 {{ request()->is('master/sekolah*') ? 'bg-[#147D73]/25 font-semibold text-teal-300 border-l-2 border-teal-400' : 'text-slate-300 hover:bg-[#23344E]/60 hover:text-white' }}">
                                        <x-icon name="building" class="h-4 w-4 shrink-0 text-slate-400" />
                                        <span>Data Sekolah</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- 4. SISTEM & PEMELIHARAN (Super Admin Only) -->
                        @if ($user->role === 'super_admin')
                            <div class="space-y-1 pt-2">
                                <div class="px-3 pb-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sistem & Pemulihan</p>
                                </div>
                                <div class="space-y-0.5">
                                    <a href="/admin/backups" 
                                       class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400 {{ request()->is('admin/backups*') ? 'bg-[#147D73]/25 font-semibold text-teal-300 border-l-2 border-teal-400' : 'text-slate-300 hover:bg-[#23344E]/60 hover:text-white' }}">
                                        <x-icon name="database" class="h-4 w-4 shrink-0 text-slate-400" />
                                        <span>Backup & Pemulihan</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </nav>
                </div>

                <!-- Sidebar Bottom: Portal Tamu Utility -->
                <div class="border-t border-[#23344E] p-3">
                    <a href="/" target="_blank" class="flex min-h-[44px] items-center justify-between rounded-lg border border-[#23344E] bg-[#0F1725]/60 px-3 text-xs font-semibold text-slate-300 hover:bg-[#23344E] hover:text-white transition-colors">
                        <span class="flex items-center gap-2">
                            <x-icon name="signature" class="h-4 w-4 text-teal-400" />
                            Portal Tamu (Ambil Piagam)
                        </span>
                        <x-icon name="external-link" class="h-3.5 w-3.5 text-slate-400" />
                    </a>
                </div>
            </aside>

            <!-- ============================================================ -->
            <!-- ACCESSIBLE MOBILE DRAWER NAVIGATION                          -->
            <!-- ============================================================ -->
            <div id="mobile-sidebar-drawer" 
                 class="fixed inset-0 z-50 hidden md:hidden" 
                 role="dialog" 
                 aria-modal="true" 
                 aria-label="Navigasi Utama Mobile">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-[#0F1725]/75 backdrop-blur-xs transition-opacity" 
                     onclick="closeMobileSidebar()"></div>

                <!-- Drawer Panel -->
                <div class="fixed inset-y-0 left-0 flex w-72 flex-col justify-between border-r border-[#23344E] bg-[#17243A] p-4 text-slate-200 shadow-2xl">
                    <div class="flex flex-col">
                        <div class="flex items-center justify-between border-b border-[#23344E] pb-3">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ asset('images/icon/icon.svg') }}" alt="Logo SISURAT" class="h-8 w-8 shrink-0 object-contain drop-shadow-xs" />
                                <div>
                                    <span class="text-base font-bold text-white">SISURAT</span>
                                    <span class="ml-1 rounded bg-teal-950 px-1.5 py-0.2 text-[10px] font-semibold text-teal-300">v1.3</span>
                                </div>
                            </div>
                            <button type="button" 
                                    id="mobile-close-drawer-btn"
                                    onclick="closeMobileSidebar()" 
                                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-slate-400 hover:bg-[#23344E] hover:text-white focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-400" 
                                    aria-label="Tutup menu">
                                <x-icon name="x" class="h-6 w-6" />
                            </button>
                        </div>

                        <nav class="mt-4 space-y-3 text-sm" aria-label="Menu Mobile">
                            <a href="/dashboard" class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium {{ request()->is('dashboard') ? 'bg-[#147D73] font-semibold text-white' : 'text-slate-300 hover:bg-[#23344E]' }}">
                                <x-icon name="home" class="h-5 w-5" /> Dashboard
                            </a>

                            <div class="space-y-1">
                                <p class="px-3 pt-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Layanan Persuratan & Piagam</p>
                                <div class="space-y-0.5">
                                    <a href="/persuratan" class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium {{ request()->is('persuratan*') ? 'bg-[#147D73] font-semibold text-white' : 'text-slate-300 hover:bg-[#23344E]' }}">
                                        <x-icon name="layers" class="h-4 w-4 text-teal-400" />
                                        <span>Persuratan & Piagam</span>
                                        <span class="ml-auto rounded bg-teal-500/20 px-1.5 py-0.5 text-[9px] font-bold text-teal-300 uppercase">Terpadu</span>
                                    </a>
                                </div>
                            </div>

                            <a href="/laporan" class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium {{ request()->is('laporan*') ? 'bg-[#147D73] font-semibold text-white' : 'text-slate-300' }}">
                                <x-icon name="file-text" class="h-5 w-5" /> Pencarian & Laporan
                            </a>

                            @if ($user->role === 'super_admin' || $user->role === 'division_admin')
                                <div class="pt-2 border-t border-[#23344E] space-y-1">
                                    <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Master Data</p>
                                    <div class="space-y-0.5">
                                        <a href="/master/divisi" class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium {{ request()->is('master/divisi*') ? 'bg-[#147D73]/25 text-teal-300 font-semibold' : 'text-slate-300' }}">
                                            <x-icon name="building" class="h-4 w-4" /> Data Divisi
                                        </a>
                                        <a href="/master/pengguna" class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium {{ request()->is('master/pengguna*') ? 'bg-[#147D73]/25 text-teal-300 font-semibold' : 'text-slate-300' }}">
                                            <x-icon name="users" class="h-4 w-4" /> Data Pengguna
                                        </a>
                                        <a href="/master/kategori" class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium {{ request()->is('master/kategori*') ? 'bg-[#147D73]/25 text-teal-300 font-semibold' : 'text-slate-300' }}">
                                            <x-icon name="settings" class="h-4 w-4" /> Kategori Referensi
                                        </a>
                                        <a href="/master/sekolah" class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium {{ request()->is('master/sekolah*') ? 'bg-[#147D73]/25 text-teal-300 font-semibold' : 'text-slate-300' }}">
                                            <x-icon name="building" class="h-4 w-4" /> Data Sekolah
                                        </a>
                                    </div>
                                </div>
                            @endif

                            @if ($user->role === 'super_admin')
                                <div class="pt-2 border-t border-[#23344E] space-y-1">
                                    <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Sistem & Pemulihan</p>
                                    <div class="space-y-0.5">
                                        <a href="/admin/backups" class="flex min-h-[44px] items-center gap-3 rounded-lg px-3 font-medium {{ request()->is('admin/backups*') ? 'bg-[#147D73]/25 text-teal-300 font-semibold' : 'text-slate-300' }}">
                                            <x-icon name="database" class="h-4 w-4" /> Backup & Pemulihan
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </nav>
                    </div>

                    <div class="border-t border-[#23344E] pt-3">
                        <a href="/" target="_blank" class="flex min-h-[44px] items-center justify-between rounded-lg border border-[#23344E] bg-[#0F1725]/60 px-3 text-xs font-semibold text-slate-300">
                            <span>Portal Tamu (Ambil Piagam)</span>
                            <x-icon name="external-link" class="h-3.5 w-3.5 text-slate-400" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- MAIN CONTENT AREA                                            -->
            <!-- ============================================================ -->
            <div class="flex flex-1 flex-col min-w-0">
                <!-- ======================================================== -->
                <!-- REFINED INSTITUTIONAL TOPBAR                             -->
                <!-- ======================================================== -->
                <header class="flex min-h-[58px] items-center justify-between border-b border-[#E3E8EE] bg-white px-4 py-2 shadow-2xs sm:px-6">
                    <!-- Left: Mobile Toggle & Division Context -->
                    <div class="flex items-center gap-2 sm:gap-4 min-w-0 flex-1 mr-2">
                        <button type="button" 
                                id="mobile-sidebar-toggle-btn"
                                onclick="openMobileSidebar()" 
                                class="flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center rounded-lg border border-[#E3E8EE] bg-white text-slate-700 hover:bg-slate-50 md:hidden focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700" 
                                aria-label="Buka menu navigasi"
                                aria-expanded="false"
                                aria-controls="mobile-sidebar-drawer">
                            <x-icon name="menu" class="h-5 w-5" />
                        </button>

                        <!-- Division Context Switcher / Pill -->
                        @if ($user->role === 'super_admin')
                            <form method="post" action="/api/v1/session/switch-division" class="flex items-center gap-1.5 sm:gap-2 min-w-0 max-w-full">
                                @csrf
                                <div class="hidden sm:flex items-center gap-1.5 text-xs font-semibold text-[#657184] shrink-0">
                                    <x-icon name="building" class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Divisi:</span>
                                </div>
                                <select name="division_id" 
                                        aria-label="Pilih Konteks Divisi"
                                        class="min-h-[40px] w-full max-w-[135px] sm:max-w-[210px] md:max-w-xs truncate rounded-lg border border-[#E3E8EE] bg-[#F7F8FA] px-2.5 py-1 text-xs font-semibold text-[#17243A] focus:border-[#147D73] focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-[#147D73] transition-colors" 
                                        onchange="this.form.submit()">
                                    <option value="" @selected(session('active_division_id') === null)>
                                        Semua Divisi (Global)
                                    </option>
                                    @foreach (\App\Models\Division::where('status', 'active')->orderBy('name')->get() as $div)
                                        <option value="{{ $div->id }}" @selected(session('active_division_id') == $div->id)>
                                            {{ $div->code }} — {{ $div->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <div class="flex items-center gap-1.5 sm:gap-2 rounded-lg bg-[#EBF7F6] border border-[#9DD8D2] px-2.5 sm:px-3 py-1.5 text-xs font-bold text-[#0F635B] min-w-0 truncate">
                                <x-icon name="building" class="h-3.5 w-3.5 text-[#147D73] shrink-0" />
                                <span class="shrink-0">{{ $user->division?->code ?? 'SEKRETARIAT' }}</span>
                                <span class="hidden sm:inline text-teal-400 shrink-0">&bull;</span>
                                <span class="hidden sm:inline font-medium text-slate-700 truncate">{{ $user->division?->name ?? 'Dinas' }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Right: User Identity & Account Actions -->
                    <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                        <!-- User Name & Role -->
                        <div class="hidden text-right sm:block">
                            <p class="text-xs font-bold text-[#17243A]">{{ $user->name }}</p>
                            <p class="text-[11px] font-semibold text-[#657184] uppercase tracking-wider">{{ str_replace('_', ' ', $user->role) }}</p>
                        </div>

                        <!-- Ganti Password -->
                        <a href="/password/activate" 
                           class="flex min-h-[40px] items-center gap-1.5 rounded-lg border border-[#E3E8EE] bg-white px-2.5 sm:px-3 text-xs font-semibold text-[#17243A] hover:bg-[#F7F8FA] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-teal-700 transition-colors" 
                           title="Ganti kata sandi akun">
                            <x-icon name="lock" class="h-3.5 w-3.5 text-slate-500" />
                            <span class="hidden sm:inline">Ganti Password</span>
                        </a>

                        <!-- Logout Button -->
                        <form method="post" action="/logout" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="flex min-h-[40px] items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 sm:px-3 text-xs font-semibold text-rose-700 hover:bg-rose-100 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-rose-500 transition-colors" 
                                    aria-label="Keluar dari sistem" 
                                    title="Logout">
                                <x-icon name="log-out" class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">Logout</span>
                            </button>
                        </form>
                    </div>
                </header>

                <!-- Page Content Body -->
                <main class="flex-1 overflow-y-auto">
                    @if (session('success'))
                        <div class="mx-4 mt-4 flex items-center gap-2.5 rounded-lg border border-teal-200 bg-teal-50 p-3.5 text-sm font-medium text-teal-900 md:mx-8 shadow-2xs" role="alert">
                            <x-icon name="check" class="h-5 w-5 shrink-0 text-teal-700" />
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mx-4 mt-4 flex items-center gap-2.5 rounded-lg border border-rose-200 bg-rose-50 p-3.5 text-sm font-medium text-rose-900 md:mx-8 shadow-2xs" role="alert">
                            <x-icon name="alert-triangle" class="h-5 w-5 shrink-0 text-rose-700" />
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Dynamic Client-Side Toast Container (UX-008) -->
        <div id="sisurat-client-toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none" aria-live="polite"></div>

        <!-- Accessible Drawer & Toast Management Script -->
        <script>
            function showToast(message, type = 'success') {
                const container = document.getElementById('sisurat-client-toast-container');
                if (!container) return;
                const toast = document.createElement('div');
                toast.className = `pointer-events-auto flex items-center gap-2.5 rounded-lg border p-3.5 text-xs font-semibold shadow-lg transition-all transform duration-300 translate-y-2 opacity-0 ${
                    type === 'success' 
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-950' 
                        : 'border-rose-300 bg-rose-50 text-rose-950'
                }`;
                toast.innerHTML = `
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full ${type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'} text-[10px] font-bold">
                        ${type === 'success' ? '✓' : '!'}
                    </span>
                    <span class="flex-1 leading-snug">${message}</span>
                    <button type="button" class="text-slate-400 hover:text-slate-700 ml-1 text-base leading-none" aria-label="Tutup notifikasi">&times;</button>
                `;
                toast.querySelector('button').onclick = () => toast.remove();
                container.appendChild(toast);
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-y-2', 'opacity-0');
                });
                setTimeout(() => {
                    toast.classList.add('opacity-0', 'translate-y-2');
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            }

            window.showToast = showToast;

            // Global Export Button Loading Feedback (UX-016)
            window.handleExportClick = function(el, loadingText = 'Menyiapkan Unduhan...') {
                if (!el || el.dataset.exporting === 'true') return;
                el.dataset.exporting = 'true';
                const originalHtml = el.innerHTML;
                el.classList.add('opacity-75', 'pointer-events-none');
                el.innerHTML = `
                    <svg class="animate-spin h-4 w-4 text-current shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>${loadingText}</span>
                `;
                setTimeout(() => {
                    el.innerHTML = originalHtml;
                    el.classList.remove('opacity-75', 'pointer-events-none');
                    delete el.dataset.exporting;
                }, 2500);
            };

            // Mobile Viewport Keyboard Scroll Helper (UX-019)
            document.addEventListener('focusin', function(e) {
                if (window.innerWidth <= 768 && e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA')) {
                    const modal = e.target.closest('[role="dialog"], [id*="modal"]');
                    if (modal) {
                        setTimeout(() => {
                            e.target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 300);
                    }
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                const storedToast = sessionStorage.getItem('sisurat_toast');
                if (storedToast) {
                    try {
                        const parsed = JSON.parse(storedToast);
                        showToast(parsed.message, parsed.type || 'success');
                    } catch (e) {
                        showToast(storedToast, 'success');
                    }
                    sessionStorage.removeItem('sisurat_toast');
                }
            });

            function openMobileSidebar() {
                const drawer = document.getElementById('mobile-sidebar-drawer');
                const triggerBtn = document.getElementById('mobile-sidebar-toggle-btn');
                drawer.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                triggerBtn.setAttribute('aria-expanded', 'true');
                
                // Focus on close button inside drawer
                const closeBtn = document.getElementById('mobile-close-drawer-btn');
                if (closeBtn) closeBtn.focus();
            }

            function closeMobileSidebar() {
                const drawer = document.getElementById('mobile-sidebar-drawer');
                const triggerBtn = document.getElementById('mobile-sidebar-toggle-btn');
                drawer.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                triggerBtn.setAttribute('aria-expanded', 'false');
                
                // Restore focus to triggering control
                if (triggerBtn) triggerBtn.focus();
            }

            // Close on Escape key press
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const drawer = document.getElementById('mobile-sidebar-drawer');
                    if (drawer && !drawer.classList.contains('hidden')) {
                        closeMobileSidebar();
                    }
                }
            });
        </script>

        <x-modal.confirm />
    @else
        <!-- Guest Viewport Layout (Login, Claim Piagam, etc.) -->
        <main class="min-h-screen bg-[#F7F8FA]">
            {{ $slot }}
        </main>
    @endauth
</body>
</html>
