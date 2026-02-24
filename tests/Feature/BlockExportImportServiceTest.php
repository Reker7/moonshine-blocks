<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Reker7\MoonShineBlocks\Services\BlockExportImportService;
use Reker7\MoonShineBlocks\Tests\TestCase;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;

final class BlockExportImportServiceTest extends TestCase
{
    private BlockExportImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BlockExportImportService();
    }

    // =========================================================================
    // export / import roundtrip
    // =========================================================================

    #[Test]
    public function roundtrip_export_and_import_restores_block(): void
    {
        $block = Block::create([
            'slug'           => 'my-block',
            'name'           => 'My Block',
            'is_active'      => true,
            'is_multiple'    => true,
            'is_api_enabled' => true,
            'sorting'        => 100,
            'fields'         => [['name' => 'Title', 'key' => 'title', 'type' => 'text', 'required' => false]],
        ]);

        $encoded = $this->service->export([$block->id]);

        // Delete the original to prove import re-creates it
        $block->forceDelete();
        $this->assertDatabaseEmpty('blocks');

        $result = $this->service->import($encoded);

        $this->assertSame([], $result['errors'], 'Import should not produce errors');
        $this->assertSame(1, $result['blocks']);

        $restored = Block::where('slug', 'my-block')->firstOrFail();
        $this->assertSame('My Block', $restored->name);
        $this->assertTrue($restored->is_active);
        $this->assertTrue($restored->is_multiple);
        $this->assertSame(100, $restored->sorting);
    }

    #[Test]
    public function import_preserves_falsy_boolean_and_zero_values(): void
    {
        // Regression test: is_active=false and sorting=0 must not be stripped by array_filter
        $block = Block::create([
            'slug'        => 'falsy-block',
            'name'        => 'Falsy Block',
            'is_active'   => false,
            'is_multiple' => false,
            'sorting'     => 0,
        ]);

        $encoded = $this->service->export([$block->id]);

        // Update block to different values, then re-import
        $block->update(['is_active' => true, 'sorting' => 999]);

        $result = $this->service->import($encoded);

        $this->assertSame([], $result['errors']);

        $restored = Block::where('slug', 'falsy-block')->firstOrFail();
        $this->assertFalse($restored->is_active, 'is_active=false must be restored, not lost to array_filter');
        $this->assertSame(0, $restored->sorting, 'sorting=0 must be restored, not lost to array_filter');
    }

    #[Test]
    public function import_updates_existing_block_by_slug(): void
    {
        $block = Block::create([
            'slug'    => 'update-me',
            'name'    => 'Original Name',
            'sorting' => 500,
        ]);

        $encoded = $this->service->export([$block->id]);

        // Change name before import
        $block->update(['name' => 'Changed Name']);

        $this->service->import($encoded);

        $updated = Block::where('slug', 'update-me')->firstOrFail();
        $this->assertSame('Original Name', $updated->name);
    }

    #[Test]
    public function roundtrip_with_groups_links_block_to_group(): void
    {
        $group = BlockGroup::create([
            'slug'      => 'my-group',
            'name'      => 'My Group',
            'is_active' => true,
            'sorting'   => 100,
        ]);

        $block = Block::create([
            'slug'           => 'grouped-block',
            'name'           => 'Grouped Block',
            'block_group_id' => $group->id,
        ]);

        $encoded = $this->service->export([$block->id], includeGroups: true);

        // Wipe and re-import
        $block->forceDelete();
        $group->forceDelete();

        $result = $this->service->import($encoded);

        $this->assertSame([], $result['errors']);
        $this->assertSame(1, $result['groups']);
        $this->assertSame(1, $result['blocks']);

        $restoredGroup = BlockGroup::where('slug', 'my-group')->firstOrFail();
        $restoredBlock = Block::where('slug', 'grouped-block')->firstOrFail();

        $this->assertSame($restoredGroup->id, $restoredBlock->block_group_id);
    }

    #[Test]
    public function import_with_multiple_blocks_counts_all(): void
    {
        $b1 = Block::create(['slug' => 'block-1', 'name' => 'Block 1']);
        $b2 = Block::create(['slug' => 'block-2', 'name' => 'Block 2']);
        $b3 = Block::create(['slug' => 'block-3', 'name' => 'Block 3']);

        $encoded = $this->service->export([$b1->id, $b2->id, $b3->id]);

        Block::whereIn('slug', ['block-1', 'block-2', 'block-3'])->forceDelete();

        $result = $this->service->import($encoded);

        $this->assertSame([], $result['errors']);
        $this->assertSame(3, $result['blocks']);
    }

    // =========================================================================
    // validate()
    // =========================================================================

    #[Test]
    public function validate_valid_encoded_returns_valid_true(): void
    {
        $block   = Block::create(['slug' => 'validate-me', 'name' => 'Validate Me']);
        $encoded = $this->service->export([$block->id]);

        $result = $this->service->validate($encoded);

        $this->assertTrue($result['valid']);
        $this->assertSame(0, $result['groups_count']);
        $this->assertSame(1, $result['blocks_count']);
        $this->assertSame([], $result['errors']);
    }

    #[Test]
    public function validate_invalid_base64_returns_errors(): void
    {
        $result = $this->service->validate('not!valid!base64!!!');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    #[Test]
    public function validate_valid_base64_but_not_gzip_returns_errors(): void
    {
        // base64 of random bytes that are not gzip-compressed
        $encoded = base64_encode('this-is-not-gzipped-data');

        $result = $this->service->validate($encoded);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    #[Test]
    public function validate_wrong_version_returns_errors(): void
    {
        $wrongVersionData = json_encode(['version' => 99, 'groups' => [], 'blocks' => []]);
        $encoded = base64_encode(gzcompress($wrongVersionData, 9));

        $result = $this->service->validate($encoded);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    #[Test]
    public function validate_with_groups_counts_groups(): void
    {
        $group = BlockGroup::create(['slug' => 'g1', 'name' => 'G1', 'is_active' => true, 'sorting' => 100]);
        $block = Block::create(['slug' => 'b1', 'name' => 'B1', 'block_group_id' => $group->id]);

        $encoded = $this->service->export([$block->id], includeGroups: true);
        $result  = $this->service->validate($encoded);

        $this->assertTrue($result['valid']);
        $this->assertSame(1, $result['groups_count']);
        $this->assertSame(1, $result['blocks_count']);
    }

    // =========================================================================
    // import() error handling
    // =========================================================================

    #[Test]
    public function import_invalid_base64_returns_errors(): void
    {
        $result = $this->service->import('not!valid!base64!!!');

        $this->assertSame(0, $result['blocks']);
        $this->assertNotEmpty($result['errors']);
    }

    #[Test]
    public function import_wrong_version_returns_version_error(): void
    {
        $wrongVersion = json_encode(['version' => 99, 'groups' => [], 'blocks' => []]);
        $encoded = base64_encode(gzcompress($wrongVersion, 9));

        $result = $this->service->import($encoded);

        $this->assertSame(0, $result['blocks']);
        $this->assertNotEmpty($result['errors']);
    }
}
