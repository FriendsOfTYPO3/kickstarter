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
 * Adds the packages the site set depends on to the composer.json the extension creators wrote. A
 * site set naming a dependency which is not installed is not a missing feature: the set is dropped
 * and every site depending on it answers with an error.
 */
class ComposerRequirementCreator implements SitePackageCreatorInterface
{
    private const FLUID_STYLED_CONTENT_PACKAGE = 'typo3/cms-fluid-styled-content';

    private const CORE_PACKAGE = 'typo3/cms-core';

    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        if (!$sitePackageInformation->isUseFluidStyledContent()) {
            return;
        }

        $creatorInformation = $sitePackageInformation->getCreatorInformation();
        $composerManifestPath = $sitePackageInformation->getExtensionInformation()->getExtensionPath() . 'composer.json';

        if (!is_file($composerManifestPath)) {
            $creatorInformation->fileModificationFailed(
                $composerManifestPath,
                sprintf('The package %s can not be required, there is no composer.json.', self::FLUID_STYLED_CONTENT_PACKAGE)
            );
            return;
        }

        try {
            $composerManifest = json_decode(
                (string)file_get_contents($composerManifestPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            $creatorInformation->fileModificationFailed(
                $composerManifestPath,
                sprintf('The composer.json can not be decoded: %s', $e->getMessage())
            );
            return;
        }

        if (!is_array($composerManifest)) {
            $creatorInformation->fileModificationFailed($composerManifestPath, 'The composer.json holds no object.');
            return;
        }

        if (isset($composerManifest['require'][self::FLUID_STYLED_CONTENT_PACKAGE])) {
            $creatorInformation->fileNotModified(
                $composerManifestPath,
                sprintf('The package %s is already required.', self::FLUID_STYLED_CONTENT_PACKAGE)
            );
            return;
        }

        // Follow whatever constraint the extension was created with, so both packages stay on the
        // same TYPO3 version.
        $composerManifest['require'][self::FLUID_STYLED_CONTENT_PACKAGE] =
            $composerManifest['require'][self::CORE_PACKAGE] ?? '^14';
        ksort($composerManifest['require']);

        $this->fileManager->modifyFile(
            $composerManifestPath,
            (string)json_encode($composerManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            $creatorInformation
        );
    }
}
