# Segurança e privacidade

## Segredos

- `.env` é ignorado pelo Git.
- `.env.example` contém somente nomes e valores públicos seguros.
- `npm run test:secrets` verifica arquivos versionáveis antes do commit.
- Uma chave Steam que apareça fora do `.env` deve ser revogada, não apenas movida.
- API keys ou segredos Google enviados por chat também devem ser rotacionados e restringidos no Google Cloud.

## Google Agenda

- conexão, callback, sincronização manual e revogação exigem usuário verificado cujo e-mail coincide com `PORTFOLIO_ADMIN_EMAIL`;
- OAuth usa state descartável e refresh token com cast `encrypted`;
- o escopo é `calendar.events` quando a escrita está ativa e `calendar.events.readonly` quando desativada; a projeção local mantém títulos genéricos por padrão;
- a projeção local guarda título genérico, categoria, horário e estado; nunca descrição, participantes, localização ou link de reunião;
- revogação remove o espelho vindo do Google e preserva compromissos criados localmente; token inválido mantém o último snapshot marcado como defasado.

## Login com Google

- usa escopos mínimos `openid email profile`, `state` descartável e somente e-mails marcados como verificados pelo Google;
- contas existentes são vinculadas pelo e-mail verificado; um vínculo Google diferente já existente é rejeitado;
- o identificador Google não é exposto nas propriedades públicas do usuário e nenhum access token é persistido.

## Duolingo e perfil profissional

- Duolingo usa somente perfil público, timeout curto, retry limitado, circuit breaker e nenhum payload bruto;
- o conteúdo profissional é revisado no código e não oferece upload, scraping ou persistência administrativa.

## Telemetria

- ingestão exige Bearer token comparado com `hash_equals`;
- payload possui allowlist, faixas numéricas e limite de tamanho implícito pela validação;
- hostname, usuário Windows, IP público e identificadores de hardware não são armazenados;
- o endpoint público retorna somente métricas normalizadas e versão do agente.

## Localização

- localização aproximada por IP é identificada automaticamente e sinalizada na interface;
- IP e coordenadas aproximadas não são persistidos nem retornados ao navegador;
- coordenadas do navegador exigem clique e permissão explícita;
- coordenadas são usadas somente para consultar o clima e não são persistidas/logadas;
- negação, timeout ou indisponibilidade mantêm o fallback anterior.

## Logs

Logs de integração usam nome do provedor e estado, nunca token, URL com IP, coordenadas ou payload pessoal. Health checks detalhados futuros ficarão atrás de autenticação.
