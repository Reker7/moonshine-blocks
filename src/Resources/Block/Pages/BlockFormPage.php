<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\Block\Pages;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Reker7\MoonShineBlocks\Resources\BlockGroup\BlockGroupResource;
use Reker7\MoonShineBlocks\Resources\FieldPreset\FieldPresetResource;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder;

/**
 * @extends FormPage<BlockFormPage>
 */
class BlockFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name')
                ->required()
                ->reactive(),
            Slug::make('Символьный код', 'slug')
                ->required()
                ->from('name')
                ->unique()
                ->live(),

            BelongsTo::make(
                'Группа',
                'blockGroup',
                resource: BlockGroupResource::class
            )
                ->nullable(),

            Switcher::make('Множественный блок', 'is_multiple')
                ->hint('Данная настройка отвечает за возможность создания элементов внутри блока')
                ->default(false),

            BelongsToMany::make(
                __('moonshine-blocks::ui.field_preset.select_presets'),
                'fieldPresets',
                resource: FieldPresetResource::class
            )
                ->selectMode()
                ->hint(__('moonshine-blocks::ui.field_preset.presets_hint')),

            FieldsBuilder::make('Дополнительные поля', 'fields')
                ->hint('Поля специфичные для этого блока (в дополнение к пресетам)'),
            Switcher::make('Активен', 'is_active')->default(true),
            Number::make('Сортировка', 'sorting')->default(500),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }
}
