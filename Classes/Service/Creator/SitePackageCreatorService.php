<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Creator\SitePackage\SitePackageCreatorInterface;
use FriendsOfTYPO3\Kickstarter\Creator\SitePackage\SitePackageDownloader;
use FriendsOfTYPO3\Kickstarter\Information\DownloadedSitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Information\ServicesConfigInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageDownloadInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * A site package comes from one of two sources. Generating it locally builds an extension carrying
 * a site set in three steps: the files every extension has, the site set itself, and the
 * TypoScript, page TSconfig, Fluid files and assets which make that set render a site. Downloading
 * it asks the builder at get.typo3.org for a skeleton instead.
 */
readonly class SitePackageCreatorService
{
    /**
     * @param iterable<SitePackageCreatorInterface> $sitePackageCreators
     */
    public function __construct(
        #[AutowireIterator('ext-kickstarter.creator.site-package')]
        private iterable $sitePackageCreators,
        private ExtensionCreatorService $extensionCreatorService,
        private SiteSetCreatorService $siteSetCreatorService,
        private SitePackageDownloader $sitePackageDownloader,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $extensionInformation = $sitePackageInformation->getExtensionInformation();
        $creatorInformation = $sitePackageInformation->getCreatorInformation();

        $this->extensionCreatorService->create(
            $extensionInformation,
            new ServicesConfigInformation(
                extensionInformation: $extensionInformation,
                creatorInformation: $creatorInformation,
            )
        );

        $this->siteSetCreatorService->create($sitePackageInformation->getSiteSetInformation());

        foreach ($this->sitePackageCreators as $creator) {
            $creator->create($sitePackageInformation);
        }
    }

    /**
     * Fetches a site package from the builder at get.typo3.org instead of generating one. Returns
     * null when the builder refused the request, which is recorded in the CreatorInformation of the
     * given information object.
     */
    public function download(SitePackageDownloadInformation $downloadInformation): ?DownloadedSitePackageInformation
    {
        return $this->sitePackageDownloader->download($downloadInformation);
    }
}
