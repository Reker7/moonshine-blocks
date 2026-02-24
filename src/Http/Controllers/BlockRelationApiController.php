<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;

/**
 * API controller for block relation field options.
 */
final class BlockRelationApiController extends Controller
{
    /**
     * Get multiple blocks for selection.
     */
    public function blocks(): JsonResponse
    {
        $blocks = Block::query()
            ->where('is_active', true)
            ->where('is_multiple', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Block $block) => [
                'value' => $block->slug,
                'label' => $block->name,
            ])
            ->values();

        return response()->json($blocks);
    }

    /**
     * Get block groups for selection.
     */
    public function groups(): JsonResponse
    {
        $groups = BlockGroup::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (BlockGroup $group) => [
                'value' => $group->slug,
                'label' => $group->name,
            ])
            ->values();

        return response()->json($groups);
    }
}
