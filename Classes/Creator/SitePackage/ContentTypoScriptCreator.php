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

class ContentTypoScriptCreator implements SitePackageCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $this->fileManager->createOrModifyFile(
            $sitePackageInformation->getSiteSetTypoScriptPath() . 'content.typoscript',
            $this->getFileContent($sitePackageInformation),
            $sitePackageInformation->getCreatorInformation()
        );
    }

    private function getFileContent(SitePackageInformation $sitePackageInformation): string
    {
        $template = $sitePackageInformation->isUseFluidStyledContent()
            ? $this->getFluidStyledContentTemplate()
            : $this->getOwnContentElementTemplate();

        return sprintf($template, $sitePackageInformation->getTemplateReference());
    }

    /**
     * EXT:fluid_styled_content provides lib.contentElement, so this site package only adds its own
     * template roots on top of the ones that extension configures.
     */
    private function getFluidStyledContentTemplate(): string
    {
        return <<<'TYPOSCRIPT'
# This site package depends on the site set of EXT:fluid_styled_content, which
# provides lib.contentElement and the rendering of the content elements TYPO3
# ships. The roots below are added on top of them, so a file of this site
# package overrides the one of EXT:fluid_styled_content.
#
# Keep addressing layouts and partials with their subdirectory. The page frame
# and the content element frame are both called Default, and a layout or partial
# placed directly into the shared root would be rendered inside every single
# content element on the page.
lib.contentElement {
  templateRootPaths.20 = %1$sContent/
  partialRootPaths.20 = %1$sPartials/
  layoutRootPaths.20 = %1$sLayouts/
}

TYPOSCRIPT;
    }

    /**
     * Without EXT:fluid_styled_content nothing defines lib.contentElement, and every Extbase plugin
     * is rendered through it as well. So this site package has to define it itself.
     */
    private function getOwnContentElementTemplate(): string
    {
        return <<<'TYPOSCRIPT'
# This site package does not depend on the site set of EXT:fluid_styled_content,
# so it defines the rendering of a single content element itself. Every Extbase
# plugin is rendered through lib.contentElement as well, with the template name
# Generic, which is why Content/Generic.fluid.html ships with this site package.
lib.contentElement = FLUIDTEMPLATE
lib.contentElement {
  templateRootPaths.10 = %1$sContent/
  partialRootPaths.10 = %1$sPartials/
  layoutRootPaths.10 = %1$sLayouts/

  # Derive the template name from the content type, so a CType "my_element" is
  # rendered by Content/MyElement.fluid.html. Words the identifier does not
  # separate the file name does not separate either.
  templateName >
  templateName.ifEmpty.cObject = TEXT
  templateName.ifEmpty.cObject {
    field = CType
    required = 1
    case = uppercamelcase
  }

  dataProcessing.10 = record-transformation
}

# Register the rendering of your own content elements like this, and add the
# matching template to Content/:
#
# tt_content.my_element =< lib.contentElement
#
# The content elements TYPO3 ships are rendered by EXT:fluid_styled_content and
# stay without a rendering definition here.

TYPOSCRIPT;
    }
}
