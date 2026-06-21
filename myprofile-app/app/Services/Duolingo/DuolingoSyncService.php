<?php

declare(strict_types=1);

namespace App\Services\Duolingo;

use App\Services\Telemetry\IntegrationHealthMonitor;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DuolingoSyncService
{
    public function __construct(
        private readonly DuolingoProvider $provider,
        private readonly IntegrationHealthMonitor $health,
    ) {}

    public function sync(): int
    {
        if (! config('services.duolingo.enabled') || blank(config('services.duolingo.username'))) {
            return 0;
        }

        $startedAt = microtime(true);
        try {
            $profile = $this->provider->profile();
            DB::transaction(function () use ($profile): void {
                foreach ($profile['courses'] as $course) {
                    DB::table('duolingo_snapshots')->upsert(
                        [[
                            'username' => $profile['username'],
                            'language' => $course['language'],
                            'snapshot_date' => now()->utc()->toDateString(),
                            'language_name' => $course['language_name'],
                            'course_xp' => $course['xp'],
                            'total_xp' => $profile['total_xp'],
                            'streak' => $profile['streak'],
                            'collected_at' => now()->utc(),
                            'created_at' => now()->utc(),
                            'updated_at' => now()->utc(),
                        ]],
                        ['username', 'language', 'snapshot_date'],
                        ['language_name', 'course_xp', 'total_xp', 'streak', 'collected_at', 'updated_at'],
                    );
                }
            });
            $this->health->success('duolingo', $startedAt);

            return count($profile['courses']);
        } catch (Throwable $exception) {
            $this->health->failure('duolingo', $startedAt);
            throw $exception;
        }
    }
}
