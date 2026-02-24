<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Pages;

use MoonShine\UI\Fields\Hidden;

abstract class AbstractBlockFormPage extends AbstractBlockPage
{
    protected function isReactiveRequest(): bool
    {
        return request()->boolean('_async')
            || request()->has('_component_name')
            || request()->has('_fragment');
    }

    /**
     * @return list<\MoonShine\Contracts\UI\FieldContract>
     */
    protected function systemFields(string $method): array
    {
        if (strtoupper($method) !== 'PUT') {
            return [];
        }

        return [
            Hidden::make('_method')->setValue('PUT'),
        ];
    }
}
