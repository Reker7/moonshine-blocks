<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | MoonShine Blocks — FieldsBuilder extensions
    |--------------------------------------------------------------------------
    |
    | Registers block_relation type handler for every FieldsBuilder instance.
    | Merged into the global fields-builder config automatically.
    |
    */
    'types' => [
        \Reker7\MoonShineBlocks\FieldTypes\BlockRelationFieldType::class,
        \Reker7\MoonShineBlocks\FieldTypes\FieldsetFieldType::class,
    ],

    'exclude' => [],
];
