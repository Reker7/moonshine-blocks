<?php

return [
    // Common
    'title' => 'Название',
    'slug' => 'Символьный код',
    'is_active' => 'Активен',
    'sorting' => 'Сортировка',
    'created_at' => 'Создано',
    'category' => 'Категория',

    // Actions
    'save' => 'Сохранить',
    'create' => 'Создать',
    'back' => 'Назад',
    'delete_confirm' => 'Вы уверены, что хотите удалить?',
    'editing' => 'Редактирование',
    'creating' => 'Создание',

    // Pages
    'block_item' => 'Элемент блока',
    'block_items' => 'Элементы блока',
    'block_not_found' => 'Блок не найден',
    'categories' => 'Категории',
    'items_list' => 'Элементы',

    // Fields
    'fields' => [
        'name' => 'Название',
        'slug' => 'Слаг',
        'is_active' => 'Активен',
        'sorting' => 'Сортировка',
    ],

    // Export
    'export' => [
        'button' => 'Экспорт',
        'title' => 'Экспорт блоков',
        'include_groups' => 'Экспортировать с группами',
        'include_groups_hint' => 'Если включено, группы блоков также будут экспортированы',
        'hint' => 'Выберите блоки в таблице и нажмите "Сгенерировать". Скопируйте полученный код для импорта.',
        'generate' => 'Сгенерировать',
        'result' => 'Результат',
        'select_blocks' => 'Выберите блоки для экспорта',
        'success' => 'Экспорт выполнен успешно',
        'error' => 'Ошибка экспорта',
        'copied' => 'Скопировано в буфер обмена',
    ],

    // Import
    'import' => [
        'button' => 'Импорт',
        'title' => 'Импорт блоков',
        'data_label' => 'Данные для импорта',
        'placeholder' => 'Вставьте сюда экспортированную строку...',
        'hint' => 'Вставьте код экспорта и нажмите "Импорт". Существующие блоки с такими же слагами будут обновлены.',
        'enter_data' => 'Введите данные для импорта',
        'error' => 'Ошибка импорта',
        'success' => 'Импортировано: :groups групп, :blocks блоков',
        'partial_success' => 'Импортировано: :groups групп, :blocks блоков. Ошибок: :errors',
    ],

    // Field Presets
    'field_preset' => [
        'description' => 'Описание',
        'fields' => 'Поля пресета',
        'select_presets' => 'Выберите пресеты',
        'presets_hint' => 'Поля из выбранных пресетов будут добавлены к форме блока',
    ],

    // FieldsBuilder
    'name' => 'Название',
    'key' => 'Ключ',
    'type' => 'Тип',
    'required' => 'Обязательное',
    'field_name' => 'Название поля',
    'field_key' => 'ключ_поля',
    'field_type' => 'Тип поля',
    'settings' => 'Настройки',
    'no_fields' => 'Поля ещё не добавлены',
    'field_settings' => 'Настройки поля',
    'placeholder' => 'Плейсхолдер',
    'default_value' => 'Значение по умолчанию',
    'hint' => 'Подсказка',
    'options' => 'Опции',
    'value' => 'Значение',
    'label' => 'Подпись',
    'option_value' => 'значение',
    'option_label' => 'Подпись',
    'no_options' => 'Нет опций',
    'add_option' => 'Добавить опцию',
    'min' => 'Мин',
    'max' => 'Макс',
    'step' => 'Шаг',
    'nested_fields' => 'Вложенные поля',
    'no_nested_fields' => 'Нет вложенных полей',
    'add_nested_field' => 'Добавить вложенное поле',
    'actions' => 'Действия',

    'field_types' => [
        'text' => 'Текст',
        'textarea' => 'Многострочный текст',
        'number' => 'Число',
        'email' => 'Email',
        'phone' => 'Телефон',
        'password' => 'Пароль',
        'date' => 'Дата',
        'datetime' => 'Дата и время',
        'checkbox' => 'Чекбокс',
        'switcher' => 'Переключатель',
        'select' => 'Выпадающий список',
        'radio' => 'Радиокнопки',
        'file' => 'Файл',
        'image' => 'Изображение',
        'color' => 'Цвет',
        'url' => 'Ссылка',
        'range' => 'Диапазон',
        'json' => 'JSON',
        'code' => 'Код',
        'markdown' => 'Markdown',
        'tinymce' => 'Редактор TinyMCE',
        'nested' => 'Вложенные поля',
    ],
];
