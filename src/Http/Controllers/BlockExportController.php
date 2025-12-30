<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Http\Controllers;

use Illuminate\Http\Request;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use MoonShine\Support\Enums\ToastType;
use Reker7\MoonShineBlocks\Resources\Block\BlockResource;
use Reker7\MoonShineBlocks\Services\BlockExportImportService;

final class BlockExportController extends MoonShineController
{
    public function __construct(
        private readonly BlockExportImportService $service,
        private readonly BlockResource $blockResource,
    ) {}

    public function export(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $includeGroups = (bool) $request->input('include_groups', false);

        if (empty($ids)) {
            return JsonResponse::make()
                ->toast(__('moonshine-blocks::ui.export.select_blocks'), ToastType::ERROR);
        }

        try {
            $encoded = $this->service->export($ids, $includeGroups);

            return JsonResponse::make()
                ->fieldsValues(['.export_result' => $encoded])
                ->toast(__('moonshine-blocks::ui.export.success'), ToastType::SUCCESS);
        } catch (\Throwable $e) {
            return JsonResponse::make()
                ->toast(__('moonshine-blocks::ui.export.error') . ': ' . $e->getMessage(), ToastType::ERROR);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->input('data', '');

        if (empty($data)) {
            return JsonResponse::make()
                ->toast(__('moonshine-blocks::ui.import.enter_data'), ToastType::ERROR);
        }

        $result = $this->service->import($data);

        if (!empty($result['errors'])) {
            $errorMessages = implode('; ', $result['errors']);
            return JsonResponse::make()
                ->toast(
                    __('moonshine-blocks::ui.import.partial_success', [
                        'groups' => $result['groups'],
                        'blocks' => $result['blocks'],
                        'errors' => count($result['errors']),
                    ]) . ': ' . $errorMessages,
                    ToastType::WARNING,
                    duration: false
                );
        }

        return JsonResponse::make()
            ->toast(
                __('moonshine-blocks::ui.import.success', [
                    'groups' => $result['groups'],
                    'blocks' => $result['blocks'],
                ]),
                ToastType::SUCCESS
            )
            ->redirect($this->blockResource->getUrl());
    }
}
