# GitHub

`GitHubClient` normaliza perfil, repositórios, eventos públicos e calendários anuais exibidos pelo perfil público do usuário configurado. O ano atual fica em cache por 30 minutos e anos encerrados por 24 horas. A interface abre no ano atual e consulta `GET /api/github/contributions?year=AAAA` ao navegar para anos anteriores. Se o calendário público estiver indisponível, usa um fallback baseado somente nos eventos da API. Token é opcional e usado apenas no backend.

Timeout, retry e fallback da home evitam impacto quando o rate limit ou o provedor falhar.
