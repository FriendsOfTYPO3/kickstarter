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
 * Creates the Fluid files rendering a single content element. A site package depending on
 * EXT:fluid_styled_content only gets the directory its own content element templates go into, as
 * that extension ships the frame and the templates of the content elements TYPO3 provides. Without
 * that dependency this site package carries the frame and the Generic template every Extbase plugin
 * is rendered with.
 */
class ContentTemplateCreator implements SitePackageCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $templatePath = $sitePackageInformation->getTemplatePath();
        $creatorInformation = $sitePackageInformation->getCreatorInformation();

        $this->fileManager->createOrModifyFile(
            $templatePath . 'Content/.gitkeep',
            '',
            $creatorInformation
        );

        if ($sitePackageInformation->isUseFluidStyledContent()) {
            return;
        }

        $this->fileManager->createOrModifyFile(
            $templatePath . 'Layouts/Content/Default.fluid.html',
            $this->getContentLayout(),
            $creatorInformation
        );

        $this->fileManager->createOrModifyFile(
            $templatePath . 'Content/Generic.fluid.html',
            $this->getGenericTemplate(),
            $creatorInformation
        );
    }

    private function getContentLayout(): string
    {
        return <<<'HTML'
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    data-namespace-typo3-fluid="true"
>

<f:comment>
    The frame around a single content element. It is addressed as
    "Content/Default", with its subdirectory, because the frame of the page is
    called Default as well.
</f:comment>

<div class="content-element">
    <f:render section="Header" optional="true">
        <f:if condition="{record.header}">
            <h2 class="content-element__header">{record.header}</h2>
        </f:if>
    </f:render>

    <f:render section="Main" optional="true"/>
</div>

</html>

HTML;
    }

    private function getGenericTemplate(): string
    {
        return <<<'HTML'
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    data-namespace-typo3-fluid="true"
>

<f:layout name="Content/Default"/>

<f:comment>
    Registering an Extbase plugin writes a rendering definition copying
    lib.contentElement with this template name, so every plugin is rendered
    here. It renders the content the plugin assigned, and otherwise calls back
    onto the TypoScript path the plugin itself was registered at.
</f:comment>

<f:section name="Header"/>

<f:section name="Main">
    <f:if condition="{content}">
        <f:then>{content -> f:format.raw()}</f:then>
        <f:else><f:cObject typoscriptObjectPath="{record.fullType}.20" data="{record}" table="{record.mainType}"/></f:else>
    </f:if>
</f:section>

</html>

HTML;
    }
}
