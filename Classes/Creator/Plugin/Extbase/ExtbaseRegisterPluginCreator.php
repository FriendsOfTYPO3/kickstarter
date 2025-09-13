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
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/**
 * Registers the Extbase plugin in the TCA/Overrides
 */
class ExtbaseRegisterPluginCreator implements ExtbasePluginCreatorInterface
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
        $overridesPath = sprintf(
            '/%s/%s/',
            trim($pluginInformation->getExtensionInformation()->getExtensionPath(), '/'),
            'Configuration/TCA/Overrides',
        );
        GeneralUtility::mkdir_deep($overridesPath);

        $targetFile = $overridesPath . 'tt_content.php';
        $fileStructure = $this->buildFileStructure($targetFile);

        if (!is_file($targetFile)) {
            $fileStructure->addDeclareStructure(new DeclareStructure($this->nodeFactory->createDeclareStrictTypes()));
        }

        $fileStructure->addUseStructure(new UseStructure(
            $this->builderFactory->use(ExtensionUtility::class)->getNode()
        ));

        if (!$this->getStaticCallForRegisterPlugin($fileStructure, $pluginInformation) instanceof StaticCall) {
            $fileStructure->addExpressionStructure(new ExpressionStructure(
                $this->getExpressionForRegisterPlugin($pluginInformation)
            ));
        }

        $this->fileManager->createOrModifyFile($targetFile, $fileStructure->getFileContents(), $pluginInformation->getCreatorInformation());
    }

    private function getStaticCallForRegisterPlugin(
        FileStructure $fileStructure,
        PluginInformation $pluginInformation
    ): ?StaticCall {
        $nodeFinder = new NodeFinder();
        $matchedNode = $nodeFinder->findFirst($fileStructure->getExpressionStructures()->getStmts(), static fn(Node $node): bool => $node instanceof StaticCall
            && $node->class->toString() === 'ExtensionUtility'
            && $node->name->toString() === 'registerPlugin'
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

    private function getExpressionForRegisterPlugin(PluginInformation $pluginInformation): Expression
    {
        return new Expression($this->builderFactory->staticCall(
            'ExtensionUtility',
            'registerPlugin',
            [
                $pluginInformation->getExtensionInformation()->getExtensionName(),
                $pluginInformation->getPluginName(),
                $pluginInformation->getPluginLabel(),
                $pluginInformation->getPluginIconIdentifier(),
                'plugins',
                $pluginInformation->getPluginDescription(),
            ]
        ));
    }
}
