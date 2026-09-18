<?php

namespace Tests\Feature\Piagam;

use App\Models\Attachment;
use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\Piagam;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PiagamWorkflowContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_creates_piagam_in_own_division_and_lists_only_own_division(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $otherDivision = Division::factory()->create(['code' => 'PAUD']);
        $operator = User::factory()->for($division)->create();
        $school = School::factory()->create(['npsn' => '20320251']);
        $category = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'FLS2N']);
        $otherPiagam = $this->piagam($otherDivision, School::factory()->create(), CompetitionCategory::query()->create([
            'division_id' => $otherDivision->id,
            'name' => 'OSN',
        ]));

        $this->actingAs($operator)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam', [
                'student_name' => 'Siti Aminah',
                'npsn' => '20320251',
                'competition_category_id' => $category->id,
                'competition_year' => 2026,
            ])
            ->assertCreated()
            ->assertJsonPath('data.student_name', 'Siti Aminah')
            ->assertJsonPath('data.division_id', $division->id);

        $this->actingAs($operator)
            ->getJson('/api/v1/piagam')
            ->assertOk()
            ->assertJsonMissing(['id' => $otherPiagam->id]);

        $this->assertDatabaseHas('piagams', [
            'division_id' => $division->id,
            'school_id' => $school->id,
            'competition_category_id' => $category->id,
            'student_name' => 'Siti Aminah',
            'created_by_user_id' => $operator->id,
        ]);
    }

    public function test_public_claim_requires_valid_school_and_signature_then_stores_private_signature(): void
    {
        Storage::fake('private_signatures');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $school = School::factory()->create(['npsn' => '20320251']);
        $category = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'FLS2N']);
        $piagam = $this->piagam($division, $school, $category);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', [
                'piagam_id' => $piagam->id,
                'npsn' => '99999999',
                'recipient_name' => '',
                'recipient_position' => 'Guru',
                'recipient_unit' => 'SD Negeri 1',
                'signature_canvas' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['npsn', 'recipient_name', 'signature_canvas']);

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/piagam/ambil', [
                'piagam_id' => $piagam->id,
                'npsn' => '20320251',
                'recipient_name' => 'Budi Santoso',
                'recipient_position' => 'Guru',
                'recipient_unit' => 'SD Negeri 1',
                'signature_canvas' => $this->pngDataUrl(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.recipient_name', 'Budi Santoso')
            ->json();

        $attachment = Attachment::query()->where('attachable_type', Piagam::class)->where('attachable_id', $piagam->id)->firstOrFail();
        Storage::disk('private_signatures')->assertExists($attachment->file_path);
        $this->assertStringStartsWith('signatures/DIKDAS/', $attachment->file_path);
        $this->assertDatabaseHas('audit_logs', ['event_code' => 'PIAGAM_PUBLIC_SUBMITTED', 'auditable_id' => $piagam->id]);
        $this->assertSame($response['data']['recipient_unit'], 'SD Negeri 1');
    }

    public function test_public_claim_replays_identical_idempotent_response(): void
    {
        Storage::fake('private_signatures');

        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $school = School::factory()->create(['npsn' => '20320251']);
        $category = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'FLS2N']);
        $piagam = $this->piagam($division, $school, $category);
        $key = (string) Str::uuid();
        $payload = [
            'piagam_id' => $piagam->id,
            'npsn' => '20320251',
            'recipient_name' => 'Budi Santoso',
            'recipient_position' => 'Guru',
            'recipient_unit' => 'SD Negeri 1',
            'signature_canvas' => $this->pngDataUrl(),
        ];

        $first = $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/piagam/ambil', $payload)
            ->assertCreated()
            ->json();

        $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/piagam/ambil', $payload)
            ->assertCreated()
            ->assertExactJson($first);

        $this->assertDatabaseCount('attachments', 1);
    }

    private function piagam(Division $division, School $school, CompetitionCategory $category): Piagam
    {
        return Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Siti Aminah',
            'school_id' => $school->id,
            'competition_category_id' => $category->id,
            'competition_year' => 2026,
            'created_by_user_id' => null,
        ]);
    }

    private function pngDataUrl(): string
    {
        return 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
    }
}
