<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IntegrationHealth;
use Illuminate\Http\JsonResponse;

final class IntegrationHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $integrations = IntegrationHealth::query()
            ->orderBy('integration')
            ->get()
            ->map(fn (IntegrationHealth $health): array => [
                'integration' => $health->integration,
                'status' => $health->status,
                'last_success_at' => $health->last_success_at?->utc()->toIso8601String(),
                'last_failure_at' => $health->last_failure_at?->utc()->toIso8601String(),
                'latency_ms' => $health->latency_ms,
            ])->all();

        return response()->json([
            'status' => 'available',
            'data' => $integrations,
            'meta' => ['generated_at' => now()->utc()->toIso8601String()],
            'error' => null,
        ]);
    }
}
