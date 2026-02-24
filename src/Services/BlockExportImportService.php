<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Reker7\MoonShineBlocksCore\Models\Block;
use Reker7\MoonShineBlocksCore\Models\BlockGroup;

final class BlockExportImportService
{
    private const VERSION = 1;

    /**
     * Export blocks to encoded string
     *
     * @param  array<int>  $blockIds
     */
    public function export(array $blockIds, bool $includeGroups = false): string
    {
        $blocks = Block::query()
            ->whereIn('id', $blockIds)
            ->with($includeGroups ? ['blockGroup'] : [])
            ->get();

        $data = [
            'version' => self::VERSION,
            'include_groups' => $includeGroups,
            'groups' => [],
            'blocks' => [],
        ];

        if ($includeGroups) {
            $groups = $blocks
                ->pluck('blockGroup')
                ->filter()
                ->unique('id')
                ->values();

            $data['groups'] = $groups->map(fn(BlockGroup $group) => [
                'name' => $group->name,
                'slug' => $group->slug,
                'is_active' => $group->is_active,
                'sorting' => $group->sorting,
            ])->toArray();
        }

        $data['blocks'] = $blocks->map(fn(Block $block) => [
            'name' => $block->name,
            'slug' => $block->slug,
            'is_active' => $block->is_active,
            'is_multiple' => $block->is_multiple,
            'is_api_enabled' => $block->is_api_enabled,
            'sorting' => $block->sorting,
            'fields' => $block->fields,
            'group_slug' => $includeGroups ? $block->blockGroup?->slug : null,
        ])->toArray();

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        return base64_encode(gzcompress($json, 9));
    }

    /**
     * Import blocks from encoded string
     *
     * @return array{groups: int, blocks: int, errors: array<string>}
     */
    public function import(string $encoded): array
    {
        $result = [
            'groups' => 0,
            'blocks' => 0,
            'errors' => [],
        ];

        try {
            $data = $this->decodePayload($encoded);
        } catch (\RuntimeException $e) {
            $result['errors'][] = $e->getMessage();
            return $result;
        } catch (\Throwable $e) {
            Log::error('BlockExportImportService: import decoding exception', ['error' => $e->getMessage()]);
            $result['errors'][] = 'Ошибка декодирования: ' . $e->getMessage();
            return $result;
        }

        $groupSlugToId = [];

        // Import groups
        if (!empty($data['groups'])) {
            foreach ($data['groups'] as $groupData) {
                try {
                    $group = BlockGroup::query()
                        ->where('slug', $groupData['slug'])
                        ->first();

                    $group
                        ? $group->update(['name' => $groupData['name'], 'is_active' => $groupData['is_active'], 'sorting' => $groupData['sorting']])
                        : $group = BlockGroup::create($groupData);

                    $groupSlugToId[$groupData['slug']] = $group->id;
                    $result['groups']++;
                } catch (\Throwable $e) {
                    $result['errors'][] = "Ошибка импорта группы {$groupData['slug']}: " . $e->getMessage();
                }
            }
        }

        // Import blocks
        if (!empty($data['blocks'])) {
            foreach ($data['blocks'] as $blockData) {
                try {
                    $groupSlug = $blockData['group_slug'] ?? null;
                    unset($blockData['group_slug']);

                    $blockGroupId = ($groupSlug && isset($groupSlugToId[$groupSlug]))
                        ? $groupSlugToId[$groupSlug]
                        : null;

                    // Remove null values - let DB defaults handle them
                    $blockData = array_filter($blockData, fn($v) => $v !== null);

                    // block_group_id can be null (orphan block)
                    $blockData['block_group_id'] = $blockGroupId;

                    $block = Block::query()
                        ->where('slug', $blockData['slug'])
                        ->first();

                    $block ? $block->update($blockData) : Block::create($blockData);

                    $result['blocks']++;
                } catch (\Throwable $e) {
                    $result['errors'][] = "Ошибка импорта блока {$blockData['slug']}: " . $e->getMessage();
                }
            }
        }

        return $result;
    }

    /**
     * Validate encoded string without importing
     */
    public function validate(string $encoded): array
    {
        $result = [
            'valid' => false,
            'groups_count' => 0,
            'blocks_count' => 0,
            'errors' => [],
        ];

        try {
            $data = $this->decodePayload($encoded);

            $result['valid'] = true;
            $result['groups_count'] = count($data['groups'] ?? []);
            $result['blocks_count'] = count($data['blocks'] ?? []);
        } catch (\RuntimeException $e) {
            $result['errors'][] = $e->getMessage();
        } catch (\Throwable $e) {
            Log::error('BlockExportImportService: validate exception', ['error' => $e->getMessage()]);
            $result['errors'][] = 'Ошибка валидации: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Decode payload string to data array.
     *
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    private function decodePayload(string $encoded): array
    {
        $decoded = base64_decode($encoded, strict: true);
        if ($decoded === false) {
            Log::debug('BlockExportImportService: base64_decode failed', ['input_length' => strlen($encoded)]);
            throw new \RuntimeException('Не удалось декодировать данные');
        }

        $json = gzuncompress($decoded);
        if ($json === false) {
            Log::debug('BlockExportImportService: gzuncompress failed', ['decoded_length' => strlen($decoded)]);
            throw new \RuntimeException('Не удалось декодировать данные');
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Неверный формат JSON: ' . $e->getMessage(), previous: $e);
        }

        if (! is_array($data) || ! isset($data['version']) || $data['version'] !== self::VERSION) {
            throw new \RuntimeException('Неподдерживаемая версия формата');
        }

        return $data;
    }
}
