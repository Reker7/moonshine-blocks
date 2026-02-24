<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages\BlockItem;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;
use Reker7\MoonShineBlocks\Pages\AbstractBlockPage;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockItem;

final class IndexBlockItemPage extends AbstractBlockPage
{
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

    protected function prepareBeforeRender(): void
    {
        parent::prepareBeforeRender();
        $this->ensureBlockExists();
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
            ActionGroup::make($this->topButtons($block)),
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

    protected function getTableComponent(Block $block): TableBuilder
    {
        return TableBuilder::make(items: $this->getPaginator($block))
            ->name("block-items-{$block->id}")
            ->fields($this->indexFields())
            ->cast(new ModelCaster(BlockItem::class))
            ->buttons($this->rowButtons($block))
            ->withNotFound();
    }

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

    /**
     * @return LengthAwarePaginator<BlockItem>
     */
    protected function getPaginator(Block $block): LengthAwarePaginator
    {
        $query = BlockItem::query()->where('block_id', $block->id);

        if ($term = trim((string) request('search', ''))) {
            $query->where(function (Builder $q) use ($term) {
                $lower = mb_strtolower($term);
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$lower}%"]);
            });
        }

        if ($categoryId = request('category_id')) {
            $query->where('block_category_id', (int) $categoryId);
        }

        return $query
            ->orderBy('sorting')
            ->orderBy('id')
            ->paginate(config('moonshine-blocks.ui.per_page', 20))
            ->withQueryString();
    }
}
