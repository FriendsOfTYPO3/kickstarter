<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

readonly class ModuleInformation
{
    private const MODULES_FILE_PATH = 'Configuration/Backend/Module.php';

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $identifier,
        private string $parent,
        private string $position,
        private string $access,
        private string $workspaces,
        private string $path,
        private string $title,
        private string $description,
        private string $shortDescription,
        private string $iconIdentifier,
        private bool $isExtbaseModule,
        private string $extensionName,
        private array $referencedControllerActions,
        private array $referencedRoutes,
    ) {}

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getParent(): string
    {
        return $this->parent;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getAccess(): string
    {
        return $this->access;
    }

    public function getWorkspaces(): string
    {
        return $this->workspaces;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function getLabels(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'shortDescription' => $this->shortDescription,
        ];
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    public function isExtbaseModule(): bool
    {
        return $this->isExtbaseModule;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getReferencedControllerActions(): array
    {
        $referencedControllerActions = [];

        foreach ($this->referencedControllerActions as $referencedExtbaseControllerClassname => $referencedControllerActionNames) {
            // Remove "Action" from the action name
            $controllerActionNames = array_map(static function ($controllerActionName): string {
                return substr($controllerActionName, 0, -6);
            }, $referencedControllerActionNames);

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

    public function getReferencedRoutes(): array
    {
        return $this->referencedRoutes;
    }

    public function getModuleFilePath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::MODULES_FILE_PATH;
    }
}
