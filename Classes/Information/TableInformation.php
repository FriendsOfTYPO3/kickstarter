<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class TableInformation
{
    private const TABLE_PATH = 'Configuration/TCA/';

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $tableName,
        private string $title,
        private string $label,
        private array $columns,
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getTableConfigurationPath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::TABLE_PATH;
    }

    public function getFullTableConfigurationFilePath(): string
    {
        return $this->getTableConfigurationPath() . $this->getTableName() . '.php';
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }

    public function hasExtTables(): bool
    {
        foreach ($this->columns as $column) {
            if (!$column['type_info']?->isDatabaseColumnAutoCreated()) {
                return true;
            }
        }
        return false;
    }
}
