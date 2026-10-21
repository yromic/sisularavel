<?php

namespace App\Services\Bulk;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class BulkDeleteResult implements Arrayable, JsonSerializable
{
    /**
     * @param  array<int, int|string>  $deleted
     * @param  array<int, array{id: int|string, label: string, reason: string}>  $skipped
     */
    public function __construct(
        public array $deleted = [],
        public array $skipped = [],
        public int $total = 0
    ) {
        if ($this->total === 0) {
            $this->total = count($this->deleted) + count($this->skipped);
        }
    }

    public function addDeleted(int|string $id): void
    {
        $this->deleted[] = $id;
        $this->total++;
    }

    public function addSkipped(int|string $id, string $label, string $reason): void
    {
        $this->skipped[] = [
            'id' => $id,
            'label' => $label,
            'reason' => $reason,
        ];
        $this->total++;
    }

    public function deletedCount(): int
    {
        return count($this->deleted);
    }

    public function skippedCount(): int
    {
        return count($this->skipped);
    }

    /**
     * @return array{success: bool, total: int, deleted_count: int, skipped_count: int, deleted: array<int, int|string>, skipped: array<int, array{id: int|string, label: string, reason: string}>}
     */
    public function toArray(): array
    {
        return [
            'success' => true,
            'total' => $this->total,
            'deleted_count' => $this->deletedCount(),
            'skipped_count' => $this->skippedCount(),
            'deleted' => $this->deleted,
            'skipped' => $this->skipped,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
