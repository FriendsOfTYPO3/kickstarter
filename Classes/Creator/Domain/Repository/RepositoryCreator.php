<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Creator\Domain\Repository;

use PhpParser\BuilderFactory;
use StefanFroemken\ExtKickstarter\Creator\FileManager;
use StefanFroemken\ExtKickstarter\Information\RepositoryInformation;
use StefanFroemken\ExtKickstarter\PhpParser\NodeFactory;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\ClassStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\DeclareStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\FileStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\NamespaceStructure;
use StefanFroemken\ExtKickstarter\PhpParser\Structure\UseStructure;
use StefanFroemken\ExtKickstarter\Traits\FileStructureBuilderTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RepositoryCreator implements RepositoryCreatorInterface
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

    public function create(RepositoryInformation $repositoryInformation): void
    {
        GeneralUtility::mkdir_deep($repositoryInformation->getRepositoryPath());

        $repositoryFilePath = $repositoryInformation->getRepositoryFilePath();
        $fileStructure = $this->buildFileStructure($repositoryFilePath);

        if (is_file($repositoryFilePath)) {
            $repositoryInformation->getCreatorInformation()->fileExists(
                $repositoryFilePath,
                sprintf(
                    'Repositories can only be created, not modified. The file %s already exists and cannot be overridden. ',
                    $repositoryInformation->getRepositoryFilename()
                )
            );
            return;
        }
        $this->addClassNodes($fileStructure, $repositoryInformation);
        $this->fileManager->createFile($repositoryFilePath, $fileStructure->getFileContents(), $repositoryInformation->getCreatorInformation());
    }

    private function addClassNodes(FileStructure $fileStructure, RepositoryInformation $repositoryInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\Persistence\Repository'))
        );
        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $repositoryInformation->getNamespace(),
                $repositoryInformation->getExtensionInformation(),
            ))
        );
        $fileStructure->addClassStructure(
            new ClassStructure(
                $this->builderFactory
                    ->class($repositoryInformation->getRepositoryClassName())
                    ->extend('Repository')
                    ->makeFinal()
                    ->getNode(),
            )
        );
    }
}
