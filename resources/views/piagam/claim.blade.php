<x-layouts.app title="Ambil Piagam">
    <form data-testid="piagam-claim-form" method="post" action="/api/v1/piagam/ambil" class="grid min-h-screen grid-cols-1 gap-4 px-4 pb-28 pt-4" data-idempotent-form>
        @csrf
        <header>
            <p class="text-sm font-semibold text-emerald-700">Piagam</p>
            <h1 class="text-2xl font-semibold">Ambil Piagam</h1>
        </header>

        <label class="block">
            <span class="text-sm font-medium">NPSN Sekolah</span>
            <input name="npsn" inputmode="numeric" maxlength="8" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3">
        </label>

        <label class="block">
            <span class="text-sm font-medium">Nama Pengambil</span>
            <input name="recipient_name" class="mt-1 min-h-[48px] w-full rounded-md border border-slate-300 px-3">
        </label>

        <section>
            <div class="mb-2 flex items-center gap-2">
                <x-icon name="signature" class="h-5 w-5 text-slate-700" />
                <h2 class="font-semibold">Tanda Tangan</h2>
            </div>
            <canvas data-testid="signature-pad" class="min-h-[180px] w-full rounded-md border border-slate-300 bg-white"></canvas>
            <button type="button" class="mt-2 min-h-[44px] rounded-md border border-slate-300 px-4">Bersihkan Tanda Tangan</button>
        </section>
    </form>
</x-layouts.app>
