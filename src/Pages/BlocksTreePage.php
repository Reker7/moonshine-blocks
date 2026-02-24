<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages;

use Illuminate\Support\Collection;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Components\Table\TableRow;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;

final class BlocksTreePage extends Page
{
    protected string $title = 'Блоки и группы';

    public function __invoke(): self
    {
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUriKey(): string
    {
        return 'blocks-tree';
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return [
            Flex::make([
                Heading::make($this->getTitle(), 3),
            ])->justifyAlign('between')->itemsAlign('center'),
            LineBreak::make(),
            Box::make([
                $this->getTreeTable(),
            ]),
        ];
    }

    protected function getTreeTable(): ComponentContract
    {
        $items = $this->getTreeItems();

        return TableBuilder::make(items: $items)
            ->name('blocks-tree')
            ->fields($this->getFields())
            ->buttons($this->getRowButtons())
            ->withNotFound();
    }

    /**
     * @return list<FieldContract>
     */
    protected function getFields(): array
    {
        return [
            Text::make('Название', 'name')
                ->changePreview(function ($value, $field) {
                    $data = $field->getData()?->toArray() ?? [];
                    return $this->formatName($value, $data);
                }),
            Text::make('Слаг', 'slug')
                ->badge(fn($value, $field) => ($field->getData()?->toArray()['is_group'] ?? false) ? 'purple' : 'gray'),
            Text::make('Тип', 'type_label')
                ->badge(fn($value, $field) => ($field->getData()?->toArray()['is_group'] ?? false) ? 'info' : 'success'),
            Preview::make('Активен', 'is_active')
                ->boolean(),
        ];
    }

    protected function formatName(string $value, array $data): string
    {
        $isGroup = $data['is_group'] ?? false;
        $hasGroup = $data['has_group'] ?? false;

        if ($isGroup) {
            return '<span class="font-semibold">' . e($value) . '</span>';
        }

        $prefix = $hasGroup ? '&nbsp;&nbsp;&nbsp;&nbsp;└ ' : '';
        return $prefix . e($value);
    }

    /**
     * @return list<ActionButtonContract>
     */
    protected function getRowButtons(): array
    {
        return [
            ActionButton::make(
                '',
                static fn ($item) => is_array($item)
                    ? route('moonshine.blocks.edit', ['block' => $item['slug'] ?? ''])
                    : '#'
            )
                ->icon('pencil')
                ->primary()
                ->canSee(static fn ($item) => is_array($item) && ($item['entity_type'] ?? '') === 'block'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function getTreeItems(): Collection
    {
        $items = collect();

        // Группы с блоками
        $groups = BlockGroup::query()
            ->with(['blocks' => fn($q) => $q->orderBy('sorting')])
            ->orderBy('sorting')
            ->get();

        foreach ($groups as $group) {
            // Добавляем группу
            $items->push([
                'id' => 'group_' . $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'is_active' => $group->is_active,
                'is_group' => true,
                'has_group' => false,
                'type_label' => 'Группа',
                'entity_type' => 'group',
                'entity_id' => $group->id,
            ]);

            // Добавляем блоки группы
            foreach ($group->blocks as $block) {
                $items->push([
                    'id' => 'block_' . $block->id,
                    'name' => $block->name,
                    'slug' => $block->slug,
                    'is_active' => $block->is_active,
                    'is_group' => false,
                    'has_group' => true,
                    'type_label' => $block->is_multiple ? 'Множ.' : 'Един.',
                    'entity_type' => 'block',
                    'entity_id' => $block->id,
                ]);
            }
        }

        // Блоки без группы
        $orphanBlocks = Block::query()
            ->whereNull('block_group_id')
            ->orderBy('sorting')
            ->get();

        foreach ($orphanBlocks as $block) {
            $items->push([
                'id' => 'block_' . $block->id,
                'name' => $block->name,
                'slug' => $block->slug,
                'is_active' => $block->is_active,
                'is_group' => false,
                'has_group' => false,
                'type_label' => $block->is_multiple ? 'Множ.' : 'Един.',
                'entity_type' => 'block',
                'entity_id' => $block->id,
            ]);
        }

        return $items;
    }
}
