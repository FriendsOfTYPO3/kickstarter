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
 * Creates the Fluid files rendering the page frame: the page template PAGEVIEW resolves for the
 * page layout of this site package, and the layout carrying the markup around the content.
 */
class PageTemplateCreator implements SitePackageCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $templatePath = $sitePackageInformation->getTemplatePath();
        $creatorInformation = $sitePackageInformation->getCreatorInformation();

        $this->fileManager->createOrModifyFile(
            $templatePath . 'Pages/' . BackendLayoutCreator::LAYOUT_IDENTIFIER . '.fluid.html',
            $this->getPageTemplate(),
            $creatorInformation
        );

        $this->fileManager->createOrModifyFile(
            $templatePath . 'Layouts/Pages/' . BackendLayoutCreator::LAYOUT_IDENTIFIER . '.fluid.html',
            $this->getPageLayout($sitePackageInformation),
            $creatorInformation
        );

        // Partials are resolved from this root, so the directory is part of the contract even while
        // this site package ships no partial of its own yet.
        $this->fileManager->createOrModifyFile(
            $templatePath . 'Partials/.gitkeep',
            '',
            $creatorInformation
        );
    }

    private function getPageTemplate(): string
    {
        return <<<'HTML'
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    data-namespace-typo3-fluid="true"
>

<f:layout name="Pages/Default"/>

<f:comment>
    The frame of the page lives in the layout, so a second page layout only has
    to override the sections it wants to change. The name of this file is not
    configurable: PAGEVIEW derives it from the page layout of the page.
</f:comment>

</html>

HTML;
    }

    private function getPageLayout(SitePackageInformation $sitePackageInformation): string
    {
        return sprintf(
            $this->getPageLayoutTemplate(),
            $sitePackageInformation->getAssetIdentifier(),
            $sitePackageInformation->getPublicReference(),
            BackendLayoutCreator::COLUMN_IDENTIFIER,
            $sitePackageInformation->getCopyrightSettingKey()
        );
    }

    private function getPageLayoutTemplate(): string
    {
        return <<<'HTML'
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    data-namespace-typo3-fluid="true"
>

<f:page.meta property="viewport">width=device-width, initial-scale=1.0</f:page.meta>
<f:asset.css identifier="%1$s-main" href="%2$sCss/main.css" media="all"/>
<f:asset.script identifier="%1$s-main" src="%2$sJavaScript/main.js" type="module"/>

<header class="page-header">
    <f:link.page pageUid="{site.rootPageId}" class="page-header__title">
        {language.websiteTitle}
    </f:link.page>

    <f:if condition="{mainMenu}">
        <nav class="page-navigation">
            <ul class="page-navigation__list">
                <f:for each="{mainMenu}" as="item">
                    <li class="page-navigation__item">
                        <f:link.typolink
                            parameter="{item.link}"
                            class="page-navigation__link {f:if(condition: item.active, then: 'page-navigation__link--active')}"
                        >{item.title}</f:link.typolink>
                    </li>
                </f:for>
            </ul>
        </nav>
    </f:if>
</header>

<main class="page-content">
    <f:render section="Main" optional="true">
        <f:comment>
            The identifier of a content area is the one the page layout declares
            for that column. An identifier no layout declares is an error on
            every page rendered by this template, not an empty column.
        </f:comment>
        <!--TYPO3SEARCH_begin-->
        <f:render.contentArea contentArea="{content.%3$s}"/>
        <!--TYPO3SEARCH_end-->
    </f:render>
</main>

<footer class="page-footer">
    <f:if condition="{site.configuration.settings.%4$s}">
        <p class="page-footer__copyright">{site.configuration.settings.%4$s}</p>
    </f:if>
</footer>

</html>

HTML;
    }
}
