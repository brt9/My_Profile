$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

if (-not (Test-Path 'vendor')) {
    composer install --no-interaction --prefer-dist
}

if (-not (Test-Path 'node_modules')) {
    npm ci
}

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
}

$envText = Get-Content '.env' -Raw
if ($envText -match '(?m)^APP_KEY=\s*$') {
    php artisan key:generate --force
}

npm run build
php artisan migrate --force

$scheduler = Start-Process -FilePath 'php' -ArgumentList @('artisan', 'schedule:work') -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru

Write-Host ''
Write-Host 'Portfólio disponível em http://127.0.0.1:8085' -ForegroundColor Green
Write-Host 'Scheduler ativo para telemetria, Google Agenda e Duolingo.' -ForegroundColor Green
Write-Host 'Pressione Ctrl+C para encerrar.' -ForegroundColor DarkGray

try {
    php -d zlib.output_compression=On -S 127.0.0.1:8085 -t public
}
finally {
    if ($scheduler -and -not $scheduler.HasExited) {
        Stop-Process -Id $scheduler.Id -Force -ErrorAction SilentlyContinue
    }
}
