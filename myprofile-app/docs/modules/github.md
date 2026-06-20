# GitHub

`GitHubClient` normaliza perfil, repositórios e eventos públicos do usuário configurado. O resultado fica em cache por 30 minutos. Token é opcional e usado apenas no backend.

Timeout, retry e fallback da home evitam impacto quando o rate limit ou o provedor falhar.
