<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\BlockGroup\Pages;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<BlockGroupIndexPage>
 */
final class BlockGroupIndexPage extends IndexPage
{
    protected bool $withPrintButton = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Text::make(__('moonshine-blocks::ui.block.name'), 'name')->sortable(),
            Text::make(__('moonshine-blocks::ui.block.slug'), 'slug')->sortable(),
            Switcher::make(__('moonshine-blocks::ui.block.is_active'), 'is_active'),
            Number::make(__('moonshine-blocks::ui.block.sorting'), 'sorting')->default(500),
        ];
    }
}
