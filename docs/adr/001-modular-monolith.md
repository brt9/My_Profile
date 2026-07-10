# ADR-001 — Monólito modular Laravel

- Status: aceito
- Data: 2026-06-20

## Decisão

Manter a home pública em Laravel, Blade, Tailwind e Alpine. Clientes externos ficam em serviços próprios e a view recebe dados normalizados.

## Consequências

Há um único deploy e uma única política de cache/erro. React/Inertia permanece apenas na área autenticada existente. Next.js exige uma ADR futura com benefício mensurável.
