<?php

return [
    'token' => env('TELEMETRY_TOKEN', ''),
    'ttl' => (int) env('TELEMETRY_TTL', 180),
    'stale_after' => (int) env('TELEMETRY_STALE_AFTER', 30),
    'offline_after' => (int) env('TELEMETRY_OFFLINE_AFTER', 180),
    'raw_retention_days' => (int) env('TELEMETRY_RAW_RETENTION_DAYS', 7),
    'aggregate_retention_days' => (int) env('TELEMETRY_AGGREGATE_RETENTION_DAYS', 90),
    'max_payload_bytes' => (int) env('TELEMETRY_MAX_PAYLOAD_BYTES', 16384),
    'metrics' => [
        'cpu_load' => ['column' => 'cpu_usage', 'label' => 'Uso da CPU', 'unit' => '%', 'precision' => 1],
        'cpu_temp' => ['column' => 'cpu_temperature', 'label' => 'Temperatura da CPU', 'unit' => '°C', 'precision' => 1],
        'gpu_load' => ['column' => 'gpu_usage', 'label' => 'Uso da GPU', 'unit' => '%', 'precision' => 1],
        'gpu_temp' => ['column' => 'gpu_temperature', 'label' => 'Temperatura da GPU', 'unit' => '°C', 'precision' => 1],
        'memory_usage' => ['column' => 'memory_usage', 'label' => 'Uso da memória', 'unit' => '%', 'precision' => 1],
        'disk_usage' => ['column' => 'disk_usage', 'label' => 'Uso do disco', 'unit' => '%', 'precision' => 1],
        'uptime_seconds' => ['column' => 'uptime_seconds', 'label' => 'Tempo ligado', 'unit' => 's', 'precision' => 0],
    ],
    'ranges' => [
        '1h' => 3600,
        '6h' => 21600,
        '12h' => 43200,
        '24h' => 86400,
    ],
    'resolutions' => [
        '1m' => 60,
        '5m' => 300,
        '15m' => 900,
        '1h' => 3600,
    ],
];
