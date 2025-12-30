<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\FieldPreset\Pages;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<FieldPresetIndexPage>
 */
class FieldPresetIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Text::make(__('moonshine-blocks::ui.fields.name'), 'name')->sortable(),
            Text::make(__('moonshine-blocks::ui.fields.slug'), 'slug')->sortable()->badge('gray'),
            Text::make(__('moonshine-blocks::ui.field_preset.description'), 'description'),
            Number::make(__('moonshine-blocks::ui.fields.sorting'), 'sorting')->sortable(),
        ];
    }
}
