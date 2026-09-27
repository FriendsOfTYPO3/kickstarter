<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Traits;

use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait ExtensionInformationTrait
{
    /**
     * Collects data from composer.json to build ExtensionInformation object.
     * Can only be called for existing extensions with existing composer.json file
     */
    private function getExtensionInformation(string $extensionKey, CommandContext $commandContext): ExtensionInformation
    {
        $extensionPath = $this->getExtensionPath($extensionKey);
        if (!is_dir($extensionPath)) {
            $commandContext->getIo()->error([
                'No extension found at: ' . $extensionPath,
                'Please use command "make:extension" to create a new extension.',
            ]);
            die();
        }

        $composerManifestPath = $extensionPath . 'composer.json';
        if (!is_file($composerManifestPath)) {
            $commandContext->getIo()->error([
                'Extension "' . $extensionKey . '" does not have a composer.json file.',
                'Seems that the existing directory is no TYPO3 extension.',
                'Please use command "make:extension" to create a new extension.',
            ]);
            die();
        }

        try {
            $composerManifest = json_decode((file_get_contents($composerManifestPath) ?: ''), true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException $e) {
            $commandContext->getIo()->error(['Could not decode composer.json. Please check syntax: ' . $e->getMessage()]);
            die();
        }

        $extEmConfManifest = $this->getExtEmConf($extensionKey, $extensionPath);

        return new ExtensionInformation(
            $extensionKey,
            $composerManifest['name'] ?? '',
            $extEmConfManifest['title'] ?? '',
            $composerManifest['description'] ?? '',
            $extEmConfManifest['version'] ?? '0.0.0',
            $extEmConfManifest['category'] ?? 'plugin',
            $extEmConfManifest['state'] ?? 'alpha',
            $extEmConfManifest['author'] ?? '',
            $extEmConfManifest['author_email'] ?? '',
            $extEmConfManifest['author_company'] ?? '',
            key($composerManifest['autoload']['psr-4']) ?? '',
            $extensionPath
        );
    }

    private function getExtEmConf(string $extensionKey, string $extensionPath): array
    {
        $_EXTKEY = $extensionKey;
        $path = $extensionPath . 'ext_emconf.php';
        $EM_CONF = null;
        if (@file_exists($path)) {
            include $path;
            if (is_array($EM_CONF[$_EXTKEY] ?? false)) {
                return $EM_CONF[$_EXTKEY];
            }
        }

        return [];
    }

    /**
     * Returns the directory path (incl. trailing slash) where an existing extension resides.
     * Prefers registered extensions from PackageManager of type 'typo3-cms-extension',
     * then falls back to the configured export directory.
     */
    private function getExtensionPath(string $extensionKey): string
    {
        if ($extensionKey === '') {
            throw new \InvalidArgumentException('Extension key must not be empty', 1741623620);
        }

        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        if ($packageManager->isPackageAvailable($extensionKey)) {
            $package = $packageManager->getPackage($extensionKey);
            $packageType = $package->getPackageMetaData()->getPackageType()
                ?? $package->getValueFromComposerManifest('type');

            if ($packageType === 'typo3-cms-extension') {
                return rtrim($package->getPackagePath(), '/') . '/';
            }
        }

        return $this->getExportExtensionPath($extensionKey);
    }

    /**
     * Returns the target directory (incl. trailing slash) in the configured export directory.
     */
    private function getExportExtensionPath(string $extensionKey): string
    {
        if ($extensionKey === '') {
            throw new \InvalidArgumentException('Extension key must not be empty', 1741623620);
        }

        $extConf = GeneralUtility::makeInstance(ExtConf::class);

        return sprintf(
            '/%s/%s/',
            trim($extConf->getExportDirectory(), '/'),
            $extensionKey
        );
    }

    private function createExtensionPath(
        string $extensionKey,
        bool $removePreviousExportDirectoryIfExists = false
    ): string {
        $extensionPath = $this->getExportExtensionPath($extensionKey);

        if ($removePreviousExportDirectoryIfExists && is_dir($extensionPath)) {
            GeneralUtility::rmdir($extensionPath, true);
        }

        GeneralUtility::mkdir_deep($extensionPath);

        return $extensionPath;
    }
}
