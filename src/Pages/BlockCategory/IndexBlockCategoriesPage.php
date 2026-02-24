<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages\BlockCategory;

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
use Reker7\MoonShineBlocksCore\Models\BlockCategory;

final class IndexBlockCategoriesPage extends AbstractBlockPage
{
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

    protected function getTableComponent(Block $block): TableBuilder
    {
        return TableBuilder::make(items: $this->getPaginator($block))
            ->name("block-categories-{$block->id}")
            ->fields($this->indexFields())
            ->cast(new ModelCaster(BlockCategory::class))
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

    /**
     * @return LengthAwarePaginator<BlockCategory>
     */
    protected function getPaginator(Block $block): LengthAwarePaginator
    {
        $query = BlockCategory::query()->where('block_id', $block->id);

        if ($term = trim((string) request('search', ''))) {
            $query->where(function (Builder $q) use ($term) {
                $lower = mb_strtolower($term);
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$lower}%"]);
            });
        }

        return $query
            ->orderBy('sorting')
            ->orderBy('id')
            ->paginate(config('moonshine-blocks.ui.per_page', 20))
            ->withQueryString();
    }
}
