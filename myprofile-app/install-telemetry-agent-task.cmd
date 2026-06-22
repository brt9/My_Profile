@echo off
set "SCRIPT=%~dp0install-telemetry-agent-task.ps1"
if not exist "%SCRIPT%" (
  echo Script nao encontrado: %SCRIPT%
  pause
  exit /b 1
)
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -Command "Start-Process -FilePath '%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe' -Verb RunAs -Wait -ArgumentList '-NoProfile','-ExecutionPolicy','Bypass','-File','""%SCRIPT%""'"
