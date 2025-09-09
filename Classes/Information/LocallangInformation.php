<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class LocallangInformation
{
    private const LANGUAGE_PATH = 'Resources/Private/Language/';

    private \DateTime $creationDate;

    /**
     * @param TransUnitInformation[] $transUnits
     */
    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $fileName,
        ?\DateTime $creationDate = null,
        private array $transUnits = [],
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {
        $this->creationDate = $creationDate ?? new \DateTime();
    }

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
