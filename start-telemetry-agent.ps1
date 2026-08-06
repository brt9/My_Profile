$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$dotnet = Join-Path $PSScriptRoot '.tools\dotnet\dotnet.exe'
$agentDirectory = Join-Path $PSScriptRoot 'dist\telemetry-agent'
$agent = Join-Path $agentDirectory 'PC-Telemetry-Agent.dll'
$configuration = Join-Path $agentDirectory 'telemetry-agent.json'

if (-not (Test-Path -LiteralPath $dotnet)) {
    throw "Host .NET não encontrado: $dotnet"
}

if (-not (Test-Path -LiteralPath $agent)) {
    throw "Agente não encontrado: $agent"
}

if (-not (Test-Path -LiteralPath $configuration)) {
    throw 'Configuração não encontrada. Execute configure-telemetry.cmd primeiro.'
}

$arguments = "`"$agent`" --config `"$configuration`""
Start-Process -FilePath $dotnet -ArgumentList $arguments -WorkingDirectory $agentDirectory -Verb RunAs
