<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\BlockGroup;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
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
