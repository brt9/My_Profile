# PC Telemetry Agent

Agente Windows que lê sensores usando LibreHardwareMonitor e envia os dados para `POST /api/telemetry/push`.

## Métricas

- temperatura e carga da CPU;
- temperatura e carga da GPU;
- uso de memória e do disco principal;
- uptime em segundos e instante UTC da coleta;
- rotação da bomba e temperatura do líquido quando o controlador expõe esses sensores;
- versão e identificador aleatório do agente, sem publicar o nome interno do computador.

## Uso

O executável compilado fica em `dist/telemetry-agent/PC-Telemetry-Agent.exe`.

1. Execute `configure-telemetry.cmd` uma vez.
2. Execute `start-telemetry-agent.cmd` para iniciar como administrador.
3. Mantenha o site rodando em `http://127.0.0.1:8085`.

Parâmetros úteis:

```powershell
PC-Telemetry-Agent.exe --once
PC-Telemetry-Agent.exe --list-sensors
PC-Telemetry-Agent.exe --config C:\caminho\telemetry-agent.json
```

Para um site publicado, altere `endpoint` em `telemetry-agent.json` para a URL HTTPS pública. O valor de `token` deve ser igual ao `TELEMETRY_TOKEN` configurado no servidor.

Alguns sensores, especialmente temperatura da CPU e controladores Corsair, podem exigir execução como administrador ou não estar disponíveis via LibreHardwareMonitor.
