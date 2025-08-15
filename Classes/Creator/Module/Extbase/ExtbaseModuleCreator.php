<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Module\Extbase;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use FriendsOfTYPO3\Kickstarter\Information\ModuleInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ReturnStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtbaseModuleCreator implements ExtbaseModuleCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    )
    {
        $this->builderFactory = new BuilderFactory();
    }

    public function create(ModuleInformation $moduleInformation): void
    {
        $targetFile = $moduleInformation->getModuleFilePath();
        GeneralUtility::mkdir_deep(dirname($targetFile));
        $fileStructure = $this->buildFileStructure($targetFile);

        if (!is_file($targetFile)) {
            $fileStructure->addReturnStructure(new ReturnStructure(new Return_(new Array_())));
        }

        foreach ($moduleInformation->getReferencedControllerNames() as $controllerName) {
            $fileStructure->addUseStructure(new UseStructure(
                $this->builderFactory->use($moduleInformation->getNamespaceForControllerName($controllerName))->getNode()
            ));
        }

        if (!$this->hasArrayItemWithModuleIdentifier($fileStructure, $moduleInformation)) {
            $returnNode = $this->getReturnNode($fileStructure);
            if (($arrayNode = $returnNode->expr)
                && $arrayNode instanceof Array_
            ) {
                $arrayNode->items = $this->getNewModule($arrayNode, $moduleInformation)->items;
            }
        }

        $this->fileManager->createOrModifyFile($targetFile, $fileStructure->getFileContents(), $moduleInformation->getCreatorInformation());
    }

    private function getNewModule(Array_ $existingModules, ModuleInformation $moduleInformation): Array_
    {
        $newModuleArrayItem = new ArrayItem(
            $this->builderFactory->val([
                'parent' => $moduleInformation->getParent(),
                'position' => $moduleInformation->getPosition(),
                'access' => $moduleInformation->getAccess(),
                'workspaces' => $moduleInformation->getWorkspaces(),
                'labels' => $moduleInformation->getLabels(),
                'iconIdentifier' => $moduleInformation->getIconIdentifier(),
                'extensionName' => $moduleInformation->getExtensionName(),
                'controllerActions' => [],
            ]),
            $this->builderFactory->val($moduleInformation->getIdentifier()),
        );

        $nodeFinder = new NodeFinder();
        $controllerActionsPlaceholder = $nodeFinder->findFirst($newModuleArrayItem, static function (Node $node): bool {
            return $node instanceof ArrayItem
                && $node->key instanceof String_
                && $node->key->value === 'controllerActions';
        });

        if ($newModuleArrayItem->value instanceof Array_ && $controllerActionsPlaceholder instanceof ArrayItem) {
            $this->addControllerAndActions($controllerActionsPlaceholder, $this->getReferencedControllerActions($moduleInformation));
        }

        $existingModules->items[] = $newModuleArrayItem;

        return $existingModules;
    }

    /**
     * @param array|ArrayItem[] $newControllersWithActions
     */
    private function addControllerAndActions(ArrayItem $controllerActionsPlaceholder, array $newControllersWithActions): void
    {
        foreach ($newControllersWithActions as $newControllerWithActions) {
            if (!$newControllerWithActions->key instanceof ClassConstFetch) {
                continue;
            }

            if (!$controllerActionsPlaceholder->value instanceof Array_) {
                continue;
            }

            $controllerActionsPlaceholder->value->items[] = $newControllerWithActions;
        }
    }

    /**
     * @return array|ArrayItem[]
     */
    private function getReferencedControllerActions(ModuleInformation $moduleInformation): array
    {
        $referencedControllerActions = [];
        foreach ($moduleInformation->getReferencedControllerActions() as $controllerClassname => $controllerActions) {
            $referencedControllerActions[] = new ArrayItem(
                $this->builderFactory->val($controllerActions),
                $this->builderFactory->classConstFetch($controllerClassname, 'class'),
            );
        }

        return $referencedControllerActions;
    }

    private function getReturnNode(FileStructure $fileStructure): ?Return_
    {
        $nodeFinder = new NodeFinder();
        $returnNode = $nodeFinder->findFirst($fileStructure->getReturnStructures()->getStmts(), static function (Node $node): bool {
            return $node instanceof Return_;
        });

        return $returnNode instanceof Return_ ? $returnNode : null;
    }

    private function hasArrayItemWithModuleIdentifier(FileStructure $fileStructure, ModuleInformation $moduleInformation): bool
    {
        $nodeFinder = new NodeFinder();
        $moduleIdentifierNode = $nodeFinder->findFirst($fileStructure->getReturnStructures()->getStmts(), static function (Node $node) use ($moduleInformation): bool {
            return $node instanceof ArrayItem
                && $node->key instanceof String_
                && $node->key->value === $moduleInformation->getIdentifier();
        });

        return $moduleIdentifierNode instanceof ArrayItem;
    }
}
