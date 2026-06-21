<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Models\WeatherSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WeatherClient
{
    private const OPEN_METEO = 'https://api.open-meteo.com/v1/forecast';

    private const NATAL_LATITUDE = -5.795;

    private const NATAL_LONGITUDE = -35.209;

    private const NATAL_KEY = 'natal-rn';

    /** @return array<string, mixed> */
    public function byCoords(float $lat, float $lon, ?string $label = null, string $source = 'fixed'): array
    {
        $cacheKey = $this->coordsCacheKey($lat, $lon, $source);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($lat, $lon, $label, $source): array {
            $json = Http::acceptJson()
                ->timeout(8)
                ->retry(1, 250)
                ->get(self::OPEN_METEO, [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'current' => implode(',', [
                        'temperature_2m',
                        'apparent_temperature',
                        'weather_code',
                        'wind_speed_10m',
                        'wind_direction_10m',
                        'relative_humidity_2m',
                        'pressure_msl',
                        'uv_index',
                        'cloud_cover',
                    ]),
                    'timezone' => 'auto',
                ])
                ->throw()
                ->json();
            $current = $json['current'] ?? [];
            $code = (int) ($current['weather_code'] ?? 0);
            $capturedAt = now()->utc();

            $weather = [
                'label' => $label ?: '—',
                'temp' => $current['temperature_2m'] ?? null,
                'feels_like' => $current['apparent_temperature'] ?? null,
                'wind_kmh' => isset($current['wind_speed_10m']) ? round((float) $current['wind_speed_10m']) : null,
                'wind_dir' => $current['wind_direction_10m'] ?? null,
                'humidity' => $current['relative_humidity_2m'] ?? null,
                'pressure' => $current['pressure_msl'] ?? null,
                'uv' => $current['uv_index'] ?? null,
                'clouds' => $current['cloud_cover'] ?? null,
                'updated_at' => $current['time'] ?? null,
                'code' => $code,
                'condition' => self::describe($code),
                'emoji' => self::emoji($code),
                'source' => $source,
                'origin' => match ($source) {
                    'browser' => 'Localização autorizada no navegador',
                    'ip' => 'Localização aproximada por IP',
                    'fallback' => 'Localização padrão: Natal/RN',
                    default => 'Localização fixa: Natal/RN',
                },
                'captured_at' => $capturedAt->toIso8601String(),
                'is_stale' => false,
            ];

            if ($this->isNatal($lat, $lon)) {
                $this->persistNatal($weather, $lat, $lon, $capturedAt);
            }

            return $weather;
        });
    }

    /** @return array<string, mixed>|null */
    public function cachedByCoords(float $lat, float $lon, string $source = 'fixed'): ?array
    {
        $weather = Cache::get($this->coordsCacheKey($lat, $lon, $source));

        if (is_array($weather)) {
            return $weather;
        }

        if (! $this->isNatal($lat, $lon)) {
            return null;
        }

        $snapshot = WeatherSnapshot::query()
            ->where('location_key', self::NATAL_KEY)
            ->latest('captured_at')
            ->first();

        return $snapshot ? $this->fromSnapshot($snapshot) : null;
    }

    /** @return array<string, mixed> */
    public function byRequest(Request $request): array
    {
        $forwarded = $request->server('HTTP_X_FORWARDED_FOR');
        $candidates = [
            $request->server('HTTP_CF_CONNECTING_IP'),
            $forwarded ? trim(explode(',', (string) $forwarded)[0]) : null,
            $request->server('HTTP_X_REAL_IP'),
            $request->ip(),
        ];
        $ip = collect($candidates)->first(
            fn (mixed $candidate): bool => is_string($candidate)
                && filter_var($candidate, FILTER_VALIDATE_IP) !== false,
        );

        if (self::isPrivateIp($ip)) {
            return $this->byCoords(self::NATAL_LATITUDE, self::NATAL_LONGITUDE, 'Natal, RN', 'fallback');
        }

        $geo = null;
        try {
            $geo = Http::timeout(5)->retry(1, 200)
                ->get("https://ipapi.co/{$ip}/json/")
                ->json();
        } catch (Throwable) {
            Log::warning('Weather geolocation provider unavailable', [
                'integration' => 'weather',
                'provider' => 'ipapi',
                'status' => 'error',
            ]);
        }

        if (empty($geo) || empty($geo['latitude']) || empty($geo['longitude'])) {
            try {
                $alternate = Http::timeout(5)->retry(1, 200)
                    ->get("https://ipwho.is/{$ip}")
                    ->json();

                if (! empty($alternate) && ! ($alternate['bogon'] ?? false) && ($alternate['success'] ?? true)) {
                    $geo = [
                        'latitude' => $alternate['latitude'] ?? null,
                        'longitude' => $alternate['longitude'] ?? null,
                        'city' => $alternate['city'] ?? null,
                        'region' => $alternate['region'] ?? null,
                        'region_code' => $alternate['region_code'] ?? null,
                    ];
                }
            } catch (Throwable) {
                Log::warning('Weather geolocation provider unavailable', [
                    'integration' => 'weather',
                    'provider' => 'ipwho',
                    'status' => 'error',
                ]);
            }
        }

        if (empty($geo['latitude']) || empty($geo['longitude'])) {
            return $this->byCoords(self::NATAL_LATITUDE, self::NATAL_LONGITUDE, 'Natal, RN', 'fallback');
        }

        $lat = (float) $geo['latitude'];
        $lon = (float) $geo['longitude'];
        $city = trim(($geo['city'] ?? 'Localização aproximada').(
            ! empty($geo['region_code']) ? ", {$geo['region_code']}" : (! empty($geo['region']) ? ", {$geo['region']}" : '')
        ));

        return $this->byCoords($lat, $lon, $city, 'ip');
    }

    private static function isPrivateIp(?string $ip): bool
    {
        return ! $ip || filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }

    private function coordsCacheKey(float $lat, float $lon, string $source): string
    {
        return sprintf('wx:%s:%s:%s', round($lat, 3), round($lon, 3), $source);
    }

    private function isNatal(float $lat, float $lon): bool
    {
        return abs($lat - self::NATAL_LATITUDE) < 0.02
            && abs($lon - self::NATAL_LONGITUDE) < 0.02;
    }

    /** @param array<string, mixed> $weather */
    private function persistNatal(array $weather, float $lat, float $lon, mixed $capturedAt): void
    {
        $timezone = (string) config('portfolio.presentation_timezone', 'America/Fortaleza');
        $observedAt = filled($weather['updated_at'] ?? null)
            ? CarbonImmutable::parse((string) $weather['updated_at'], $timezone)->utc()
            : null;

        WeatherSnapshot::query()->create([
            'location_key' => self::NATAL_KEY,
            'label' => 'Natal, RN',
            'latitude' => $lat,
            'longitude' => $lon,
            'temperature' => $weather['temp'],
            'feels_like' => $weather['feels_like'],
            'humidity' => $weather['humidity'],
            'wind_kmh' => $weather['wind_kmh'],
            'weather_code' => $weather['code'],
            'condition' => $weather['condition'],
            'emoji' => $weather['emoji'],
            'observed_at' => $observedAt,
            'captured_at' => $capturedAt,
        ]);

        WeatherSnapshot::query()
            ->where('location_key', self::NATAL_KEY)
            ->where('captured_at', '<', now()->subDays(30))
            ->delete();
    }

    /** @return array<string, mixed> */
    private function fromSnapshot(WeatherSnapshot $snapshot): array
    {
        return [
            'label' => $snapshot->label,
            'temp' => $snapshot->temperature,
            'feels_like' => $snapshot->feels_like,
            'wind_kmh' => $snapshot->wind_kmh,
            'wind_dir' => null,
            'humidity' => $snapshot->humidity,
            'pressure' => null,
            'uv' => null,
            'clouds' => null,
            'updated_at' => $snapshot->observed_at?->toIso8601String(),
            'code' => $snapshot->weather_code,
            'condition' => $snapshot->condition,
            'emoji' => $snapshot->emoji,
            'source' => 'database',
            'origin' => 'Último registro salvo para Natal/RN',
            'captured_at' => $snapshot->captured_at?->toIso8601String(),
            'is_stale' => true,
        ];
    }

    public static function describe(int $code): string
    {
        return [
            0 => 'Céu limpo', 1 => 'Principalmente limpo', 2 => 'Parcialmente nublado', 3 => 'Nublado',
            45 => 'Nevoeiro', 48 => 'Nevoeiro com gelo', 51 => 'Garoa fraca', 53 => 'Garoa',
            55 => 'Garoa forte', 56 => 'Garoa congelante fraca', 57 => 'Garoa congelante',
            61 => 'Chuva fraca', 63 => 'Chuva', 65 => 'Chuva forte', 66 => 'Chuva congelante fraca',
            67 => 'Chuva congelante', 71 => 'Neve fraca', 73 => 'Neve', 75 => 'Neve forte',
            77 => 'Grãos de neve', 80 => 'Aguaceiros fracos', 81 => 'Aguaceiros', 82 => 'Aguaceiros fortes',
            85 => 'Aguaceiros de neve fracos', 86 => 'Aguaceiros de neve fortes', 95 => 'Trovoadas',
            96 => 'Trovoadas com granizo leve', 99 => 'Trovoadas com granizo forte',
        ][$code] ?? 'Tempo variável';
    }

    public static function emoji(int $code): string
    {
        return match (true) {
            $code === 0 => '☀️',
            in_array($code, [1, 2], true) => '🌤️',
            $code === 3 => '☁️',
            in_array($code, [45, 48], true) => '🌫️',
            in_array($code, [51, 53, 55, 61, 63, 65, 80, 81, 82], true) => '🌧️',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => '❄️',
            in_array($code, [95, 96, 99], true) => '⛈️',
            default => '⛅',
        };
    }
}
