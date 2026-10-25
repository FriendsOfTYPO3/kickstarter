<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\SitePackage;

use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Information\SiteSettingsDefinitionInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SiteSettingsDefinitionCreatorService;
use TYPO3\CMS\Core\Settings\CategoryDefinition;
use TYPO3\CMS\Core\Settings\SettingDefinition;

/**
 * Creates the settings.definitions.yaml of the site set with one example setting, which the page
 * layout of this site package renders. Site settings are what replaces TypoScript constants, so a
 * value an editor is supposed to change belongs here rather than into the TypoScript of the set.
 */
class SettingsDefinitionCreator implements SitePackageCreatorInterface
{
    public function __construct(
        private readonly SiteSettingsDefinitionCreatorService $siteSettingsDefinitionCreatorService,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $siteSetInformation = $sitePackageInformation->getSiteSetInformation();
        $category = $sitePackageInformation->getSettingsPrefix();

        $this->siteSettingsDefinitionCreatorService->create(
            new SiteSettingsDefinitionInformation(
                $sitePackageInformation->getExtensionInformation(),
                $siteSetInformation,
                [
                    new CategoryDefinition(
                        key: $category,
                        label: $siteSetInformation->getLabel(),
                    ),
                ],
                [
                    new SettingDefinition(
                        key: $sitePackageInformation->getCopyrightSettingKey(),
                        type: 'string',
                        default: '',
                        label: 'Copyright',
                        description: 'Rendered into the footer of every page of this site.',
                        category: $category,
                    ),
                ],
                $sitePackageInformation->getCreatorInformation(),
            )
        );
    }
}
