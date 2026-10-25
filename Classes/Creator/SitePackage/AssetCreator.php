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

/**
 * Creates the stylesheet and the JavaScript module the page layout of this site package loads.
 */
class AssetCreator implements SitePackageCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $publicPath = $sitePackageInformation->getExtensionInformation()->getResourcesPublicPath();
        $creatorInformation = $sitePackageInformation->getCreatorInformation();

        $this->fileManager->createOrModifyFile(
            $publicPath . 'Css/main.css',
            $this->getStylesheet(),
            $creatorInformation
        );

        $this->fileManager->createOrModifyFile(
            $publicPath . 'JavaScript/main.js',
            $this->getJavaScript(),
            $creatorInformation
        );
    }

    private function getStylesheet(): string
    {
        return <<<'CSS'
/*
 * Stylesheet of this site package, loaded by
 * Resources/Private/Templates/Layouts/Pages/Default.fluid.html.
 */

:root {
    --page-width: 60rem;
    --page-spacing: 1.5rem;
}

body {
    margin: 0;
    font-family: system-ui, sans-serif;
    line-height: 1.5;
}

.page-header,
.page-content,
.page-footer {
    margin-inline: auto;
    max-width: var(--page-width);
    padding: var(--page-spacing);
}

.page-navigation__list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--page-spacing);
    margin: 0;
    padding: 0;
    list-style: none;
}

.page-navigation__link--active {
    font-weight: 700;
}

CSS;
    }

    private function getJavaScript(): string
    {
        return <<<'JS'
/*
 * JavaScript module of this site package, loaded by
 * Resources/Private/Templates/Layouts/Pages/Default.fluid.html.
 */

JS;
    }
}
