# Clima

Open-Meteo fornece as métricas. A origem exibida pode ser fixa, aproximação por IP, fallback Natal/RN ou coordenada autorizada pelo navegador.

O visitante aciona a geolocalização manualmente. Coordenadas são enviadas em JSON para `POST /api/weather/location`, não são persistidas e não aparecem em logs. Falhas mantêm o dado anterior.
