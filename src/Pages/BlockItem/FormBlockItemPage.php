<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages\BlockItem;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Reker7\MoonShineBlocks\BuildBlockItemFields;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockItem;

final class FormBlockItemPage extends Page
{
    protected ?Block $block = null;

    protected ?BlockItem $item = null;

    // ==========================================
    // Public API Methods
    // ==========================================

    public function getTitle(): string
    {
        $block = $this->getBlock();

        if (! $block) {
            return $this->title ?? __('moonshine-blocks::ui.block_item');
        }

        return $this->isEditMode()
            ? __('moonshine-blocks::ui.editing') . ": {$block->name}"
            : __('moonshine-blocks::ui.creating') . ": {$block->name}";
    }

    public function getBreadcrumbs(): array
    {
        $block = $this->getBlock();

        if (! $block) {
            return ['#' => $this->getTitle()];
        }

        return [
            route('moonshine.blocks.index', ['block' => $block->slug]) => $block->name,
            '#' => $this->isEditMode()
                ? __('moonshine-blocks::ui.editing')
                : __('moonshine-blocks::ui.creating'),
        ];
    }

    // ==========================================
    // Lifecycle Methods
    // ==========================================

    protected function prepareBeforeRender(): void
    {
        parent::prepareBeforeRender();

        if ($this->isReactiveRequest()) {
            return;
        }

        if (! $this->getBlock()) {
            throw new ModelNotFoundException('Block not found');
        }

        if (request()->route('item') && ! $this->getItem()) {
            throw new ModelNotFoundException('Block item not found');
        }
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        if (! $this->getBlock()) {
            return [
                FlexibleRender::make(__('moonshine-blocks::ui.block_not_found')),
            ];
        }

        return $this->getLayers();
    }

    // ==========================================
    // Layer Methods
    // ==========================================

    /**
     * @return list<ComponentContract>
     */
    protected function topLayer(): array
    {
        $block = $this->getBlock();

        if (! $block) {
            return [];
        }

        return [
            Flex::make([
                Heading::make($this->getTitle(), 3),
                ActionGroup::make($this->topButtons($block)),
            ])->justifyAlign('between')->itemsAlign('center'),
            LineBreak::make(),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function mainLayer(): array
    {
        $block = $this->getBlock();

        if (! $block) {
            return [];
        }

        return [
            Box::make([
                $this->getFormComponent($block),
            ]),
        ];
    }

    // ==========================================
    // Button Methods
    // ==========================================

    /**
     * @return list<ActionButtonContract>
     */
    protected function topButtons(Block $block): array
    {
        return [
            ActionButton::make(
                __('moonshine-blocks::ui.back'),
                route('moonshine.blocks.index', ['block' => $block->slug])
            )->secondary()->icon('arrow-left'),
        ];
    }

    // ==========================================
    // Component Builders
    // ==========================================

    public function getForm(): ?FormBuilder
    {
        $block = $this->getBlock();

        return $block ? $this->getFormComponent($block) : null;
    }

    protected function getFormComponent(Block $block): FormBuilder
    {
        $item = $this->getItem();
        [$action, $method] = $this->getFormAction($block, $item);

        $form = FormBuilder::make($action)
            ->name('block-item-form')
            ->async()
            ->reactiveUrl(
                fn (FormBuilder $form) => $form->getCore()->getRouter()->getEndpoints()->reactive($this)
            )
            ->fields($this->formFields($block, $method))
            ->submit(__('moonshine-blocks::ui.save'), ['class' => 'btn-primary'])
            ->cast(new ModelCaster(BlockItem::class));

        if ($item) {
            $form->fillCast($item, new ModelCaster(BlockItem::class));
        }

        return $form;
    }

    // ==========================================
    // Field Definitions
    // ==========================================

    /**
     * @return list<FieldContract>
     */
    protected function formFields(Block $block, string $method): array
    {
        return [
            ...$this->baseFields($block),
            ...$this->dynamicFields($block),
            ...$this->systemFields($method),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function baseFields(Block $block): array
    {
        $fields = [
            Hidden::make('block_id')->setValue($block->id),

            Text::make(__('moonshine-blocks::ui.title'), 'title')
                ->reactive(lazy: true)
                ->required(),

            Slug::make(__('moonshine-blocks::ui.slug'), 'slug')
                ->from('title')
                ->unique()
                ->live(lazy: true)
                ->required(),

            Switcher::make(__('moonshine-blocks::ui.is_active'), 'is_active')
                ->default(true),

            Number::make(__('moonshine-blocks::ui.sorting'), 'sorting')
                ->default(500),
        ];

        if ($block->categories()->exists()) {
            $fields[] = Select::make(__('moonshine-blocks::ui.category'), 'block_category_id')
                ->options($block->categories()->pluck('name', 'id')->toArray())
                ->nullable()
                ->searchable();
        }

        return $fields;
    }

    /**
     * @return list<FieldContract>
     */
    protected function dynamicFields(Block $block): array
    {
        // Ensure fieldPresets are loaded for merged fields
        $block->loadMissing('fieldPresets');

        $groupedFields = $block->getGroupedFields();

        if (empty($groupedFields)) {
            return [];
        }

        return [(new BuildBlockItemFields())->buildForBlock($block)];
    }

    /**
     * @return list<FieldContract>
     */
    protected function systemFields(string $method): array
    {
        if (strtoupper($method) !== 'PUT') {
            return [];
        }

        return [
            Hidden::make('_method')->setValue('PUT'),
        ];
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * @return array{0: string, 1: string}
     */
    protected function getFormAction(Block $block, ?BlockItem $item): array
    {
        if ($item) {
            return [
                route('moonshine.blocks.update', [
                    'block' => $block->slug,
                    'item' => $item->id,
                ]),
                'PUT',
            ];
        }

        return [
            route('moonshine.blocks.store', ['block' => $block->slug]),
            'POST',
        ];
    }

    protected function isEditMode(): bool
    {
        return $this->getItem() !== null;
    }

    protected function isReactiveRequest(): bool
    {
        return request()->boolean('_async')
            || request()->has('_component_name')
            || request()->has('_fragment');
    }

    // ==========================================
    // Data Retrieval
    // ==========================================

    protected function getBlock(): ?Block
    {
        if ($this->block !== null) {
            return $this->block;
        }

        $param = request()->route('block');

        if ($param instanceof Block) {
            return $this->block = $param;
        }

        $slug = (string) ($param ?? request('block', ''));

        return $this->block = $slug !== ''
            ? Block::query()->where('slug', $slug)->first()
            : null;
    }

    protected function getItem(): ?BlockItem
    {
        if ($this->item !== null) {
            return $this->item;
        }

        $block = $this->getBlock();

        if (! $block) {
            return null;
        }

        $id = request()->route('item');

        if (empty($id)) {
            return null;
        }

        return $this->item = BlockItem::query()
            ->where('block_id', $block->id)
            ->whereKey((int) $id)
            ->first();
    }
}
