<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Reker7\MoonShineBlocks\Tests\TestCase;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockCategory;
use Reker7\MoonShineBlocksCore\Models\BlockItem;

final class BlockItemControllerTest extends TestCase
{
    private Block $block;

    protected function setUp(): void
    {
        parent::setUp();

        $this->block = Block::create([
            'slug'        => 'test-block',
            'name'        => 'Test Block',
            'is_multiple' => true,
            'is_active'   => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    #[Test]
    public function destroy_deletes_item(): void
    {
        $item = BlockItem::create([
            'block_id'  => $this->block->id,
            'title'     => 'Item to delete',
            'slug'      => 'item-to-delete',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->deleteJson(
                route('moonshine.blocks.destroy', [
                    'block' => $this->block->slug,
                    'item'  => $item->id,
                ])
            )
            ->assertOk();

        $this->assertSoftDeleted('block_items', ['id' => $item->id]);
    }

    #[Test]
    public function destroy_returns_404_for_item_from_different_block(): void
    {
        $otherBlock = Block::create([
            'slug' => 'other-block',
            'name' => 'Other Block',
        ]);

        $item = BlockItem::create([
            'block_id'  => $otherBlock->id,
            'title'     => 'Item',
            'slug'      => 'item',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->deleteJson(
                route('moonshine.blocks.destroy', [
                    'block' => $this->block->slug,
                    'item'  => $item->id,
                ])
            )
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // update validation (BlockItemRequest)
    // -------------------------------------------------------------------------

    #[Test]
    public function update_rejects_duplicate_slug_within_block(): void
    {
        BlockItem::create([
            'block_id'  => $this->block->id,
            'title'     => 'First',
            'slug'      => 'first-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $second = BlockItem::create([
            'block_id'  => $this->block->id,
            'title'     => 'Second',
            'slug'      => 'second-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->putJson(
                route('moonshine.blocks.update', [
                    'block' => $this->block->slug,
                    'item'  => $second->id,
                ]),
                ['title' => 'Second', 'slug' => 'first-slug']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');
    }

    #[Test]
    public function update_rejects_invalid_category_from_another_block(): void
    {
        $otherBlock = Block::create([
            'slug' => 'other-block',
            'name' => 'Other Block',
        ]);

        $foreignCategory = BlockCategory::create([
            'block_id'  => $otherBlock->id,
            'name'      => 'Foreign',
            'slug'      => 'foreign',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $item = BlockItem::create([
            'block_id'  => $this->block->id,
            'title'     => 'Item',
            'slug'      => 'item-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->putJson(
                route('moonshine.blocks.update', [
                    'block' => $this->block->slug,
                    'item'  => $item->id,
                ]),
                [
                    'title'             => 'Item',
                    'slug'              => 'item-slug',
                    'block_category_id' => $foreignCategory->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('block_category_id');
    }

    #[Test]
    public function update_accepts_valid_category_from_same_block(): void
    {
        $category = BlockCategory::create([
            'block_id'  => $this->block->id,
            'name'      => 'My Category',
            'slug'      => 'my-category',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $item = BlockItem::create([
            'block_id'  => $this->block->id,
            'title'     => 'Item',
            'slug'      => 'item-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        // update with a valid category — validation should pass (not 422)
        $this->actingAsAdmin()
            ->putJson(
                route('moonshine.blocks.update', [
                    'block' => $this->block->slug,
                    'item'  => $item->id,
                ]),
                [
                    'title'             => 'Item',
                    'slug'              => 'item-slug',
                    'block_category_id' => $category->id,
                ]
            )
            ->assertSuccessful();
    }
}
