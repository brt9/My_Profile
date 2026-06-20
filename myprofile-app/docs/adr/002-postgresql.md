# ADR-002 — PostgreSQL como banco-alvo

- Status: aceito e implementado
- Data: 2026-06-20

## Decisão

Migrar Docker, produção e testes de persistência para PostgreSQL antes de criar histórico de telemetria, Calendar ou Duolingo.

## Gate

- compose com health check PostgreSQL;
- migrations do zero;
- testes executados no PostgreSQL em CI;
- backup e restauração documentados.

O compose, o `.env.example`, a máquina local e o CI usam PostgreSQL 16. SQLite permanece apenas como verificação adicional de portabilidade. O procedimento de backup e restauração está em `docs/development.md`.
