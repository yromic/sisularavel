<?php

namespace Tests\Feature\UI;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileFirstUiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_mobile_first_and_accessible(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('SISURAT')
            ->assertSee('name="username"', false)
            ->assertSee('autocapitalize="none"', false)
            ->assertSee('aria-label="Tampilkan password"', false)
            ->assertSee('min-h-[48px]', false)
            ->assertSee('<svg', false);
    }

    public function test_mobile_incoming_letter_create_form_has_touch_first_controls_and_duplicate_shield(): void
    {
        $operator = User::factory()->for(Division::factory())->create();

        $this->actingAs($operator)
            ->get('/surat-masuk/create')
            ->assertOk()
            ->assertSee('data-testid="incoming-letter-form"', false)
            ->assertSee('grid-cols-1', false)
            ->assertSee('type="date"', false)
            ->assertSee('accept="application/pdf,image/*,.doc,.docx"', false)
            ->assertSee('sticky bottom-0', false)
            ->assertSee('data-loading-label="Menyimpan..."', false)
            ->assertSee('crypto.randomUUID()', false)
            ->assertSee('Idempotency-Key', false);
    }

    public function test_incoming_letter_index_has_mobile_cards_desktop_grid_and_zero_hover_actions(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create();

        IncomingLetter::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'letter_number' => 'MOB-001',
            'origin_agency' => 'Dinas Pendidikan',
            'letter_date' => '2026-09-01',
            'received_date' => '2026-09-02',
            'subject' => 'Undangan koordinasi',
        ]);

        $this->actingAs($operator)
            ->get('/surat-masuk')
            ->assertOk()
            ->assertSee('data-testid="mobile-letter-card"', false)
            ->assertSee('data-testid="desktop-letter-grid"', false)
            ->assertSee('aria-label="Buka aksi surat MOB-001"', false)
            ->assertSee('min-h-[44px]', false)
            ->assertSee('No. Agenda')
            ->assertSee('Nomor Surat')
            ->assertSee('Tanggal Diterima')
            ->assertSee('Lampiran')
            ->assertSee('Aksi');
    }

    public function test_public_piagam_claim_screen_is_mobile_first_with_signature_canvas(): void
    {
        $this->get('/piagam/ambil')
            ->assertOk()
            ->assertSee('data-testid="piagam-claim-form"', false)
            ->assertSee('name="npsn"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('data-testid="signature-pad"', false)
            ->assertSee('min-h-[180px]', false)
            ->assertSee('Bersihkan Tanda Tangan');
    }
}
