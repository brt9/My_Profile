# Agenda local e Google Calendar

## Fonte de verdade

Os compromissos são armazenados em `calendar_events`. A home lê somente o banco local, portanto continua funcionando sem conta Google ou durante uma indisponibilidade do provedor.

O administrador autenticado usa os endpoints protegidos por sessão, verificação de e-mail, middleware administrativo e CSRF:

- `GET /api/calendar/events`
- `POST /api/calendar/events`
- `PUT /api/calendar/events/{event}`
- `DELETE /api/calendar/events/{event}`

Excluir marca o registro como `cancelado`, preservando o histórico local. Dois ou mais eventos no mesmo dia são mantidos como registros separados e exibidos em linhas independentes no diagrama de Gantt.

## Google opcional

O polling executa `calendar:sync` a cada 15 minutos e faz upsert do espelho seguro dos eventos. A home nunca consulta o Google diretamente.

Com a configuração abaixo, atualmente ativa no ambiente local, o OAuth solicita `calendar.events` e permite que criações, edições e exclusões locais também sejam enviadas ao Google:

```dotenv
GOOGLE_CALENDAR_WRITE_ENABLED=true
```

Ao mudar essa opção é necessário autorizar a conta novamente. Se a chamada externa falhar, a operação local permanece válida e `sync_status` registra o erro para nova tentativa.

## Privacidade

Eventos importados do Google armazenam apenas projeções públicas. Com `GOOGLE_CALENDAR_PUBLIC_EVENT_IDS` vazio, o título vira **Compromisso**. Não são persistidos participantes, descrição, sala, link de reunião ou payload bruto; a chave do provedor é um SHA-256 e o ID necessário para escrita fica criptografado.

Ao revogar o Google, o espelho vindo do provedor é removido. Eventos criados localmente são preservados e voltam ao estado `local_only`.

## Operação

```powershell
php artisan calendar:sync
php artisan schedule:work
```
