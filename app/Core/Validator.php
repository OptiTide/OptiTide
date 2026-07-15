<?php

namespace App\Core;

use App\Core\Exceptions\ValidationException;

final class Validator
{
    protected array $errors = [];
    protected array $validated = [];

    /** Whether the field currently being checked is numeric-typed (has a numeric|integer rule). */
    protected bool $numericField = false;

    public function __construct(
        protected array $data,
        protected array $rules,
        protected array $labels = []
    ) {
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    /** @return array validated subset; throws on failure */
    public function validate(): array
    {
        $this->run();

        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }

        return $this->validated;
    }

    public function fails(): bool
    {
        $this->run();

        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validatedData(): array
    {
        return $this->validated;
    }

    protected function run(): void
    {
        $this->errors = [];
        $this->validated = [];

        foreach ($this->rules as $field => $ruleset) {
            $rules = is_array($ruleset) ? $ruleset : explode('|', $ruleset);
            $value = $this->data[$field] ?? null;

            $nullable = in_array('nullable', $rules, true);
            if ($nullable && ($value === null || $value === '')) {
                $this->validated[$field] = $value;
                continue;
            }

            // min/max mean "length" for strings and "value" only for numeric
            // fields (those carrying a numeric|integer rule). Without this a
            // numeric-looking string like a postcode "2481" would be compared
            // as a value (2481 > max:12 → wrongly rejected), and password
            // min:8 would check value ≥ 8 instead of length ≥ 8.
            $this->numericField = in_array('numeric', $rules, true) || in_array('integer', $rules, true);

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                if (! $this->applyRule($name, $field, $value, $param)) {
                    break; // one message per field
                }
            }

            if (! isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    protected function applyRule(string $rule, string $field, mixed $value, ?string $param): bool
    {
        $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                    return $this->fail($field, "$label is required.");
                }
                break;

            case 'email':
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $this->fail($field, "$label must be a valid email address.");
                }
                break;

            case 'url':
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    return $this->fail($field, "$label must be a valid URL.");
                }
                break;

            case 'numeric':
                if (! is_numeric($value)) {
                    return $this->fail($field, "$label must be a number.");
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return $this->fail($field, "$label must be an integer.");
                }
                break;

            case 'boolean':
                if (! in_array($value, [true, false, 0, 1, '0', '1', 'on', 'true', 'false'], true)) {
                    return $this->fail($field, "$label must be true or false.");
                }
                break;

            case 'min':
                if ($this->numericField
                    ? (float) $value < (float) $param
                    : mb_strlen((string) $value) < (int) $param) {
                    return $this->fail($field, $this->numericField
                        ? "$label must be at least $param."
                        : "$label must be at least $param characters.");
                }
                break;

            case 'max':
                if ($this->numericField
                    ? (float) $value > (float) $param
                    : mb_strlen((string) $value) > (int) $param) {
                    return $this->fail($field, $this->numericField
                        ? "$label may not be greater than $param."
                        : "$label may not be longer than $param characters.");
                }
                break;

            case 'in':
                if (! in_array((string) $value, explode(',', (string) $param), true)) {
                    return $this->fail($field, "$label is invalid.");
                }
                break;

            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    return $this->fail($field, "$label confirmation does not match.");
                }
                break;

            case 'same':
                if (($this->data[$param] ?? null) !== $value) {
                    return $this->fail($field, "$label must match $param.");
                }
                break;

            case 'date':
                if (strtotime((string) $value) === false) {
                    return $this->fail($field, "$label must be a valid date.");
                }
                break;

            case 'unique':
                if ($this->existsInTable($param, $field, $value)) {
                    return $this->fail($field, "$label is already taken.");
                }
                break;

            case 'exists':
                if (! $this->existsInTable($param, $field, $value, false)) {
                    return $this->fail($field, "$label does not exist.");
                }
                break;
        }

        return true;
    }

    /**
     * unique:table[,column[,ignoreId[,idColumn]]]
     * exists:table[,column]
     */
    protected function existsInTable(?string $param, string $field, mixed $value, bool $isUnique = true): bool
    {
        $parts = explode(',', (string) $param);
        $table = $parts[0];
        $column = $parts[1] ?? $field;

        $query = Database::instance()->table($table)->where($column, $value);

        if ($isUnique && isset($parts[2]) && $parts[2] !== '' && $parts[2] !== 'NULL') {
            $idColumn = $parts[3] ?? 'id';
            $query->where($idColumn, '!=', $parts[2]);
        }

        return $query->exists();
    }

    protected function fail(string $field, string $message): bool
    {
        $this->errors[$field] ??= $message;

        return false;
    }
}
