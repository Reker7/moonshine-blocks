<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Support\DTOs\Select\Option;
use MoonShine\Support\DTOs\Select\Options;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;
use Reker7\MoonShineBlocksCore\Models\BlockItem;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder\FieldItem as ConfigFieldItem;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder\FieldsCollection;

/**
 * Builder for MoonShine fields from JSON config (FieldsCollection/FieldItem).
 *
 * Usage:
 *   $builder = new BuildFieldsFromConfig();
 *   $fields = $builder->build($fieldsCollection, 'data');
 *
 * Or statically:
 *   $fields = BuildFieldsFromConfig::make($config)->build();
 */
final class BuildFieldsFromConfig
{
    private FieldsCollection $collection;

    private string $root;

    /**
     * @param FieldsCollection|array<int, array<string, mixed>>|string|null $config
     */
    public function __construct(
        FieldsCollection|array|string|null $config = null,
        string $root = 'data'
    ) {
        $this->collection = FieldsCollection::fromMixed($config);
        $this->root = $root;
    }

    /**
     * Static constructor
     *
     * @param FieldsCollection|array<int, array<string, mixed>>|string|null $config
     */
    public static function make(
        FieldsCollection|array|string|null $config = null,
        string $root = 'data'
    ): self {
        return new self($config, $root);
    }

    /**
     * Build array of MoonShine fields
     *
     * @return list<FieldContract>
     */
    public function build(): array
    {
        $fields = [];

        foreach ($this->collection->all() as $item) {
            $fields[] = $this->makeField($item, $this->root);
        }

        return $fields;
    }

    /**
     * Build fields wrapped in Json::object()
     * For saving to JSON column
     */
    public function buildAsJsonObject(string $column = 'data'): FieldContract
    {
        return Json::make('', $column)
            ->object()
            ->fields($this->build());
    }

    /**
     * Create one field from FieldItem
     */
    public function makeField(ConfigFieldItem $item, string $root = ''): FieldContract
    {
        $key = $item->key ?: 'field';
        $name = $root !== '' ? rtrim($root, '.') . '.' . $key : $key;
        $label = $item->name ?: $key;

        $field = match ($item->type) {
            'text' => $this->text($label, $name, $item),
            'textarea' => $this->textarea($label, $name, $item),
            'number' => $this->number($label, $name, $item),
            'switcher' => Switcher::make($label, $name),
            'date' => Date::make($label, $name),
            'datetime' => Date::make($label, $name)->withTime(),
            'phone' => $this->phone($label, $name, $item),
            'select' => $this->select($label, $name, $item),
            'json', 'nested' => $this->json($label, $name, $item),
            'image' => Image::make($label, $name)->dir('fields'),
            'file' => File::make($label, $name)->dir('fields'),
            'block_relation' => $this->blockRelation($label, $name, $item),
            default => Text::make($label, $name),
        };

        if ($item->required && method_exists($field, 'required')) {
            $field->required();
        }

        $hint = $item->hint();
        if ($hint !== '' && method_exists($field, 'hint')) {
            $field->hint($hint);
        }

        $defaultValue = $item->defaultValue();
        if (
            $defaultValue !== ''
            && $item->type !== 'select'
            && method_exists($field, 'default')
        ) {
            $field->default($defaultValue);
        }

        return $field;
    }

