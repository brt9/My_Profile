<?php

declare(strict_types=1);

namespace App\Services\Professional;

use Carbon\CarbonImmutable;

final class ProfessionalProfile
{
    /** @return array{experiences: list<array<string, mixed>>, education: list<array<string, mixed>>, languages: list<array<string, mixed>>, source: string, updated_at: CarbonImmutable|null} */
    public function publicData(): array
    {
        return [
            'experiences' => config('portfolio.experience', []),
            'education' => config('portfolio.education', []),
            'languages' => config('portfolio.languages', []),
            'source' => (string) config('portfolio.profile_source', 'portfolio'),
            'updated_at' => filled(config('portfolio.profile_updated_at'))
                ? CarbonImmutable::parse((string) config('portfolio.profile_updated_at'))
                : null,
        ];
    }
}
