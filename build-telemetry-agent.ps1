$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$dotnet = Join-Path $PSScriptRoot '.tools\dotnet\dotnet.exe'
if (-not (Test-Path $dotnet)) {
    throw 'SDK local não encontrado. Instale o SDK .NET 10 em .tools\dotnet antes de compilar.'
}

$project = Join-Path $PSScriptRoot 'tools\telemetry-agent\PcTelemetryAgent.csproj'
$output = Join-Path $PSScriptRoot 'dist\telemetry-agent'

& $dotnet publish $project -c Release -r win-x64 --self-contained false -p:UseAppHost=false -p:PublishSingleFile=false -o $output
if ($LASTEXITCODE -ne 0) {
    throw "Falha ao compilar o agente (código $LASTEXITCODE)."
}

$legacyExecutable = Join-Path $output 'PC-Telemetry-Agent.exe'
if (Test-Path -LiteralPath $legacyExecutable) {
    Remove-Item -LiteralPath $legacyExecutable -Force
}

Write-Host "Agente criado em $output\PC-Telemetry-Agent.dll" -ForegroundColor Green
