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

O compose e a máquina local preservam PostgreSQL 16 para não converter implicitamente o volume persistente. A CI usa PostgreSQL 18 em banco descartável. SQLite permanece apenas como verificação adicional de portabilidade. O procedimento de backup e restauração está em `docs/development.md`.
