<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Telemetry\TelemetryMaintenance;
use Illuminate\Console\Command;

final class MaintainTelemetry extends Command
{
    protected $signature = 'telemetry:maintain';

    protected $description = 'Agrega e aplica a retenção dos snapshots de telemetria';

    public function handle(TelemetryMaintenance $maintenance): int
    {
        $result = $maintenance->run();
        $this->info(sprintf(
            'Agregados: %d; brutos removidos: %d; agregados removidos: %d.',
            $result['aggregates'],
            $result['raw_deleted'],
            $result['aggregates_deleted'],
        ));

        return self::SUCCESS;
    }
}