    /**
     * Text field with placeholder
     */
    private function text(string $label, string $name, ConfigFieldItem $item): FieldContract
    {
        $field = Text::make($label, $name);

        $placeholder = $item->placeholder();
        if ($placeholder !== '') {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    /**
     * Textarea field
     */
    private function textarea(string $label, string $name, ConfigFieldItem $item): FieldContract
    {
        $field = Textarea::make($label, $name);

        $placeholder = $item->placeholder();
        if ($placeholder !== '') {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    /**
     * Number field with min/max/step
     */
    private function number(string $label, string $name, ConfigFieldItem $item): FieldContract
    {
        $field = Number::make($label, $name);

        $placeholder = $item->placeholder();
        if ($placeholder !== '') {
            $field->placeholder($placeholder);
        }

        $attrs = [];

        $min = $item->min();
        if ($min !== null) {
            $attrs['min'] = $min;
        }

        $max = $item->max();
        if ($max !== null) {
            $attrs['max'] = $max;
        }

        $step = $item->step();
        if ($step !== null) {
            $attrs['step'] = $step;
        }

        if ($attrs !== []) {
            $field->customAttributes($attrs);
        }

        return $field;
    }

    /**
     * Phone field (text with mask/tel type)
     */
    private function phone(string $label, string $name, ConfigFieldItem $item): FieldContract
    {
        $field = Text::make($label, $name);

        $field->customAttributes([
            'type' => 'tel',
            'inputmode' => 'tel',
        ]);

        $placeholder = $item->placeholder();
        if ($placeholder !== '') {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    /**
     * Select field with options
     */
    private function select(string $label, string $name, ConfigFieldItem $item): FieldContract
    {
        $field = Select::make($label, $name)->native();

        $values = $item->values();
        if ($values !== []) {
            $options = $this->buildSelectOptions($values, $item->defaultValue());
            if ($options !== []) {
                $field->options(new Options($options));
            }
        }

        if ($item->isMultiple()) {
            $field->multiple();
        }

        $placeholder = $item->placeholder();
        if ($placeholder !== '') {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    /**
     * JSON/Nested field with nested fields
     */
    private function json(string $label, string $name, ConfigFieldItem $item): FieldContract
    {
        $json = Json::make($label, $name);

        if ($item->fields !== []) {
            $nestedFields = [];

            foreach ($item->fields as $nested) {
                $nestedFields[] = $this->makeField($nested, '');
            }

            $json->fields($nestedFields);
            $json->creatable()->removable();
        } else {
            $json->keyValue('Key', 'Value');
        }

        return $json;
    }

    /**
     * Build array of Option for Select
     *
     * @param array<string, string> $values
     * @return array<int, Option>
     */
    private function buildSelectOptions(array $values, string $defaultValue = ''): array
    {
        $result = [];

        foreach ($values as $value => $label) {
            $value = (string) $value;
            if ($value === '') {
                continue;
            }

            $result[] = new Option(
                label: (string) $label,
                value: $value,
                selected: $value === $defaultValue,
                properties: null
            );
        }

        return $result;
    }

    /**
     * Block relation field - select items from another block or group
     *
     * Settings (from options):
     * - relation_type: 'block' (multiple block) or 'group' (group of singular blocks)
     * - relation_target: slug of the block or group
     * - multiple: allow selecting multiple items
     */
    private function blockRelation(string $label, string $name, ConfigFieldItem $item): FieldContract
    {
        $relationType = $item->relationType();
        $relationTarget = $item->relationTarget();
        $isMultiple = $item->isMultiple();

        $field = Select::make($label, $name)->native();

        if ($isMultiple) {
            $field->multiple();
        }

        $field->nullable();

        // Build options based on relation type
        $options = $this->buildBlockRelationOptions($relationType, $relationTarget);

        if ($options !== []) {
            $field->options(new Options($options));
        }

        $placeholder = $item->placeholder();
        if ($placeholder !== '') {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    /**
     * Build options for block relation select
     *
     * @return array<int, Option>
     */
    private function buildBlockRelationOptions(string $type, string $target): array
    {
        if ($target === '') {
            return [];
        }

        $items = collect();

        if ($type === 'block') {
            // Get items from a specific block
            $block = Block::query()
                ->where('slug', $target)
                ->where('is_active', true)
                ->first();

            if ($block) {
                $items = $block->items()
                    ->where('is_active', true)
                    ->orderBy('sorting')
                    ->get();
            }
        } elseif ($type === 'group') {
            // Get blocks from group - for group relation, we select BLOCKS not items
            $group = BlockGroup::query()
                ->where('slug', $target)
                ->where('is_active', true)
                ->first();

            if ($group) {
                // For group relation - return blocks themselves as options
                $blocks = $group->blocks()
                    ->where('is_active', true)
                    ->orderBy('sorting')
                    ->get();

                // Return blocks as options (not items)
                return $blocks->map(fn (Block $block) => new Option(
                    label: $block->name,
                    value: $block->slug,
                    selected: false,
                    properties: null
                ))->values()->all();
            }
        }

        return $items->map(fn (BlockItem $item) => new Option(
            label: $item->title ?: $item->slug,
            value: (string) $item->id,
            selected: false,
            properties: null
        ))->values()->all();
    }
}
