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

class PageTypoScriptCreator implements SitePackageCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $this->fileManager->createOrModifyFile(
            $sitePackageInformation->getSiteSetTypoScriptPath() . 'page.typoscript',
            $this->getFileContent($sitePackageInformation),
            $sitePackageInformation->getCreatorInformation()
        );
    }

    private function getFileContent(SitePackageInformation $sitePackageInformation): string
    {
        return sprintf($this->getTemplate(), $sitePackageInformation->getTemplateReference());
    }

    private function getTemplate(): string
    {
        return <<<'TYPOSCRIPT'
# Render the pages of this site with Fluid.
#
# PAGEVIEW derives the template name from the page layout: the identifier of the
# backend layout without its "pagets__" prefix, and "default" for a page which
# has no layout selected. There is nothing to configure about that name.
#
# The single paths entry below becomes three roots: Pages/ for the templates,
# Partials/ and Layouts/.
lib.fluidPage = PAGEVIEW
lib.fluidPage {
  paths.10 = %1$s

  dataProcessing {
    10 = menu
    10 {
      as = mainMenu
      levels = 2
    }

    20 = site
    20.as = site
  }
}

TYPOSCRIPT;
    }
}
