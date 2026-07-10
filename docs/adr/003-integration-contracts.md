# ADR-003 — Contratos internos de integrações

- Status: aceito
- Data: 2026-06-20

## Decisão

Toda integração possui cliente/controle interno, timeout, retry limitado, cache e estados normalizados: `loading`, `available`, `stale`, `unavailable`, `unsupported`, `error` e `disabled`.

## Consequências

Blade e JavaScript não dependem diretamente do payload bruto do provedor. Falhas são isoladas e mensagens internas não são expostas.
