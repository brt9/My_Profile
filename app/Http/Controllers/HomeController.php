<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Duolingo\DuolingoDashboard;
use App\Services\GitHub\GitHubClient;
use App\Services\Professional\ProfessionalProfile;
use App\Services\Telemetry\IntegrationHealthMonitor;
use Throwable;

final class HomeController extends Controller
{
    public function __invoke(
        IntegrationHealthMonitor $health,
        DuolingoDashboard $duolingoDashboard,
        ProfessionalProfile $professionalProfile,
    ) {
        $portfolio = config('portfolio');
        $github = null;
        $externalCallsEnabled = ! app()->environment('testing')
            || (bool) ($portfolio['integrations']['allow_in_tests'] ?? false);

        if ($externalCallsEnabled && ($portfolio['integrations']['github'] ?? true)) {
            $githubClient = GitHubClient::fromConfig();
            $github = $githubClient->cachedDashboard();

            defer(function () use ($githubClient, $health): void {
                $startedAt = microtime(true);
                try {
                    $githubClient->dashboard();
                    $health->success('github', $startedAt);
                } catch (Throwable) {
                    $health->failure('github', $startedAt);
                }
            });
        }

        return view('home', [
            'portfolio' => $portfolio,
            'github' => $github,
            'duolingo' => ($portfolio['integrations']['duolingo'] ?? false)
                ? $duolingoDashboard->forHome()
                : null,
            'professional' => $professionalProfile->publicData(),
        ]);
    }
}
