<?php

use Illuminate\Support\Facades\Route;
use Reker7\MoonShineBlocks\Http\Controllers\BlockCategoryController;
use Reker7\MoonShineBlocks\Http\Controllers\BlockExportController;
use Reker7\MoonShineBlocks\Http\Controllers\BlockItemController;
use Reker7\MoonShineBlocks\Http\Controllers\BlockRelationApiController;
use Reker7\MoonShineBlocks\Pages\BlocksTreePage;

// API routes for block relation field
Route::middleware(['moonshine'])
    ->prefix('admin/api/blocks')
    ->name('moonshine.api.blocks.')
    ->controller(BlockRelationApiController::class)
    ->group(function () {
        Route::get('multiple', 'blocks')->name('multiple');
        Route::get('groups', 'groups')->name('groups');
    });

Route::middleware(['moonshine'])
    ->prefix('admin/blocks')
    ->name('moonshine.blocks.')
    ->group(function () {
        Route::get('tree', BlocksTreePage::class)
            ->name('tree');
        Route::controller(BlockItemController::class)
            ->group(function () {
                Route::get('{block}/items', 'index')
                    ->name('index');

                Route::get('{block}/items/create', 'create')
                    ->name('create');
                Route::post('{block}/items', 'store')
                    ->name('store');

                Route::get('{block}/items/{item}', 'edit')
                    ->name('edit');
                Route::put('{block}/items/{item}', 'update')
                    ->name('update');

                Route::delete('{block}/items/{item}', 'destroy')
                    ->name('destroy');
            });

        Route::controller(BlockCategoryController::class)
            ->group(function () {
                Route::get('{block}/categories', 'index')
                    ->name('categories.index');

                Route::get('{block}/categories/create', 'create')
                    ->name('categories.create');
                Route::post('{block}/categories', 'store')
                    ->name('categories.store');

                Route::get('{block}/categories/{category}', 'edit')
                    ->name('categories.edit');
                Route::put('{block}/categories/{category}', 'update')
                    ->name('categories.update');

                Route::delete('{block}/categories/{category}', 'destroy')
                    ->name('categories.destroy');
            });

        Route::controller(BlockExportController::class)
            ->group(function () {
                Route::post('export', 'export')
                    ->name('export');
                Route::post('import', 'import')
                    ->name('import');
            });
    });
