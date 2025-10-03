<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service;

use FriendsOfTYPO3\Kickstarter\Enums\TcaFieldType;
use FriendsOfTYPO3\Kickstarter\Information\ControllerInformation;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\ModelInformation;
use FriendsOfTYPO3\Kickstarter\Information\PluginInformation;
use FriendsOfTYPO3\Kickstarter\Information\RepositoryInformation;
use FriendsOfTYPO3\Kickstarter\Information\ServicesConfigInformation;
use FriendsOfTYPO3\Kickstarter\Information\TableInformation;
use FriendsOfTYPO3\Kickstarter\Model\Graph;
use FriendsOfTYPO3\Kickstarter\Model\Node\Main\AuthorNode;
use FriendsOfTYPO3\Kickstarter\Model\Node\Main\ExtensionNode;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ControllerCreatorService;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ExtensionCreatorService;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ModelCreatorService;
use FriendsOfTYPO3\Kickstarter\Service\Creator\PluginCreatorService;
use FriendsOfTYPO3\Kickstarter\Service\Creator\RepositoryCreatorService;
use FriendsOfTYPO3\Kickstarter\Service\Creator\TableCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

readonly class BuildExtensionService
{
    use ExtensionInformationTrait;

    public function __construct(
        private ExtensionCreatorService $extensionCreatorService,
        private PluginCreatorService $pluginCreatorService,
        private ControllerCreatorService $controllerCreatorService,
        private RepositoryCreatorService $repositoryCreatorService,
        private TableCreatorService $tableCreatorService,
        private ModelCreatorService $modelCreatorService,
    ) {}

    public function build(Graph $graph): ResponseInterface
    {
        if (!$this->validate($graph)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Graph is missing an extension node',
            ]);
        }

        $this->generateExtensionFiles($graph);

        return new JsonResponse([
            'status' => 'ok',
            'message' => 'Extension was created successfully',
        ]);
    }

    private function validate(Graph $graph): bool
    {
        // Extension node is a must-have
        return $graph->getExtensionNode() instanceof ExtensionNode;
    }

    private function generateExtensionFiles(Graph $graph): void
    {
        $extensionInformation = $this->getExtensionInformation($graph);
        $this->extensionCreatorService->create($extensionInformation, new ServicesConfigInformation($extensionInformation));
        $this->createPlugins($graph);
        $this->createControllers($graph);
        $this->createRepositories($graph);
        $this->createTcaTables($graph);
        $this->createModels($graph);
    }

    private function getExtensionInformation(Graph $graph): ExtensionInformation
    {
        $extensionNode = $graph->getExtensionNode();
        $authorNode = $extensionNode->getAuthorNodes()->current();

        return new ExtensionInformation(
            $extensionNode->getExtensionKey(),
            $extensionNode->getComposerName(),
            $extensionNode->getTitle(),
            $extensionNode->getDescription(),
            '0.0.1',
            'plugin',
            'stable',
            $authorNode instanceof AuthorNode ? $authorNode->getAuthorName() : '',
            $authorNode instanceof AuthorNode ? $authorNode->getAuthorEmail() : '',
            $authorNode instanceof AuthorNode ? $authorNode->getAuthorCompany() : '',
            $extensionNode->getNamespaceForAutoload(),
            $this->getExtensionPath($extensionNode->getExtensionKey())
        );
    }

    private function createPlugins(Graph $graph): void
    {
        $extensionNode = $graph->getExtensionNode();
        foreach ($extensionNode->getExtbasePluginNodes() as $extbasePluginNode) {
            $referencedControllerActions = [];
            foreach ($extbasePluginNode->getControllerNodes() as $controllerNode) {
                $controllerActionNames = [];
                foreach ($controllerNode->getControllerActionNodes() as $controllerActionNode) {
                    $controllerActionNames[] = $controllerActionNode->getActionName();
                }
                $referencedControllerActions[$controllerNode->getControllerName()]['cached'] = $controllerActionNames;

                $uncachedControllerActionNames = [];
                foreach ($controllerNode->getUncachedControllerActionNodes() as $uncachedControllerActionNode) {
                    $uncachedControllerActionNames[] = $uncachedControllerActionNode->getActionName();
                }
                $referencedControllerActions[$controllerNode->getControllerName()]['uncached'] = $uncachedControllerActionNames;
            }

            $this->pluginCreatorService->create(new PluginInformation(
                $this->getExtensionInformation($graph),
                true,
                $extbasePluginNode->getPluginName(),
                $extbasePluginNode->getPluginName(),
                '',
                $referencedControllerActions
            ));
        }
    }

    private function createControllers(Graph $graph): void
    {
        $extensionNode = $graph->getExtensionNode();
        foreach ($extensionNode->getExtbaseControllerNodes() as $extbaseControllerNode) {
            $actionNames = [];
            foreach ($extbaseControllerNode->getControllerActionNodes() as $actionNode) {
                $actionNames[] = $actionNode->getActionName();
            }

            $this->controllerCreatorService->create(new ControllerInformation(
                $this->getExtensionInformation($graph),
                true,
                $extbaseControllerNode->getControllerName(),
                $actionNames,
            ));
        }
    }

    private function createRepositories(Graph $graph): void
    {
        $extensionNode = $graph->getExtensionNode();
        foreach ($extensionNode->getExtbaseRepositoryNodes() as $extbaseRepositoryNode) {
            $this->repositoryCreatorService->create(new RepositoryInformation(
                $this->getExtensionInformation($graph),
                $extbaseRepositoryNode->getRepositoryName(),
            ));
        }
    }

    private function createTcaTables(Graph $graph): void
    {
        $extensionNode = $graph->getExtensionNode();
        foreach ($extensionNode->getTableNodes() as $tableNode) {
            $columns = [];
            foreach ($tableNode->getColumnNodes() as $columnNode) {
                $columns[$columnNode->getColumnName()] = [
                    'exclude' => true,
                    'label' => $columnNode->getLabel(),
                    'config' => TcaFieldType::from($columnNode->getColumnType())->exampleTca(),
                ];
            }

            $this->tableCreatorService->create(new TableInformation(
                $this->getExtensionInformation($graph),
                $tableNode->getTableName(),
                $tableNode->getTitle(),
                $tableNode->getLabel(),
                $columns,
            ));
        }
    }

    private function createModels(Graph $graph): void
    {
        $extensionNode = $graph->getExtensionNode();
        foreach ($extensionNode->getExtbaseRepositoryNodes() as $repositoryNode) {
            $properties = [];
            foreach ($repositoryNode->getTableNode()->getModelProperties() as $propertyNode) {
                $properties[$propertyNode->getColumnName()] = [
                    'propertyName' => $propertyNode->getPropertyName(),
                    'dataType' => $propertyNode->getPropertyDataType(),
                ];
            }

            $this->modelCreatorService->create(new ModelInformation(
                $this->getExtensionInformation($graph),
                $repositoryNode->getModelName(),
                $repositoryNode->getTableName(),
                true,
                $properties,
            ));
        }
    }
}
