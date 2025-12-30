<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\FieldPreset;

use Illuminate\Database\Query\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use Reker7\MoonShineBlocks\Resources\FieldPreset\Pages\FieldPresetFormPage;
use Reker7\MoonShineBlocks\Resources\FieldPreset\Pages\FieldPresetIndexPage;
use Reker7\MoonShineBlocksCore\Models\FieldPreset;

/**
 * @extends ModelResource<FieldPreset>
 */
class FieldPresetResource extends ModelResource
{
    protected string $model = FieldPreset::class;

    protected string $title = 'Пресеты полей';

    protected string $column = 'name';

    protected function rules(mixed $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:field_presets,slug,' . ($item->id ?? 'null')],
            'sorting' => ['integer', 'min:0'],
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
            FieldPresetIndexPage::class,
            FieldPresetFormPage::class,
        ];
    }
}
