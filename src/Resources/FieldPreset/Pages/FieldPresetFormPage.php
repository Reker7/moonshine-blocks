<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\FieldPreset\Pages;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder;

/**
 * @extends FormPage<FieldPresetFormPage>
 */
class FieldPresetFormPage extends FormPage
{

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make(__('moonshine-blocks::ui.fields.name'), 'name')
                ->required()
                ->reactive(),
            Slug::make(__('moonshine-blocks::ui.fields.slug'), 'slug')
                ->required()
                ->from('name')
                ->unique()
                ->live(),
            Textarea::make(__('moonshine-blocks::ui.field_preset.description'), 'description')
                ->nullable(),

            FieldsBuilder::make(__('moonshine-blocks::ui.field_preset.fields'), 'fields'),

            Number::make(__('moonshine-blocks::ui.fields.sorting'), 'sorting')->default(500),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }
}
