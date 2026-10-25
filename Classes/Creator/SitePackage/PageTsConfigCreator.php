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

class PageTsConfigCreator implements SitePackageCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $this->fileManager->createOrModifyFile(
            $sitePackageInformation->getSiteSetPath() . 'page.tsconfig',
            $this->getFileContent(),
            $sitePackageInformation->getCreatorInformation()
        );
    }

    private function getFileContent(): string
    {
        return <<<'TSCONFIG'
# The page layouts of this site package, one layout per file. As this site
# package ships a single site set and no page TSconfig of its own, nothing
# reaches a backend without the set, so the layouts belong beside it.
@import './BackendLayouts/*.tsconfig'

TSCONFIG;
    }
}
