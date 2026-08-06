$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$taskName = 'MyProfile PC Telemetry'
$agentDirectory = Join-Path $PSScriptRoot 'dist\telemetry-agent'
$dotnet = Join-Path $PSScriptRoot '.tools\dotnet\dotnet.exe'
$agent = Join-Path $agentDirectory 'PC-Telemetry-Agent.dll'
$configuration = Join-Path $agentDirectory 'telemetry-agent.json'

if (-not (Test-Path -LiteralPath $dotnet)) {
    throw "Host .NET não encontrado: $dotnet"
}

if (-not (Test-Path -LiteralPath $agent)) {
    throw "Agente nao encontrado: $agent"
}

if (-not (Test-Path -LiteralPath $configuration)) {
    throw 'Configuracao nao encontrada. Execute configure-telemetry.cmd primeiro.'
}

$user = [Security.Principal.WindowsIdentity]::GetCurrent().Name
$arguments = "`"$agent`" --config `"$configuration`""
$action = New-ScheduledTaskAction -Execute $dotnet -Argument $arguments -WorkingDirectory $agentDirectory
$trigger = New-ScheduledTaskTrigger -AtLogOn -User $user
$principal = New-ScheduledTaskPrincipal -UserId $user -LogonType Interactive -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -RestartCount 10 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Description 'Envia telemetria local do PC para o MyProfile.' `
    -Force | Out-Null

Get-CimInstance Win32_Process -Filter "Name = 'dotnet.exe'" |
    Where-Object { $_.CommandLine -like '*PC-Telemetry-Agent.dll*' } |
    ForEach-Object { Stop-Process -Id $_.ProcessId -Force }
Start-ScheduledTask -TaskName $taskName
Start-Sleep -Seconds 2

$task = Get-ScheduledTask -TaskName $taskName
Write-Host "Tarefa '$taskName' instalada e iniciada: $($task.State)" -ForegroundColor Green
