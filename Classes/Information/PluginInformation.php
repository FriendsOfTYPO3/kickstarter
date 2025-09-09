<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class PluginInformation
{
    private const CONFIGURATION_PATH = 'Configuration/';

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private bool $extbasePlugin,
        private string $pluginLabel,
        private string $pluginName,
        private string $pluginDescription,
        private array $referencedControllerActions,
        private CreatorInformation $creatorInformation = new CreatorInformation(),
        private bool $typoScriptCreation = false,
        private string $set = '',
        private string $templatePath = '',
    ) {}

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function isExtbasePlugin(): bool
    {
        return $this->extbasePlugin;
    }

    public function getPluginLabel(): string
    {
        return $this->pluginLabel;
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    public function getPluginDescription(): string
    {
        return $this->pluginDescription;
    }

    public function getConfigurationPath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::CONFIGURATION_PATH;
    }

    public function getPluginIconIdentifier(): string
    {
        return sprintf(
            'ext-%s-plugin',
            str_replace('_', '-', $this->extensionInformation->getExtensionKey()),
        );
    }

    public function getReferencedControllerActions(bool $cached): array
    {
        $referencedControllerActions = [];

        foreach ($this->referencedControllerActions as $referencedExtbaseControllerClassname => $referencedControllerActionNames) {
            // Remove "Action" from the action name
            $controllerActionNames = array_map(static function ($controllerActionName): string {
                return substr($controllerActionName, 0, -6);
            }, $referencedControllerActionNames[$cached ? 'cached' : 'uncached']);

            $referencedControllerActions[$referencedExtbaseControllerClassname] = implode(
                ', ',
                $controllerActionNames
            );
        }

        return $referencedControllerActions;
    }

    /**
     * Needed to create all "use" imports
     */
    public function getReferencedControllerNames(): array
    {
        return array_keys($this->referencedControllerActions);
    }

    public function getNamespaceForControllerName(string $controllerName): string
    {
        return $this->extensionInformation->getNamespacePrefix() . 'Controller\\' . $controllerName;
    }

    /**
     * Needed for core native plugins
     */
    public function getPluginNamespace(): string
    {
        return sprintf(
            '%s_%s',
            str_replace('_', '', $this->extensionInformation->getExtensionKey()),
            strtolower($this->pluginName),
        );
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }

    /**
     * Needed for core native plugins
     */
    public function getTypoScriptPluginNamespace(): string
    {
        return sprintf(
            'tx_%s_%s',
            str_replace('_', '', $this->extensionInformation->getExtensionKey()),
            strtolower($this->pluginName),
        );
    }

    public function isTypoScriptCreation(): bool
    {
        return $this->typoScriptCreation;
    }

    public function getSet(): string
    {
        return $this->set;
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }
}
