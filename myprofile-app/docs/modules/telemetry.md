# Telemetria

O agente .NET 8 usa LibreHardwareMonitor e APIs seguras do Windows para coletar CPU, GPU, memória, disco principal, uptime e controladores opcionais. Nenhum hostname, usuário, IP público ou identificador de hardware é enviado. `agent_id` é um UUID aleatório criado pela configuração.

## Fluxo

```text
agente 1.1 -> POST /api/telemetry/push -> validação + idempotência
                                      -> PostgreSQL (snapshot bruto)
                                      -> cache (última leitura)
                                      -> integration_health
```

O token Bearer é comparado em tempo constante. O rate limit é derivado do hash do token, o corpo é limitado a 16 KiB e o instante de coleta só é aceito dentro da janela configurada.

## Endpoints

- `GET /api/telemetry/latest`: última leitura com estado `available`, `stale` ou `unavailable`;
- `GET /api/telemetry/history?metric=cpu_load&range=6h&resolution=5m`: série agregada e limitada;
- `GET /api/health/integrations`: saúde operacional sanitizada;
- `POST /api/telemetry/push`: ingestão privada do agente.

Métricas não suportadas permanecem `null`; zero é preservado. Pontos ausentes no histórico são devolvidos como `null` e o Chart.js usa `spanGaps: false`, portanto nenhuma lacuna é inventada.

## Retenção

`php artisan telemetry:maintain` cria agregados horários, remove dados brutos após 7 dias e agregados após 90 dias. Os prazos são configuráveis. O comando é agendado de hora em hora em `routes/console.php`; produção deve executar `php artisan schedule:run` por cron ou `php artisan schedule:work` como processo supervisionado.
