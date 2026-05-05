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
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class SitePackageCreatorService
{
    /**
     * @param iterable<SitePackageCreatorInterface> $sitePackageCreators
     */
    public function __construct(
        #[AutowireIterator('ext-kickstarter.creator.property.site-package')]
        private iterable $sitePackageCreators,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        foreach ($this->sitePackageCreators as $creator) {
            $creator->create($sitePackageInformation);
        }
    }
}
