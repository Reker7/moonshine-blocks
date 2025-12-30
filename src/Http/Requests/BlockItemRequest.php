<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Http\Requests;

use Reker7\MoonShineBlocksCore\Models\Block;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlockItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $block = Block::query()
            ->where('slug', (string) $this->route('block'))
            ->firstOrFail();

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
            'uuid' => ['prohibited'],
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

    /**
     * @return array{0:int,1:int|null}
     */
    private function resolveRouteIds(): array
    {
        $blockParam = $this->route('block');
        $blockId = $blockParam instanceof Block
            ? (int)$blockParam->getKey()
            : (int)Block::query()->where('slug', (string)$blockParam)->value('id');

        $item = $this->route('item');
        $itemId = is_object($item) ? (int)$item->getKey() : ($item ? (int)$item : null);

        return [$blockId, $itemId];
    }
}
