<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Plugin\Extbase;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\NodeFinder;
use FriendsOfTYPO3\Kickstarter\Information\PluginInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ExpressionStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtbaseConfigurePluginCreator implements ExtbasePluginCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    private NodeFactory $nodeFactory;

    public function __construct(NodeFactory $nodeFactory)
    {
        $this->builderFactory = new BuilderFactory();
        $this->nodeFactory = $nodeFactory;
    }

    public function create(PluginInformation $pluginInformation): void
    {
        $targetFile = $pluginInformation->getExtensionInformation()->getExtensionPath() . 'ext_localconf.php';
        $fileStructure = $this->buildFileStructure($targetFile);

        if (!is_file($targetFile)) {
            $fileStructure->addDeclareStructure(new DeclareStructure($this->nodeFactory->createDeclareStrictTypes()));
        }

        $fileStructure->addUseStructure(new UseStructure(
            $this->builderFactory->use('TYPO3\CMS\Extbase\Utility\ExtensionUtility')->getNode()
        ));

        foreach ($pluginInformation->getReferencedControllerNames() as $controllerName) {
            $fileStructure->addUseStructure(new UseStructure(
                $this->builderFactory->use($pluginInformation->getNamespaceForControllerName($controllerName))->getNode()
            ));
        }

        if ($staticCall = $this->getStaticCallForConfigurePlugin($fileStructure, $pluginInformation)) {
            if (($cachedControllerActions = $staticCall->args[2]->value) && $cachedControllerActions instanceof Node\Expr\Array_) {
                $this->addMissingControllerAndActions($cachedControllerActions, $this->getReferencedControllerActions($pluginInformation, true));
            }
            if (($unCachedControllerActions = $staticCall->args[3]->value) && $unCachedControllerActions instanceof Node\Expr\Array_) {
                $this->addMissingControllerAndActions($unCachedControllerActions, $this->getReferencedControllerActions($pluginInformation, false));
            }
        } else {
            $fileStructure->addExpressionStructure(new ExpressionStructure(
                $this->getExpressionForConfigurePlugin($pluginInformation)
            ));
        }

        file_put_contents($targetFile, $fileStructure->getFileContents());
    }

    /**
     * @param array|ArrayItem[] $newControllersWithActions
     */
    private function addMissingControllerAndActions(Node\Expr\Array_ $existingControllerActions, array $newControllersWithActions): void
    {
        $nodeFinder = new NodeFinder();

        foreach ($newControllersWithActions as $newControllerWithActions) {
            if (!$newControllerWithActions->key instanceof Node\Expr\ClassConstFetch) {
                continue;
            }

            $controllerClassname = $newControllerWithActions->key->class->toString();
            $existingControllerActionNode = $nodeFinder->findFirst($existingControllerActions, static function (Node $node) use ($controllerClassname): bool {
                return $node instanceof ArrayItem
                    && $node->key instanceof Node\Expr\ClassConstFetch
                    && $node->key->class->toString() === $controllerClassname;
            });

            if ($existingControllerActionNode instanceof ArrayItem) {
                if ($existingControllerActionNode->value instanceof Node\Scalar\String_
                    && $newControllerWithActions->value instanceof Node\Scalar\String_
                ) {
                    $existingActionNames = GeneralUtility::trimExplode(',', $existingControllerActionNode->value->value, true);
                    $newActionNames = GeneralUtility::trimExplode(',', $newControllerWithActions->value->value, true);
                    $existingControllerActionNode->value->value = implode(', ', array_unique(array_merge($existingActionNames, $newActionNames)));
                }
            } else {
                $existingControllerActions->items[] = $newControllerWithActions;
            }
        }
    }

    private function getStaticCallForConfigurePlugin(
        FileStructure $fileStructure,
        PluginInformation $pluginInformation
    ): ?Node\Expr\StaticCall {
        $nodeFinder = new NodeFinder();
        $matchedNode = $nodeFinder->findFirst($fileStructure->getExpressionStructures()->getStmts(), static function (Node $node) use ($pluginInformation): bool {
            return $node instanceof Node\Expr\StaticCall
                && $node->class->toString() === 'ExtensionUtility'
                && $node->name->toString() === 'configurePlugin'
                && isset($node->args[0], $node->args[1])
                && $node->args[0] instanceof Node\Arg
                && ($extensionNameNode = $node->args[0])
                && $extensionNameNode->value instanceof Node\Scalar\String_
                && $extensionNameNode->value->value === $pluginInformation->getExtensionInformation()->getExtensionName()
                && ($pluginNameNode = $node->args[1])
                && $pluginNameNode->value instanceof Node\Scalar\String_
                && $pluginNameNode->value->value === $pluginInformation->getPluginName();
        });

        return $matchedNode instanceof Node\Expr\StaticCall ? $matchedNode : null;
    }

    private function getExpressionForConfigurePlugin(PluginInformation $pluginInformation): Node\Stmt\Expression
    {
        if ($pluginInformation->getPluginType() === 'plugin') {
            $pluginTypeNode = new Node\Expr\ClassConstFetch(
                new Node\Name('ExtensionUtility'),
                'PLUGIN_TYPE_PLUGIN'
            );
        } else {
            $pluginTypeNode = new Node\Expr\ClassConstFetch(
                new Node\Name('ExtensionUtility'),
                'PLUGIN_TYPE_CONTENT_ELEMENT'
            );
        }

        return new Node\Stmt\Expression($this->builderFactory->staticCall(
            'ExtensionUtility',
            'configurePlugin',
            [
                $pluginInformation->getExtensionInformation()->getExtensionName(),
                $pluginInformation->getPluginName(),
                new Node\Expr\Array_($this->getReferencedControllerActions($pluginInformation, true)),
                new Node\Expr\Array_($this->getReferencedControllerActions($pluginInformation, false)),
                $pluginTypeNode,
            ]
        ));
    }

    /**
     * @return array|ArrayItem[]
     */
    private function getReferencedControllerActions(PluginInformation $pluginInformation, bool $cached): array
    {
        $referencedControllerActions = [];
        foreach ($pluginInformation->getReferencedControllerActions($cached) as $controllerClassname => $controllerActions) {
            $referencedControllerActions[] = new ArrayItem(
                $this->builderFactory->val($controllerActions),
                $this->builderFactory->classConstFetch($controllerClassname, 'class'),
            );
        }

        return $referencedControllerActions;
    }
}
