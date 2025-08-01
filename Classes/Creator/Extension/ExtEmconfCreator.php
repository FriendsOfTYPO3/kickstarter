<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Creator\Extension;

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Stmt\Expression;
use StefanFroemken\ExtKickstarter\Creator\FileManager;
use StefanFroemken\ExtKickstarter\Information\ExtensionInformation;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\ExpressionStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\FileStructure;
use StefanFroemken\ExtKickstarter\Traits\FileStructureBuilderTrait;

class ExtEmconfCreator implements ExtensionCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $factory;

    public function __construct(
        private readonly FileManager $fileManager,
    ) {
        $this->factory = new BuilderFactory();
    }

    public function create(ExtensionInformation $extensionInformation): void
    {
        $extEmconfFilePath = $extensionInformation->getExtensionPath() . 'ext_emconf.php';
        $fileStructure = $this->buildFileStructure($extEmconfFilePath);

        if (is_file($extEmconfFilePath)) {
            $extensionInformation->getCreatorInformation()->fileExists(
                $extEmconfFilePath
            );
            return;
        }
        $this->setExtEmconfConfiguration($fileStructure, $extensionInformation);

        $this->fileManager->createFile($extEmconfFilePath, $fileStructure->getFileContents(), $extensionInformation->getCreatorInformation());
    }

    private function setExtEmconfConfiguration(FileStructure $fileStructure, ExtensionInformation $configurator): void
    {
        $configuration = [
            'title' => $configurator->getTitle(),
            'description' => $configurator->getDescription(),
            'category' => $configurator->getCategory(),
            'state' => $configurator->getState(),
            'author' => $configurator->getAuthor(),
            'author_email' => $configurator->getAuthorEmail(),
            'author_company' => $configurator->getAuthorCompany(),
            'version' => $configurator->getVersion(),
            'constraints' => [
                'depends' => [
                    'typo3' => '13.4.0-13.4.99',
                ],
                'conflicts' => [],
                'suggests' => [],
            ],
        ];

        $fileStructure->addExpressionStructure(new ExpressionStructure(new Expression(new Assign(
            new ArrayDimFetch(
                $this->factory->var('EM_CONF'),
                $this->factory->var('_EXTKEY'),
            ),
            $this->factory->val($configuration),
        ))));
    }
}
