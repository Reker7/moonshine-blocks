<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Reker7\MoonShineBlocks\Tests\TestCase;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;

final class BlockModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // scopeEffectiveActive
    // -------------------------------------------------------------------------

    #[Test]
    public function scope_effective_active_includes_active_ungrouped_block(): void
    {
        Block::create(['slug' => 'active', 'name' => 'Active', 'is_active' => true]);
        Block::create(['slug' => 'inactive', 'name' => 'Inactive', 'is_active' => false]);

        $results = Block::query()->effectiveActive()->pluck('slug');

        $this->assertContains('active', $results->all());
        $this->assertNotContains('inactive', $results->all());
    }

    #[Test]
    public function scope_effective_active_excludes_block_in_inactive_group(): void
    {
        $inactiveGroup = BlockGroup::create([
            'slug'      => 'inactive-group',
            'name'      => 'Inactive Group',
            'is_active' => false,
        ]);

        $activeGroup = BlockGroup::create([
            'slug'      => 'active-group',
            'name'      => 'Active Group',
            'is_active' => true,
        ]);

        Block::create([
            'slug'           => 'block-in-inactive-group',
            'name'           => 'Block In Inactive Group',
            'is_active'      => true,
            'block_group_id' => $inactiveGroup->id,
        ]);

        Block::create([
            'slug'           => 'block-in-active-group',
            'name'           => 'Block In Active Group',
            'is_active'      => true,
            'block_group_id' => $activeGroup->id,
        ]);

        $results = Block::query()->effectiveActive()->pluck('slug');

        $this->assertNotContains('block-in-inactive-group', $results->all());
        $this->assertContains('block-in-active-group', $results->all());
    }

    // -------------------------------------------------------------------------
    // scopeInGroupSlug
    // -------------------------------------------------------------------------

    #[Test]
    public function scope_in_group_slug_filters_by_group_slug(): void
    {
        $group = BlockGroup::create(['slug' => 'my-group', 'name' => 'My Group', 'is_active' => true]);

        Block::create(['slug' => 'in-group', 'name' => 'In Group', 'block_group_id' => $group->id]);
        Block::create(['slug' => 'no-group', 'name' => 'No Group']);

        $results = Block::query()->inGroupSlug('my-group')->pluck('slug');

        $this->assertContains('in-group', $results->all());
        $this->assertNotContains('no-group', $results->all());
    }

    #[Test]
    public function scope_in_group_slug_returns_all_when_slug_is_null(): void
    {
        $group = BlockGroup::create(['slug' => 'some-group', 'name' => 'Some Group', 'is_active' => true]);

        Block::create(['slug' => 'in-group', 'name' => 'In Group', 'block_group_id' => $group->id]);
        Block::create(['slug' => 'no-group', 'name' => 'No Group']);

        $results = Block::query()->inGroupSlug(null)->pluck('slug');

        $this->assertContains('in-group', $results->all());
        $this->assertContains('no-group', $results->all());
    }

    // -------------------------------------------------------------------------
    // scopeWithoutGroup
    // -------------------------------------------------------------------------

    #[Test]
    public function scope_without_group_returns_only_ungrouped_blocks(): void
    {
        $group = BlockGroup::create(['slug' => 'some-group', 'name' => 'Some Group', 'is_active' => true]);

        Block::create(['slug' => 'in-group', 'name' => 'In Group', 'block_group_id' => $group->id]);
        Block::create(['slug' => 'no-group', 'name' => 'No Group']);

        $results = Block::query()->withoutGroup()->pluck('slug');

        $this->assertNotContains('in-group', $results->all());
        $this->assertContains('no-group', $results->all());
    }
}
