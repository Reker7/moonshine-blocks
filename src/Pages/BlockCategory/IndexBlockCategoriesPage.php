<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages\BlockCategory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockCategory;

final class IndexBlockCategoriesPage extends Page
{
    protected ?Block $block = null;

    // ==========================================
    // Public API Methods
    // ==========================================

    public function getTitle(): string
    {
        $block = $this->getBlock();

        return $block
            ? __('moonshine-blocks::ui.categories') . ": {$block->name}"
            : ($this->title ?? __('moonshine-blocks::ui.categories'));
    }

    public function getBreadcrumbs(): array
    {
        $block = $this->getBlock();

        if (! $block) {
            return ['#' => $this->getTitle()];
        }

        return [
            route('moonshine.blocks.index', ['block' => $block->slug]) => $block->name,
            '#' => __('moonshine-blocks::ui.categories'),
        ];
    }

    // ==========================================
    // Lifecycle Methods
    // ==========================================

    protected function prepareBeforeRender(): void
    {
        parent::prepareBeforeRender();

        if (! $this->getBlock()) {
            throw new ModelNotFoundException('Block not found');
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
                $this->getTableComponent($block),
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
                __('moonshine-blocks::ui.items_list'),
                route('moonshine.blocks.index', ['block' => $block->slug])
            )->secondary()->icon('list-bullet'),

            ActionButton::make(
                __('moonshine-blocks::ui.create'),
                route('moonshine.blocks.categories.create', ['block' => $block->slug])
            )->primary()->icon('plus'),
        ];
    }

    /**
     * @return list<ActionButtonContract>
     */
    protected function rowButtons(Block $block): array
    {
        return [
            ActionButton::make(
                '',
                fn (BlockCategory $category) => route('moonshine.blocks.categories.edit', [
                    'block' => $block->slug,
                    'category' => $category->id,
                ])
            )->icon('pencil')->primary(),

            ActionButton::make(
                '',
                fn (BlockCategory $category) => route('moonshine.blocks.categories.destroy', [
                    'block' => $block->slug,
                    'category' => $category->id,
                ])
            )
                ->icon('trash')
                ->error()
                ->withConfirm(__('moonshine-blocks::ui.delete_confirm'))
                ->async(method: HttpMethod::DELETE),
        ];
    }

    // ==========================================
    // Component Builders
    // ==========================================

    protected function getTableComponent(Block $block): ComponentContract
    {
        return TableBuilder::make(items: $this->getPaginator($block))
            ->name("block-categories-{$block->id}")
            ->fields($this->indexFields())
            ->cast(new ModelCaster(BlockCategory::class))
            ->buttons($this->rowButtons($block))
            ->withNotFound();
    }

    // ==========================================
    // Field Definitions
    // ==========================================

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make(__('moonshine-blocks::ui.title'), 'name')->sortable(),
            Text::make(__('moonshine-blocks::ui.slug'), 'slug')
                ->sortable()
                ->badge(color: 'gray'),
            Preview::make(__('moonshine-blocks::ui.is_active'), 'is_active')
                ->boolean(),
            Number::make(__('moonshine-blocks::ui.sorting'), 'sorting')->sortable(),
            Date::make(__('moonshine-blocks::ui.created_at'), 'created_at')
                ->format('d.m.Y H:i')
                ->sortable(),
        ];
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

    /**
     * @return LengthAwarePaginator<BlockCategory>
     */
    protected function getPaginator(Block $block): LengthAwarePaginator
    {
        /** @var Builder<BlockCategory> $query */
        $query = BlockCategory::query()->where('block_id', $block->id);

        if ($term = trim((string) request('search', ''))) {
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'ilike', "%{$term}%")
                    ->orWhere('slug', 'ilike', "%{$term}%");
            });
        }

        return $query
            ->orderBy('sorting')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();
    }
}
