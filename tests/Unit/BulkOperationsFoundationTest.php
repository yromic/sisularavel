<?php

namespace Tests\Unit;

use App\Http\Requests\BulkDeleteRequest;
use App\Models\User;
use App\Services\Bulk\BulkDeleteResult;
use App\Services\Bulk\BulkDeleteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class BulkOperationsFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_delete_result_dto_counts_and_serializes_properly(): void
    {
        $result = new BulkDeleteResult;
        $result->addDeleted(1);
        $result->addDeleted(2);
        $result->addSkipped(3, 'SMP 1', 'Memiliki piagam terkait');

        $this->assertEquals(2, $result->deletedCount());
        $this->assertEquals(1, $result->skippedCount());
        $this->assertEquals(3, $result->total);

        $array = $result->toArray();
        $this->assertTrue($array['success']);
        $this->assertEquals(2, $array['deleted_count']);
        $this->assertEquals(1, $array['skipped_count']);
        $this->assertEquals([1, 2], $array['deleted']);
        $this->assertEquals([['id' => 3, 'label' => 'SMP 1', 'reason' => 'Memiliki piagam terkait']], $array['skipped']);
    }

    public function test_bulk_delete_request_validates_ids_limits_and_uniqueness(): void
    {
        $request = new BulkDeleteRequest;
        $rules = $request->rules();

        // Valid IDs
        $v1 = Validator::make(['ids' => [1, 2, 3]], $rules);
        $this->assertFalse($v1->fails());

        // Empty IDs without filter
        $v2 = Validator::make(['ids' => []], $rules);
        $this->assertTrue($v2->fails());

        // Duplicate IDs
        $v3 = Validator::make(['ids' => [1, 2, 2]], $rules);
        $this->assertTrue($v3->fails());

        // Over limit (201 items)
        $overLimit = range(1, 201);
        $v4 = Validator::make(['ids' => $overLimit], $rules);
        $this->assertTrue($v4->fails());
    }

    public function test_bulk_delete_request_enforces_filter_and_expected_count(): void
    {
        $request = new BulkDeleteRequest;
        $rules = $request->rules();

        // Filter without expected_count
        $v1 = Validator::make(['filter' => ['status' => 'active']], $rules);
        $this->assertTrue($v1->fails());

        // Filter with valid expected_count
        $v2 = Validator::make(['filter' => ['status' => 'active'], 'expected_count' => 5], $rules);
        $this->assertFalse($v2->fails());
    }

    public function test_bulk_delete_service_detects_filter_count_mismatch(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $service = new class extends BulkDeleteService {};

        $request = new BulkDeleteRequest;
        $request->merge([
            'filter' => ['role' => 'operator'],
            'expected_count' => 999, // Mismatched!
        ]);

        $this->expectException(ConflictHttpException::class);
        $service->resolveTargetIds($request, User::query());
    }

    public function test_bulk_delete_service_resolves_matching_filter_and_explicit_ids(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $service = new class extends BulkDeleteService {};

        // Explicit IDs
        $req1 = new BulkDeleteRequest;
        $req1->merge(['ids' => [$user1->id, $user2->id]]);
        $resolvedIds = $service->resolveTargetIds($req1, User::query());
        $this->assertEquals([$user1->id, $user2->id], $resolvedIds);

        // Filter with matching count
        $count = User::query()->count();
        $req2 = new BulkDeleteRequest;
        $req2->merge([
            'filter' => ['all' => 1],
            'expected_count' => $count,
        ]);
        $resolvedFiltered = $service->resolveTargetIds($req2, User::query());
        $this->assertCount($count, $resolvedFiltered);
    }
}
