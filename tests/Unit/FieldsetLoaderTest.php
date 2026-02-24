<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Reker7\MoonShineBlocks\Support\FieldsetLoader;

final class FieldsetLoaderTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/fieldset_loader_test_' . uniqid();
        mkdir($this->dir, 0755, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*.json') ?: []);
        rmdir($this->dir);

        parent::tearDown();
    }

    private function write(string $key, array $data): void
    {
        file_put_contents($this->dir . '/' . $key . '.json', json_encode($data));
    }

    private function loader(): FieldsetLoader
    {
        return new FieldsetLoader($this->dir);
    }

    // =========================================================================
    // load
    // =========================================================================

    #[Test]
    public function load_returns_null_for_empty_key(): void
    {
        $this->assertNull($this->loader()->load(''));
    }

    #[Test]
    public function load_returns_null_for_missing_file(): void
    {
        $this->assertNull($this->loader()->load('nonexistent'));
    }

    #[Test]
    public function load_returns_null_for_invalid_json(): void
    {
        file_put_contents($this->dir . '/bad.json', 'not-json');

        $this->assertNull($this->loader()->load('bad'));
    }

    #[Test]
    public function load_returns_title_and_fields_collection(): void
    {
        $this->write('seo', [
            'title'  => 'SEO поля',
            'fields' => [
                ['name' => 'Meta Title', 'key' => 'meta_title', 'type' => 'text'],
            ],
        ]);

        $result = $this->loader()->load('seo');

        $this->assertNotNull($result);
        $this->assertSame('SEO поля', $result['title']);
        $this->assertCount(1, $result['fields']);
        $this->assertSame('meta_title', $result['fields']->get(0)->key);
    }

    #[Test]
    public function load_falls_back_to_key_when_title_missing(): void
    {
        $this->write('media', [
            'fields' => [
                ['name' => 'Image', 'key' => 'image', 'type' => 'image'],
            ],
        ]);

        $result = $this->loader()->load('media');

        $this->assertSame('media', $result['title']);
    }

    #[Test]
    public function load_returns_empty_collection_when_fields_missing(): void
    {
        $this->write('empty', ['title' => 'Empty']);

        $result = $this->loader()->load('empty');

        $this->assertNotNull($result);
        $this->assertTrue($result['fields']->isEmpty());
    }

    // =========================================================================
    // available
    // =========================================================================

    #[Test]
    public function available_returns_empty_for_missing_directory(): void
    {
        $loader = new FieldsetLoader('/nonexistent/path');

        $this->assertSame([], $loader->available());
    }

    #[Test]
    public function available_returns_empty_when_no_json_files(): void
    {
        $this->assertSame([], $this->loader()->available());
    }

    #[Test]
    public function available_returns_filenames_without_extension(): void
    {
        $this->write('seo', ['title' => 'SEO']);
        $this->write('media', ['title' => 'Media']);

        $available = $this->loader()->available();

        sort($available);
        $this->assertSame(['media', 'seo'], $available);
    }
}
