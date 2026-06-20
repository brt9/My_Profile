# Segurança e privacidade

## Segredos

- `.env` é ignorado pelo Git.
- `.env.example` contém somente nomes e valores públicos seguros.
- `npm run test:secrets` verifica arquivos versionáveis antes do commit.
- Uma chave Steam que apareça fora do `.env` deve ser revogada, não apenas movida.

## Telemetria

- ingestão exige Bearer token comparado com `hash_equals`;
- payload possui allowlist, faixas numéricas e limite de tamanho implícito pela validação;
- hostname, usuário Windows, IP público e identificadores de hardware não são armazenados;
- o endpoint público retorna somente métricas normalizadas e versão do agente.

## Localização

- localização aproximada por IP é identificada na interface;
- coordenadas do navegador exigem clique e permissão explícita;
- coordenadas são usadas somente para consultar o clima e não são persistidas/logadas;
- negação, timeout ou indisponibilidade mantêm o fallback anterior.

## Logs

Logs de integração usam nome do provedor e estado, nunca token, URL com IP, coordenadas ou payload pessoal. Health checks detalhados futuros ficarão atrás de autenticação.
