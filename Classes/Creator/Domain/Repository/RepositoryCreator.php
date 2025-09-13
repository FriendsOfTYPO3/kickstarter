<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Domain\Repository;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\RepositoryInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class RepositoryCreator implements RepositoryCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
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
            new UseStructure($this->nodeFactory->createUseImport(Repository::class))
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
