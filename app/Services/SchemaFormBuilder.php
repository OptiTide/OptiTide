<?php

namespace App\Services;

use App\Models\FormSchema;
use Illuminate\Validation\Rule;

/**
 * Turns a FormSchema's `fields` JSON into Laravel validation rules and knows
 * which fields are brand assets (files + colors) versus plain answers.
 */
class SchemaFormBuilder
{
    /** @return array<int, array<string, mixed>> */
    public function fields(FormSchema $schema): array
    {
        return $schema->schema['fields'] ?? [];
    }

    /** @return array<string, mixed> validation rules keyed by field name */
    public function rules(FormSchema $schema): array
    {
        $rules = [];

        foreach ($this->fields($schema) as $field) {
            $name = $field['name'];
            $required = (bool) ($field['required'] ?? false);
            $multiple = (bool) ($field['multiple'] ?? false);
            $base = [$required ? 'required' : 'nullable'];

            $key = $name;

            $rules[$key] = match ($field['type']) {
                'textarea', 'text' => [...$base, 'string', 'max:5000'],
                'url' => [...$base, 'url', 'max:2000'],
                'date' => [...$base, 'date'],
                'color' => [...$base, 'regex:/^#[0-9A-Fa-f]{6}$/'],
                // Rule::in quotes each value, so options containing commas
                // (e.g. "Yes, all ready") match literally — the string form
                // would split them on every comma.
                'select' => [...$base, 'string', Rule::in($field['options'] ?? [])],
                'file' => $multiple ? [$required ? 'required' : 'nullable', 'array'] : [...$base, 'file', 'max:10240'],
                default => [...$base, 'string', 'max:5000'],
            };

            // Per-file rules for multi-file uploads.
            if ($field['type'] === 'file' && $multiple) {
                $rules["{$name}.*"] = ['file', 'max:10240'];
            }
        }

        return $rules;
    }

    public function isAssetField(array $field): bool
    {
        return in_array($field['type'], ['file', 'color'], true);
    }
}
