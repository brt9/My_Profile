<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VisitorMapEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

final class VisitorMapController extends Controller
{
    private const CONSENT_COOKIE = 'portfolio_cookie_consent';

    private const VISITOR_COOKIE = 'visitor_map_id';

    private const CONSENT_VALUE = 'location-v1';

    public function index(): JsonResponse
    {
        $points = VisitorMapEntry::query()
            ->selectRaw('latitude, longitude, COUNT(*) as visitors')
            ->groupBy('latitude', 'longitude')
            ->orderByDesc('visitors')
            ->get()
            ->map(fn (VisitorMapEntry $entry): array => [
                'latitude' => (float) $entry->latitude,
                'longitude' => (float) $entry->longitude,
                'visitors' => (int) $entry->getAttribute('visitors'),
            ])
            ->values();

        return response()->json([
            'status' => 'available',
            'data' => [
                'total_visitors' => $points->sum('visitors'),
                'regions' => $points->count(),
                'points' => $points,
            ],
            'meta' => [
                'precision' => '0.1_degree_grid',
                'stores_exact_coordinates' => false,
            ],
            'error' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->cookie(self::CONSENT_COOKIE) === self::CONSENT_VALUE, 403);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $anonymousToken = $this->validToken($request->cookie(self::VISITOR_COOKIE))
            ?: Str::random(64);
        $latitude = $this->roundedCoordinate((float) $validated['latitude'], -90, 90);
        $longitude = $this->roundedCoordinate((float) $validated['longitude'], -180, 180);

        VisitorMapEntry::query()->updateOrCreate(
            ['visitor_key' => $this->visitorKey($anonymousToken)],
            ['latitude' => $latitude, 'longitude' => $longitude],
        );

        VisitorMapEntry::query()
            ->where('updated_at', '<', now()->subYears(2))
            ->delete();

        $response = response()->json([
            'status' => 'recorded',
            'data' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'precision' => 'approximate',
            ],
            'error' => null,
        ], 201);

        return $response->withCookie(cookie(
            self::VISITOR_COOKIE,
            $anonymousToken,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        ));
    }

    public function destroy(Request $request): JsonResponse
    {
        $anonymousToken = $this->validToken($request->cookie(self::VISITOR_COOKIE));

        if ($anonymousToken) {
            VisitorMapEntry::query()
                ->where('visitor_key', $this->visitorKey($anonymousToken))
                ->delete();
        }

        return response()->json([
            'status' => 'removed',
            'data' => null,
            'error' => null,
        ])->withCookie(Cookie::forget(self::VISITOR_COOKIE));
    }

    private function visitorKey(string $anonymousToken): string
    {
        return hash_hmac('sha256', $anonymousToken, (string) config('app.key'));
    }

    private function validToken(mixed $token): ?string
    {
        return is_string($token) && preg_match('/^[A-Za-z0-9]{64}$/', $token) === 1
            ? $token
            : null;
    }

    private function roundedCoordinate(float $coordinate, float $minimum, float $maximum): float
    {
        return max($minimum, min($maximum, round($coordinate, 1)));
    }
}
