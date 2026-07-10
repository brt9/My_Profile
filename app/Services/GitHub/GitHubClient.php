<?php

declare(strict_types=1);

namespace App\Services\GitHub;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;
use UnexpectedValueException;

final class GitHubClient
{
    private const BASE_URL = 'https://api.github.com';

    public function __construct(
        private readonly string $username,
        private readonly ?string $token = null,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.github.username', 'brt9'),
            config('services.github.token'),
        );
    }

    /**
     * @return array{profile: array<string, mixed>, repositories: array<int, array<string, mixed>>, activity: array<string, mixed>}
     */
    public function dashboard(): array
    {
        return Cache::remember($this->cacheKey(), now()->addMinutes(30), function (): array {
            $profile = $this->request()->get(self::BASE_URL."/users/{$this->username}")->throw()->json();
            $repositories = $this->request()->get(self::BASE_URL."/users/{$this->username}/repos", [
                'sort' => 'updated',
                'direction' => 'desc',
                'per_page' => 8,
            ])->throw()->json();
            $events = $this->request()->get(self::BASE_URL."/users/{$this->username}/events/public", [
                'per_page' => 30,
            ])->throw()->json();

            $repositories = collect($repositories)
                ->reject(fn (array $repository): bool => (bool) ($repository['fork'] ?? false))
                ->take(4)
                ->map(fn (array $repository): array => [
                    'name' => $repository['name'],
                    'description' => $repository['description'],
                    'language' => $repository['language'],
                    'stars' => (int) $repository['stargazers_count'],
                    'forks' => (int) $repository['forks_count'],
                    'updated_at' => $repository['updated_at'],
                    'url' => $repository['html_url'],
                ])->values()->all();

            $pushEvents = collect($events)->where('type', 'PushEvent')->values();
            $languages = collect($repositories)->pluck('language')->filter()->countBy()->sortDesc();
            $currentYear = now()->utc()->year;
            $createdYear = CarbonImmutable::parse((string) $profile['created_at'])->utc()->year;
            $calendar = $this->contributionsForYear($currentYear, $events);

            return [
                'profile' => [
                    'login' => $profile['login'],
                    'name' => $profile['name'],
                    'avatar' => $profile['avatar_url'],
                    'bio' => $profile['bio'],
                    'url' => $profile['html_url'],
                    'followers' => (int) $profile['followers'],
                    'public_repositories' => (int) $profile['public_repos'],
                    'created_year' => $createdYear,
                ],
                'repositories' => $repositories,
                'activity' => [
                    'recent_events' => count($events),
                    'recent_pushes' => $pushEvents->count(),
                    'last_push_at' => Arr::get($pushEvents->first(), 'created_at'),
                    'main_language' => $languages->keys()->first(),
                    'calendar' => $calendar,
                ],
            ];
        });
    }

    /**
     * Retorna somente o snapshot local, sem bloquear a página com uma chamada externa.
     *
     * @return array{profile: array<string, mixed>, repositories: array<int, array<string, mixed>>, activity: array<string, mixed>}|null
     */
    public function cachedDashboard(): ?array
    {
        $dashboard = Cache::get($this->cacheKey());

        return is_array($dashboard) ? $dashboard : null;
    }

    private function cacheKey(): string
    {
        return "github:dashboard:v3:{$this->username}";
    }

    /** @param array<int, array<string, mixed>> $fallbackEvents
     * @return array<string, mixed>
     */
    public function contributionsForYear(int $year, array $fallbackEvents = []): array
    {
        $currentYear = now()->utc()->year;
        if ($year < 2008 || $year > $currentYear) {
            throw new InvalidArgumentException('Ano do GitHub fora do intervalo disponível.');
        }

        $expiresAt = $year === $currentYear ? now()->addMinutes(30) : now()->addDay();

        return Cache::remember(
            "github:contributions:v1:{$this->username}:{$year}",
            $expiresAt,
            fn (): array => $this->contributionCalendar($fallbackEvents, $year),
        );
    }

    /** @param array<int, array<string, mixed>> $events
     * @return array<string, mixed>
     */
    private function contributionCalendar(array $events, int $year): array
    {
        try {
            $html = Http::withHeaders(['User-Agent' => 'Pedro-Felipe-Portfolio'])
                ->accept('text/html')
                ->timeout(8)
                ->retry(1, 250)
                ->get('https://github.com/users/'.rawurlencode($this->username).'/contributions', [
                    'from' => $year.'-01-01',
                    'to' => $year.'-12-31',
                ])
                ->throw()
                ->body();

            return $this->parseContributionCalendar($html, $year);
        } catch (Throwable) {
            return $this->publicEventsCalendar($events, $year);
        }
    }

    /** @return array<string, mixed> */
    private function parseContributionCalendar(string $html, int $year): array
    {
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }

        if (! $loaded) {
            throw new UnexpectedValueException('Calendário de contribuições inválido.');
        }

        $xpath = new DOMXPath($document);
        $tooltipByDay = [];
        foreach ($xpath->query('//tool-tip[@for]') ?: [] as $tooltip) {
            $tooltipByDay[$tooltip->attributes->getNamedItem('for')->nodeValue ?? ''] = trim($tooltip->textContent);
        }

        $nodes = $xpath->query("//td[contains(concat(' ', normalize-space(@class), ' '), ' ContributionCalendar-day ')][@data-date][@data-level]");
        if ($nodes === false || $nodes->length === 0) {
            throw new UnexpectedValueException('Dias do calendário não encontrados.');
        }

        $days = [];
        foreach ($nodes as $node) {
            $date = $node->attributes->getNamedItem('data-date')->nodeValue ?? '';
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }

            $id = $node->attributes->getNamedItem('id')->nodeValue ?? '';
            $tooltip = $tooltipByDay[$id] ?? '';
            preg_match('/([\d,]+) contributions?/i', $tooltip, $countMatch);
            $count = isset($countMatch[1]) ? (int) str_replace(',', '', $countMatch[1]) : 0;
            $day = CarbonImmutable::parse($date, 'UTC');

            $days[] = [
                'date' => $date,
                'count' => $count,
                'level' => max(0, min(4, (int) ($node->attributes->getNamedItem('data-level')->nodeValue ?? 0))),
                'label' => $count === 0
                    ? 'Nenhuma contribuição em '.$day->format('d/m/Y')
                    : $count.' '.($count === 1 ? 'contribuição' : 'contribuições').' em '.$day->format('d/m/Y'),
            ];
        }

        if ($days === []) {
            throw new UnexpectedValueException('Calendário de contribuições vazio.');
        }

        return $this->calendarEnvelope($days, 'github_profile', 'contribuições em '.$year, $year);
    }

    /** @param array<int, array<string, mixed>> $events
     * @return array<string, mixed>
     */
    private function publicEventsCalendar(array $events, int $year): array
    {
        $start = CarbonImmutable::create($year, 1, 1, 0, 0, 0, 'UTC');
        $end = $start->endOfYear();
        $counts = collect($events)
            ->pluck('created_at')
            ->filter()
            ->map(fn (mixed $date): string => CarbonImmutable::parse((string) $date)->utc()->toDateString())
            ->countBy();

        $days = [];
        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $count = (int) ($counts[$day->toDateString()] ?? 0);
            $days[] = [
                'date' => $day->toDateString(),
                'count' => $count,
                'level' => min(4, $count),
                'label' => $count === 0
                    ? 'Nenhum evento público em '.$day->format('d/m/Y')
                    : $count.' '.($count === 1 ? 'evento público' : 'eventos públicos').' em '.$day->format('d/m/Y'),
            ];
        }

        return $this->calendarEnvelope($days, 'public_events', 'eventos públicos disponíveis em '.$year, $year);
    }

    /** @param array<int, array{date: string, count: int, level: int, label: string}> $days
     * @return array<string, mixed>
     */
    private function calendarEnvelope(array $days, string $source, string $summaryLabel, int $year): array
    {
        usort($days, fn (array $left, array $right): int => $left['date'] <=> $right['date']);
        $rangeFrom = $days[0]['date'];
        $rangeTo = $days[array_key_last($days)]['date'];
        $daysByDate = collect($days)->keyBy('date');
        $gridStart = CarbonImmutable::parse($rangeFrom, 'UTC')->startOfWeek(CarbonInterface::SUNDAY);
        $gridEnd = CarbonImmutable::parse($rangeTo, 'UTC')->endOfWeek(CarbonInterface::SATURDAY);
        $gridDays = [];
        for ($day = $gridStart; $day->lessThanOrEqualTo($gridEnd); $day = $day->addDay()) {
            $date = $day->toDateString();
            $gridDays[] = $daysByDate->get($date, [
                'date' => $date,
                'count' => 0,
                'level' => 0,
                'label' => '',
                'outside' => true,
            ]) + ['outside' => false];
        }

        $grouped = [];
        foreach ($gridDays as $day) {
            $week = CarbonImmutable::parse($day['date'], 'UTC')
                ->startOfWeek(CarbonInterface::SUNDAY)
                ->toDateString();
            $grouped[$week][] = $day;
        }
        ksort($grouped);

        $months = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $previousMonth = null;
        $previousYear = null;
        $lastMonthLabelIndex = -4;
        $weeks = [];
        foreach ($grouped as $start => $weekDays) {
            $visibleWeekDays = collect($weekDays)->reject(fn (array $day): bool => $day['outside']);
            $yearDay = $visibleWeekDays->first(
                fn (array $day): bool => CarbonImmutable::parse($day['date'], 'UTC')->dayOfYear <= 7,
            );
            $weekYear = $yearDay === null ? null : CarbonImmutable::parse($yearDay['date'], 'UTC')->year;
            if ($weekYear === null && $weeks === []) {
                $weekYear = CarbonImmutable::parse($visibleWeekDays->first()['date'], 'UTC')->year;
            }
            $yearLabel = $weekYear !== null && $weekYear !== $previousYear ? (string) $weekYear : null;
            $previousYear = $weekYear ?? $previousYear;

            $monthDay = $visibleWeekDays->first(
                fn (array $day): bool => CarbonImmutable::parse($day['date'], 'UTC')->day <= 7,
            );
            $month = $monthDay === null ? null : CarbonImmutable::parse($monthDay['date'], 'UTC')->month;
            if ($month === null && $weeks === []) {
                $month = CarbonImmutable::parse($visibleWeekDays->first()['date'], 'UTC')->month;
            }
            $weekIndex = count($weeks);
            $monthLabel = $month !== null
                && $month !== $previousMonth
                && $weekIndex - $lastMonthLabelIndex >= 3
                    ? $months[$month]
                    : null;
            if ($monthLabel !== null) {
                $lastMonthLabelIndex = $weekIndex;
            }
            $previousMonth = $month ?? $previousMonth;
            $weeks[] = ['start' => $start, 'year' => $yearLabel, 'month' => $monthLabel, 'days' => $weekDays];
        }

        return [
            'year' => $year,
            'source' => $source,
            'summary_label' => $summaryLabel,
            'total' => array_sum(array_column($days, 'count')),
            'active_days' => count(array_filter($days, fn (array $day): bool => $day['count'] > 0)),
            'from' => $rangeFrom,
            'to' => $rangeTo,
            'weeks' => $weeks,
        ];
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->withHeaders([
                'User-Agent' => 'Pedro-Felipe-Portfolio',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->timeout(8)
            ->retry(1, 250);

        return filled($this->token) ? $request->withToken($this->token) : $request;
    }
}
