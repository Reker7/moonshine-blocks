<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks;

use Closure;
use Illuminate\Support\Collection;
use MoonShine\Contracts\MenuManager\MenuElementContract;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use Reker7\MoonShineBlocks\Resources\Block\BlockResource;
use Reker7\MoonShineBlocks\Resources\BlockGroup\BlockGroupResource;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;

/**
 * Генератор элементов меню из блоков.
 *
 * Использование:
 *
 * 1. Как spread в массиве меню:
 *    Menu::make([
 *        ...BlocksMenu::make()->elements(),
 *    ])
 *
 * 2. Обёрнутый в группу:
 *    BlocksMenu::make()->asGroup('Контент', 'document-text')
 *
 * 3. С фильтрацией:
 *    BlocksMenu::make()
 *        ->onlyActive()
 *        ->withoutGroups()
 *        ->elements()
 */
final class BlocksMenu
{
    protected bool $onlyActive = true;

    protected bool $withGroups = true;

    protected ?Closure $blockFilter = null;

    protected string $singleBlockIcon = 'rectangle-stack';

    protected string $multipleBlockIcon = 'cube';

    protected string $categoriesIcon = 'list-bullet';

    protected string $itemsIcon = 'square-3-stack-3d';

    protected string $groupIcon = 'folder';

    public static function make(): static
    {
        return new self();
    }

    /**
     * Показывать только активные блоки
     */
    public function onlyActive(bool $value = true): static
    {
        $this->onlyActive = $value;

        return $this;
    }

    /**
     * Включить группировку блоков
     */
    public function withGroups(bool $value = true): static
    {
        $this->withGroups = $value;

        return $this;
    }

    /**
     * Отключить группировку — все блоки плоским списком
     */
    public function withoutGroups(): static
    {
        return $this->withGroups(false);
    }

    /**
     * Фильтр блоков
     *
     * @param Closure(Block): bool $filter
     */
    public function filterBlocks(Closure $filter): static
    {
        $this->blockFilter = $filter;

        return $this;
    }

    /**
     * Иконка для singular блоков
     */
    public function singleBlockIcon(string $icon): static
    {
        $this->singleBlockIcon = $icon;

        return $this;
    }

    /**
     * Иконка для multiple блоков
     */
    public function multipleBlockIcon(string $icon): static
    {
        $this->multipleBlockIcon = $icon;

        return $this;
    }

    /**
     * Иконка для групп блоков
     */
    public function groupIcon(string $icon): static
    {
        $this->groupIcon = $icon;

        return $this;
    }

    /**
     * Получить все элементы меню
     *
     * @return array<int, MenuElementContract>
     */
    public function elements(): array
    {
        if (!$this->withGroups) {
            return $this->getAllBlocks()
                ->map(fn(Block $block) => $this->makeBlockElement($block))
                ->values()
                ->all();
        }

        $items = collect();

        foreach ($this->getUngroupedBlocks() as $block) {
            $items->push([
                'sorting' => $block->sorting ?? 500,
                'name' => $block->name,
                'element' => $this->makeBlockElement($block),
            ]);
        }

        foreach ($this->getGroupsWithBlocks() as $group) {
            $children = $group->blocks
                ->filter(fn(Block $block) => $this->shouldIncludeBlock($block))
                ->map(fn(Block $block) => $this->makeBlockElement($block))
                ->values()
                ->all();

            if ($children !== []) {
                $items->push([
                    'sorting' => $group->sorting ?? 500,
                    'name' => $group->name,
                    'element' => MenuGroup::make($group->name, $children, $this->groupIcon),
                ]);
            }
        }

        return $items
            ->sortBy([
                ['sorting', 'asc'],
                ['name', 'asc'],
            ])
            ->pluck('element')
            ->values()
            ->all();
    }

    /**
     * Получить элементы обёрнутые в MenuGroup
     */
    public function asGroup(string $label, ?string $icon = null): MenuGroup
    {
        return MenuGroup::make($label, $this->elements(), $icon);
    }

