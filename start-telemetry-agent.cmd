@echo off
set "AGENT=%~dp0dist\telemetry-agent\PC-Telemetry-Agent.exe"
if not exist "%AGENT%" (
  echo Executavel nao encontrado: %AGENT%
  pause
  exit /b 1
)
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -Command "Start-Process -FilePath '%AGENT%' -Verb RunAs"
