<?php

namespace Tests\Feature\Database;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\IdempotencyKey;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_model_classes_exist(): void
    {
        foreach ($this->contractModels() as $modelClass) {
            $this->assertTrue(class_exists($modelClass), "Expected model [{$modelClass}] to exist.");
        }
    }

    public function test_soft_delete_models_match_contract(): void
    {
        $softDeletingModels = [
            Division::class,
            User::class,
            IncomingLetter::class,
            OutgoingLetter::class,
            Piagam::class,
            Attachment::class,
        ];

        foreach ($softDeletingModels as $modelClass) {
            $this->assertContains(
                SoftDeletes::class,
                class_uses_recursive($modelClass),
                "Expected [{$modelClass}] to use SoftDeletes."
            );
        }
    }

    public function test_user_factory_creates_division_scoped_operator(): void
    {
        $division = Division::factory()->create();
        $user = User::factory()->for($division)->create();

        $this->assertSame('operator', $user->role);
        $this->assertFalse($user->must_reset_password);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->division()->is($division));
    }

    /**
     * @return list<class-string>
     */
    private function contractModels(): array
    {
        return [
            Division::class,
            User::class,
            School::class,
            IncomingLetter::class,
            OutgoingLetter::class,
            Piagam::class,
            Attachment::class,
            PickupCategory::class,
            CompetitionCategory::class,
            Setting::class,
            AuditLog::class,
            IdempotencyKey::class,
        ];
    }
}
