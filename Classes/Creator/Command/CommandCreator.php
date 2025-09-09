<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Command;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\CommandInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\MethodStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node\Stmt\Return_;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CommandCreator implements CommandCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
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
            new UseStructure($this->nodeFactory->createUseImport(AsCommand::class))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport(Command::class))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport(InputInterface::class))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport(OutputInterface::class))
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
