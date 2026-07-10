<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Weather\WeatherClient;
use Illuminate\Console\Command;
use Throwable;

final class CaptureNatalWeather extends Command
{
    protected $signature = 'weather:capture-natal';

    protected $description = 'Captura e persiste o clima atual de Natal/RN';

    public function handle(WeatherClient $weather): int
    {
        if (! config('portfolio.integrations.weather')) {
            $this->components->warn('Integração de clima desativada.');

            return self::SUCCESS;
        }

        try {
            $weather->byCoords(-5.795, -35.209, 'Natal, RN');
            $this->components->info('Clima de Natal capturado e salvo.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error('A API de clima está indisponível; o último registro salvo foi preservado.');
            report($exception);

            return self::FAILURE;
        }
    }
}
