<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки для отображения ссылок на API в админ-панели.
    | API реализован в отдельном пакете moonshine-blocks-api.
    |
    */
    'api' => [
        // Включить отображение ссылок на API в таблицах
        'enabled' => env('MOONSHINE_BLOCKS_API_ENABLED', false),

        // Базовый URL для API (по умолчанию используется URL текущего приложения)
        // Примеры: 'https://api.example.com', 'https://example.com'
        'base_url' => env('MOONSHINE_BLOCKS_API_URL', null),

        // Префикс API (должен совпадать с настройкой в moonshine-blocks-api)
        'prefix' => env('MOONSHINE_BLOCKS_API_PREFIX', 'api/blocks'),
    ],
];
