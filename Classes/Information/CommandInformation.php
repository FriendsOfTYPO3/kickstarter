<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class CommandInformation
{
    private const COMMAND_PATH = 'Classes/Command/';

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $commandClassName,
        private string $name,
        private string $description,
        private array $aliases,
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getCommandClassName(): string
    {
        return $this->commandClassName;
    }

    public function getName(): string
    {
        return trim($this->name, ':');
    }

    public function getDescription(): string
    {
        return trim($this->description);
    }

    public function getAliases(): array
    {
        return array_map(trim(...), $this->aliases);
    }

    public function getCommandFilename(): string
    {
        return $this->commandClassName . '.php';
    }

    public function getCommandFilePath(): string
    {
        return $this->getCommandPath() . $this->getCommandFilename();
    }

    public function getCommandPath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::COMMAND_PATH;
    }

    public function getNamespace(): string
    {
        return $this->extensionInformation->getNamespacePrefix() . 'Command';
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }
}
