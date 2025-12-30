<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Fieldset;
use MoonShine\UI\Fields\Json;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder\FieldsCollection;

/**
 * Собирает набор MoonShine-полей для редактирования значений блоков (BlockItem).
 *
 * Использует JSON-конфиг полей из Block->fields.
 *
 * Usage:
 *   $dynamicFields = (new BuildBlockItemFields())->buildForBlock($block);
 */
final class BuildBlockItemFields
{
    /**
     * Построить поля для конкретного блока.
     * Возвращает Json::object() с динамическими полями внутри.
     *
     * Поля из пресетов оборачиваются в Fieldset с названием пресета.
     */
    public function buildForBlock(Block $block, string $root = 'data'): FieldContract
    {
        // Ensure fieldPresets relation is loaded
        $block->loadMissing('fieldPresets');

        $groupedFields = $block->getGroupedFields();

        if (empty($groupedFields)) {
            return Json::make('', 'data')->object()->fields([]);
        }

        $allFields = $this->buildGroupedFields($groupedFields, $root);

        return Json::make('', 'data')->object()->fields($allFields);
    }

    /**
     * Построить массив полей (без обёртки Json::object)
     *
     * Поля из пресетов оборачиваются в Fieldset с названием пресета.
     *
     * @return list<FieldContract>
     */
    public function buildFieldsArray(Block $block, string $root = 'data'): array
    {
        // Ensure fieldPresets relation is loaded
        $block->loadMissing('fieldPresets');

        $groupedFields = $block->getGroupedFields();

        return $this->buildGroupedFields($groupedFields, $root);
    }

    /**
     * Build fields from grouped structure, wrapping presets in Fieldset
     *
     * @param array<int, array{name: string|null, fields: array}> $groups
     * @return list<FieldContract>
     */
    private function buildGroupedFields(array $groups, string $root): array
    {
        $result = [];

        foreach ($groups as $group) {
            $collection = FieldsCollection::fromMixed($group['fields']);

            if ($collection->isEmpty()) {
                continue;
            }

            $fields = BuildFieldsFromConfig::make($collection, $root)->build();

            if ($group['name'] !== null) {
                // Wrap preset fields in Fieldset
                $result[] = Fieldset::make($group['name'], $fields);
            } else {
                // Custom fields without wrapper
                foreach ($fields as $field) {
                    $result[] = $field;
                }
            }
        }

        return $result;
    }
}
