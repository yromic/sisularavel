<?php

namespace App\Services\Bulk;

use App\Http\Requests\BulkDeleteRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

abstract class BulkDeleteService
{
    /**
     * Resolve target IDs from either an explicit list of IDs or dynamic filter query.
     * If expected_count mismatch occurs, throws a 409 Conflict to protect against stale UI state.
     *
     * @return array<int, int>
     */
    public function resolveTargetIds(BulkDeleteRequest $request, Builder $filterQuery): array
    {
        if ($request->filled('ids')) {
            /** @var array<int, int> $ids */
            $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids'))));

            return $ids;
        }

        $expectedCount = (int) $request->input('expected_count');
        $actualCount = (clone $filterQuery)->count();

        if ($actualCount !== $expectedCount) {
            throw new ConflictHttpException(
                "Jumlah data pada filter telah berubah di server (ditemukan: {$actualCount}, dikirim: {$expectedCount}). Silakan muat ulang halaman sebelum melanjutkan tindakan massal."
            );
        }

        /** @var array<int, int> $ids */
        $ids = $filterQuery->pluck($filterQuery->getModel()->getQualifiedKeyName())
            ->map(fn ($id) => (int) $id)
            ->all();

        return $ids;
    }

    /**
     * Chunked execution within DB transaction to guarantee ACID integrity per chunk.
     *
     * @param  array<int, int>  $ids
     * @param  callable(array<int, int>): void  $callback
     */
    public function processChunks(array $ids, callable $callback): void
    {
        $chunkSize = (int) config('bulk.chunk_size', 50);

        foreach (array_chunk($ids, $chunkSize) as $chunk) {
            DB::transaction(function () use ($chunk, $callback): void {
                $callback($chunk);
            });
        }
    }
}
