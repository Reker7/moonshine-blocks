<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Unit;

use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Fields\Template;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use PHPUnit\Framework\Attributes\Test;
use Reker7\MoonShineBlocks\BuildBlockItemFields;
use Reker7\MoonShineBlocks\Tests\TestCase;
use Reker7\MoonShineBlocksCore\Models\Block;

final class BuildBlockItemFieldsTest extends TestCase
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

    private function builder(): BuildBlockItemFields
    {
        return new BuildBlockItemFields();
    }

    private function makeBlock(?array $fields = null, bool $isMultiple = false): Block
    {
        return Block::create([
            'slug'        => 'test-' . uniqid(),
            'name'        => 'Test Block',
            'is_multiple' => $isMultiple,
            'fields'      => $fields,
        ]);
    }

    // =========================================================================
    // buildForBlock
    // =========================================================================

    #[Test]
    public function build_for_block_returns_array(): void
    {
        $block  = $this->makeBlock();
        $result = $this->builder()->buildForBlock($block);

        $this->assertIsArray($result);
    }

    #[Test]
    public function build_for_block_empty_block_returns_empty_array(): void
    {
        $block  = $this->makeBlock(null);
        $result = $this->builder()->buildForBlock($block);

        $this->assertSame([], $result);
    }

    #[Test]
    public function build_for_block_with_regular_fields_returns_template_as_first_element(): void
    {
        $block = $this->makeBlock([
            ['name' => 'Title', 'key' => 'title', 'type' => 'text', 'required' => false],
        ]);

        $result = $this->builder()->buildForBlock($block);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Template::class, $result[0]);
    }

    #[Test]
    public function build_for_block_with_fieldset_fields_returns_single_template(): void
    {
        $dir = sys_get_temp_dir() . '/bbif_test_' . uniqid();
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/seo.json', json_encode([
            'title'  => 'SEO',
            'fields' => [
                ['name' => 'Meta Title', 'key' => 'meta_title', 'type' => 'text'],
            ],
        ]));

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $block = $this->makeBlock([
                ['name' => 'Body', 'key' => 'body', 'type' => 'textarea', 'required' => false],
                ['name' => 'SEO',  'key' => 'seo',  'type' => 'fieldset', 'required' => false],
            ]);

            $result = $this->builder()->buildForBlock($block);

            // All block fields (including expanded fieldset sub-fields) wrapped in one Template
            $this->assertCount(1, $result);
            $this->assertInstanceOf(Template::class, $result[0]);
        } finally {
            array_map('unlink', glob($dir . '/*.json') ?: []);
            rmdir($dir);
        }
    }

    // =========================================================================
    // buildFieldsArray — simpler to assert on
    // =========================================================================

    #[Test]
    public function build_fields_array_empty_block_returns_empty_array(): void
    {
        $block  = $this->makeBlock(null);
        $fields = $this->builder()->buildFieldsArray($block);

        $this->assertSame([], $fields);
    }

    #[Test]
    public function build_fields_array_returns_fields_for_block(): void
    {
        $block = $this->makeBlock([
            ['name' => 'Title', 'key' => 'title', 'type' => 'text',     'required' => false],
            ['name' => 'Body',  'key' => 'body',  'type' => 'textarea', 'required' => false],
        ]);

        $fields = $this->builder()->buildFieldsArray($block);

        $this->assertCount(2, $fields);
        $this->assertInstanceOf(Text::class, $fields[0]);
        $this->assertInstanceOf(Textarea::class, $fields[1]);
    }

    #[Test]
    public function build_fields_array_field_key_includes_root_prefix(): void
    {
        $block = $this->makeBlock([
            ['name' => 'Title', 'key' => 'title', 'type' => 'text', 'required' => false],
        ]);

        $fields = $this->builder()->buildFieldsArray($block, 'data');

        $this->assertCount(1, $fields);
        $this->assertSame('data.title', $fields[0]->getColumn());
    }
}
