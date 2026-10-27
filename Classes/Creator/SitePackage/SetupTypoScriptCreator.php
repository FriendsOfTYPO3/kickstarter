<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\SitePackage;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;

class SetupTypoScriptCreator implements SitePackageCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $this->fileManager->createOrModifyFile(
            $sitePackageInformation->getSiteSetPath() . 'setup.typoscript',
            $this->getFileContent(),
            $sitePackageInformation->getCreatorInformation()
        );
    }

    private function getFileContent(): string
    {
        return <<<'TYPOSCRIPT'
@import './TypoScript/*.typoscript'

# The page object of this site. Every page of a site depending on this site set
# is rendered by it, which is why a site needs no sys_template record anymore.
page = PAGE
page {
  10 =< lib.fluidPage
}

TYPOSCRIPT;
    }
}
