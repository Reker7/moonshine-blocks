<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Template;
use Reker7\MoonShineBlocksCore\Models\Block;

/**
 * Собирает набор MoonShine-полей для редактирования значений блоков (BlockItem).
 *
 * Использует JSON-конфиг полей из Block->fields.
 *
 * Usage:
 *   $dynamicFields = (new BuildBlockItemFields())->buildForBlock($block); // returns FieldContract[]
 */
final class BuildBlockItemFields
{
    /**
     * Построить поля для конкретного блока.
     *
     * Возвращает массив с одним Template полем, обёртывающим все поля блока.
     * Fieldset sub-fields встраиваются плоско (без Fieldset::make() обёртки).
     *
     * Используем Template вместо Json::object по следующим причинам:
     * - Fieldset implements FieldsWrapperContract → onlyFields() разворачивает его
     * - Fieldset::resolveFill() не вызывается → getData() = null → prepareFields()
     *   заполняет sub-fields из [] → значения теряются при рендере
     * - Template с явными changeFill/changeRender/onApply обходит эту проблему
     *
     * Sub-fields используют plain колонки ('title', не 'data.title').
     * Template::wrapNames('data') делает HTML инпуты data[title], data[meta_title].
     *
     * @return list<FieldContract>
     */
    public function buildForBlock(Block $block): array
    {
        $collection = $block->getFieldsCollection();

        if ($collection->isEmpty()) {
            return [];
        }

        $fields = BuildFieldsFromConfig::make($collection, '')->buildFlat();

        return [BlockDataTemplate::make($fields)];
    }

    /**
     * Построить массив полей (без обёртки Json::object)
     *
     * @return list<FieldContract>
     */
    public function buildFieldsArray(Block $block, string $root = 'data'): array
    {
        $collection = $block->getFieldsCollection();

        if ($collection->isEmpty()) {
            return [];
        }

        return BuildFieldsFromConfig::make($collection, $root)->build();
    }
}
