$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$dotnet = Join-Path $PSScriptRoot '.tools\dotnet\dotnet.exe'
if (-not (Test-Path $dotnet)) {
    throw 'SDK local não encontrado. Execute a instalação do SDK .NET 8 antes de compilar.'
}

$project = Join-Path $PSScriptRoot 'tools\telemetry-agent\PcTelemetryAgent.csproj'
$output = Join-Path $PSScriptRoot 'dist\telemetry-agent'

& $dotnet publish $project -c Release -r win-x64 --self-contained false -p:PublishSingleFile=true -o $output
if ($LASTEXITCODE -ne 0) {
    throw "Falha ao compilar o agente (código $LASTEXITCODE)."
}

Write-Host "Executável criado em $output\PC-Telemetry-Agent.exe" -ForegroundColor Green
