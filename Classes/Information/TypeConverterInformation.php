<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class TypeConverterInformation
{
    private const TYPE_CONVERTER_PATH = 'Classes/Property/TypeConverter/';

    public const ALLOWED_SOURCE_TYPES = [
        'string',
        'array',
        'float',
        'integer',
        'boolean',
    ];

    public const SOURCE_TYPE_ALIASES = [
        'int' => 'integer',
        'bool' => 'boolean',
    ];

    private string $source;

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $typeConverterClassName,
        private int $priority,
        string $source,
        private string $target,
        private CreatorInformation $creatorInformation = new CreatorInformation(),
    ) {
        $normalizedTypes = self::parseAndNormalizeSourceTypes($source);
        $this->validateSourceTypes($normalizedTypes);
        $this->source = implode(', ', $normalizedTypes);
    }

    /**
     * @return list<string>
     */
    public static function parseAndNormalizeSourceTypes(string $source): array
    {
        $types = array_filter(array_map(trim(...), explode(',', $source)));
        $normalized = [];

        foreach ($types as $type) {
            $lower = strtolower($type);
            $normalized[] = self::SOURCE_TYPE_ALIASES[$lower] ?? $lower;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param list<string> $types
     * @return list<string>
     */
    public static function findInvalidSourceTypes(array $types): array
    {
        return array_values(array_diff($types, self::ALLOWED_SOURCE_TYPES));
    }

    /**
     * @param list<string> $normalizedTypes
     */
    private function validateSourceTypes(array $normalizedTypes): void
    {
        if ($normalizedTypes === []) {
            throw new \InvalidArgumentException('Source types of type converter cannot be empty.', 1758920001);
        }

        $invalidTypes = self::findInvalidSourceTypes($normalizedTypes);
        if ($invalidTypes !== []) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid source type(s) "%s". Allowed source types are: %s.',
                    implode('", "', $invalidTypes),
                    implode(', ', self::ALLOWED_SOURCE_TYPES),
                ),
                1758920002,
            );
        }
    }

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getTypeConverterClassName(): string
    {
        return $this->typeConverterClassName;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return list<string>
     */
    public function getSourceTypes(): array
    {
        return self::parseAndNormalizeSourceTypes($this->source);
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Returns true when the target is a class/object (FQCN),
     * false when it is a simple PHP type (int, string, ...).
     */
    public function isObjectTarget(): bool
    {
        $simpleTypes = ['string', 'array', 'float', 'integer', 'boolean', 'int', 'bool', 'double', 'null', 'void', 'mixed'];
        return !in_array(strtolower(ltrim($this->target, '\\')), $simpleTypes, true);
    }

    /**
     * Returns the short (unqualified) class name for use in return type hints.
     * For simple types, returns the type as-is.
     */
    public function getTargetShortName(): string
    {
        if (!$this->isObjectTarget()) {
            return $this->target;
        }

        $parts = explode('\\', ltrim($this->target, '\\'));
        return end($parts);
    }

    public function getTypeConverterFilename(): string
    {
        return $this->typeConverterClassName . '.php';
    }

    public function getTypeConverterFilePath(): string
    {
        return $this->getTypeConverterPath() . $this->getTypeConverterFilename();
    }

    public function getTypeConverterPath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::TYPE_CONVERTER_PATH;
    }

    public function getNamespace(): string
    {
        return $this->extensionInformation->getNamespacePrefix() . 'Property\\TypeConverter';
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }
}
