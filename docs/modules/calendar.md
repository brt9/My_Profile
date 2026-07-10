# Agenda local e Google Calendar

## Fonte de verdade

Os compromissos são armazenados em `calendar_events`. A home lê somente o banco local, portanto continua funcionando sem conta Google ou durante uma indisponibilidade do provedor.

O administrador autenticado usa os endpoints protegidos por sessão, middleware administrativo e CSRF. A verificação de e-mail é ignorada somente quando `APP_DEBUG=true`; em produção continua obrigatória:

- `GET /api/calendar/events`
- `POST /api/calendar/events`
- `PUT /api/calendar/events/{event}`
- `DELETE /api/calendar/events/{event}`

Excluir marca o registro como `cancelado`, preservando o histórico local. Dois ou mais eventos no mesmo dia são mantidos como registros separados e exibidos em linhas independentes no diagrama de Gantt.

## Conexão automática com o Google

O polling executa `calendar:sync` a cada 15 minutos e faz upsert do espelho seguro dos eventos. A home nunca consulta o Google diretamente.

Quando o administrador entra com senha ou com Google e ainda não existe uma conexão válida, a aplicação inicia automaticamente o OAuth do Google Agenda. O consentimento é solicitado na primeira conexão ou quando o Google exige nova autorização; não há botão manual para conectar ou sincronizar.

Com a configuração abaixo, atualmente ativa no ambiente local, o OAuth solicita `calendar.events`. Criações, edições e exclusões feitas pelo formulário são enviadas automaticamente ao Google quando a conta está conectada:

```dotenv
GOOGLE_CALENDAR_WRITE_ENABLED=true
```

Ao mudar essa opção é necessário entrar novamente para renovar a autorização. Se a chamada externa falhar, a operação local permanece válida e `sync_status` registra o erro para nova tentativa.

O envio ao Google usa a fila `database` depois que a API já respondeu ao navegador. O inicializador `start-portfolio.cmd` mantém `queue:work` ativo, evitando que o botão aguarde a rede do Google.

## Privacidade

Eventos importados do Google armazenam apenas a projeção necessária. Com `GOOGLE_CALENDAR_SHOW_EVENT_TITLES=true`, todos exibem o título sanitizado. A allowlist `GOOGLE_CALENDAR_PUBLIC_EVENT_IDS` permite liberar apenas títulos específicos quando a opção global estiver desativada. Não são persistidos participantes, descrição, sala, link de reunião ou payload bruto; a chave do provedor é um SHA-256 e o ID necessário para escrita fica criptografado.

Ao revogar o Google, o espelho vindo do provedor é removido. Eventos criados localmente são preservados e voltam ao estado `local_only`.

## Operação

```powershell
php artisan calendar:sync
php artisan queue:work
php artisan schedule:work
```
