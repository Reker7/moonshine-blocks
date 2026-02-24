<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\FieldTypes;

use Reker7\MoonShineBlocks\Support\FieldsetLoader;
use Reker7\MoonShineFieldsBuilder\FieldTypes\FieldType;

final class FieldsetFieldType extends FieldType
{
    public function type(): string
    {
        return 'fieldset';
    }

    public function label(): string
    {
        return __('moonshine-blocks::ui.fieldset.label');
    }

    public function modalSections(): array
    {
        return ['basic', 'rules'];
    }

    /**
     * Only default_value is relevant for fieldsets.
     * placeholder and hint are hidden via hiddenBasicFields.
     *
     * @return array<string>
     */
    public function optionKeys(): array
    {
        return ['default_value'];
    }

    public function modalView(): ?string
    {
        return 'moonshine-blocks::modal.fieldset';
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $available    = [];
        $fieldsetData = [];

        if (function_exists('config')) {
            $path      = (string) config('moonshine-blocks.fieldsets.path', resource_path('blocks/fieldsets'));
            $loader    = new FieldsetLoader($path);
            $available = $loader->available();

            foreach ($available as $key) {
                $data = $loader->load($key);

                if ($data === null) {
                    continue;
                }

                $fields = [];

                foreach ($data['fields']->all() as $field) {
                    // Skip types where a default value makes no sense
                    if (in_array($field->type, ['image', 'file', 'repeater'], true)) {
                        continue;
                    }

                    $fields[] = [
                        'key'  => $field->key,
                        'name' => $field->name ?: $field->key,
                        'type' => $field->type,
                    ];
                }

                $fieldsetData[$key] = [
                    'title'  => $data['title'],
                    'fields' => $fields,
                ];
            }
        }

        return [
            'hiddenBasicFields' => ['placeholder', 'hint', 'key'],
            'availableFieldsets' => $available,
            'fieldsetData'       => $fieldsetData,
        ];
    }
}
