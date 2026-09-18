<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;

class PruneExpiredIdempotencyKeys extends Command
{
    protected $signature = 'sisurat:prune-idempotency';

    protected $description = 'Prune expired SISURAT idempotency keys for shared-hosting maintenance.';

    public function handle(): int
    {
        $deleted = IdempotencyKey::query()
            ->where('expires_at', '<', now())
            ->delete();

        $this->info(sprintf(
            'Pruned %d expired idempotency %s.',
            $deleted,
            $deleted === 1 ? 'key' : 'keys'
        ));

        return self::SUCCESS;
    }
}
