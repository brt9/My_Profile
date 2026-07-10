<?php

declare(strict_types=1);

namespace App\Services\Duolingo;

interface DuolingoProvider
{
    /** @return array{username: string, total_xp: int, streak: int, courses: list<array{language: string, language_name: string, xp: int}>} */
    public function profile(): array;
}
