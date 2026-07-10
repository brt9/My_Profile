<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Telemetry\TelemetryHistory;
use App\Services\Telemetry\TelemetryIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class TelemetryController extends Controller
{
    public function store(Request $request, TelemetryIngestor $ingestor): JsonResponse
    {
        $expectedToken = (string) config('telemetry.token');
        $providedToken = (string) $request->bearerToken();

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        if (strlen($request->getContent()) > (int) config('telemetry.max_payload_bytes', 16384)) {
            return response()->json(['message' => 'payload_too_large'], 413);
        }

        $validated = $request->validate([
            'agent_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'],
            'collected_at' => ['required', 'date'],
            'cpu_temp' => ['nullable', 'numeric', 'between:0,120'],
            'gpu_temp' => ['nullable', 'numeric', 'between:0,120'],
            'cpu_load' => ['nullable', 'numeric', 'between:0,100'],
            'gpu_load' => ['nullable', 'numeric', 'between:0,100'],
            'memory_usage' => ['nullable', 'numeric', 'between:0,100'],
            'disk_usage' => ['nullable', 'numeric', 'between:0,100'],
            'pump_rpm' => ['nullable', 'numeric', 'between:0,10000'],
            'coolant_temp' => ['nullable', 'numeric', 'between:0,120'],
            'uptime_seconds' => ['nullable', 'integer', 'between:0,315360000'],
            'agent_version' => ['required', 'string', 'max:30'],
        ]);

        $collectedAt = Carbon::parse((string) $validated['collected_at'])->utc();
        if ($collectedAt->isAfter(now()->utc()->addMinutes(5)) || $collectedAt->isBefore(now()->utc()->subDays(2))) {
            throw ValidationException::withMessages([
                'collected_at' => 'O instante de coleta está fora da janela aceita.',
            ]);
        }

        $inserted = $ingestor->ingest($validated);

        return response()->json([
            'ok' => true,
            'duplicate' => ! $inserted,
        ]);
    }

    public function show(TelemetryIngestor $ingestor): JsonResponse
    {
        $payload = $ingestor->latestPayload();

        if ($payload === []) {
            return response()->json([
                'status' => 'unavailable',
                'data' => null,
                'meta' => [
                    'source' => 'telemetry-agent',
                    'collected_at' => null,
                    'stale' => true,
                    'machine_status' => 'offline',
                ],
                'error' => null,
            ]);
        }

        $age = Carbon::parse($payload['collected_at'])->diffInSeconds(now()->utc(), true);
        $stale = $age > (int) config('telemetry.stale_after', 30);
        $offline = $age > (int) config('telemetry.offline_after', 180);

        return response()->json([
            'status' => $stale ? 'stale' : 'available',
            'data' => collect($payload)->except(['agent_id', 'agent_version', 'collected_at'])->all(),
            'meta' => [
                'source' => 'telemetry-agent',
                'collected_at' => $payload['collected_at'],
                'stale' => $stale,
                'machine_status' => $offline ? 'offline' : ($stale ? 'stale' : 'online'),
                'agent_version' => $payload['agent_version'] ?? null,
            ],
            'error' => null,
        ]);
    }

    public function history(Request $request, TelemetryHistory $history): JsonResponse
    {
        $validated = $request->validate([
            'metric' => ['required', 'string', 'max:40'],
            'range' => ['sometimes', 'string', 'max:4'],
            'resolution' => ['sometimes', 'string', 'max:4'],
        ]);

        try {
            return response()->json($history->get(
                $validated['metric'],
                $validated['range'] ?? '6h',
                $validated['resolution'] ?? null,
            ));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['metric' => $exception->getMessage()]);
        }
    }
}
