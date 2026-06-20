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

Write-Host ''
Write-Host 'Portfólio disponível em http://127.0.0.1:8085' -ForegroundColor Green
Write-Host 'Pressione Ctrl+C para encerrar.' -ForegroundColor DarkGray

php -d zlib.output_compression=On -S 127.0.0.1:8085 -t public
