<?php

declare(strict_types=1);

namespace App\Services\Duolingo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class DuolingoClient implements DuolingoProvider
{
    private const LANGUAGE_NAMES = [
        'de' => 'Alemão', 'en' => 'Inglês', 'es' => 'Espanhol', 'fr' => 'Francês',
        'it' => 'Italiano', 'ja' => 'Japonês', 'ko' => 'Coreano', 'pt' => 'Português',
        'ru' => 'Russo', 'zh' => 'Chinês',
    ];

    public function __construct(
        private readonly string $username,
        private readonly string $endpoint,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.duolingo.username'),
            (string) config('services.duolingo.endpoint'),
        );
    }

    public function profile(): array
    {
        throw_if($this->username === '', RuntimeException::class, 'DUOLINGO_USERNAME não configurado.');
        throw_if(Cache::has($this->key('circuit-open')), RuntimeException::class, 'Integração Duolingo temporariamente suspensa.');

        try {
            $payload = Http::acceptJson()
                ->timeout(6)
                ->retry(2, 250)
                ->get($this->endpoint, ['username' => $this->username])
                ->throw()
                ->json();
            $user = is_array($payload['users'][0] ?? null) ? $payload['users'][0] : [];
            throw_if($user === [], RuntimeException::class, 'Perfil público do Duolingo não encontrado.');

            $courses = is_array($user['courses'] ?? null) ? $user['courses'] : [];
            if ($courses === [] && is_array($user['currentCourse'] ?? null)) {
                $courses = [$user['currentCourse']];
            }

            $normalized = collect($courses)->filter(fn ($course): bool => is_array($course))
                ->map(function (array $course): ?array {
                    $language = $course['learningLanguage'] ?? $course['language'] ?? null;
                    if (! is_string($language) || $language === '') {
                        return null;
                    }

                    return [
                        'language' => mb_strtolower($language),
                        'language_name' => $this->languageName($language, $course['title'] ?? null),
                        'xp' => max(0, (int) ($course['xp'] ?? 0)),
                    ];
                })->filter()->unique('language')->values()->all();

            Cache::forget($this->key('failures'));
            Cache::forget($this->key('circuit-open'));

            return [
                'username' => (string) ($user['username'] ?? $this->username),
                'total_xp' => max(0, (int) ($user['totalXp'] ?? 0)),
                'streak' => max(0, (int) ($user['streak'] ?? 0)),
                'courses' => $normalized,
            ];
        } catch (Throwable $exception) {
            $failures = (int) Cache::get($this->key('failures'), 0) + 1;
            Cache::put($this->key('failures'), $failures, now()->addHour());
            if ($failures >= 3) {
                Cache::put($this->key('circuit-open'), true, now()->addMinutes(30));
            }

            throw $exception;
        }
    }

    private function languageName(string $code, mixed $title): string
    {
        $normalizedCode = mb_strtolower($code);
        if (isset(self::LANGUAGE_NAMES[$normalizedCode])) {
            return self::LANGUAGE_NAMES[$normalizedCode];
        }

        if (is_string($title) && trim($title) !== '') {
            return mb_substr(strip_tags(trim($title)), 0, 80);
        }

        return mb_strtoupper($code);
    }

    private function key(string $suffix): string
    {
        return 'duolingo:'.hash('sha256', $this->username).':'.$suffix;
    }
}
