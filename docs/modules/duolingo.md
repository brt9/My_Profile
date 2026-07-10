# Duolingo experimental

## Contrato

A integração consulta um endpoint público não oficial no backend. Ela requer somente `DUOLINGO_USERNAME`; senha, cookie e sessão nunca são usados. O adaptador normaliza usuário, XP total, sequência e cursos e descarta o payload original.

## Resiliência e histórico

- timeout de 6 segundos e retry limitado;
- circuit breaker por 30 minutos após três falhas;
- sincronização a cada 6 horas;
- `upsert` garante no máximo um snapshot por usuário, idioma e dia;
- a home lê exclusivamente PostgreSQL e preserva o último snapshot como defasado;
- gráfico aparece somente depois de dois snapshots, com tabela textual equivalente.

## Configuração

```dotenv
DUOLINGO_ENABLED=true
DUOLINGO_USERNAME=nome_publico_exato
```

```powershell
php artisan duolingo:sync
php artisan schedule:work
```

Se o provedor mudar o payload, a home permanece disponível. Defina `DUOLINGO_ENABLED=false` para desligar job e interface.
