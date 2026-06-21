<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Duolingo\DuolingoSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncDuolingoProfile implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public function handle(DuolingoSyncService $sync): void
    {
        $sync->sync();
    }
}
