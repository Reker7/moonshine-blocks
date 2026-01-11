<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Providers;

use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\MenuManager\MenuManagerContract;
use Reker7\MoonShineBlocks\Pages\BlockCategory\FormBlockCategoryPage;
use Reker7\MoonShineBlocks\Pages\BlockCategory\IndexBlockCategoriesPage;
use Reker7\MoonShineBlocks\Pages\BlockItem\FormBlockItemPage;
use Reker7\MoonShineBlocks\Pages\BlockItem\IndexBlockItemPage;
use Reker7\MoonShineBlocks\Resources\Block\BlockResource;
use Reker7\MoonShineBlocks\Resources\Block\Pages\BlockFormPage;
use Reker7\MoonShineBlocks\Resources\Block\Pages\BlockIndexPage;
use Reker7\MoonShineBlocks\Resources\BlockGroup\BlockGroupResource;
use Reker7\MoonShineBlocks\Resources\BlockGroup\Pages\BlockGroupFormPage;
use Reker7\MoonShineBlocks\Resources\BlockGroup\Pages\BlockGroupIndexPage;
use Reker7\MoonShineBlocks\Resources\FieldPreset\FieldPresetResource;
use Reker7\MoonShineBlocks\Resources\FieldPreset\Pages\FieldPresetFormPage;
use Reker7\MoonShineBlocks\Resources\FieldPreset\Pages\FieldPresetIndexPage;
use Reker7\MoonShineBlocks\Pages\BlocksTreePage;

final class MoonShineBlocksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(CoreContract $core, MenuManagerContract $menu): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/moonshine-blocks.php');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'moonshine-blocks');

        $this->publishes([
            __DIR__ . '/../../config/moonshine-blocks.php' => config_path('moonshine-blocks.php'),
        ], 'moonshine-blocks-config');

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/moonshine-blocks.php',
            'moonshine-blocks'
        );

        $this->publishes([
            __DIR__ . '/../../lang' => $this->app->langPath('vendor/moonshine-blocks'),
        ], 'moonshine-blocks-lang');

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/moonshine-blocks'),
        ], 'moonshine-blocks-assets');

        $core
            ->resources([
                BlockResource::class,
                BlockGroupResource::class,
                FieldPresetResource::class,
            ]);

        $core
            ->pages([
                IndexBlockItemPage::class,
                IndexBlockCategoriesPage::class,
                FormBlockItemPage::class,
                FormBlockCategoryPage::class,
                BlockIndexPage::class,
                BlockFormPage::class,

                BlockGroupIndexPage::class,
                BlockGroupFormPage::class,

                FieldPresetIndexPage::class,
                FieldPresetFormPage::class,

                BlocksTreePage::class,
            ]);
    }
}
