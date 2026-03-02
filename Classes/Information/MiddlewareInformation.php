<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class MiddlewareInformation
{
    private const NAMESPACE_PART = 'Middleware';

    private const CLASS_PATH = 'Classes/Middleware/';

    private const FILE_EXTENSION = '.php';

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $className,
        private string $stack,
        private string $identifier,
        private array $before,
        private array $after,
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getFilename(): string
    {
        return $this->className . self::FILE_EXTENSION;
    }

    public function getFilePath(): string
    {
        return $this->getPath() . $this->getFilename();
    }

    public function getPath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::CLASS_PATH;
    }

    public function getNamespace(): string
    {
        return $this->extensionInformation->getNamespacePrefix() . self::NAMESPACE_PART;
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }

    public function getStack(): string
    {
        return $this->stack;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getBefore(): array
    {
        return $this->before;
    }

    public function getAfter(): array
    {
        return $this->after;
    }
}
