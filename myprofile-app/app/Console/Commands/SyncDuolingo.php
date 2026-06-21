<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncDuolingoProfile;
use Illuminate\Console\Command;

final class SyncDuolingo extends Command
{
    protected $signature = 'duolingo:sync';

    protected $description = 'Enfileira a sincronização do perfil público do Duolingo';

    public function handle(): int
    {
        if (! config('services.duolingo.enabled') || blank(config('services.duolingo.username'))) {
            $this->components->warn('Duolingo desativado ou sem username.');

            return self::SUCCESS;
        }

        SyncDuolingoProfile::dispatch();
        $this->components->info('Sincronização do Duolingo enfileirada.');

        return self::SUCCESS;
    }
}
