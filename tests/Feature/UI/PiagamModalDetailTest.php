<?php

namespace Tests\Feature\UI;

use App\Models\Attachment;
use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\Piagam;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PiagamModalDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_piagam_index_renders_split_modal_component_and_triggers(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Ahmad Dahlan',
            'piagam_description' => 'Juara 1 OSN Matematika',
            'school_origin' => 'SMP Negeri 1 Ungaran',
            'recipient_name' => 'Budi Santoso',
            'recipient_position' => 'Guru Pendamping',
            'recipient_unit' => 'SMP Negeri 1 Ungaran',
            'pickup_category_name' => 'Diambil Langsung',
            'created_by_user_id' => $operator->id,
        ]);

        $this->actingAs($operator)
            ->get('/piagam')
            ->assertOk()
            ->assertSee('id="split-piagam-detail-modal"', false)
            ->assertSee('openPiagamDetailModal', false)
            ->assertSee('closePiagamDetailModal', false)
            ->assertSee('id="modal-piagam-btn-download"', false)
            ->assertSee('flex flex-col lg:flex-row', false)
            ->assertSee("openPiagamDetailModal({$piagam->id})", false);
    }

    public function test_staff_can_fetch_piagam_detail_json_within_same_division(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS', 'name' => 'Bidang Pendidikan Dasar']);
        $operator = User::factory()->for($division)->create(['role' => 'operator']);

        $category = CompetitionCategory::query()->create(['division_id' => $division->id, 'name' => 'FLS2N']);
        $pickup = PickupCategory::query()->create(['division_id' => $division->id, 'name' => 'Diambil Kolektif']);
        $school = School::query()->create(['name' => 'SD Negeri 2 Ungaran', 'npsn' => '20320001']);

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Siti Nurhaliza',
            'piagam_description' => 'Juara 2 Menyanyi Solo FLS2N',
            'school_id' => $school->id,
            'competition_category_id' => $category->id,
            'competition_year' => 2026,
            'pickup_category_id' => $pickup->id,
            'recipient_name' => 'Dra. Endang Purwanti',
            'recipient_position' => 'Kepala Sekolah',
            'recipient_unit' => 'SD Negeri 2 Ungaran',
            'created_by_user_id' => $operator->id,
        ]);

        $attachment = Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => Piagam::class,
            'attachable_id' => $piagam->id,
            'file_path' => 'signatures/dikdas/test_signature.png',
            'original_name' => 'tanda_tangan.png',
            'file_size_bytes' => 12048,
            'mime_type' => 'image/png',
            'file_hash' => hash('sha256', 'dummy_signature'),
            'is_missing' => false,
            'migration_status' => 'migrated',
            'created_by_user_id' => $operator->id,
        ]);

        $response = $this->actingAs($operator)
            ->getJson("/api/v1/piagam/{$piagam->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $piagam->id)
            ->assertJsonPath('data.division_code', 'DIKDAS')
            ->assertJsonPath('data.student_name', 'Siti Nurhaliza')
            ->assertJsonPath('data.recipient_name', 'Dra. Endang Purwanti')
            ->assertJsonPath('data.recipient_position', 'Kepala Sekolah')
            ->assertJsonPath('data.signature.uuid', $attachment->uuid)
            ->assertJsonPath('data.signature.download_url', "/api/v1/attachments/{$attachment->uuid}/download")
            ->assertJsonPath('data.signature.preview_url', "/api/v1/attachments/{$attachment->uuid}/preview");
    }

    public function test_staff_cannot_fetch_piagam_detail_from_another_division(): void
    {
        $divisionA = Division::factory()->create(['code' => 'DIKDAS']);
        $divisionB = Division::factory()->create(['code' => 'PAUD']);

        $operatorA = User::factory()->for($divisionA)->create(['role' => 'operator']);
        $operatorB = User::factory()->for($divisionB)->create(['role' => 'operator']);

        $piagamB = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $divisionB->id,
            'student_name' => 'Dewi Lestari',
            'piagam_description' => 'Lomba Mewarnai PAUD',
            'created_by_user_id' => $operatorB->id,
        ]);

        $this->actingAs($operatorA)
            ->getJson("/api/v1/piagam/{$piagamB->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ERR_403_DIVISI');
    }

    public function test_super_admin_can_fetch_piagam_detail_across_all_divisions(): void
    {
        $division = Division::factory()->create(['code' => 'DIKDAS']);
        $superAdmin = User::factory()->superAdmin()->create();

        $piagam = Piagam::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'student_name' => 'Bayu Skak',
            'piagam_description' => 'Lomba Cerdas Cermat',
        ]);

        $this->actingAs($superAdmin)
            ->getJson("/api/v1/piagam/{$piagam->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $piagam->id);
    }
}
