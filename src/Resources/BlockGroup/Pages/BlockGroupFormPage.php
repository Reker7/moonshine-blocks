<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\BlockGroup\Pages;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Reker7\MoonShineBlocks\Resources\BlockGroup\BlockGroupResource;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder;

/**
 * @extends FormPage<BlockGroupFormPage>
 */
class BlockGroupFormPage extends FormPage
{

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name')
                ->required()
                ->reactive(),
            Slug::make('Символьный код', 'slug')
                ->required()
                ->from('name')
                ->unique()
                ->live(),

            BelongsTo::make(
                'Группа',
                'blockGroup',
                resource: BlockGroupResource::class
            )
                ->nullable(),

            Switcher::make('Множественный блок', 'is_multiple')
                ->hint('Данная настройка отвечает за возможность создания элементов внутри блока')
                ->default(false),

            FieldsBuilder::make('Поля формы', 'fields'),
            Switcher::make('Активен', 'is_active')->default(true),
            Number::make('Сортировка', 'sorting')->default(500),
        ];
    }

	protected function rules(DataWrapperContract $item): array
	{
		return [];
	}
}
