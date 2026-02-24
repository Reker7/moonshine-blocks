<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages\BlockItem;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\Tabs;
use MoonShine\UI\Components\Tabs\Tab;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use Reker7\MoonShineBlocks\BuildBlockItemFields;
use Reker7\MoonShineBlocks\Pages\AbstractBlockFormPage;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockItem;

final class FormBlockItemPage extends AbstractBlockFormPage
{
    protected ?BlockItem $item = null;

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

    protected function prepareBeforeRender(): void
    {
        parent::prepareBeforeRender();

        if ($this->isReactiveRequest()) {
            return;
        }

        $this->ensureBlockExists();

        if (request()->route('item') && ! $this->getItem()) {
            throw new ModelNotFoundException('Block item not found');
        }
    }

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

    /**
     * @return list<FieldContract>
     */
    protected function formFields(Block $block, string $method): array
    {
        $tabs = [
            Tab::make(__('moonshine-blocks::ui.block.tab_main'), $this->baseFields($block)),
        ];

        $dynamicFields = $this->dynamicFields($block);
        if ($dynamicFields !== []) {
            $tabs[] = Tab::make(__('moonshine-blocks::ui.block.tab_fields'), $dynamicFields);
        }

        if ($this->isContentEnabled()) {
            $tabs[] = Tab::make(__('moonshine-blocks::ui.content'), [$this->resolveContentField()]);
        }

        return [
            Hidden::make('block_id')->setValue($block->id),
            Tabs::make($tabs),
            ...$this->systemFields($method),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function baseFields(Block $block): array
    {
        $fields = [
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
                ->default(config('moonshine-blocks.ui.sorting_default', 500)),
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
        return (new BuildBlockItemFields())->buildForBlock($block);
    }

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

    /**
     * Expose the content field in the components tree so that third-party
     * packages that discover page fields via getComponents() (e.g.
     * moonshine/layouts-field LayoutsController) can locate it by column
     * name without requiring a block context.
     *
     * @return iterable<\MoonShine\Contracts\UI\ComponentContract>
     */
    protected function components(): iterable
    {
        if (! $this->getBlock() && $this->isContentEnabled()) {
            return [$this->resolveContentField()];
        }

        return parent::components();
    }

    protected function isContentEnabled(): bool
    {
        return (bool) config('moonshine-blocks.content.enabled', false);
    }

    protected function resolveContentField(): FieldContract
    {
        $field = config('moonshine-blocks.content.field');

        if ($field === null) {
            return Textarea::make(__('moonshine-blocks::ui.content'), 'content');
        }

        if (is_string($field) && class_exists($field)) {
            $instance = app($field);

            if (method_exists($instance, 'make')) {
                return $instance->make();
            }

            if ($instance instanceof FieldContract) {
                return $instance;
            }
        }

        if (is_callable($field)) {
            $result = $field();

            if ($result instanceof FieldContract) {
                return $result;
            }
        }

        return Textarea::make(__('moonshine-blocks::ui.content'), 'content');
    }
}
