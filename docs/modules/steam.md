# Steam

`SteamClient` consulta presença, biblioteca, jogos recentes e conquistas, com timeout, retry e cache uniforme de 30 minutos. Durante esse intervalo, novas visitas reutilizam o snapshot e não fazem chamadas à Steam Web API. O estado “não está jogando” também é armazenado, evitando consultas repetidas. A integração é desativada quando chave ou Steam ID não estão configurados.

Imagens usam header, capsule e fallback local. Uma falha Steam nunca impede a renderização da home. Chaves são exclusivas do `.env`.
