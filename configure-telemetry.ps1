$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$envPath = Join-Path $PSScriptRoot '.env'
$agentDirectory = Join-Path $PSScriptRoot 'dist\telemetry-agent'
$agentConfig = Join-Path $agentDirectory 'telemetry-agent.json'

if (-not (Test-Path $envPath)) {
    throw '.env não encontrado. Execute a configuração do Laravel primeiro.'
}

if (-not (Test-Path (Join-Path $agentDirectory 'PC-Telemetry-Agent.exe'))) {
    throw 'PC-Telemetry-Agent.exe não encontrado. Compile o agente primeiro.'
}

$utf8 = New-Object System.Text.UTF8Encoding($false)
$envText = [IO.File]::ReadAllText($envPath, $utf8)
$processToken = [Environment]::GetEnvironmentVariable('TELEMETRY_TOKEN')
$fileMatch = [Regex]::Match($envText, '(?m)^TELEMETRY_TOKEN=(.*)$')
$fileToken = if ($fileMatch.Success) { $fileMatch.Groups[1].Value.Trim() } else { '' }
$appUrlMatch = [Regex]::Match($envText, '(?m)^APP_URL=(.*)$')
$appUrl = if ($appUrlMatch.Success) { $appUrlMatch.Groups[1].Value.Trim().Trim('"').Trim("'").TrimEnd('/') } else { '' }
$endpointsMatch = [Regex]::Match($envText, '(?m)^TELEMETRY_ENDPOINTS=(.*)$')
$configuredEndpoints = if ($endpointsMatch.Success) {
    $endpointsMatch.Groups[1].Value.Trim().Trim('"').Trim("'").Split(',') |
        ForEach-Object { $_.Trim() } |
        Where-Object { -not [string]::IsNullOrWhiteSpace($_) }
} else {
    @()
}

if ([string]::IsNullOrWhiteSpace($appUrl)) {
    throw 'APP_URL nao encontrado no .env.'
}

$localEndpoint = "$appUrl/api/telemetry/push"
$endpoints = if (@($configuredEndpoints).Count -gt 0) {
    @($configuredEndpoints) | Select-Object -Unique
} else {
    @($localEndpoint)
}

if (-not [string]::IsNullOrWhiteSpace($processToken)) {
    $token = $processToken
} elseif (-not [string]::IsNullOrWhiteSpace($fileToken)) {
    $token = $fileToken
} else {
    $tokenBytes = New-Object byte[] 32
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()
    $generator.GetBytes($tokenBytes)
    $generator.Dispose()
    $token = [BitConverter]::ToString($tokenBytes).Replace('-', '').ToLowerInvariant()
}

if ($envText -match '(?m)^TELEMETRY_TOKEN=.*$') {
    $envText = [Regex]::Replace($envText, '(?m)^TELEMETRY_TOKEN=.*$', "TELEMETRY_TOKEN=$token")
} else {
    $envText = $envText.TrimEnd() + "`r`nTELEMETRY_TOKEN=$token`r`n"
}
[IO.File]::WriteAllText($envPath, $envText, $utf8)

$agentId = $null
if (Test-Path $agentConfig) {
    try {
        $existingConfig = Get-Content $agentConfig -Raw | ConvertFrom-Json
        $agentId = $existingConfig.agent_id
    } catch {
        $agentId = $null
    }
}
if ([string]::IsNullOrWhiteSpace($agentId)) {
    $agentId = [Guid]::NewGuid().ToString('N')
}

$configuration = [ordered]@{
    endpoint = $endpoints[0]
    endpoints = @($endpoints)
    token = $token
    agent_id = $agentId
    interval_seconds = 10
}
$configuration | ConvertTo-Json | Set-Content $agentConfig -Encoding utf8

php artisan config:clear | Out-Null

Write-Host 'Telemetria configurada com token privado.' -ForegroundColor Green
Write-Host "Execute: $agentDirectory\PC-Telemetry-Agent.exe"
