<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Http\Controllers;


use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Support\Enums\ToastType;
use Reker7\MoonShineBlocks\Http\Requests\BlockItemRequest;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockItem;
use Reker7\MoonShineBlocks\Pages\BlockItem\FormBlockItemPage;
use Reker7\MoonShineBlocks\Pages\BlockItem\IndexBlockItemPage;

final class BlockItemController extends MoonShineController
{
    public function index(MoonShineRequest $request, IndexBlockItemPage $page): PageContract
    {
        return $page->loaded();
    }

    public function create(MoonShineRequest $request, FormBlockItemPage $page): PageContract
    {
        return $page->loaded();
    }

    public function store(BlockItemRequest $request, FormBlockItemPage $page): MoonShineJsonResponse
    {
        $block = $this->resolveBlock($request);

        $form = $page->getForm();

        $ok = $form->apply(
            apply: static fn(Model $item): bool => $item->save(),
            throw: true
        );

        $itemId = BlockItem::query()
            ->where('block_id', $block->id)
            ->when(
                $request->filled('slug'),
                fn(Builder $q) => $q->where('slug', (string)$request->string('slug'))
            )
            ->latest('id')
            ->value('id');

        return MoonShineJsonResponse::make()
            ->toast(
                $ok ? __('moonshine-blocks::ui.item_created') : __('moonshine-blocks::ui.save_error'),
                $ok ? ToastType::SUCCESS : ToastType::ERROR,
                duration: config('moonshine-blocks.ui.toast_duration', 2500)
            )
            ->redirect(
                route('moonshine.blocks.edit', [
                    'block' => $block->slug,
                    'item' => $itemId,
                ])
            );
    }

    public function edit(MoonShineRequest $request, FormBlockItemPage $page): PageContract
    {
        return $page->loaded();
    }

    public function update(BlockItemRequest $request, FormBlockItemPage $page): MoonShineJsonResponse
    {
        $block = $this->resolveBlock($request);
        $item = $this->resolveItem($request, $block);

        $form = $page->getForm();

        $ok = $form->apply(
            apply: static fn(Model $item): bool => $item->save(),
            throw: true
        );

        return MoonShineJsonResponse::make()
            ->toast(
                $ok ? __('moonshine-blocks::ui.item_saved') : __('moonshine-blocks::ui.save_error'),
                $ok ? ToastType::SUCCESS : ToastType::ERROR,
                duration: config('moonshine-blocks.ui.toast_duration', 2500)
            );
    }

    public function destroy(MoonShineRequest $request): MoonShineJsonResponse
    {
        $block = $this->resolveBlock($request);
        $item = $this->resolveItem($request, $block);

        $item->delete();

        return MoonShineJsonResponse::make()
            ->toast(__('moonshine-blocks::ui.item_deleted'), ToastType::SUCCESS, duration: config('moonshine-blocks.ui.toast_duration', 2500))
            ->redirect(route('moonshine.blocks.index', ['block' => $block->slug]));
    }

    private function resolveBlock(Request $request): Block
    {
        $param = $request->route('block');
        if ($param instanceof Block) {
            return $param;
        }

        return Block::query()
            ->where('slug', (string)$param)
            ->firstOrFail();
    }

    private function resolveItem(Request $request, Block $block): BlockItem
    {
        $param = $request->route('item');
        if ($param instanceof BlockItem) {
            abort_if($param->block_id !== $block->id, 404);
            return $param;
        }

        return $block->items()
            ->whereKey((int)$param)
            ->firstOrFail();
    }
}
