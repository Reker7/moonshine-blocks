<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\BlockGroup\Pages;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<BlockGroupIndexPage>
 */
class BlockGroupIndexPage extends FormPage
{
    protected bool $withPrintButton = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Text::make('Название', 'name')->sortable(),
            Text::make('Слаг', 'slug')->sortable(),
            Switcher::make('Активен', 'is_active'),
            Number::make('Сортировка', 'sorting')->default(500),
        ];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }
}
