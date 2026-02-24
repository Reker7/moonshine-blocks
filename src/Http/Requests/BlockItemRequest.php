<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Http\Requests;

use Reker7\MoonShineBlocksCore\Models\Block;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class BlockItemRequest extends FormRequest
{
    private ?Block $resolvedBlock = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $block = $this->resolveBlock();

        $itemId = (int) $this->route('item');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('block_items', 'slug')
                    ->where(fn($q) => $q->where('block_id', $block->id))
                    ->ignore($itemId),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'sorting' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'block_category_id' => [
                'nullable',
                'integer',
                Rule::exists('block_categories', 'id')->where('block_id', $block->id),
            ],

            'data' => ['sometimes', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Название',
            'slug' => 'Слаг',
            'status' => 'Статус',
            'sorting' => 'Сортировка',
            'category_id' => 'Категория',
            'data' => 'Контент',
        ];
    }

    protected function prepareForValidation(): void
    {
        [$blockId] = $this->resolveRouteIds();

        $slug = (string)$this->input('slug', '');
        $title = (string)$this->input('title', '');

        if ($slug === '' && $title !== '') {
            $slug = Str::slug($title);
        }

        $this->merge([
            'block_id' => $blockId,
            'slug' => $slug !== '' ? Str::slug($slug) : null,
            'sorting' => $this->filled('sorting') ? (int)$this->input('sorting') : null,
        ]);
    }

    private function resolveBlock(): Block
    {
        if ($this->resolvedBlock === null) {
            $blockParam = $this->route('block');

            $this->resolvedBlock = $blockParam instanceof Block
                ? $blockParam
                : Block::query()->where('slug', (string) $blockParam)->firstOrFail();
        }

        return $this->resolvedBlock;
    }

    /**
     * @return array{0:int,1:int|null}
     */
    private function resolveRouteIds(): array
    {
        $blockId = (int) $this->resolveBlock()->getKey();

        $item   = $this->route('item');
        $itemId = is_object($item) ? (int) $item->getKey() : ($item ? (int) $item : null);

        return [$blockId, $itemId];
    }
}
