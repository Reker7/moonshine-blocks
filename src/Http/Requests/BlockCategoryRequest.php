<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Http\Requests;

use Reker7\MoonShineBlocksCore\Models\Block;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class BlockCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        [$blockId, $categoryId] = $this->resolveRouteIds();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('block_categories', 'slug')
                    ->where(fn($q) => $q->where('block_id', $blockId))
                    ->ignore($categoryId),
            ],
            'sorting' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Название',
            'slug' => 'Слаг',
            'sorting' => 'Сортировка',
            'is_active' => 'Активно',
        ];
    }

    protected function prepareForValidation(): void
    {
        [$blockId] = $this->resolveRouteIds();

        $slug = (string)$this->input('slug', '');
        $name = (string)$this->input('name', '');

        if ($slug === '' && $name !== '') {
            $slug = Str::slug($name);
        }

        $this->merge([
            'block_id' => $blockId,
            'slug' => $slug !== '' ? Str::slug($slug) : null,
            'sorting' => $this->filled('sorting') ? (int)$this->input('sorting') : null,
            'is_active' => $this->boolean('is_active'),
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

        $category = $this->route('category');
        $categoryId = is_object($category) ? (int)$category->getKey() : ($category ? (int)$category : null);

        return [$blockId, $categoryId];
    }
}
