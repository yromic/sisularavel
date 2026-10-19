<x-layouts.app title="Login SISURAT">
    <section class="relative min-h-screen flex flex-col items-center justify-center px-4 py-8 overflow-hidden bg-slate-950">
        <!-- Background Image (Gedung Dinas) -->
        <div class="fixed inset-0 pointer-events-none"
             style="background-image: url('{{ asset('images/bg-hero.jpg') }}'); background-repeat: no-repeat; background-size: cover; background-position: center center;">
        </div>

        <!-- Lapisan Gelap Redup (Dark Dimming Overlay) -->
        <div class="fixed inset-0 pointer-events-none"
             style="background: linear-gradient(180deg, rgba(15, 23, 42, 0.90) 0%, rgba(15, 23, 42, 0.80) 50%, rgba(15, 23, 42, 0.95) 100%); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);">
        </div>

        <!-- Kontainer Form Login -->
        <div class="relative z-10 w-full max-w-md">
            <!-- Navigasi Kembali ke Beranda -->
            <div class="mb-4">
                <a href="/" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold text-slate-200 backdrop-blur-sm border border-white/10 hover:bg-white/20 hover:text-white transition-colors">
                    <x-icon name="arrow-left" class="h-3.5 w-3.5 text-emerald-400" />
                    <span>Kembali ke Beranda</span>
                </a>
            </div>

            <!-- Card Utama Form Login -->
            <div class="rounded-2xl border border-white/20 bg-white/95 p-6 sm:p-8 shadow-2xl backdrop-blur-md">
                <div class="mb-6">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/icon/icon.svg') }}" alt="Logo SISURAT" class="h-10 w-10 shrink-0 object-contain drop-shadow-xs" />
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">SISURAT</span>
                            <p class="text-[11px] text-slate-500">Disdikbudpora Kab. Semarang</p>
                        </div>
                    </div>
                    <h1 class="mt-4 text-2xl font-bold text-slate-900 tracking-tight">Masuk ke Arsip Surat</h1>
                    <p class="mt-1 text-sm text-slate-600 leading-relaxed">Gunakan akun resmi sesuai divisi kerja aktif Anda.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-300 bg-rose-50 p-3.5 text-sm text-rose-800" role="alert">
                        <div class="flex items-center gap-2">
                            <x-icon name="alert-triangle" class="h-4 w-4 shrink-0 text-rose-600" />
                            <p class="font-medium">{{ $errors->first() }}</p>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-xl border border-rose-300 bg-rose-50 p-3.5 text-sm text-rose-800" role="alert">
                        <div class="flex items-center gap-2">
                            <x-icon name="alert-triangle" class="h-4 w-4 shrink-0 text-rose-600" />
                            <p class="font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 p-3.5 text-sm text-emerald-800" role="status">
                        <div class="flex items-center gap-2">
                            <x-icon name="check" class="h-4 w-4 shrink-0 text-emerald-600" />
                            <p class="font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                <form method="post" action="/login" class="space-y-4">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-800">Username</span>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <x-icon name="user" class="h-4 w-4" />
                            </div>
                            <input name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" autocorrect="off" placeholder="Masukkan username petugas" class="min-h-[48px] w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3.5 text-base text-slate-900 focus:border-emerald-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-100 transition-colors" required>
                        </div>
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-800">Password</span>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <x-icon name="lock" class="h-4 w-4" />
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" class="min-h-[48px] w-full rounded-xl border border-slate-300 bg-white pl-10 pr-12 text-base text-slate-900 focus:border-emerald-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-100 transition-colors" required>
                            <button type="button" class="absolute inset-y-0 right-0 flex min-h-[44px] min-w-[44px] items-center justify-center text-slate-400 hover:text-slate-700 transition-colors" aria-label="Tampilkan password" onclick="const input=document.getElementById('password'); input.type = input.type === 'password' ? 'text' : 'password';">
                                <x-icon name="eye" class="h-4 w-4" />
                            </button>
                        </div>
                    </label>

                    <button class="mt-2 flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 font-bold text-white shadow-md shadow-emerald-950/20 hover:bg-emerald-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-300 transition-all" type="submit">
                        <x-icon name="key" class="h-4 w-4 text-emerald-200" />
                        <span>Masuk</span>
                    </button>
                </form>

                <div class="mt-5 pt-4 border-t border-slate-100 flex items-start gap-2.5 text-xs text-slate-500">
                    <x-icon name="info" class="h-4 w-4 shrink-0 text-slate-400 mt-0.5" />
                    <span>Lupa kata sandi atau kendala akun? Hubungi Administrator SIM Disdikbudpora Kab. Semarang untuk bantuan akses.</span>
                </div>
            </div>

            <!-- Footer Hak Cipta Ringkas -->
            <p class="mt-6 text-center text-xs text-slate-400 drop-shadow-xs">
                &copy; {{ date('Y') }} Disdikbudpora Kab. Semarang. Seluruh hak cipta dilindungi.
            </p>
        </div>
    </section>
</x-layouts.app>
