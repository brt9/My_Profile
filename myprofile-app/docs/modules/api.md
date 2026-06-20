# API

## Rotas públicas

- `GET /api/telemetry`: última leitura sanitizada.
- `POST /api/weather/location`: clima para coordenadas autorizadas, com rate limit.

## Rota autenticada por token

- `POST /api/telemetry/push`: ingestão do agente, com rate limit e validação por allowlist.

Erros usam mensagens genéricas. Detalhes de integração ficam somente nos logs sanitizados.
