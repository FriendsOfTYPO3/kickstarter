<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Creator\Plugin\Native;

use PhpParser\BuilderFactory;
use PhpParser\Node\Stmt\Expression;
use StefanFroemken\ExtKickstarter\Creator\FileManager;
use StefanFroemken\ExtKickstarter\Information\PluginInformation;
use StefanFroemken\ExtKickstarter\PhpParser\NodeFactory;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\DeclareStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\ExpressionStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\UseStructure;
use StefanFroemken\ExtKickstarter\Traits\FileStructureBuilderTrait;

class NativeAddPluginCreator implements NativePluginCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    private NodeFactory $nodeFactory;

    public function __construct(
        NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
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
