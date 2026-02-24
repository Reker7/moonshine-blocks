<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\FieldTypes;

use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;
use Reker7\MoonShineFieldsBuilder\FieldTypes\FieldType;

/**
 * Field type for selecting items from another block or group.
 *
 * Provides blocks and groups data directly to Alpine.js via typesMeta,
 * eliminating the need for async API calls.
 */
final class BlockRelationFieldType extends FieldType
{
    public function type(): string
    {
        return 'block_relation';
    }

    public function label(): string
    {
        return __('moonshine-blocks::ui.block_relation.label');
    }

    public function modalSections(): array
    {
        return ['basic', 'rules'];
    }

    public function optionKeys(): array
    {
        return ['relation_type', 'relation_target', 'relation_multiple'];
    }

    public function modalView(): string
    {
        return 'moonshine-blocks::modal.block-relation';
    }

    /**
     * Provide blocks and groups for the relation dropdowns.
     * Called at render time — data flows into typesMeta.block_relation.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [
            'hiddenBasicFields' => ['placeholder', 'default_value'],
            'supportsMultiple'  => true,
            'blocks' => Block::where('is_multiple', true)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Block $b) => ['value' => $b->slug, 'label' => $b->name])
                ->all(),

            'groups' => BlockGroup::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (BlockGroup $g) => ['value' => $g->slug, 'label' => $g->name])
                ->all(),
        ];
    }
}
