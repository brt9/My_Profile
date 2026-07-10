$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$taskName = 'MyProfile PC Telemetry'
$agentDirectory = Join-Path $PSScriptRoot 'dist\telemetry-agent'
$agent = Join-Path $agentDirectory 'PC-Telemetry-Agent.exe'
$configuration = Join-Path $agentDirectory 'telemetry-agent.json'

if (-not (Test-Path -LiteralPath $agent)) {
    throw "Executavel nao encontrado: $agent"
}

if (-not (Test-Path -LiteralPath $configuration)) {
    throw 'Configuracao nao encontrada. Execute configure-telemetry.cmd primeiro.'
}

$user = [Security.Principal.WindowsIdentity]::GetCurrent().Name
$powershell = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
$escapedAgent = $agent.Replace("'", "''")
$arguments = "-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -Command `"& '$escapedAgent'`""
$action = New-ScheduledTaskAction -Execute $powershell -Argument $arguments -WorkingDirectory $agentDirectory
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

Get-Process -Name 'PC-Telemetry-Agent' -ErrorAction SilentlyContinue | Stop-Process -Force
Start-ScheduledTask -TaskName $taskName
Start-Sleep -Seconds 2

$task = Get-ScheduledTask -TaskName $taskName
Write-Host "Tarefa '$taskName' instalada e iniciada: $($task.State)" -ForegroundColor Green
