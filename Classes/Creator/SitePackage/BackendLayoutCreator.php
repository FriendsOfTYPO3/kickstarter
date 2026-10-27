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

class BackendLayoutCreator implements SitePackageCreatorInterface
{
    /**
     * The identifier of the page layout, the name of the file declaring it and the name of the page
     * template rendering it are one name written in three places.
     */
    public const LAYOUT_IDENTIFIER = 'Default';

    /**
     * Identifier of the single content area of that layout. A page template addresses an area by
     * this identifier, and one the layout never declared is an error on every page rather than an
     * empty column.
     */
    public const COLUMN_IDENTIFIER = 'main';

    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $this->fileManager->createOrModifyFile(
            $sitePackageInformation->getSiteSetBackendLayoutPath() . self::LAYOUT_IDENTIFIER . '.tsconfig',
            $this->getFileContent($sitePackageInformation),
            $sitePackageInformation->getCreatorInformation()
        );
    }

    private function getFileContent(SitePackageInformation $sitePackageInformation): string
    {
        return sprintf(
            $this->getTemplate(),
            self::LAYOUT_IDENTIFIER,
            $sitePackageInformation->getLabelReference('backend_layout.default.title'),
            $sitePackageInformation->getLabelReference('backend_layout.default.main.name'),
            self::COLUMN_IDENTIFIER
        );
    }

    private function getTemplate(): string
    {
        return <<<'TSCONFIG'
mod {
  web_layout {
    BackendLayouts {
      %1$s {
        title = %2$s
        config {
          backend_layout {
            colCount = 1
            rowCount = 1
            rows {
              1 {
                columns {
                  1 {
                    name = %3$s
                    colPos = 0
                    identifier = %4$s
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}

TSCONFIG;
    }
}
