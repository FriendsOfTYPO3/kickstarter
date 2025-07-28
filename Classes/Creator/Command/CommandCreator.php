<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Creator\Command;

use PhpParser\BuilderFactory;
use PhpParser\Node\Stmt\Return_;
use StefanFroemken\ExtKickstarter\Creator\FileManager;
use StefanFroemken\ExtKickstarter\Information\CommandInformation;
use StefanFroemken\ExtKickstarter\PhpParser\NodeFactory;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\ClassStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\DeclareStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\FileStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\MethodStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\NamespaceStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\UseStructure;
use StefanFroemken\ExtKickstarter\Traits\FileStructureBuilderTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CommandCreator implements CommandCreatorInterface
{
    use FileStructureBuilderTrait;

    private NodeFactory $nodeFactory;

    private BuilderFactory $builderFactory;

    public function __construct(
        NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
        $this->nodeFactory = $nodeFactory;
        $this->builderFactory = new BuilderFactory();
    }

    public function create(CommandInformation $commandInformation): void
    {
        GeneralUtility::mkdir_deep($commandInformation->getCommandPath());

        $commandFilePath = $commandInformation->getCommandFilePath();
        $fileStructure = $this->buildFileStructure($commandFilePath);

        if (is_file($commandFilePath)) {
            $commandInformation->getCreatorInformation()->fileExists(
                $commandFilePath,
                sprintf(
                    'Commands can only be  created, not modified. The file %s already exists and cannot be overridden. ',
                    $commandInformation->getCommandClassName()
                )
            );
            return;
        }
        $this->addClassNodes($fileStructure, $commandInformation);
        $this->fileManager->createFile($commandFilePath, $fileStructure->getFileContents(), $commandInformation->getCreatorInformation());
    }

    private function addClassNodes(FileStructure $fileStructure, CommandInformation $commandInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Symfony\Component\Console\Attribute\AsCommand'))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Symfony\Component\Console\Command\Command'))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Symfony\Component\Console\Input\InputInterface'))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Symfony\Component\Console\Output\OutputInterface'))
        );
        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $commandInformation->getNamespace(),
                $commandInformation->getExtensionInformation(),
            ))
        );

        $commandPhpAttributes = [
            'name' => $commandInformation->getName(),
        ];
        if ($commandInformation->getDescription() !== '') {
            $commandPhpAttributes['description'] = $commandInformation->getDescription();
        }
        if ($commandInformation->getAliases() !== []) {
            $commandPhpAttributes['aliases'] = $commandInformation->getAliases();
        }

        $fileStructure->addClassStructure(
            new ClassStructure(
                $this->builderFactory
                    ->class($commandInformation->getCommandClassName())
                    ->addAttribute($this->builderFactory->attribute(
                        'AsCommand',
                        $commandPhpAttributes,
                    ))
                    ->makeFinal()
                    ->extend('Command')
                    ->getNode(),
            )
        );
        $fileStructure->addMethodStructure(
            new MethodStructure(
                $this->builderFactory
                    ->method('configure')
                    ->makeProtected()
                    ->setReturnType('void')
                    ->getNode()
            )
        );
        $fileStructure->addMethodStructure(
            new MethodStructure(
                $this->builderFactory
                    ->method('execute')
                    ->addParam($this->builderFactory->param('input')->setType('InputInterface'))
                    ->addParam($this->builderFactory->param('output')->setType('OutputInterface'))
                    ->makeProtected()
                    ->setReturnType('int')
                    ->addStmt(new Return_($this->builderFactory->val(
                        $this->builderFactory->classConstFetch('Command', 'SUCCESS'),
                    )))
                    ->getNode()
            )
        );
    }
}
