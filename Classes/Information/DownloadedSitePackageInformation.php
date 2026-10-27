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
 * A site package that came from the builder at get.typo3.org. The builder names the extension, so
 * these values are read back from what it delivered rather than asked for.
 */
readonly class DownloadedSitePackageInformation
{
    public function __construct(
        private string $extensionKey,
        private string $composerPackageName,
        private string $extensionPath,
        private string $siteSetIdentifier,
        private string $siteSetPath,
    ) {}

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }

    public function getComposerPackageName(): string
    {
        return $this->composerPackageName;
    }

    public function getExtensionPath(): string
    {
        return $this->extensionPath;
    }

    /**
     * Identifier of the site set the downloaded package ships, taken from the config.yaml of that
     * set. A site has to name this identifier to be rendered by the package.
     */
    public function getSiteSetIdentifier(): string
    {
        return $this->siteSetIdentifier;
    }

    /**
     * Directory of that site set below Configuration/Sets/, so the instructions can point at the
     * files the user edits next.
     */
    public function getSiteSetPath(): string
    {
        return $this->siteSetPath;
    }
}
