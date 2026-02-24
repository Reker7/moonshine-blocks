<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\Block;

use Illuminate\Database\Query\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use Reker7\MoonShineBlocks\Resources\Block\Pages\BlockFormPage;
use Reker7\MoonShineBlocks\Resources\Block\Pages\BlockIndexPage;
use Reker7\MoonShineBlocksCore\Models\Block;

/**
 * @extends ModelResource<Block>
 */
final class BlockResource extends ModelResource
{
    protected string $model = Block::class;

    protected string $title = 'Блоки';

    protected string $column = 'name';

    protected function rules(mixed $item): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['required', 'string', 'max:255', 'unique:blocks,slug,' . ($item->id ?? 'null')],
            'sorting'   => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    protected function modifyQuery(Builder $query): Builder
    {
        return $query->orderBy('sorting');
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            BlockIndexPage::class,
            BlockFormPage::class,
        ];
    }
}
