<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Resources\Block\Pages;

use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Modal;
use MoonShine\UI\Fields\HiddenIds;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends IndexPage<BlockIndexPage>
 */
class BlockIndexPage extends IndexPage
{
    protected bool $withPrintButton = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Text::make(__('moonshine-blocks::ui.fields.name'), 'name')->sortable(),
            Text::make(__('moonshine-blocks::ui.fields.slug'), 'slug')->sortable(),
            Switcher::make(__('moonshine-blocks::ui.fields.is_active'), 'is_active'),
            Number::make(__('moonshine-blocks::ui.fields.sorting'), 'sorting')->default(500),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
            $this->getImportModal(),
        ];
    }

    protected function buttons(): \MoonShine\Support\ListOf
    {
        return parent::buttons()
            ->prepend($this->getExportButton());
    }


    protected function topLeftButtons(): \MoonShine\Support\ListOf
    {
        return parent::topLeftButtons()
            ->add(
                $this->getImportButton(),
            );
    }


    private function getExportButton(): ActionButton
    {
        return ActionButton::make(__('moonshine-blocks::ui.export.button'))
            ->bulk()
            ->icon('arrow-up-tray')
            ->primary()
            ->inModal(
                title: __('moonshine-blocks::ui.export.title'),
                content: static fn(mixed $item, ActionButton $ctx): string => (string)FormBuilder::make(
                    route('moonshine.blocks.export')
                )
                    ->async()
                    ->fields([
                        HiddenIds::make($ctx->getBulkForComponent()),
                        Switcher::make(__('moonshine-blocks::ui.export.include_groups'), 'include_groups')
                            ->hint(__('moonshine-blocks::ui.export.include_groups_hint')),
                        Heading::make(__('moonshine-blocks::ui.export.hint')),
                        Text::make(__('moonshine-blocks::ui.export.result'), 'export_result')
                            ->copy()
                            ->readonly()
                            ->class('export_result')
                        ,
                    ])
                    ->submit(
                        label: __('moonshine-blocks::ui.export.generate'),
                        attributes: ['class' => 'btn-primary']
                    ),
                name: 'export-blocks-modal',
                builder: fn(Modal $modal) => $modal->wide()->autoClose(false)
            );
    }

    private function getImportButton(): ActionButton
    {
        return ActionButton::make(__('moonshine-blocks::ui.import.button'))
            ->icon('arrow-down-tray')
            ->secondary()
            ->toggleModal('import-blocks-modal');
    }

    private function getImportModal(): Modal
    {
        return Modal::make(
            title: __('moonshine-blocks::ui.import.title'),
            components: [
                FormBuilder::make(route('moonshine.blocks.import'))
                    ->async()
                    ->fields([
                        Textarea::make(__('moonshine-blocks::ui.import.data_label'), 'data')
                            ->hint(__('moonshine-blocks::ui.import.placeholder'))
                            ->customAttributes(['rows' => 6, 'class' => 'font-mono text-xs']),
                        Heading::make(__('moonshine-blocks::ui.import.hint')),
                    ])
                    ->submit(
                        label: __('moonshine-blocks::ui.import.button'),
                        attributes: ['class' => 'btn-primary']
                    ),
            ]
        )->name('import-blocks-modal')
            ->wide();
    }
}
