<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class ViewHelperInformation
{
    private const CLASS_PATH = 'Classes/ViewHelper/';

    private const NAMESPACE_PREFIX = 'ViewHelper';

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $name,
        private bool $tagBased = false,
        private string $tagName = '',
        private array $arguments = [],
        private bool $escapeOutput = true,
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getFilename(): string
    {
        return $this->name . 'ViewHelper.php';
    }

    public function getClassname(): string
    {
        return $this->name . 'ViewHelper';
    }

    public function getPath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::CLASS_PATH;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isTagBased(): bool
    {
        return $this->tagBased;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function isEscapeOutput(): bool
    {
        return $this->escapeOutput;
    }

    public function getNamespace(): string
    {
        return $this->extensionInformation->getNamespacePrefix() . self::NAMESPACE_PREFIX;
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }
}