    /**
     * Создать элемент меню для блока
     */
    protected function makeBlockElement(Block $block): MenuElementContract
    {
        if (!$block->is_multiple) {
            return $this->makeSingularBlockItem($block);
        }

        return $this->makeMultipleBlockElement($block);
    }

    /**
     * MenuItem для singular блока
     */
    protected function makeSingularBlockItem(Block $block): MenuItem
    {
        $itemId = $block->items()->value('id');

        $url = $itemId
            ? route('moonshine.blocks.edit', ['block' => $block->slug, 'item' => $itemId])
            : route('moonshine.blocks.create', ['block' => $block->slug]);

        return MenuItem::make($url, $block->name, $this->singleBlockIcon);
    }

    /**
     * Элемент меню для multiple блока.
     * Если has_categories — MenuGroup с [Категории, Элементы].
     * Иначе — MenuItem только на список элементов.
     */
    protected function makeMultipleBlockElement(Block $block): MenuElementContract
    {
        $itemsLink = MenuItem::make(
            route('moonshine.blocks.index', ['block' => $block->slug]),
            __('moonshine-blocks::ui.items_list'),
            $this->itemsIcon
        );

        if (! $block->has_categories) {
            return MenuGroup::make($block->name, [$itemsLink], $this->multipleBlockIcon);
        }

        return MenuGroup::make($block->name, [
            MenuItem::make(
                route('moonshine.blocks.categories.index', ['block' => $block->slug]),
                __('moonshine-blocks::ui.categories'),
                $this->categoriesIcon
            ),
            $itemsLink,
        ], $this->multipleBlockIcon);
    }

    /**
     * Проверить нужно ли включать блок
     */
    protected function shouldIncludeBlock(Block $block): bool
    {
        if ($this->onlyActive && !$block->is_active) {
            return false;
        }

        if ($this->blockFilter !== null && !($this->blockFilter)($block)) {
            return false;
        }

        return true;
    }

    /**
     * Блоки без группы
     *
     * @return Collection<int, Block>
     */
    protected function getUngroupedBlocks(): Collection
    {
        return Block::query()
            ->whereNull('block_group_id')
            ->when($this->onlyActive, fn($q) => $q->where('is_active', true))
            ->orderBy('sorting')
            ->orderBy('name')
            ->get()
            ->filter(fn(Block $block) => $this->shouldIncludeBlock($block));
    }

    /**
     * Группы с блоками
     *
     * @return Collection<int, BlockGroup>
     */
    protected function getGroupsWithBlocks(): Collection
    {
        return BlockGroup::query()
            ->when($this->onlyActive, fn($q) => $q->where('is_active', true))
            ->with([
                'blocks' => fn($q) => $q
                    ->when($this->onlyActive, fn($q) => $q->where('is_active', true))
                    ->orderBy('sorting')
                    ->orderBy('name'),
            ])
            ->orderBy('sorting')
            ->orderBy('name')
            ->get();
    }

    /**
     * Все блоки (для режима без группировки)
     *
     * @return Collection<int, Block>
     */
    protected function getAllBlocks(): Collection
    {
        return Block::query()
            ->when($this->onlyActive, fn($q) => $q->where('is_active', true))
            ->orderBy('sorting')
            ->orderBy('name')
            ->get()
            ->filter(fn(Block $block) => $this->shouldIncludeBlock($block));
    }

    /**
     * Элементы меню для настроек блоков (ресурсы)
     *
     * @return array<int, MenuElementContract>
     */
    public static function settingsElements(): array
    {
        return [
            MenuItem::make(
                app(BlockResource::class)->getUrl(),
                __('moonshine-blocks::ui.menu.blocks'),
                'rectangle-stack'
            ),
            MenuItem::make(
                app(BlockGroupResource::class)->getUrl(),
                __('moonshine-blocks::ui.menu.groups'),
                'folder'
            ),
        ];
    }

    /**
     * Элементы настроек обёрнутые в MenuGroup
     */
    public static function settingsGroup(string $label = 'Настройки блоков', ?string $icon = 'cog-6-tooth'): MenuGroup
    {
        return MenuGroup::make($label, static::settingsElements(), $icon);
    }
}
