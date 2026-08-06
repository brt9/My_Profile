@echo off
set "SCRIPT=%~dp0start-telemetry-agent.ps1"
if not exist "%SCRIPT%" (
  echo Script nao encontrado: %SCRIPT%
  pause
  exit /b 1
)
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT%"
