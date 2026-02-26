<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\Block\Pages;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Tabs;
use MoonShine\UI\Components\Tabs\Tab;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Reker7\MoonShineBlocks\Resources\BlockGroup\BlockGroupResource;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder;

/**
 * @extends FormPage<BlockFormPage>
 */
final class BlockFormPage extends FormPage
{
	/**
	 * @return list<ComponentContract|FieldContract>
	 */
	protected function fields(): iterable
	{
		return [
			ID::make(),

			Tabs::make([
				Tab::make(__('moonshine-blocks::ui.block.tab_main'), $this->mainTabFields()),
				Tab::make(__('moonshine-blocks::ui.block.tab_fields'), $this->fieldsTabFields()),
			]),
		];
	}

	/**
	 * @return list<FieldContract>
	 */
	protected function mainTabFields(): array
	{
		return [
			Text::make(__('moonshine-blocks::ui.block.name'), 'name')
				->required()
				->reactive(),

			Slug::make(__('moonshine-blocks::ui.block.slug'), 'slug')
				->required()
				->from('name')
				->unique()
				->live(),

			BelongsTo::make(
				__('moonshine-blocks::ui.block.group'),
				'blockGroup',
				resource: BlockGroupResource::class
			)->nullable(),

			Switcher::make(__('moonshine-blocks::ui.block.is_multiple'), 'is_multiple')
				->hint(__('moonshine-blocks::ui.block.is_multiple_hint'))
				->default(false),

			Switcher::make(__('moonshine-blocks::ui.block.has_categories'), 'has_categories')
				->hint(__('moonshine-blocks::ui.block.has_categories_hint'))
				->default(false),

			Switcher::make(__('moonshine-blocks::ui.block.is_active'), 'is_active')
				->default(true),

			Number::make(__('moonshine-blocks::ui.block.sorting'), 'sorting')
				->default(500),
		];
	}

	/**
	 * @return list<FieldContract>
	 */
	protected function fieldsTabFields(): array
	{
		return [
			FieldsBuilder::make(__('moonshine-blocks::ui.block.fields'), 'fields'),
		];
	}

	protected function rules(DataWrapperContract $item): array
	{
		return [];
	}
}
