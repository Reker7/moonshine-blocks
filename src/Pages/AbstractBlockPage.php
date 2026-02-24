<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;
use Reker7\MoonShineBlocksCore\Models\Block;

abstract class AbstractBlockPage extends Page
{
    protected ?Block $block = null;

    protected function getBlock(): ?Block
    {
        if ($this->block !== null) {
            return $this->block;
        }

        $param = request()->route('block') ?? request('block');

        if ($param instanceof Block) {
            return $this->block = $param;
        }

        $slug = (string) ($param ?? '');

        return $this->block = $slug !== ''
            ? Block::query()->where('slug', $slug)->first()
            : null;
    }

    protected function ensureBlockExists(): void
    {
        if (! $this->getBlock()) {
            throw new ModelNotFoundException('Block not found');
        }
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        if (! $this->getBlock()) {
            return [
                FlexibleRender::make(__('moonshine-blocks::ui.block_not_found')),
            ];
        }

        return $this->getLayers();
    }
}
