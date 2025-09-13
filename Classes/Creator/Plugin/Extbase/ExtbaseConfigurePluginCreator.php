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
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ExpressionStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/**
 * Configures the Extbase plugin in the ext_localconf.php
 */
class ExtbaseConfigurePluginCreator implements ExtbasePluginCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
        $this->builderFactory = new BuilderFactory();
    }

    public function create(PluginInformation $pluginInformation): void
    {
        $targetFile = $pluginInformation->getExtensionInformation()->getExtensionPath() . 'ext_localconf.php';
        $fileStructure = $this->buildFileStructure($targetFile);

        if (!is_file($targetFile)) {
            $fileStructure->addDeclareStructure(new DeclareStructure($this->nodeFactory->createDeclareStrictTypes()));
        }

        $fileStructure->addUseStructure(new UseStructure(
            $this->builderFactory->use(ExtensionUtility::class)->getNode()
        ));

        foreach ($pluginInformation->getReferencedControllerNames() as $controllerName) {
            $fileStructure->addUseStructure(new UseStructure(
                $this->builderFactory->use($pluginInformation->getNamespaceForControllerName($controllerName))->getNode()
            ));
        }

        if (($staticCall = $this->getStaticCallForConfigurePlugin($fileStructure, $pluginInformation)) instanceof StaticCall) {
            if (($cachedControllerActions = $staticCall->args[2]->value) && $cachedControllerActions instanceof Array_) {
                $this->addMissingControllerAndActions($cachedControllerActions, $this->getReferencedControllerActions($pluginInformation, true));
            }
            if (($unCachedControllerActions = $staticCall->args[3]->value) && $unCachedControllerActions instanceof Array_) {
                $this->addMissingControllerAndActions($unCachedControllerActions, $this->getReferencedControllerActions($pluginInformation, false));
            }
        } else {
            $fileStructure->addExpressionStructure(new ExpressionStructure(
                $this->getExpressionForConfigurePlugin($pluginInformation)
            ));
        }

        $this->fileManager->createOrModifyFile($targetFile, $fileStructure->getFileContents(), $pluginInformation->getCreatorInformation());
    }

    /**
     * @param array|ArrayItem[] $newControllersWithActions
     */
    private function addMissingControllerAndActions(Array_ $existingControllerActions, array $newControllersWithActions): void
    {
        $nodeFinder = new NodeFinder();

        foreach ($newControllersWithActions as $newControllerWithActions) {
            if (!$newControllerWithActions->key instanceof ClassConstFetch) {
                continue;
            }

            $controllerClassname = $newControllerWithActions->key->class->toString();
            $existingControllerActionNode = $nodeFinder->findFirst($existingControllerActions, static fn(Node $node): bool => $node instanceof ArrayItem
                && $node->key instanceof ClassConstFetch
                && $node->key->class->toString() === $controllerClassname);

            if ($existingControllerActionNode instanceof ArrayItem) {
                if ($existingControllerActionNode->value instanceof String_
                    && $newControllerWithActions->value instanceof String_
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
    ): ?StaticCall {
        $nodeFinder = new NodeFinder();
        $matchedNode = $nodeFinder->findFirst($fileStructure->getExpressionStructures()->getStmts(), static fn(Node $node): bool => $node instanceof StaticCall
            && $node->class->toString() === 'ExtensionUtility'
            && $node->name->toString() === 'configurePlugin'
            && isset($node->args[0], $node->args[1])
            && $node->args[0] instanceof Arg
            && (($extensionNameNode = $node->args[0]) instanceof Arg)
            && $extensionNameNode->value instanceof String_
            && $extensionNameNode->value->value === $pluginInformation->getExtensionInformation()->getExtensionName()
            && ($pluginNameNode = $node->args[1])
            && $pluginNameNode->value instanceof String_
            && $pluginNameNode->value->value === $pluginInformation->getPluginName());

        return $matchedNode instanceof StaticCall ? $matchedNode : null;
    }

    private function getExpressionForConfigurePlugin(PluginInformation $pluginInformation): Expression
    {
        return new Expression($this->builderFactory->staticCall(
            'ExtensionUtility',
            'configurePlugin',
            [
                $pluginInformation->getExtensionInformation()->getExtensionName(),
                $pluginInformation->getPluginName(),
                new Array_($this->getReferencedControllerActions($pluginInformation, true)),
                new Array_($this->getReferencedControllerActions($pluginInformation, false)),
                new ClassConstFetch(
                    new Name('ExtensionUtility'),
                    'PLUGIN_TYPE_CONTENT_ELEMENT'
                ),
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
