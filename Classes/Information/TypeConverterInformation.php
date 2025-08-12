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

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $typeConverterClassName,
        private int $priority,
        private string $source,
        private string $target,
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

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

    public function getTarget(): string
    {
        return $this->target;
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
