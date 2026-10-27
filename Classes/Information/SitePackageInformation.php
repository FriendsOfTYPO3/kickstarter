<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class SitePackageInformation
{
    private const TEMPLATES_PATH = 'Templates/';

    private const TYPOSCRIPT_PATH = 'TypoScript/';

    private const BACKEND_LAYOUTS_PATH = 'BackendLayouts/';

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private SiteSetInformation $siteSetInformation,
        private bool $useFluidStyledContent = true,
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getSiteSetInformation(): SiteSetInformation
    {
        return $this->siteSetInformation;
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }

    /**
     * A site package depending on the site set of EXT:fluid_styled_content keeps the rendering of
     * the content elements TYPO3 ships. Without that dependency the site package has to provide the
     * TypoScript object lib.contentElement on its own, as every Extbase plugin is rendered by it.
     */
    public function isUseFluidStyledContent(): bool
    {
        return $this->useFluidStyledContent;
    }

    public function getSiteSetPath(): string
    {
        return $this->siteSetInformation->getSiteSetPath();
    }

    public function getSiteSetTypoScriptPath(): string
    {
        return $this->getSiteSetPath() . self::TYPOSCRIPT_PATH;
    }

    public function getSiteSetBackendLayoutPath(): string
    {
        return $this->getSiteSetPath() . self::BACKEND_LAYOUTS_PATH;
    }

    /**
     * Every Fluid file of a site package lives below one single template root, with Pages/ for the
     * page templates, Content/ for the content element templates, Layouts/ and Partials/.
     */
    public function getTemplatePath(): string
    {
        return $this->extensionInformation->getResourcesPrivatePath() . self::TEMPLATES_PATH;
    }

    /**
     * The template root above as an EXT: reference, to be used within TypoScript and Fluid.
     */
    public function getTemplateReference(): string
    {
        return sprintf(
            'EXT:%s/%s%s',
            $this->extensionInformation->getExtensionKey(),
            ExtensionInformation::RESOURCES_PRIVATE_PATH,
            self::TEMPLATES_PATH
        );
    }

    public function getPublicReference(): string
    {
        return sprintf(
            'EXT:%s/%s',
            $this->extensionInformation->getExtensionKey(),
            ExtensionInformation::RESOURCES_PUBLIC_PATH
        );
    }

    /**
     * The translation domain the labels.xlf of a site set resolves to. It follows from the path of
     * that file and nothing registers it, so the directory name is transformed the same way
     * TranslationDomainResolver transforms it.
     */
    public function getTranslationDomain(): string
    {
        return sprintf(
            '%s.sets.%s',
            $this->extensionInformation->getExtensionKey(),
            $this->upperCamelCaseToSnakeCase($this->siteSetInformation->getPath())
        );
    }

    /**
     * Build a label reference pointing into the labels.xlf of the site set.
     */
    public function getLabelReference(string $id): string
    {
        return sprintf('LLL:%s:%s', $this->getTranslationDomain(), $id);
    }

    /**
     * Prefix of the site settings this site package defines. It is named after the site package
     * rather than after the set, so the settings of a second set do not collide with these.
     */
    public function getSettingsPrefix(): string
    {
        return GeneralUtility::underscoredToLowerCamelCase($this->extensionInformation->getExtensionKey());
    }

    /**
     * The one site setting this site package ships as an example. Its value is rendered into the
     * footer of every page.
     */
    public function getCopyrightSettingKey(): string
    {
        return $this->getSettingsPrefix() . '.copyright';
    }

    /**
     * Identifier the CSS and JavaScript of this site package is registered with.
     */
    public function getAssetIdentifier(): string
    {
        return str_replace('_', '-', $this->extensionInformation->getExtensionKey());
    }

    /**
     * The transformation TranslationDomainResolver applies to the directory name of a site set.
     */
    private function upperCamelCaseToSnakeCase(string $input): string
    {
        $result = strtolower((string)preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $input));

        return str_replace('-', '_', $result);
    }
}
