<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Reker7\MoonShineBlocks\Tests\TestCase;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockCategory;

final class BlockCategoryControllerTest extends TestCase
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
    // store
    // -------------------------------------------------------------------------

    #[Test]
    public function store_creates_category_and_redirects(): void
    {
        $this->actingAsAdmin()
            ->postJson(
                route('moonshine.blocks.categories.store', ['block' => $this->block->slug]),
                [
                    'name'      => 'New Category',
                    'slug'      => 'new-category',
                    'is_active' => true,
                    'sorting'   => 100,
                ]
            )
            ->assertOk();

        $this->assertDatabaseHas('block_categories', [
            'block_id'  => $this->block->id,
            'name'      => 'New Category',
            'slug'      => 'new-category',
            'is_active' => 1,
            'sorting'   => 100,
        ]);
    }

    #[Test]
    public function store_uses_default_sorting_when_not_provided(): void
    {
        $this->actingAsAdmin()
            ->postJson(
                route('moonshine.blocks.categories.store', ['block' => $this->block->slug]),
                ['name' => 'Category', 'slug' => 'category']
            )
            ->assertOk();

        $category = BlockCategory::first();
        $this->assertSame(
            config('moonshine-blocks.ui.sorting_default', 500),
            $category->sorting
        );
    }

    #[Test]
    public function store_requires_name(): void
    {
        $this->actingAsAdmin()
            ->postJson(
                route('moonshine.blocks.categories.store', ['block' => $this->block->slug]),
                ['slug' => 'no-name']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function store_requires_unique_slug_within_block(): void
    {
        BlockCategory::create([
            'block_id'  => $this->block->id,
            'name'      => 'Existing',
            'slug'      => 'existing-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->postJson(
                route('moonshine.blocks.categories.store', ['block' => $this->block->slug]),
                ['name' => 'Duplicate', 'slug' => 'existing-slug']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    #[Test]
    public function update_modifies_existing_category(): void
    {
        $category = BlockCategory::create([
            'block_id'  => $this->block->id,
            'name'      => 'Old Name',
            'slug'      => 'old-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->putJson(
                route('moonshine.blocks.categories.update', [
                    'block'    => $this->block->slug,
                    'category' => $category->id,
                ]),
                [
                    'name'      => 'New Name',
                    'slug'      => 'new-slug',
                    'is_active' => false,
                    'sorting'   => 200,
                ]
            )
            ->assertOk();

        $this->assertDatabaseHas('block_categories', [
            'id'        => $category->id,
            'name'      => 'New Name',
            'slug'      => 'new-slug',
            'is_active' => 0,
            'sorting'   => 200,
        ]);
    }

    #[Test]
    public function update_allows_keeping_same_slug(): void
    {
        $category = BlockCategory::create([
            'block_id'  => $this->block->id,
            'name'      => 'Category',
            'slug'      => 'my-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->putJson(
                route('moonshine.blocks.categories.update', [
                    'block'    => $this->block->slug,
                    'category' => $category->id,
                ]),
                ['name' => 'Updated Name', 'slug' => 'my-slug']
            )
            ->assertOk();

        $this->assertDatabaseHas('block_categories', [
            'id'   => $category->id,
            'name' => 'Updated Name',
            'slug' => 'my-slug',
        ]);
    }

    #[Test]
    public function update_rejects_slug_already_used_by_another_category(): void
    {
        BlockCategory::create([
            'block_id'  => $this->block->id,
            'name'      => 'Other',
            'slug'      => 'taken-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $category = BlockCategory::create([
            'block_id'  => $this->block->id,
            'name'      => 'Mine',
            'slug'      => 'my-slug',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->putJson(
                route('moonshine.blocks.categories.update', [
                    'block'    => $this->block->slug,
                    'category' => $category->id,
                ]),
                ['name' => 'Mine', 'slug' => 'taken-slug']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    #[Test]
    public function destroy_deletes_category(): void
    {
        $category = BlockCategory::create([
            'block_id'  => $this->block->id,
            'name'      => 'To Delete',
            'slug'      => 'to-delete',
            'is_active' => true,
            'sorting'   => 500,
        ]);

        $this->actingAsAdmin()
            ->deleteJson(
                route('moonshine.blocks.categories.destroy', [
                    'block'    => $this->block->slug,
                    'category' => $category->id,
                ])
            )
            ->assertOk();

        $this->assertSoftDeleted('block_categories', ['id' => $category->id]);
    }

}
