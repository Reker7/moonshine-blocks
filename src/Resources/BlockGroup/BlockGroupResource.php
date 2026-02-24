<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\BlockGroup;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use Reker7\MoonShineBlocks\Resources\BlockGroup\Pages\BlockGroupFormPage;
use Reker7\MoonShineBlocks\Resources\BlockGroup\Pages\BlockGroupIndexPage;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;

/**
 * @extends ModelResource<BlockGroup>
 */
final class BlockGroupResource extends ModelResource
{
    protected string $model = BlockGroup::class;
    protected string $title = 'Группы блоков';
    protected ?string $alias = 'block-groups';
    protected string $column = 'name';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            BlockGroupIndexPage::class,
            BlockGroupFormPage::class,
        ];
    }

    protected function rules(mixed $item): array
    {
        /** @var Model|null $item */
        $id = $item?->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', "unique:block_groups,slug,{$id}"],
            'is_active' => ['boolean'],
            'sorting' => ['integer'],
        ];
    }
}
