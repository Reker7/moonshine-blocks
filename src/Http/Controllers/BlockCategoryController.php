<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Http\Controllers;


use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use Reker7\MoonShineBlocks\Http\Requests\BlockCategoryRequest;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockCategory;
use Reker7\MoonShineBlocks\Pages\BlockCategory\FormBlockCategoryPage;
use Reker7\MoonShineBlocks\Pages\BlockCategory\IndexBlockCategoriesPage;

final class BlockCategoryController extends MoonShineController
{
    public function index(IndexBlockCategoriesPage $page): PageContract
    {
        return $page;
    }

    public function create(FormBlockCategoryPage $page): PageContract
    {
        return $page;
    }

    public function edit(FormBlockCategoryPage $page): PageContract
    {
        return $page;
    }

    public function store(BlockCategoryRequest $request, Block $block): MoonShineJsonResponse
    {
        $data = $request->validated();

        BlockCategory::create([
            'block_id' => $block->id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => (bool)($data['is_active'] ?? true),
            'sorting' => (int)($data['sorting'] ?? config('moonshine-blocks.ui.sorting_default', 500)),
        ]);

        return MoonShineJsonResponse::make()->redirect(
            route('moonshine.blocks.categories.index', ['block' => $block->slug])
        );
    }

    public function update(BlockCategoryRequest $request, Block $block, BlockCategory $category): MoonShineJsonResponse
    {
        abort_if($category->block_id !== $block->id, 404);

        $data = $request->validated();

        $category->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => (bool)($data['is_active'] ?? true),
            'sorting' => (int)($data['sorting'] ?? config('moonshine-blocks.ui.sorting_default', 500)),
        ]);

        return MoonShineJsonResponse::make()->redirect(
            route('moonshine.blocks.categories.index', ['block' => $block->slug])
        );
    }

    public function destroy(Block $block, BlockCategory $category): MoonShineJsonResponse
    {
        abort_if($category->block_id !== $block->id, 404);

        $category->delete();

        return MoonShineJsonResponse::make()->toast(__('moonshine-blocks::ui.item_deleted'));
    }

}
