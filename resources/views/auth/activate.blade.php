<x-layouts.app title="Aktivasi Kata Sandi">
    <section class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <span class="inline-block rounded-xs bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 uppercase">Aktivasi Akun</span>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">Perbarui Kata Sandi</h1>
                <p class="mt-2 text-sm text-slate-600">Akun Anda memerlukan penetapan kata sandi permanen baru sebelum dapat melanjutkan ke sistem SISURAT.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="/password/activate" class="space-y-4">
                @csrf
                <label class="block">
                    <span class="text-sm font-medium text-slate-800">Kata Sandi Baru *</span>
                    <input name="password" type="password" minlength="8" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Minimal 8 karakter" required>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-800">Ulangi Kata Sandi Baru *</span>
                    <input name="password_confirmation" type="password" minlength="8" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3 text-base focus:border-emerald-700 focus:outline-hidden" placeholder="Ulangi kata sandi di atas" required>
                </label>

                <button type="submit" class="min-h-[48px] w-full rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-hidden">
                    Aktifkan Kata Sandi & Lanjutkan
                </button>
            </form>
        </div>
    </section>
</x-layouts.app>
