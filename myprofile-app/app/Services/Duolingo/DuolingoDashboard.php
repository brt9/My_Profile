<?php

declare(strict_types=1);

namespace App\Services\Duolingo;

use App\Models\DuolingoSnapshot;

final class DuolingoDashboard
{
    /** @return array<string, mixed> */
    public function forHome(): array
    {
        $username = (string) config('services.duolingo.username');
        if ($username === '') {
            return ['configured' => false, 'username' => null, 'courses' => [], 'stale' => false];
        }

        $snapshots = DuolingoSnapshot::query()
            ->where('username', $username)
            ->where('snapshot_date', '>=', now()->utc()->subDays(30)->toDateString())
            ->orderBy('snapshot_date')
            ->get();
        $latestCollectedAt = $snapshots->max('collected_at');
        $courses = $snapshots->groupBy('language')->map(function ($history): array {
            $latest = $history->last();

            return [
                'language' => $latest->language,
                'language_name' => $latest->language_name,
                'course_xp' => $latest->course_xp,
                'total_xp' => $latest->total_xp,
                'streak' => $latest->streak,
                'points' => $history->map(fn (DuolingoSnapshot $snapshot): array => [
                    'date' => $snapshot->snapshot_date->toDateString(),
                    'xp' => $snapshot->course_xp,
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'configured' => true,
            'username' => $username,
            'courses' => $courses,
            'stale' => $latestCollectedAt !== null
                && $latestCollectedAt->lessThan(now()->subHours(max(12, (int) config('services.duolingo.cache_hours', 6) * 2))),
            'last_collected_at' => $latestCollectedAt,
        ];
    }
}
