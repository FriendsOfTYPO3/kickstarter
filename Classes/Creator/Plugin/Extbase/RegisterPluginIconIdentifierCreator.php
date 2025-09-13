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
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ReturnStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/**
 * Registers the plugin icon into Icons.php
 */
class RegisterPluginIconIdentifierCreator implements ExtbasePluginCreatorInterface
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
        $targetFile = $pluginInformation->getConfigurationPath() . 'Icons.php';
        $fileStructure = $this->buildFileStructure($targetFile);

        if (!is_file($targetFile)) {
            $fileStructure->addReturnStructure(new ReturnStructure(new Return_(new Array_())));
        }

        $fileStructure->addUseStructure(new UseStructure(
            $this->builderFactory->use(SvgIconProvider::class)->getNode()
        ));

        if (!$this->hasArrayItemWithPluginIcon($fileStructure, $pluginInformation)) {
            $returnNode = $this->getReturnNode($fileStructure);
            if (($arrayNode = $returnNode->expr)
                && $arrayNode instanceof Array_
            ) {
                $arrayNode->items = $this->getNewPluginIcons($arrayNode, $pluginInformation)->items;
            }
        }

        $this->fileManager->createOrModifyFile($targetFile, $fileStructure->getFileContents(), $pluginInformation->getCreatorInformation());
    }

    private function getNewPluginIcons(Array_ $existingIcons, PluginInformation $pluginInformation): Array_
    {
        $existingIcons->items[] = new ArrayItem(
            $this->builderFactory->val([
                'provider' => $this->builderFactory->classConstFetch('SvgIconProvider', 'class'),
                'source' => 'EXT:' . $pluginInformation->getExtensionInformation()->getExtensionKey() . '/Resources/Public/Icons/Plugin.svg',
            ]),
            $this->builderFactory->val($pluginInformation->getPluginIconIdentifier())
        );

        return $existingIcons;
    }

    private function getReturnNode(FileStructure $fileStructure): ?Return_
    {
        $nodeFinder = new NodeFinder();
        $returnNode = $nodeFinder->findFirst($fileStructure->getReturnStructures()->getStmts(), static fn(Node $node): bool => $node instanceof Return_);

        return $returnNode instanceof Return_ ? $returnNode : null;
    }

    private function hasArrayItemWithPluginIcon(FileStructure $fileStructure, PluginInformation $pluginInformation): bool
    {
        $nodeFinder = new NodeFinder();
        $iconIdentifierNode = $nodeFinder->findFirst($fileStructure->getReturnStructures()->getStmts(), static fn(Node $node): bool => $node instanceof ArrayItem
            && $node->key instanceof String_
            && $node->key->value === $pluginInformation->getPluginIconIdentifier());

        return $iconIdentifierNode instanceof ArrayItem;
    }
}
