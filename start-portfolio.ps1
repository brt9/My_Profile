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

function Get-EnvValue {
    param([string] $Name)

    $line = Get-Content '.env' |
        Where-Object { $_ -match ('^{0}=' -f [regex]::Escape($Name)) } |
        Select-Object -First 1

    if (-not $line) {
        return $null
    }

    $value = ($line -replace ('^{0}=' -f [regex]::Escape($Name)), '').Trim()
    if ($value.Length -ge 2) {
        $first = $value.Substring(0, 1)
        $last = $value.Substring($value.Length - 1, 1)
        if (($first -eq '"' -and $last -eq '"') -or ($first -eq "'" -and $last -eq "'")) {
            $value = $value.Substring(1, $value.Length - 2)
        }
    }

    return $value
}

function Test-TcpPort {
    param(
        [string] $HostName,
        [int] $Port
    )

    $client = [System.Net.Sockets.TcpClient]::new()
    try {
        $connect = $client.BeginConnect($HostName, $Port, $null, $null)
        if (-not $connect.AsyncWaitHandle.WaitOne(1500, $false)) {
            return $false
        }

        $client.EndConnect($connect)
        return $true
    } catch {
        return $false
    } finally {
        $client.Close()
    }
}

$dbConnection = Get-EnvValue 'DB_CONNECTION'
$dbHost = Get-EnvValue 'DB_HOST'
$dbPort = Get-EnvValue 'DB_PORT'
$usesLocalComposeMysql = (
    $dbConnection -eq 'mysql' -and
    $dbHost -in @('127.0.0.1', 'localhost') -and
    $dbPort -eq '3308' -and
    (Test-Path 'docker-compose.yml')
)

if ($usesLocalComposeMysql -and -not (Test-TcpPort -HostName $dbHost -Port ([int] $dbPort))) {
    Write-Host 'MySQL local nao encontrado; iniciando container mysql...' -ForegroundColor Yellow
    docker compose up -d mysql
    if ($LASTEXITCODE -ne 0) {
        throw 'Nao foi possivel iniciar o MySQL via docker compose.'
    }

    $deadline = (Get-Date).AddSeconds(60)
    do {
        $health = docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' myprofile-mysql 2>$null
        if ($health -eq 'healthy' -or (Test-TcpPort -HostName $dbHost -Port ([int] $dbPort))) {
            break
        }

        Start-Sleep -Seconds 2
    } while ((Get-Date) -lt $deadline)

    if (-not (Test-TcpPort -HostName $dbHost -Port ([int] $dbPort))) {
        throw 'MySQL nao ficou disponivel em 127.0.0.1:3308 dentro do tempo esperado.'
    }
}

npm run build
php artisan migrate --force

$port = if ($env:PORTFOLIO_PORT) { $env:PORTFOLIO_PORT } else { '8085' }
$bindHost = if ($env:PORTFOLIO_BIND_HOST) { $env:PORTFOLIO_BIND_HOST } else { '0.0.0.0' }
$listenAddress = '{0}:{1}' -f $bindHost, $port
$networkUrls = @()

try {
    $networkUrls = Get-NetIPConfiguration |
        Where-Object {
            $_.IPv4Address -and
            $_.NetAdapter.Status -eq 'Up' -and
            $_.InterfaceAlias -notmatch 'Loopback|vEthernet|Virtual|WSL'
        } |
        ForEach-Object {
            [pscustomobject]@{
                Label = $_.InterfaceAlias
                Url = 'http://{0}:{1}' -f $_.IPv4Address.IPAddress, $port
            }
        }
} catch {
    $networkUrls = @()
}

$scheduler = Start-Process -FilePath 'php' -ArgumentList @('artisan', 'schedule:work') -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru
$queueWorker = Start-Process -FilePath 'php' -ArgumentList @('artisan', 'queue:work', '--sleep=1', '--tries=3', '--timeout=60') -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru

Write-Host ''
Write-Host 'Portfolio disponivel em:' -ForegroundColor Green
Write-Host ('  http://127.0.0.1:{0} (neste PC)' -f $port) -ForegroundColor Green
foreach ($entry in $networkUrls) {
    Write-Host ('  {0} ({1})' -f $entry.Url, $entry.Label) -ForegroundColor Green
}
Write-Host ('Escutando em {0}; use o IP do cabo em outro dispositivo.' -f $listenAddress) -ForegroundColor DarkGray
Write-Host 'Scheduler e fila ativos para telemetria, Google Agenda e Duolingo.' -ForegroundColor Green
Write-Host 'Pressione Ctrl+C para encerrar.' -ForegroundColor DarkGray

try {
    php -d zlib.output_compression=On -S $listenAddress -t public
}
finally {
    if ($scheduler -and -not $scheduler.HasExited) {
        Stop-Process -Id $scheduler.Id -Force -ErrorAction SilentlyContinue
    }
    if ($queueWorker -and -not $queueWorker.HasExited) {
        Stop-Process -Id $queueWorker.Id -Force -ErrorAction SilentlyContinue
    }
}
