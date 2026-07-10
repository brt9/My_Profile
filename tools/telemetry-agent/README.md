# PC Telemetry Agent

Agente Windows .NET 10 LTS que lê sensores pelo LibreHardwareMonitor/PawnIO e envia os dados para `POST /api/telemetry/push`.

## Métricas exibidas

- temperatura e carga da CPU;
- temperatura e carga da GPU;
- uso de memória e do disco principal;
- uptime e instante UTC da coleta.

O agente não envia hostname, usuário, IP ou identificadores de hardware. O `agent_id` é aleatório.

## Instalação

1. Execute `configure-telemetry.cmd` para criar o token e `telemetry-agent.json`.
2. Em Windows com Integridade de memória ativa, instale o [PawnIO oficial](https://github.com/namazso/PawnIO.Setup/releases/latest). Não desative a proteção do Windows.
3. Execute `build-telemetry-agent.ps1` após alterações no agente.
4. Execute `install-telemetry-agent-task.cmd` uma vez. A tarefa inicia o agente com privilégios elevados no login e o reinicia se houver falha.

O executável publicado fica em `dist/telemetry-agent/PC-Telemetry-Agent.exe`. Para uma execução manual use `start-telemetry-agent.cmd` e mantenha a janela aberta.

## Diagnóstico

```powershell
PC-Telemetry-Agent.exe --once
PC-Telemetry-Agent.exe --list-sensors
Get-ScheduledTask -TaskName "MyProfile PC Telemetry"
Invoke-RestMethod http://127.0.0.1:8085/api/telemetry/latest
```

O site considera a leitura defasada após 30 segundos e offline após 180 segundos sem uma nova amostra. O PostgreSQL preserva o último dado; preservar o dado não significa que o agente continua conectado.

Para um site publicado, altere `endpoint` em `telemetry-agent.json` para a URL HTTPS pública. O `token` deve ser igual ao `TELEMETRY_TOKEN` do servidor.
