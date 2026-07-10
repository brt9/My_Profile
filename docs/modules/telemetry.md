# Telemetria

O agente .NET 10 LTS usa LibreHardwareMonitor com PawnIO para coletar CPU, GPU, memória, disco principal e uptime. A tarefa agendada `MyProfile PC Telemetry` inicia o agente com privilégios elevados no login do Windows e tenta reiniciá-lo quando ocorre uma falha.

Nenhum hostname, usuário, IP público ou identificador de hardware é enviado. `agent_id` é um UUID aleatório criado pela configuração.

## Fluxo

```text
agente 1.3 -> POST /api/telemetry/push -> validação + idempotência
                                      -> MySQL (snapshot bruto)
                                      -> cache (última leitura)
                                      -> integration_health
```

O token Bearer é comparado em tempo constante. O rate limit deriva do hash do token, o corpo é limitado a 16 KiB e o instante da coleta só é aceito dentro da janela configurada.

## Estados

- `online`: amostra recebida há no máximo 30 segundos;
- `stale`: última amostra tem mais de 30 segundos;
- `offline`: nenhuma amostra nova há mais de 180 segundos.

O banco preserva a última leitura para diagnóstico e histórico. Portanto, valores ainda visíveis não significam que o processo está online; o timestamp e o estado indicam se a coleta continua ativa.

## Instalação no Windows

1. `configure-telemetry.cmd` configura token, endpoint e identificador.
2. PawnIO deve estar instalado quando a Integridade de memória estiver ativa.
3. `build-telemetry-agent.ps1` publica o executável.
4. `install-telemetry-agent-task.cmd` registra e inicia a tarefa automática.

Para diagnóstico manual, use `start-telemetry-agent.cmd` e mantenha sua janela aberta. Mais detalhes estão em `tools/telemetry-agent/README.md`.

## Endpoints

- `GET /api/telemetry/latest`: última leitura e estado da máquina;
- `GET /api/telemetry/history?metric=cpu_load&range=6h&resolution=5m`: série agregada e limitada;
- `GET /api/health/integrations`: saúde operacional sanitizada;
- `POST /api/telemetry/push`: ingestão privada do agente.

Métricas não suportadas permanecem `null`; zero é preservado. Pontos ausentes no histórico são devolvidos como `null` e o Chart.js usa `spanGaps: false`, sem inventar lacunas.

## Retenção

`php artisan telemetry:maintain` cria agregados horários, remove dados brutos após 7 dias e agregados após 90 dias. O comando roda de hora em hora em `routes/console.php`; produção deve executar `php artisan schedule:run` por cron ou `php artisan schedule:work` como processo supervisionado.
