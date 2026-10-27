<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

/**
 * The answers the site package builder at get.typo3.org needs. It derives the extension key, the
 * Composer package name and the autoload namespace of the site package itself, so this holds no
 * value for any of them. What comes back is described by DownloadedSitePackageInformation.
 */
readonly class SitePackageDownloadInformation
{
    public function __construct(
        private string $basePackage,
        private string $typo3Version,
        private string $title,
        private string $description,
        private string $author,
        private string $authorEmail,
        private string $authorCompany,
        private string $homepage = '',
        private string $repositoryUrl = '',
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    /**
     * The skeleton the site package is built from, such as "fluid_styled_content".
     */
    public function getBasePackage(): string
    {
        return $this->basePackage;
    }

    /**
     * The TYPO3 version the site package is built for, as major and minor, such as "14.3". The
     * builder offers a fixed list of versions and rejects every other value.
     */
    public function getTypo3Version(): string
    {
        return $this->typo3Version;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    /**
     * The builder requires a company and turns it into the vendor part of the Composer package
     * name, so this is not optional the way it is for the other make commands.
     */
    public function getAuthorCompany(): string
    {
        return $this->authorCompany;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }
}
