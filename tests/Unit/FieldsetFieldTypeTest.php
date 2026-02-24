<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Reker7\MoonShineBlocks\FieldTypes\FieldsetFieldType;
use Reker7\MoonShineBlocks\Tests\TestCase;

final class FieldsetFieldTypeTest extends TestCase
{
    private function make(): FieldsetFieldType
    {
        return new FieldsetFieldType();
    }

    // =========================================================================
    // type / label / sections
    // =========================================================================

    #[Test]
    public function type_returns_fieldset(): void
    {
        $this->assertSame('fieldset', $this->make()->type());
    }

    #[Test]
    public function modal_sections_are_basic_and_rules(): void
    {
        $this->assertSame(['basic', 'rules'], $this->make()->modalSections());
    }

    // =========================================================================
    // optionKeys
    // =========================================================================

    #[Test]
    public function option_keys_contains_only_default_value(): void
    {
        $this->assertSame(['default_value'], $this->make()->optionKeys());
    }

    // =========================================================================
    // modalView
    // =========================================================================

    #[Test]
    public function modal_view_returns_correct_path(): void
    {
        $this->assertSame('moonshine-blocks::modal.fieldset', $this->make()->modalView());
    }

    // =========================================================================
    // data — hiddenBasicFields
    // =========================================================================

    #[Test]
    public function data_hides_placeholder_hint_and_key(): void
    {
        $data = $this->make()->data();

        $this->assertArrayHasKey('hiddenBasicFields', $data);
        $this->assertContains('placeholder', $data['hiddenBasicFields']);
        $this->assertContains('hint', $data['hiddenBasicFields']);
        $this->assertContains('key', $data['hiddenBasicFields']);
    }

    // =========================================================================
    // data — availableFieldsets + fieldsetData (with temp dir)
    // =========================================================================

    #[Test]
    public function data_returns_available_fieldsets_from_config(): void
    {
        $dir = sys_get_temp_dir() . '/fieldset_type_test_' . uniqid();
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/seo.json', json_encode([
            'title'  => 'SEO',
            'fields' => [
                ['name' => 'Meta Title', 'key' => 'meta_title', 'type' => 'text'],
            ],
        ]));
        file_put_contents($dir . '/social.json', json_encode([
            'title'  => 'Social',
            'fields' => [
                ['name' => 'OG Title', 'key' => 'og_title', 'type' => 'text'],
            ],
        ]));

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $data      = $this->make()->data();
            $available = $data['availableFieldsets'];

            sort($available);
            $this->assertSame(['seo', 'social'], $available);
        } finally {
            array_map('unlink', glob($dir . '/*.json') ?: []);
            rmdir($dir);
        }
    }

    #[Test]
    public function data_fieldset_data_contains_fields_for_each_key(): void
    {
        $dir = sys_get_temp_dir() . '/fieldset_type_test_' . uniqid();
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/seo.json', json_encode([
            'title'  => 'SEO Fields',
            'fields' => [
                ['name' => 'Meta Title', 'key' => 'meta_title', 'type' => 'text'],
                ['name' => 'Active',     'key' => 'active',     'type' => 'switcher'],
            ],
        ]));

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $data         = $this->make()->data();
            $fieldsetData = $data['fieldsetData'];

            $this->assertArrayHasKey('seo', $fieldsetData);
            $this->assertSame('SEO Fields', $fieldsetData['seo']['title']);
            $this->assertCount(2, $fieldsetData['seo']['fields']);
            $this->assertSame('meta_title', $fieldsetData['seo']['fields'][0]['key']);
            $this->assertSame('text', $fieldsetData['seo']['fields'][0]['type']);
        } finally {
            array_map('unlink', glob($dir . '/*.json') ?: []);
            rmdir($dir);
        }
    }

    #[Test]
    public function data_fieldset_data_excludes_image_file_and_repeater_fields(): void
    {
        $dir = sys_get_temp_dir() . '/fieldset_type_test_' . uniqid();
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/media.json', json_encode([
            'title'  => 'Media',
            'fields' => [
                ['name' => 'Title',    'key' => 'title',    'type' => 'text'],
                ['name' => 'Photo',    'key' => 'photo',    'type' => 'image'],
                ['name' => 'Document', 'key' => 'document', 'type' => 'file'],
                ['name' => 'Items',    'key' => 'items',    'type' => 'repeater'],
            ],
        ]));

        try {
            $this->app['config']->set('moonshine-blocks.fieldsets.path', $dir);

            $data   = $this->make()->data();
            $fields = $data['fieldsetData']['media']['fields'];

            $this->assertCount(1, $fields);
            $this->assertSame('title', $fields[0]['key']);
        } finally {
            array_map('unlink', glob($dir . '/*.json') ?: []);
            rmdir($dir);
        }
    }

    #[Test]
    public function data_returns_empty_when_no_fieldsets_dir(): void
    {
        $this->app['config']->set('moonshine-blocks.fieldsets.path', '/nonexistent/path');

        $data = $this->make()->data();

        $this->assertSame([], $data['availableFieldsets']);
        $this->assertSame([], $data['fieldsetData']);
    }
}
