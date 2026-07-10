# Code review — 21/06/2026

## Resultado

A aplicação foi atualizada para as versões estáveis mais recentes compatíveis e passou por revisão de dependências, contratos de domínio, frontend, containers, CI e agente Windows.

| Área | Versão validada |
|---|---|
| Backend | Laravel 13.16.1, Inertia Laravel 3.1, Tinker 3 |
| Frontend | React 19.2, Inertia React 3.4, Tailwind CSS 4.3, Vite 8, Alpine.js 3.15 |
| Runtime | PHP 8.5 no Docker, Node.js 24 LTS, .NET 10 LTS |
| Infraestrutura | Nginx 1.29, PostgreSQL 18 na CI |
| Qualidade | Pest 4, Pint e Larastan 3.10/PHPStan 2.2 |

## Correções aplicadas

- removidos TallStackUI/Livewire e arquivos de scaffold sem uso;
- removidos CDN não versionado do Alpine, Dockerfile duplicado e configuração Vite duplicada;
- migrado Tailwind 3 para 4 e React 18 para 19;
- corrigido o contrato de verificação de e-mail do model `User`;
- tipados casts e relacionamentos dos models de agenda, Duolingo, clima e integrações;
- simplificados fluxos redundantes de Google Calendar, Steam e GitHub;
- separado o ciclo de vida PHP do serviço Node no Docker;
- adicionada compilação do agente .NET à CI;
- adicionada análise estática de nível 5 sem baseline ou supressões;
- adicionada configuração semanal do Dependabot por ecossistema, agrupando atualizações compatíveis para reduzir conflitos.

## Dependabot

Não havia pull request nem branch `dependabot/*` aberta no repositório no momento da revisão. Composer, npm e NuGet ficaram sem atualizações pendentes e sem vulnerabilidades conhecidas. A nova configuração cobre Composer, npm, NuGet, Docker e GitHub Actions.

## Decisão de persistência

O volume local permanece em PostgreSQL 16. A CI usa PostgreSQL 18 em banco descartável. Atualizar o volume persistente sem `pg_dump`/restore seria uma migração destrutiva implícita; por isso essa mudança deve ocorrer em uma janela própria, seguindo `docs/development.md`.

## Validações executadas

- 84 testes, 465 asserções;
- PHPStan/Larastan sem erros;
- Pint sem alterações pendentes;
- build Vite de produção;
- auditorias Composer e npm sem vulnerabilidades;
- responsividade de 320 a 1440 px e estados interativos principais;
- imagens Docker PHP/Nginx construídas;
- agente .NET 10 compilado e publicado para `win-x64` sem warnings;
- workflow GitHub Actions validado por `actionlint`;
- scanner de segredos sem achados.
