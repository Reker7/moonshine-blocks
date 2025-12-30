<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages\BlockCategory;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\Laravel\Pages\Page;
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
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockCategory;

final class FormBlockCategoryPage extends Page
{
    protected ?Block $block = null;

    protected ?BlockCategory $category = null;

    // ==========================================
    // Public API Methods
    // ==========================================

    public function getTitle(): string
    {
        $block = $this->getBlock();

        if (! $block) {
            return $this->title ?? __('moonshine-blocks::ui.category');
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
            route('moonshine.blocks.categories.index', ['block' => $block->slug]) => __('moonshine-blocks::ui.categories'),
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

        if (request()->route('category') && ! $this->getCategory()) {
            throw new ModelNotFoundException('Category not found');
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
                route('moonshine.blocks.categories.index', ['block' => $block->slug])
            )->secondary()->icon('arrow-left'),
        ];
    }

    // ==========================================
    // Component Builders
    // ==========================================

    protected function getFormComponent(Block $block): FormBuilder
    {
        $category = $this->getCategory();
        [$action, $method] = $this->getFormAction($block, $category);

        $form = FormBuilder::make($action)
            ->name('block-category-form')
            ->async()
            ->reactiveUrl(
                fn (FormBuilder $form) => $form->getCore()->getRouter()->getEndpoints()->reactive($this)
            )
            ->fields($this->formFields($method))
            ->submit(__('moonshine-blocks::ui.save'), ['class' => 'btn-primary']);

        if ($category) {
            $form->fill([
                'name' => $category->name,
                'slug' => $category->slug,
                'is_active' => $category->is_active,
                'sorting' => $category->sorting,
            ]);
        }

        return $form;
    }

    // ==========================================
    // Field Definitions
    // ==========================================

    /**
     * @return list<FieldContract>
     */
    protected function formFields(string $method): array
    {
        return [
            ...$this->baseFields(),
            ...$this->systemFields($method),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function baseFields(): array
    {
        return [
            Text::make(__('moonshine-blocks::ui.title'), 'name')
                ->reactive(lazy: true)
                ->required(),

            Slug::make(__('moonshine-blocks::ui.slug'), 'slug')
                ->from('name')
                ->unique()
                ->live(lazy: true)
                ->required(),

            Switcher::make(__('moonshine-blocks::ui.is_active'), 'is_active')
                ->default(true),

            Number::make(__('moonshine-blocks::ui.sorting'), 'sorting')
                ->default(500),
        ];
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
    protected function getFormAction(Block $block, ?BlockCategory $category): array
    {
        if ($category) {
            return [
                route('moonshine.blocks.categories.update', [
                    'block' => $block->slug,
                    'category' => $category->getKey(),
                ]),
                'PUT',
            ];
        }

        return [
            route('moonshine.blocks.categories.store', ['block' => $block->slug]),
            'POST',
        ];
    }

    protected function isEditMode(): bool
    {
        return $this->getCategory() !== null;
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

        $slug = (string) ($param ?? '');

        return $this->block = $slug !== ''
            ? Block::query()->where('slug', $slug)->first()
            : null;
    }

    protected function getCategory(): ?BlockCategory
    {
        if ($this->category !== null) {
            return $this->category;
        }

        $block = $this->getBlock();

        if (! $block) {
            return null;
        }

        $id = request()->route('category');

        if (empty($id)) {
            return null;
        }

        return $this->category = $block->categories()
            ->whereKey((int) $id)
            ->first();
    }
}
