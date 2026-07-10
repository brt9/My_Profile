# Clima

Open-Meteo fornece os dados de Natal/RN e da cidade aproximada do visitante. As duas localizações são apresentadas em blocos independentes.

## Natal persistente

`weather:capture-natal` executa a cada 15 minutos. Capturas válidas são salvas em `weather_snapshots` e registros com mais de 30 dias são removidos. A home procura primeiro o cache e depois o último snapshot do banco.

Se a API estiver indisponível, o card mantém temperatura, sensação, umidade, vento e condição do último registro e mostra sua data e horário. Se nunca houve captura, a interface informa que ainda não existe dado salvo.

```powershell
php artisan weather:capture-natal
php artisan schedule:work
```

## Visitante

`GET /api/weather/visitor` identifica automaticamente a cidade aproximada pelo IP público. O IP e as coordenadas não são persistidos nem retornados pela API pública.

Após consentimento explícito do navegador, `POST /api/weather/location` consulta coordenadas mais precisas. Negação, timeout ou erro preservam o dado aproximado anterior.
