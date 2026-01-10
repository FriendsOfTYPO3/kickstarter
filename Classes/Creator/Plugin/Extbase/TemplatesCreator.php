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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the templates for the actions of a plugin
 */
class TemplatesCreator implements ExtbasePluginCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(PluginInformation $pluginInformation): void
    {
        $templatePath = str_replace(
            'EXT:' . $pluginInformation->getExtensionInformation()->getExtensionKey() . '/',
            $pluginInformation->getExtensionInformation()->getExtensionPath(),
            $pluginInformation->getTemplatePath()
        );
        GeneralUtility::mkdir_deep($templatePath . 'Templates');
        GeneralUtility::mkdir_deep($templatePath . 'Partials');
        GeneralUtility::mkdir_deep($templatePath . 'Layouts');

        $this->fileManager->createOrModifyFile($templatePath . 'Templates/.gitkeep', '', $pluginInformation->getCreatorInformation());
        $this->fileManager->createOrModifyFile($templatePath . 'Partials/.gitkeep', '', $pluginInformation->getCreatorInformation());
        $this->fileManager->createOrModifyFile($templatePath . 'Layouts/.gitkeep', '', $pluginInformation->getCreatorInformation());

        if (count($pluginInformation->getTemplates()) > 0) {

            $this->fileManager->createFile($templatePath . 'Layouts/Default.fluid.html', $this->getDefaultLayout($pluginInformation), $pluginInformation->getCreatorInformation());
        }

        foreach ($pluginInformation->getTemplates() as $template) {
            $path = str_replace(
                'EXT:' . $pluginInformation->getExtensionInformation()->getExtensionKey() . '/',
                $pluginInformation->getExtensionInformation()->getExtensionPath(),
                $template
            );

            GeneralUtility::mkdir_deep(dirname($path));
            $this->fileManager->createFile($path, $this->getDefaultTemplate($template), $pluginInformation->getCreatorInformation());
        }
    }

    private function getDefaultTemplate(string $template, string $layout = 'Default'): string
    {
        return sprintf(<<<'EOT'
<html xmlns:f="https://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
<f:layout name="%s"/>

<f:section name="content">
    <p>TODO: Modify file <code>%s</code></p>
</f:section>
</html>
EOT, $layout, $template);
    }

    private function getDefaultLayout(PluginInformation $pluginInformation): string
    {
        return sprintf(<<<'EOT'
<html xmlns:f="https://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
<div class="%s">
    <f:flashMessages />

    <f:render section="content" />
</div>
</html>

EOT, $pluginInformation->getTypoScriptPluginNamespace());
    }
}
