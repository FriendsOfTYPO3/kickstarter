<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Creator\SiteSet\SiteSettingsDefinitionCreatorInterface;
use FriendsOfTYPO3\Kickstarter\Information\SiteSettingsDefinitionInformation;

readonly class SiteSettingsDefinitionCreatorService
{
    /**
     * @param iterable<SiteSettingsDefinitionCreatorInterface> $siteSettingDefinitionCreators
     */
    public function __construct(
        private iterable $siteSettingDefinitionCreators,
    ) {}

    public function create(SiteSettingsDefinitionInformation $siteSetInformation): void
    {
        foreach ($this->siteSettingDefinitionCreators as $creator) {
            $creator->create($siteSetInformation);
        }
    }
}
