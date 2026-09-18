<x-layouts.app title="Login SISURAT">
    <section class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-sm font-semibold uppercase tracking-normal text-emerald-700">SISURAT</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Masuk ke arsip surat</h1>
                <p class="mt-2 text-sm text-slate-600">Gunakan akun resmi sesuai divisi kerja aktif.</p>
            </div>

            <form method="post" action="/login" class="space-y-4">
                @csrf
                <label class="block">
                    <span class="text-sm font-medium text-slate-800">Username</span>
                    <input name="username" autocomplete="username" autocapitalize="none" autocorrect="off" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-800">Password</span>
                    <span class="mt-1 flex min-h-[48px] items-center rounded-md border border-slate-300 focus-within:border-emerald-700 focus-within:ring-2 focus-within:ring-emerald-100">
                        <input id="password" name="password" type="password" autocomplete="current-password" class="min-h-[46px] flex-1 rounded-md border-0 px-3 text-base focus:outline-none">
                        <button type="button" class="flex min-h-[44px] min-w-[44px] items-center justify-center text-slate-700" aria-label="Tampilkan password" onclick="const input=document.getElementById('password'); input.type = input.type === 'password' ? 'text' : 'password';">
                            <x-icon name="eye" />
                        </button>
                    </span>
                </label>

                <button class="min-h-[48px] w-full rounded-md bg-emerald-700 px-4 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-emerald-300" type="submit">Masuk</button>
            </form>
        </div>
    </section>
</x-layouts.app>
