<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages\BlockItem;

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
use Reker7\MoonShineBlocksCore\Models\BlockItem;

final class IndexBlockItemPage extends Page
{
    protected ?Block $block = null;

    // ==========================================
    // Public API Methods
    // ==========================================

    public function getTitle(): string
    {
        return $this->getBlock()?->name
            ?? $this->title
            ?? __('moonshine-blocks::ui.block_items');
    }

    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
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
        $buttons = [];

        if ($block->categories()->exists()) {
            $buttons[] = ActionButton::make(
                __('moonshine-blocks::ui.categories'),
                route('moonshine.blocks.categories.index', ['block' => $block->slug])
            )->secondary()->icon('folder');
        }

        $buttons[] = ActionButton::make(
            __('moonshine-blocks::ui.create'),
            route('moonshine.blocks.create', ['block' => $block->slug])
        )->primary()->icon('plus');

        return $buttons;
    }

    /**
     * @return list<ActionButtonContract>
     */
    protected function rowButtons(Block $block): array
    {
        return [
            ActionButton::make(
                '',
                fn (BlockItem $item) => route('moonshine.blocks.edit', [
                    'block' => $block->slug,
                    'item' => $item->id,
                ])
            )->icon('pencil')->primary(),

            ActionButton::make(
                '',
                fn (BlockItem $item) => route('moonshine.blocks.destroy', [
                    'block' => $block->slug,
                    'item' => $item->id,
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
            ->name("block-items-{$block->id}")
            ->fields($this->indexFields())
            ->cast(new ModelCaster(BlockItem::class))
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
            Text::make(__('moonshine-blocks::ui.title'), 'title')->sortable(),
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

        $param = request()->route('block') ?? request('block');

        if ($param instanceof Block) {
            return $this->block = $param;
        }

        $slug = (string) ($param ?? '');

        return $this->block = $slug !== ''
            ? Block::query()->where('slug', $slug)->first()
            : null;
    }

    /**
     * @return LengthAwarePaginator<BlockItem>
     */
    protected function getPaginator(Block $block): LengthAwarePaginator
    {
        /** @var Builder<BlockItem> $query */
        $query = BlockItem::query()->where('block_id', $block->id);

        if ($term = trim((string) request('search', ''))) {
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'ilike', "%{$term}%")
                    ->orWhere('slug', 'ilike', "%{$term}%");
            });
        }

        if ($categoryId = request('category_id')) {
            $query->where('block_category_id', (int) $categoryId);
        }

        return $query
            ->orderBy('sorting')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }
}
