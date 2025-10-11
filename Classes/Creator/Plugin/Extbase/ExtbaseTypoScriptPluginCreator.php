<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Plugin\Extbase;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\PluginInformation;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the default TypoScript for an Extbase Plugin
 */
class ExtbaseTypoScriptPluginCreator implements ExtbasePluginCreatorInterface
{
    use FileStructureBuilderTrait;

    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(PluginInformation $pluginInformation): void
    {
        if (!$pluginInformation->isTypoScriptCreation()) {
            return;
        }

        $extensionInfo = $pluginInformation->getExtensionInformation();
        $pluginNamespace = $pluginInformation->getTypoScriptPluginNamespace();
        $pluginMarker = 'plugin.' . $pluginNamespace;
        $templatePath = $pluginInformation->getTemplatePath();

        // Determine the correct path based on whether using default or named set
        $path = ($pluginInformation->getSet() === $extensionInfo->getDefaultTypoScriptPath())
            ? $extensionInfo->getDefaultTypoScriptPath()
            : $extensionInfo->getSetPath() . $pluginInformation->getSet() . DIRECTORY_SEPARATOR;

        GeneralUtility::mkdir_deep($path);

        $targetSetupFile = $path . 'setup.typoscript';
        $targetConstantFile = $path . 'constants.typoscript';

        // ----- SETUP -----
        $setupContent = is_file($targetSetupFile) ? file_get_contents($targetSetupFile) : '';

        if (!str_contains($setupContent, $pluginMarker)) {
            $setupContent = rtrim($setupContent);
            if ($setupContent !== '') {
                $setupContent .= PHP_EOL . PHP_EOL;
            }
            $setupContent .= sprintf(
                <<<'EOT'
%1$s {
    view {
        templateRootPaths.0 = %2$sTemplates/
        templateRootPaths.10 = {$%1$s.view.templateRootPath}
        partialRootPaths.0 = %2$sPartials/
        partialRootPaths.10 = {$%1$s.view.partialRootPath}
        layoutRootPaths.0 = %2$sLayouts/
        layoutRootPaths.10 = {$%1$s.view.layoutRootPath}
    }
    persistence {
        storagePid = {$plugin.%1$s.persistence.storagePid}
    }
}

EOT,
                $pluginMarker,
                $templatePath
            );

            $this->fileManager->createOrModifyFile($targetSetupFile, $setupContent, $pluginInformation->getCreatorInformation());
        }

        // ----- CONSTANTS -----
        $constantContent = is_file($targetConstantFile) ? file_get_contents($targetConstantFile) : '';

        if (!str_contains($constantContent, $pluginMarker)) {
            $constantContent = rtrim($constantContent);
            if ($constantContent !== '') {
                $constantContent .= PHP_EOL . PHP_EOL;
            }
            $constantContent .= sprintf(
                <<<'EOT'
%1$s {
    view {
        templateRootPath = %2$sTemplates/
        partialRootPath = %2$sPartials/
        layoutRootPath = %2$sLayouts/
    }
    persistence {
        storagePid = 0
    }
}

EOT,
                $pluginMarker,
                $templatePath
            );

            $this->fileManager->createOrModifyFile($targetConstantFile, $constantContent, $pluginInformation->getCreatorInformation());
        }
    }
}
