<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service;

use FriendsOfTYPO3\Kickstarter\Enums\ModelPropertyType;

final readonly class TcaToModelPropertyTypeResolver
{
    /**
     * Resolve the preferred property types for a given TCA column configuration.
     * The first element is the recommended default.
     *
     * @param array<string, mixed> $columnConfig The 'config' array of a TCA column
     * @return list<string> List of ModelPropertyType values
     */
    public function resolvePropertyTypes(array $columnConfig): array
    {
        $type = (string)($columnConfig['type'] ?? '');

        return match ($type) {
            'check' => [ModelPropertyType::BOOL->value, ModelPropertyType::INT->value],
            'number' => $this->resolveNumberTypes($columnConfig),
            'datetime' => [
                ModelPropertyType::DATE_TIME->value,
                ModelPropertyType::INT->value,
                ModelPropertyType::STRING->value,
            ],
            'input', 'text', 'email', 'password', 'color', 'link', 'slug', 'uuid', 'folder', 'imageManipulation' => [ModelPropertyType::STRING->value],
            'json' => [ModelPropertyType::ARRAY->value, ModelPropertyType::STRING->value],
            'file', 'category' => $this->resolveFileOrCategoryTypes($columnConfig),
            'inline' => $this->resolveInlineTypes($columnConfig),
            'select' => $this->resolveSelectTypes($columnConfig),
            'radio' => $this->resolveRadioTypes($columnConfig),
            'language' => [ModelPropertyType::INT->value, ModelPropertyType::STRING->value],
            'flex' => [ModelPropertyType::STRING->value, ModelPropertyType::ARRAY->value],
            'group' => $this->resolveGroupTypes($columnConfig),
            'passthrough', 'none' => [
                ModelPropertyType::STRING->value,
                ModelPropertyType::INT->value,
                ModelPropertyType::FLOAT->value,
                ModelPropertyType::BOOL->value,
            ],
            default => ModelPropertyType::values(),
        };
    }

    /**
     * Resolves the primary/default property type for a given TCA column configuration.
     *
     * @param array<string, mixed> $columnConfig
     */
    public function getDefaultPropertyType(array $columnConfig): string
    {
        return $this->resolvePropertyTypes($columnConfig)[0];
    }

    /**
     * Resolves and casts the suggested default value from TCA 'default',
     * or falls back to the type's built-in suggested default.
     *
     * @param array<string, mixed> $columnConfig
     */
    public function resolveDefaultValue(array $columnConfig, ModelPropertyType $type): mixed
    {
        if (!$type->supportsDefault()) {
            return null;
        }

        if (array_key_exists('default', $columnConfig)) {
            $tcaDefault = $columnConfig['default'];
            if ($type === ModelPropertyType::BOOL) {
                return filter_var($tcaDefault, FILTER_VALIDATE_BOOL);
            }
            return $type->coerceDefault($tcaDefault);
        }

        return $type->suggestedDefault();
    }

    /**
     * @param array<string, mixed> $columnConfig
     * @return list<string>
     */
    private function resolveNumberTypes(array $columnConfig): array
    {
        if (($columnConfig['format'] ?? '') === 'decimal') {
            return [ModelPropertyType::FLOAT->value, ModelPropertyType::STRING->value];
        }

        return [ModelPropertyType::INT->value, ModelPropertyType::STRING->value];
    }

    /**
     * @param array<string, mixed> $columnConfig
     * @return list<string>
     */
    private function resolveFileOrCategoryTypes(array $columnConfig): array
    {
        if ((int)($columnConfig['maxitems'] ?? 0) === 1) {
            return [ModelPropertyType::OBJECT->value, ModelPropertyType::STRING->value];
        }

        return [ModelPropertyType::OBJECT_STORAGE->value, ModelPropertyType::ARRAY->value];
    }

    /**
     * @param array<string, mixed> $columnConfig
     * @return list<string>
     */
    private function resolveInlineTypes(array $columnConfig): array
    {
        if ((int)($columnConfig['maxitems'] ?? 0) === 1) {
            return [ModelPropertyType::OBJECT->value];
        }

        return [ModelPropertyType::OBJECT_STORAGE->value, ModelPropertyType::ARRAY->value];
    }

    /**
     * @param array<string, mixed> $columnConfig
     * @return list<string>
     */
    private function resolveSelectTypes(array $columnConfig): array
    {
        $hasForeignTable = !empty($columnConfig['foreign_table']);
        $isMultiple = ($columnConfig['renderType'] ?? '') === 'selectMultipleSideBySide'
            || ($columnConfig['renderType'] ?? '') === 'selectCheckBox'
            || ((int)($columnConfig['maxitems'] ?? 1) > 1);

        if ($hasForeignTable) {
            if ($isMultiple) {
                return [ModelPropertyType::OBJECT_STORAGE->value, ModelPropertyType::ARRAY->value];
            }
            return [ModelPropertyType::OBJECT->value, ModelPropertyType::INT->value];
        }

        if ($isMultiple) {
            return [ModelPropertyType::ARRAY->value, ModelPropertyType::STRING->value];
        }

        if ($this->hasOnlyNumericItemValues($columnConfig)) {
            return [ModelPropertyType::INT->value, ModelPropertyType::STRING->value];
        }

        return [ModelPropertyType::STRING->value, ModelPropertyType::INT->value];
    }

    /**
     * @param array<string, mixed> $columnConfig
     * @return list<string>
     */
    private function resolveRadioTypes(array $columnConfig): array
    {
        if ($this->hasOnlyNumericItemValues($columnConfig)) {
            return [ModelPropertyType::INT->value, ModelPropertyType::STRING->value];
        }

        return [ModelPropertyType::STRING->value, ModelPropertyType::INT->value];
    }

    /**
     * @param array<string, mixed> $columnConfig
     */
    private function hasOnlyNumericItemValues(array $columnConfig): bool
    {
        $items = $columnConfig['items'] ?? [];
        if (!is_array($items) || $items === []) {
            return false;
        }

        foreach ($items as $item) {
            $value = $item['value'] ?? $item[1] ?? null;
            if ($value === null || !is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $columnConfig
     * @return list<string>
     */
    private function resolveGroupTypes(array $columnConfig): array
    {
        $maxItems = (int)($columnConfig['maxitems'] ?? 1);
        if ($maxItems <= 1) {
            return [
                ModelPropertyType::OBJECT->value,
                ModelPropertyType::INT->value,
                ModelPropertyType::STRING->value,
            ];
        }

        return [
            ModelPropertyType::OBJECT_STORAGE->value,
            ModelPropertyType::ARRAY->value,
            ModelPropertyType::STRING->value,
        ];
    }
}
