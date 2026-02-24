<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Support\DTOs\Select\Option;
use MoonShine\Support\DTOs\Select\Options;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\Fieldset;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use Reker7\MoonShineBlocks\Support\FieldsetLoader;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineFieldsBuilder\Support\Json as JsonSupport;
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

    /** @var array<string, mixed> */
    private array $defaults = [];

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
     * Set default values for fields (keyed by field key)
     *
     * @param array<string, mixed> $defaults
     */
    public function withDefaults(array $defaults): self
    {
        $this->defaults = $defaults;

        return $this;
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
            if ($item->type === 'fieldset') {
                $fieldset = $this->fieldset($item);
                if ($fieldset !== null) {
                    $fields[] = $fieldset;
                }
                continue;
            }

            $fields[] = $this->makeField($item, $this->root);
        }

        return $fields;
    }

    /**
     * Build fields flattened — fieldset sub-fields expanded directly into the array.
     *
     * Unlike build(), fieldset types are NOT wrapped in Fieldset::make().
     * Their sub-fields are added at the same level as regular fields.
     *
     * Use this for Template-based forms (BlockDataTemplate) where Fieldset as
     * FieldsWrapperContract breaks the fill chain.
     *
     * @return list<FieldContract>
     */
    public function buildFlat(): array
    {
        $fields = [];

        foreach ($this->collection->all() as $item) {
            if ($item->type === 'fieldset') {
                $subFields = $this->fieldsetSubFields($item);
                array_push($fields, ...$subFields);
                continue;
            }

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
            'repeater' => $this->json($label, $name, $item),
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

        $defaultValue = $this->getDefaultValue($item->key, $item->defaultValue());
        if (
            $defaultValue !== null
            && $defaultValue !== ''
            && $item->type !== 'select'
            && method_exists($field, 'default')
        ) {
            $field->default($defaultValue);
        }

        return $field;
    }

    /**
     * Get default value for a field (from defaults array or field config)
     */
    private function getDefaultValue(string $key, string $configDefault): mixed
    {
        if ($key !== '' && array_key_exists($key, $this->defaults)) {
            return $this->defaults[$key];
        }

        return $configDefault;
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
            $defaultValue = $this->getDefaultValue($item->key, $item->defaultValue());
            $options = $this->buildSelectOptions($values, (string) $defaultValue);
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
        $json = Json::make($label, $name)->creatable()->removable();

        if ($item->fields !== []) {
            $nestedFields = [];

            foreach ($item->fields as $nested) {
                $nestedFields[] = $this->makeField($nested, '');
            }

            $json->fields($nestedFields);
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
        $relationType = (string) ($item->option('relation_type', 'block') ?: 'block');
        $relationTarget = (string) ($item->option('relation_target', '') ?: '');
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
     * Get sub-fields for a fieldset item as a flat array (no Fieldset wrapper).
     * Used by buildFlat() to expand fieldset sub-fields into the parent array.
     *
     * @return list<FieldContract>
     */
    private function fieldsetSubFields(ConfigFieldItem $item): array
    {
        $path = function_exists('config')
            ? (string) config('moonshine-blocks.fieldsets.path', resource_path('blocks/fieldsets'))
            : '';

        if ($path === '') {
            return [];
        }

        $data = (new FieldsetLoader($path))->load($item->key);

        if ($data === null) {
            return [];
        }

        $defaults = $this->resolveFieldsetDefaults($item);

        return BuildFieldsFromConfig::make($data['fields'], $this->root)
            ->withDefaults($defaults)
            ->build();
    }

    /**
     * Build a Fieldset component from a fieldset FieldItem.
     * Loads the JSON file, applies defaults, wraps sub-fields in Fieldset::make().
     * Returns null when the fieldset file is not found or produces no fields.
     */
    private function fieldset(ConfigFieldItem $item): ?FieldContract
    {
        $path = function_exists('config')
            ? (string) config('moonshine-blocks.fieldsets.path', resource_path('blocks/fieldsets'))
            : '';

        if ($path === '') {
            return null;
        }

        $data = (new FieldsetLoader($path))->load($item->key);

        if ($data === null) {
            return null;
        }

        $title    = $item->name !== '' ? $item->name : $data['title'];
        $defaults = $this->resolveFieldsetDefaults($item);

        $subFields = BuildFieldsFromConfig::make($data['fields'], $this->root)
            ->withDefaults($defaults)
            ->build();

        if ($subFields === []) {
            return null;
        }

        return Fieldset::make($title, $subFields);
    }

    /**
     * Decode default_value option for a fieldset item.
     * Accepts an array or a JSON-encoded string → returns key-value map.
     *
     * @return array<string, mixed>
     */
    private function resolveFieldsetDefaults(ConfigFieldItem $item): array
    {
        $raw = $item->option('default_value');

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && $raw !== '') {
            return JsonSupport::decodeArray($raw) ?? [];
        }

        return [];
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
        }

        if ($type === 'group') {
            // Get blocks from group - for group relation, we select BLOCKS not items
            $group = BlockGroup::query()
                ->where('slug', $target)
                ->where('is_active', true)
                ->first();

            if (! $group) {
                return [];
            }

            // For group relation - return blocks themselves as options
            return $group->blocks()
                ->where('is_active', true)
                ->orderBy('sorting')
                ->get()
                ->map(fn (Block $block) => new Option(
                    label: $block->name,
                    value: $block->slug,
                    selected: false,
                    properties: null
                ))->values()->all();
        }

        return $items->map(fn (BlockItem $item) => new Option(
            label: $item->title ?: $item->slug,
            value: (string) $item->id,
            selected: false,
            properties: null
        ))->values()->all();
    }
}
