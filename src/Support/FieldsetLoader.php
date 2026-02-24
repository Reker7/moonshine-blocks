<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Support;

use Reker7\MoonShineFieldsBuilder\Fields\FieldsBuilder\FieldsCollection;

/**
 * Loads fieldset JSON files from a configurable directory.
 *
 * Fieldset file format (resources/blocks/fieldsets/seo.json):
 * {
 *   "title": "SEO Fields",
 *   "fields": [
 *     {"name": "Meta Title", "key": "meta_title", "type": "text"},
 *     ...
 *   ]
 * }
 */
final class FieldsetLoader
{
    public function __construct(private readonly string $path) {}

    /**
     * Load a fieldset by key (= filename without extension).
     * Returns null when file not found or invalid.
     *
     * @return array{title: string, fields: FieldsCollection}|null
     */
    public function load(string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        $file = $this->path . '/' . $key . '.json';

        if (! is_readable($file)) {
            return null;
        }

        $contents = file_get_contents($file);
        $data     = json_decode($contents ?: '', true);

        if (! is_array($data)) {
            return null;
        }

        return [
            'title'  => (string) ($data['title'] ?? $key),
            'fields' => FieldsCollection::fromMixed($data['fields'] ?? []),
        ];
    }

    /**
     * List all available fieldset keys (filenames without .json extension).
     *
     * @return array<string>
     */
    public function available(): array
    {
        if (! is_dir($this->path)) {
            return [];
        }

        return array_map(
            fn (string $file): string => pathinfo($file, PATHINFO_FILENAME),
            glob($this->path . '/*.json') ?: []
        );
    }
}
