<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Components\FieldsGroup;
use MoonShine\UI\Fields\Template;

/**
 * Template-based wrapper for the block data column.
 *
 * Replaces Json::object('data') to avoid the FieldsWrapperContract fill chain issue.
 *
 * Json::object wraps Fieldset which implements FieldsWrapperContract:
 * - onlyFields() unwraps Fieldset, bypasses Fieldset::resolveFill()
 * - Fieldset::getData() stays null → prepareFields() fills sub-fields from []
 *
 * Template uses explicit changeFill/changeRender/onApply callbacks, giving full
 * control over fill and save without relying on internal MoonShine conventions.
 *
 * Sub-fields must use plain column names (e.g. 'title', 'meta_title') — no 'data.' prefix.
 * Template's wrapNames('data') handles HTML form namespacing (data[title], data[meta_title]).
 */
final class BlockDataTemplate
{
    /**
     * Create a Template field wrapping all block data sub-fields.
     *
     * @param list<FieldContract> $fields
     */
    public static function make(array $fields): Template
    {
        return Template::make('', 'data')
            ->fields($fields)
            ->changeFill(
                static fn ($model) => (array) ($model->getAttribute('data') ?? [])
            )
            ->changeRender(
                static fn (mixed $data, Template $ctx): FieldsGroup => FieldsGroup::make($ctx->getPreparedFields())
                    ->fill(is_array($data) ? $data : [])
            )
            ->onApply(
                static function ($item, mixed $value, Template $field): mixed {
                    $applyValues = [];
                    $valueArr    = is_array($value) ? $value : [];

                    foreach ($field->getPreparedFields()->onlyFields() as $f) {
                        $applied = $f->apply(
                            static fn (mixed $data): mixed => data_set(
                                $data,
                                $f->getColumn(),
                                $valueArr[$f->getColumn()] ?? null
                            ),
                            $valueArr
                        );

                        data_set($applyValues, $f->getColumn(), data_get($applied, $f->getColumn()));
                    }

                    return data_set($item, 'data', $applyValues);
                }
            );
    }
}
