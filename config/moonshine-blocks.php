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

    /*
    |--------------------------------------------------------------------------
    | Content Field Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки для поля контента в блоках.
    | Позволяет добавить отдельную вкладку с кастомным полем (например, Layouts).
    |
    */
    'content' => [
        // Включить вкладку контента
        'enabled' => env('MOONSHINE_BLOCKS_CONTENT_ENABLED', false),

        // Поле для редактирования контента
        // Варианты:
        // - null: используется Textarea по умолчанию
        // - string (class-string): класс с методом make() возвращающим Field
        // - callable: функция возвращающая Field
        //
        // Примеры:
        // 'field' => \App\MoonShine\LayoutsContent\LayoutsContent::class,
        // 'field' => fn() => \MoonShine\Layouts\Fields\Layouts::make('', 'content'),
        'field' => null,

        // Cast для колонки content в модели BlockItem
        // Варианты:
        // - null или 'array': стандартный JSON cast
        // - string (class-string): кастомный Cast класс
        //
        // Примеры:
        // 'cast' => \MoonShine\Layouts\Casts\LayoutsCast::class,
        'cast' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fieldsets Configuration
    |--------------------------------------------------------------------------
    |
    | Путь к директории с JSON-файлами шаблонов полей (fieldsets).
    |
    | Формат файла (resources/blocks/fieldsets/seo.json):
    |   {
    |     "title": "SEO поля",
    |     "fields": [
    |       {"name": "Meta Title", "key": "meta_title", "type": "text"},
    |       ...
    |     ]
    |   }
    |
    */
    'fieldsets' => [
        'path' => resource_path('blocks/fieldsets'),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки отображения и поведения UI.
    |
    */
    'ui' => [
        // Значение сортировки по умолчанию для новых элементов
        'sorting_default' => 500,

        // Количество элементов на странице в списках
        'per_page' => 20,

        // Длительность toast-уведомлений в миллисекундах
        'toast_duration' => 2500,
    ],
];
