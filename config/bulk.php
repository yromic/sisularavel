<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bulk Operations Configuration
    |--------------------------------------------------------------------------
    |
    | Maximum IDs per single bulk mutation request and the threshold
    | requiring explicit typed confirmation phrase "HAPUS {N}".
    |
    */

    'max_ids' => (int) env('BULK_MAX_IDS', 200),
    'confirm_threshold' => (int) env('BULK_CONFIRM_THRESHOLD', 50),
    'chunk_size' => (int) env('BULK_CHUNK_SIZE', 50),
];
