<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Enums;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

enum ModelPropertyType: string
{
    case ARRAY = 'array';
    case BOOL = 'bool';
    case FLOAT = 'float';
    case INT = 'int';
    case STRING = 'string';
    case OBJECT = 'object';
    case DATE_TIME = \DateTime::class;
    case OBJECT_STORAGE = ObjectStorage::class;

    /**
     * Return all model property type values as a flat list of strings
     * (useful for CLI choice lists etc).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn(self $c) => $c->value, self::cases());
    }

    /**
     * Returns true if this property type requires object initialization in Extbase.
     */
    public function needsInitialization(): bool
    {
        return match ($this) {
            self::ARRAY,
            self::BOOL,
            self::FLOAT,
            self::INT,
            self::STRING => false,

            // everything else (objects, storages, DateTime, …) needs initialization
            default => true,
        };
    }

    /**
     * Types we allow to have a default (others return null).
     */
    public function supportsDefault(): bool
    {
        return match ($this) {
            self::INT, self::FLOAT, self::BOOL, self::STRING, self::ARRAY => true,
            default => false,
        };
    }

    /**
     * Built-in suggestion when caller does not supply one.
     */
    public function suggestedDefault(): mixed
    {
        return match ($this) {
            self::INT   => 0,
            self::FLOAT => 0.0,
            self::BOOL  => false,
            self::STRING => '',
            self::ARRAY => [],
            default     => null,
        };
    }

    /**
     * Coerce an arbitrary value (from CLI/input/config) into the proper default.
     * Never does any I/O or framework calls.
     */
    public function coerceDefault(mixed $value): mixed
    {
        return match ($this) {
            self::INT   => (int)$value,
            self::FLOAT => (float)$value,
            self::BOOL  => (bool)$value,
            self::STRING => (string)$value,
            self::ARRAY => is_array($value) ? $value : self::decodeArrayJson((string)$value),
            default     => null,
        };
    }

    private static function decodeArrayJson(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
