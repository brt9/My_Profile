# Desenvolvimento local

## Requisitos

- PHP 8.3+ e Composer;
- Node.js 20+ e npm;
- .NET 8 Runtime para executar o agente;
- SDK .NET 8 apenas para recompilar o agente.

## Instalação

```powershell
docker compose up -d postgres
composer setup
start-portfolio.cmd
```

Ou dê dois cliques em `start-portfolio.cmd`. O inicializador aplica migrations e mantém `schedule:work` ativo enquanto o servidor estiver aberto.

O script de inicialização ativa compressão HTTP para aproximar o servidor local do comportamento esperado em produção. Em deploy, mantenha Brotli ou gzip habilitado no proxy/web server.

## Variáveis do portfólio

| Variável | Obrigatória | Sensível | Finalidade |
|---|---:|---:|---|
| `PORTFOLIO_NAME` | não | não | nome público |
| `PORTFOLIO_ROLE` | não | não | cargo público |
| `PORTFOLIO_LOCATION` | não | não | localização resumida |
| `PORTFOLIO_COMPANY` | não | não | empresa atual validada |
| `PORTFOLIO_EMAIL` | não | sim/pessoal | ativa contato por e-mail |
| `PORTFOLIO_GITHUB` | não | não | URL pública do GitHub |
| `PORTFOLIO_LINKEDIN` | não | não | URL pública no formato `/in/...` |
| `PORTFOLIO_ADMIN_EMAIL` | sim para administrar | pessoal | único usuário autorizado a administrar integrações |
| `GITHUB_USERNAME` | sim para o card | não | usuário público consultado |
| `GITHUB_TOKEN` | não | sim | aumenta o rate limit da API |
| `STEAM_API_KEY` | sim para Steam | sim | autentica a Steam Web API |
| `STEAM_ID` | sim para Steam | não | identifica o perfil público |
| `TELEMETRY_TOKEN` | sim para agente | sim | autentica a ingestão |
| `TELEMETRY_RAW_RETENTION_DAYS` | não | não | retenção de snapshots brutos |
| `TELEMETRY_AGGREGATE_RETENTION_DAYS` | não | não | retenção de agregados horários |
| `PORTFOLIO_WEATHER_ENABLED` | não | não | feature flag do clima |
| `GOOGLE_CALENDAR_ENABLED` | não | não | feature flag da agenda |
| `GOOGLE_CALENDAR_CLIENT_ID` | sim para Calendar | não | cliente OAuth Web do Google |
| `GOOGLE_CALENDAR_CLIENT_SECRET` | sim para Calendar | sim | segredo OAuth do Google |
| `GOOGLE_CALENDAR_REDIRECT_URI` | sim para Calendar | não | callback cadastrado no Google Cloud |
| `GOOGLE_CALENDAR_WRITE_ENABLED` | não | não | ativa criação, edição e exclusão também no Google |
| `GOOGLE_CALENDAR_PUBLIC_EVENT_IDS` | não | pessoal | IDs autorizados a publicar título; vazio usa somente FreeBusy |
| `GOOGLE_LOGIN_ENABLED` | não | não | habilita o botão de login Google |
| `GOOGLE_LOGIN_REDIRECT_URI` | sim para login Google | não | callback `/auth/google/callback` cadastrado no Google Cloud |
| `DUOLINGO_ENABLED` | não | não | feature flag experimental |
| `DUOLINGO_USERNAME` | sim para Duolingo | não | nome público exato no perfil |

Nunca copie valores reais para `.env.example`, documentação, logs ou issues.

## Telemetria

1. Execute `configure-telemetry.cmd` para gerar e alinhar o token.
2. Execute `start-telemetry-agent.cmd` como administrador.
3. Use `PC-Telemetry-Agent.exe --list-sensors` para diagnóstico.
4. Em produção, mantenha `php artisan schedule:work` supervisionado.

O executável gerado fica em `dist/telemetry-agent` e não é versionado. Para recompilar, instale o SDK local e execute `build-telemetry-agent.ps1`.

## PostgreSQL e Docker

O compose usa PostgreSQL 16 na porta local `5433`, com health check e volume persistente. A suíte do CI também executa contra PostgreSQL.

```powershell
docker compose up -d postgres
php artisan migrate --force
```

Backup lógico e restauração:

```powershell
docker compose exec -T postgres pg_dump -U myprofile --clean --if-exists myprofile > myprofile-backup.sql
Get-Content myprofile-backup.sql | docker compose exec -T postgres psql -U myprofile -d myprofile
```

Teste rápido de portabilidade em SQLite continua disponível definindo `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:` somente no processo de teste.

## Integrações profissionais

- Google Agenda: siga [docs/modules/calendar.md](modules/calendar.md). Uma API key não autoriza agendas privadas; é obrigatório criar um cliente OAuth Web.
- Login Google: no mesmo cliente OAuth Web, cadastre também `${APP_URL}/auth/google/callback` como URI de redirecionamento autorizada.
- Duolingo: configure o nome público e siga [docs/modules/duolingo.md](modules/duolingo.md).
- Perfil profissional: revise experiências, formação e idiomas diretamente em `config/portfolio.php`.
