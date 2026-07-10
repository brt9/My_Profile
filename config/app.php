<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nome da Aplicação
    |--------------------------------------------------------------------------
    |
    | Este valor é o nome da sua aplicação. Ele é utilizado quando o framework
    | precisa exibir o nome da aplicação em notificações ou em elementos de UI.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Ambiente da Aplicação
    |--------------------------------------------------------------------------
    |
    | Determina em qual "ambiente" sua aplicação está rodando (local, staging,
    | production, etc.). Ajuste isso no arquivo ".env".
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Modo Debug
    |--------------------------------------------------------------------------
    |
    | Quando a aplicação está em modo debug, mensagens de erro detalhadas com
    | stack trace serão exibidas para qualquer erro ocorrido. Em produção,
    | isso deve estar desativado.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | URL da Aplicação
    |--------------------------------------------------------------------------
    |
    | A URL base usada pela console e por helpers para gerar links.
    | Configure com a raiz do seu projeto no ".env".
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Fuso Horário
    |--------------------------------------------------------------------------
    |
    | Define o fuso horário padrão da aplicação (usado por funções de data/hora).
    | Ajustado para o Brasil por padrão. Você pode sobrescrever com APP_TIMEZONE.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Localização (Locale) da Aplicação
    |--------------------------------------------------------------------------
    |
    | Define o locale padrão utilizado pelos recursos de tradução/localização
    | do Laravel. Ajustado para pt_BR por padrão.
    |
    */

    'locale' => env('APP_LOCALE', 'pt_BR'),

    /*
    |--------------------------------------------------------------------------
    | Locale de Fallback
    |--------------------------------------------------------------------------
    |
    | Locale utilizado quando o atual não possuir a tradução necessária.
    |
    */

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Locale do Faker
    |--------------------------------------------------------------------------
    |
    | Locale usado pelo Faker para gerar dados falsos ( seeds, factories ).
    |
    */

    'faker_locale' => env('APP_FAKER_LOCALE', 'pt_BR'),

    /*
    |--------------------------------------------------------------------------
    | Chave e Cifra de Criptografia
    |--------------------------------------------------------------------------
    |
    | A chave é utilizada pelos serviços de criptografia do Laravel e deve ter
    | 32 caracteres aleatórios. Defina-a antes de implantar a aplicação.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo de Manutenção
    |--------------------------------------------------------------------------
    |
    | Configura o driver do "modo de manutenção" do Laravel. O driver "cache"
    | permite controlar o modo em múltiplas máquinas.
    |
    | Drivers suportados: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
