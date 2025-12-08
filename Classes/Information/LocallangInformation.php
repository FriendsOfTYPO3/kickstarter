<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class LocallangInformation
{
    private const LANGUAGE_PATH = 'Resources/Private/Language/';

    /**
     * @param TransUnitInformation[] $transUnits
     */
    public function __construct(private ExtensionInformation $extensionInformation, private string $fileName, private ?\DateTime $creationDate = new \DateTime(), private array $transUnits = [], private CreatorInformation $creatorInformation = new CreatorInformation()) {}

    public function getTransUnits(): array
    {
        return $this->transUnits;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFullFilePath(): string
    {
        return $this->getLanguageRessourcePath() . $this->fileName;
    }

    public function getLanguageRessourcePath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::LANGUAGE_PATH;
    }

    public function getExtFilePath(): string
    {
        return sprintf('EXT:%s/%s%s', $this->extensionInformation->getExtensionKey(), self::LANGUAGE_PATH, $this->fileName);
    }

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }

    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }
}
