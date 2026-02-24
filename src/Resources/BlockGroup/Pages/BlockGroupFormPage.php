<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\BlockGroup\Pages;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<BlockGroupFormPage>
 */
final class BlockGroupFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make(__('moonshine-blocks::ui.block.name'), 'name')
                ->required()
                ->reactive(),
            Slug::make(__('moonshine-blocks::ui.block.slug'), 'slug')
                ->required()
                ->from('name')
                ->unique()
                ->live(),
            Switcher::make(__('moonshine-blocks::ui.block.is_active'), 'is_active')
                ->default(true),
            Number::make(__('moonshine-blocks::ui.block.sorting'), 'sorting')
                ->default(500),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }
}
