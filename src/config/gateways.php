<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TTL do cache do token do Gateway 1 (em segundos)
    |--------------------------------------------------------------------------
    */
    'token_cache_ttl' => (int) env('GATEWAY_TOKEN_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Mapa de drivers disponíveis
    |--------------------------------------------------------------------------
    | Associa o valor da coluna `driver` na tabela gateways à classe PHP
    | correspondente. Para adicionar um novo gateway, basta criar o adapter
    | e registrá-lo aqui.
    */
    'drivers' => [
        'Gateway1Adapter' => \App\Gateways\Gateway1Adapter::class,
        'Gateway2Adapter' => \App\Gateways\Gateway2Adapter::class,
    ],
];
