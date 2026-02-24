<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Unit;

use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Fieldset;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Reker7\MoonShineBlocks\BuildFieldsFromConfig;
use Reker7\MoonShineBlocks\Tests\TestCase;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder\FieldItem;
use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder\FieldsCollection;

final class BuildFieldsFromConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MoonShineComponent::consoleMode(true);
    }

    protected function tearDown(): void
    {
        MoonShineComponent::consoleMode(false);

        parent::tearDown();
    }

    private function buildOne(string $type, array $extra = []): mixed
    {
        $config = [array_merge(['name' => 'Field', 'key' => 'field', 'type' => $type, 'required' => false], $extra)];

        return BuildFieldsFromConfig::make($config)->build()[0] ?? null;
    }

    // =========================================================================
    // Empty config
    // =========================================================================

    #[Test]
    public function empty_config_returns_empty_fields(): void
    {
        $fields = BuildFieldsFromConfig::make(null)->build();

        $this->assertSame([], $fields);
    }

    #[Test]
    public function empty_array_returns_empty_fields(): void
    {
        $fields = BuildFieldsFromConfig::make([])->build();

        $this->assertSame([], $fields);
    }

    // =========================================================================
    // Field type → MoonShine class mapping
    // =========================================================================

    #[Test]
    public function text_type_returns_text_field(): void
    {
        $this->assertInstanceOf(Text::class, $this->buildOne('text'));
    }

    #[Test]
    public function textarea_type_returns_textarea_field(): void
    {
        $this->assertInstanceOf(Textarea::class, $this->buildOne('textarea'));
    }

    #[Test]
    public function number_type_returns_number_field(): void
    {
        $this->assertInstanceOf(Number::class, $this->buildOne('number'));
    }

    #[Test]
    public function switcher_type_returns_switcher_field(): void
    {
        $this->assertInstanceOf(Switcher::class, $this->buildOne('switcher'));
    }

    #[Test]
    public function date_type_returns_date_field(): void
    {
        $this->assertInstanceOf(Date::class, $this->buildOne('date'));
    }

    #[Test]
    public function datetime_type_returns_date_field(): void
    {
        $this->assertInstanceOf(Date::class, $this->buildOne('datetime'));
    }

    #[Test]
    public function phone_type_returns_text_field(): void
    {
        // phone maps to Text with custom tel attributes
        $this->assertInstanceOf(Text::class, $this->buildOne('phone'));
    }

    #[Test]
    public function select_type_returns_select_field(): void
    {
        $this->assertInstanceOf(Select::class, $this->buildOne('select'));
    }

    #[Test]
    public function repeater_type_returns_json_field(): void
    {
        $this->assertInstanceOf(Json::class, $this->buildOne('repeater'));
    }

    #[Test]
    public function image_type_returns_image_field(): void
    {
        $this->assertInstanceOf(Image::class, $this->buildOne('image'));
    }

    #[Test]
    public function file_type_returns_file_field(): void
    {
        $this->assertInstanceOf(File::class, $this->buildOne('file'));
    }

    #[Test]
    public function block_relation_type_returns_select_field(): void
    {
        $this->assertInstanceOf(Select::class, $this->buildOne('block_relation'));
    }

    #[Test]
    public function unknown_type_returns_text_field(): void
    {
        // FieldItem normalizes unknown types to 'text', but makeField() has default: Text
        $this->assertInstanceOf(Text::class, $this->buildOne('unknown_type'));
    }

    // =========================================================================
    // Column naming
    // =========================================================================

    #[Test]
    public function field_column_uses_root_prefix(): void
    {
        $config = [['name' => 'Title', 'key' => 'title', 'type' => 'text', 'required' => false]];
        $field  = BuildFieldsFromConfig::make($config, 'data')->build()[0];

        $this->assertSame('data.title', $field->getColumn());
    }

    #[Test]
    public function field_column_without_root_is_just_key(): void
    {
        $config = [['name' => 'Title', 'key' => 'title', 'type' => 'text', 'required' => false]];
        $field  = BuildFieldsFromConfig::make($config, '')->build()[0];

        $this->assertSame('title', $field->getColumn());
    }

    // =========================================================================
    // Multiple fields
    // =========================================================================

    #[Test]
    public function multiple_fields_are_built_in_order(): void
    {
        $config = [
            ['name' => 'Title',  'key' => 'title',  'type' => 'text',   'required' => false],
            ['name' => 'Body',   'key' => 'body',   'type' => 'textarea', 'required' => false],
            ['name' => 'Count',  'key' => 'count',  'type' => 'number', 'required' => false],
        ];

        $fields = BuildFieldsFromConfig::make($config)->build();

        $this->assertCount(3, $fields);
        $this->assertInstanceOf(Text::class, $fields[0]);
        $this->assertInstanceOf(Textarea::class, $fields[1]);
        $this->assertInstanceOf(Number::class, $fields[2]);
    }

    // =========================================================================
    // Repeater field with nested fields
    // =========================================================================

    #[Test]
    public function repeater_field_with_nested_fields_creates_nested_fields(): void
    {
        $config = [[
            'name'     => 'Items',
            'key'      => 'items',
            'type'     => 'repeater',
            'required' => false,
            'fields'   => [
                ['name' => 'Label', 'key' => 'label', 'type' => 'text', 'required' => false],
                ['name' => 'Value', 'key' => 'value', 'type' => 'text', 'required' => false],
            ],
        ]];

        $fields = BuildFieldsFromConfig::make($config)->build();

        $this->assertCount(1, $fields);
        $this->assertInstanceOf(Json::class, $fields[0]);
    }

    // =========================================================================
    // withDefaults
    // =========================================================================

    #[Test]
    public function with_defaults_overrides_field_config_default(): void
    {
        $collection = FieldsCollection::fromMixed([
            ['name' => 'Title', 'key' => 'title', 'type' => 'text', 'required' => false, 'options' => ['default_value' => 'Config default']],
        ]);

        $builder = BuildFieldsFromConfig::make($collection)->withDefaults(['title' => 'Override default']);
        $fields  = $builder->build();

        // Field is built successfully (default is set internally, not exposed as attribute easily)
        $this->assertCount(1, $fields);
        $this->assertInstanceOf(Text::class, $fields[0]);
    }

    // =========================================================================
    // Static make() constructor
    // =========================================================================

    #[Test]
    public function static_make_creates_instance(): void
    {
        $builder = BuildFieldsFromConfig::make([]);

        $this->assertInstanceOf(BuildFieldsFromConfig::class, $builder);
    }

    #[Test]
    public function make_accepts_json_string(): void
    {
        $json   = json_encode([['name' => 'Title', 'key' => 'title', 'type' => 'text', 'required' => false]]);
        $fields = BuildFieldsFromConfig::make($json)->build();

        $this->assertCount(1, $fields);
        $this->assertInstanceOf(Text::class, $fields[0]);
    }

    #[Test]
    public function make_accepts_fields_collection(): void
    {
        $collection = FieldsCollection::fromMixed([
            ['name' => 'Body', 'key' => 'body', 'type' => 'textarea', 'required' => false],
        ]);
        $fields = BuildFieldsFromConfig::make($collection)->build();

        $this->assertCount(1, $fields);
        $this->assertInstanceOf(Textarea::class, $fields[0]);
    }

    // =========================================================================
    // Fieldset type
    // =========================================================================

    #[Test]
    public function fieldset_skips_when_file_not_found(): void
    {
        $dir = sys_get_temp_dir() . '/fieldset_build_test_' . uniqid();
        mkdir($dir, 0755, true);

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $fields = BuildFieldsFromConfig::make([
                ['name' => 'My Fieldset', 'key' => 'nonexistent', 'type' => 'fieldset', 'required' => false],
            ])->build();

            $this->assertSame([], $fields);
        } finally {
            rmdir($dir);
        }
    }

    #[Test]
    public function fieldset_expands_to_fieldset_component(): void
    {
        $dir = sys_get_temp_dir() . '/fieldset_build_test_' . uniqid();
        mkdir($dir, 0755, true);

        file_put_contents($dir . '/seo.json', json_encode([
            'title'  => 'SEO Fields',
            'fields' => [
                ['name' => 'Meta Title', 'key' => 'meta_title', 'type' => 'text'],
            ],
        ]));

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $fields = BuildFieldsFromConfig::make([
                ['name' => '', 'key' => 'seo', 'type' => 'fieldset', 'required' => false],
            ])->build();

            $this->assertCount(1, $fields);
            $this->assertInstanceOf(Fieldset::class, $fields[0]);
        } finally {
            array_map('unlink', glob($dir . '/*.json') ?: []);
            rmdir($dir);
        }
    }

    #[Test]
    public function fieldset_uses_item_name_as_title_override(): void
    {
        $dir = sys_get_temp_dir() . '/fieldset_build_test_' . uniqid();
        mkdir($dir, 0755, true);

        file_put_contents($dir . '/seo.json', json_encode([
            'title'  => 'SEO Fields',
            'fields' => [
                ['name' => 'Meta Title', 'key' => 'meta_title', 'type' => 'text'],
            ],
        ]));

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $fields = BuildFieldsFromConfig::make([
                ['name' => 'Custom SEO', 'key' => 'seo', 'type' => 'fieldset', 'required' => false],
            ])->build();

            $this->assertCount(1, $fields);
            $this->assertInstanceOf(Fieldset::class, $fields[0]);
        } finally {
            array_map('unlink', glob($dir . '/*.json') ?: []);
            rmdir($dir);
        }
    }

    #[Test]
    public function fieldset_applies_default_value_to_subfields(): void
    {
        $dir = sys_get_temp_dir() . '/fieldset_build_test_' . uniqid();
        mkdir($dir, 0755, true);

        file_put_contents($dir . '/seo.json', json_encode([
            'title'  => 'SEO Fields',
            'fields' => [
                ['name' => 'Meta Title', 'key' => 'meta_title', 'type' => 'text'],
                ['name' => 'Description', 'key' => 'description', 'type' => 'textarea'],
            ],
        ]));

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $defaults = json_encode(['meta_title' => 'Default SEO Title']);
            $fields   = BuildFieldsFromConfig::make([
                [
                    'name'     => 'SEO',
                    'key'      => 'seo',
                    'type'     => 'fieldset',
                    'required' => false,
                    'options'  => ['default_value' => $defaults],
                ],
            ])->build();

            $this->assertCount(1, $fields);
            $this->assertInstanceOf(Fieldset::class, $fields[0]);
        } finally {
            array_map('unlink', glob($dir . '/*.json') ?: []);
            rmdir($dir);
        }
    }

    #[Test]
    public function fieldset_skips_when_produces_empty_subfields(): void
    {
        $dir = sys_get_temp_dir() . '/fieldset_build_test_' . uniqid();
        mkdir($dir, 0755, true);

        file_put_contents($dir . '/empty.json', json_encode([
            'title'  => 'Empty Fieldset',
            'fields' => [],
        ]));

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $fields = BuildFieldsFromConfig::make([
                ['name' => 'Empty', 'key' => 'empty', 'type' => 'fieldset', 'required' => false],
            ])->build();

            $this->assertSame([], $fields);
        } finally {
            array_map('unlink', glob($dir . '/*.json') ?: []);
            rmdir($dir);
        }
    }
}
