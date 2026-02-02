<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Extension;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;

class FolderCreator implements ExtensionCreatorInterface
{
    public function __construct(
        protected readonly FileManager $fileManager,
        protected ?array $generalPaths = null,
        protected ?array $extbasePaths = null,
        protected ?array $sitePackagePaths = null,
        protected ?array $testPaths = null,
    ) {
        if ($this->generalPaths === null) {
            $this->generalPaths = [];
            $this->generalPaths[] = ExtensionInformation::CLASSES_PATH;
            $this->generalPaths[] = ExtensionInformation::TCA_PATH;
            $this->generalPaths[] = ExtensionInformation::TYPOSCRIPT_DEFAULT_PATH;
            $this->generalPaths[] = ExtensionInformation::RESOURCES_PUBLIC_PATH;
            $this->generalPaths[] = ExtensionInformation::RESOURCES_PRIVATE_PATH;
            $this->generalPaths[] = ExtensionInformation::LANGUAGE_PATH;
            $this->generalPaths[] = ExtensionInformation::SITE_SET_PATH;
        }
        if ($this->extbasePaths === null) {
            $this->extbasePaths = [];
            $this->extbasePaths[] = ExtensionInformation::CONTROLLER_PATH;
            $this->extbasePaths[] = ExtensionInformation::MODEL_PATH;
            $this->extbasePaths[] = ExtensionInformation::REPOSITORY_PATH;
            $this->extbasePaths[] = ExtensionInformation::SERVICES_PATH;
            $this->extbasePaths[] = ExtensionInformation::VIEWHELPERS_PATH;
        }
        if ($this->sitePackagePaths === null) {
            $this->sitePackagePaths = [];
            $this->sitePackagePaths[] = ExtensionInformation::RESOURCES_PUBLIC_PATH . 'Css/';
            $this->sitePackagePaths[] = ExtensionInformation::RESOURCES_PUBLIC_PATH . 'JavaScript/';
            $this->sitePackagePaths[] = ExtensionInformation::RESOURCES_PUBLIC_PATH . 'Fonts/';
            $this->sitePackagePaths[] = ExtensionInformation::RESOURCES_PUBLIC_PATH . 'Images/';
            $this->sitePackagePaths[] = ExtensionInformation::RESOURCES_PRIVATE_PATH . 'PageView/Pages';
            $this->sitePackagePaths[] = ExtensionInformation::RESOURCES_PRIVATE_PATH . 'PageView/Partials';
            $this->sitePackagePaths[] = ExtensionInformation::RESOURCES_PRIVATE_PATH . 'PageView/Layouts';
        }
        if ($this->testPaths === null) {
            $this->testPaths = [];
            $this->testPaths[] = ExtensionInformation::TEST_UNIT_PATH;
            $this->testPaths[] = ExtensionInformation::TEST_FUNCTIONAL_PATH;
        }
    }

    public function create(ExtensionInformation $extensionInformation): void
    {
        if (!$extensionInformation->isCreateFolders()) {
            return;
        }
        foreach ($this->generalPaths as $path) {
            $this->fileManager->createOrModifyFile(
                $extensionInformation->getExtensionPath() . $path . '.gitkeep',
                '',
                $extensionInformation->getCreatorInformation()
            );
        }
        if ($extensionInformation->isCreateExtbaseFolders()) {
            foreach ($this->extbasePaths as $path) {
                $this->fileManager->createOrModifyFile(
                    $extensionInformation->getExtensionPath() . $path . '.gitkeep',
                    '',
                    $extensionInformation->getCreatorInformation()
                );
            }
        }
        if ($extensionInformation->isCreateSitePackageFolders()) {
            foreach ($this->sitePackagePaths as $path) {
                $this->fileManager->createOrModifyFile(
                    $extensionInformation->getExtensionPath() . $path . '.gitkeep',
                    '',
                    $extensionInformation->getCreatorInformation()
                );
            }
        }
        if ($extensionInformation->isCreateTestFolders()) {
            foreach ($this->testPaths as $path) {
                $this->fileManager->createOrModifyFile(
                    $extensionInformation->getExtensionPath() . $path . '.gitkeep',
                    '',
                    $extensionInformation->getCreatorInformation()
                );
            }
        }
    }
}
