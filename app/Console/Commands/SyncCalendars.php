<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncGoogleCalendar;
use App\Models\GoogleCalendarConnection;
use Illuminate\Console\Command;

final class SyncCalendars extends Command
{
    protected $signature = 'calendar:sync';

    protected $description = 'Enfileira a sincronização das agendas Google conectadas';

    public function handle(): int
    {
        if (! config('services.google_calendar.enabled')) {
            $this->components->info('Google Calendar desativado.');

            return self::SUCCESS;
        }

        $count = 0;
        GoogleCalendarConnection::query()->whereIn('status', ['connected', 'unavailable'])->pluck('id')
            ->each(function (int $id) use (&$count): void {
                SyncGoogleCalendar::dispatch($id);
                $count++;
            });

        $this->components->info("Sincronizações enfileiradas: {$count}.");

        return self::SUCCESS;
    }
}
