<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Creator\SiteSet\SiteSetCreatorInterface;
use FriendsOfTYPO3\Kickstarter\Information\SiteSetInformation;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class SiteSetCreatorService
{
    /**
     * @param iterable<SiteSetCreatorInterface> $siteSetCreators
     */
    public function __construct(
        #[AutowireIterator('ext-kickstarter.creator.site-set')]
        private iterable $siteSetCreators,
    ) {}

    public function create(SiteSetInformation $siteSetInformation): void
    {
        foreach ($this->siteSetCreators as $creator) {
            $creator->create($siteSetInformation);
        }
    }
}
