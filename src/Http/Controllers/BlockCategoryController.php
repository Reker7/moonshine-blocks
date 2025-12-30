<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Http\Controllers;


use Illuminate\Validation\Rule;
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
            'sorting' => (int)($data['sorting'] ?? 500),
        ]);

        return MoonShineJsonResponse::make()->redirect(
            route('moonshine.blocks.categories.index', ['block' => $block->slug])
        );
    }

    public function update(BlockCategoryRequest $request, Block $block, BlockCategory $category): MoonShineJsonResponse
    {
        $data = $request->validated();

        $category->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => (bool)($data['is_active'] ?? true),
            'sorting' => (int)($data['sorting'] ?? 500),
        ]);

        return MoonShineJsonResponse::make()->redirect(
            route('moonshine.blocks.categories.index', ['block' => $block->slug])
        );
    }

    public function destroy(Block $block, BlockCategory $category): MoonShineJsonResponse
    {
        $category->delete();

        return MoonShineJsonResponse::make()->toast('Элемент удален');
    }

    /**
     * @return array{name:string,slug:string,is_active?:mixed,sorting?:mixed}
     */
    private function validated(Request $request, int $blockId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('block_categories', 'slug')
                    ->where('block_id', $blockId)
                    ->ignore($ignoreId),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sorting' => ['nullable', 'integer'],
        ]);
    }
}
