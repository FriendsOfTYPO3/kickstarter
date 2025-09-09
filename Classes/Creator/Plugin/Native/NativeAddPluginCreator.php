<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Plugin\Native;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\PluginInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ExpressionStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node\Stmt\Expression;

class NativeAddPluginCreator implements NativePluginCreatorInterface
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
            $this->builderFactory->use('TYPO3\CMS\Core\Utility\ExtensionManagementUtility')->getNode()
        ));
        $fileStructure->addExpressionStructure(new ExpressionStructure(
            $this->getExpressionForAddPlugin($pluginInformation)
        ));

        $this->fileManager->createOrModifyFile($targetFile, $fileStructure->getFileContents(), $pluginInformation->getCreatorInformation());
    }

    private function getExpressionForAddPlugin(PluginInformation $pluginInformation): Expression
    {
        $pluginIconPath = sprintf(
            'EXT:%s/Resources/Public/Icons/Extension.svg',
            $pluginInformation->getExtensionInformation()->getExtensionKey(),
        );

        return new Expression($this->builderFactory->staticCall(
            'ExtensionManagementUtility',
            'addPlugin',
            [
                [
                    'label' => $pluginInformation->getPluginLabel(),
                    'value' => $pluginInformation->getPluginNamespace(),
                    'group' => 'plugins',
                    'icon' => $pluginIconPath,
                    'description' => 'Please update the description',
                ],
                'CType',
                $pluginInformation->getExtensionInformation()->getExtensionKey(),
            ]
        ));
    }
}
