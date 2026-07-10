<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GitHub\GitHubClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GitHubContributionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2008,'.now()->utc()->year],
        ]);

        return response()->json([
            'status' => 'available',
            'data' => GitHubClient::fromConfig()->contributionsForYear((int) $validated['year']),
            'error' => null,
        ]);
    }
}
