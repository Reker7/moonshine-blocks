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

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Слаг', 'slug')->sortable(),
            Switcher::make('Активно', 'is_active')->sortable(),
            Number::make('Сортировка', 'sorting')->sortable(),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            Box::make('Группа', [
                ID::make(),
                Text::make('Название', 'name')
                    ->required()
                    ->reactive(),
                Slug::make('Символьный код', 'slug')
                    ->required()
                    ->from('name')
                    ->unique()
                    ->live(),
                Switcher::make('Активно', 'is_active')->default(true),
                Number::make('Сортировка', 'sorting')->default(500),
            ]),
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
