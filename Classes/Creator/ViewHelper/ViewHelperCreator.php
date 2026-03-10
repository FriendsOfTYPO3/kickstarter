<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\ViewHelper;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\ViewHelperInformation;
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ViewHelperCreator implements ViewHelperCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
        $this->builderFactory = new BuilderFactory();
    }

    public function create(ViewHelperInformation $viewHelperInformation): void
    {
        GeneralUtility::mkdir_deep($viewHelperInformation->getPath());

        $filePath = $viewHelperInformation->getPath() . $viewHelperInformation->getFilename();

        if (is_file($filePath)) {
            $viewHelperInformation->getCreatorInformation()->fileExists(
                $filePath,
                sprintf(
                    'ViewHelpers can only be created, not modified. The file %s already exists and cannot be overridden. ',
                    $filePath
                )
            );
            return;
        }
        $fileStructure = $this->buildFileStructure($filePath);
        $this->addClassNodes($fileStructure, $viewHelperInformation);
        $this->fileManager->createFile($filePath, $fileStructure->getFileContents(), $viewHelperInformation->getCreatorInformation());
    }

    private function addClassNodes(FileStructure $fileStructure, ViewHelperInformation $viewHelperInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );
        if ($viewHelperInformation->isTagBased()) {
            $this->createTagBasedViewHelper($fileStructure, $viewHelperInformation);
        } else {
            $this->createPlainViewHelper($fileStructure, $viewHelperInformation);
        }
    }

    private function createPlainViewHelper(FileStructure $fileStructure, ViewHelperInformation $viewHelperInformation): void
    {
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport(AbstractViewHelper::class))
        );
        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $viewHelperInformation->getNamespace(),
                $viewHelperInformation->getExtensionInformation(),
            ))
        );
        $fileStructure->addClassStructure(
            new ClassStructure(
                $this->builderFactory
                    ->class($viewHelperInformation->getClassname())
                    ->makeFinal()
                    ->extend('AbstractViewHelper')
                    ->getNode(),
            )
        );
        $fileStructure->addMethodStructure(
            new MethodStructure(
                $this->builderFactory
                    ->method('initializeArguments')
                    ->makePublic()
                    ->setReturnType('void')
                    ->getNode()
            )
        );
        $fileStructure->addMethodStructure(
            new MethodStructure(
                $this->builderFactory
                    ->method('render')
                    ->makePublic()
                    ->setReturnType('string')
                    ->addStmt(new Return_($this->builderFactory->val('ViewHelper ' . $viewHelperInformation->getClassname() . ' content. ')))
                    ->getNode()
            )
        );
    }

    private function createTagBasedViewHelper(FileStructure $fileStructure, ViewHelperInformation $viewHelperInformation): void
    {

        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport(AbstractTagBasedViewHelper::class))
        );
        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $viewHelperInformation->getNamespace(),
                $viewHelperInformation->getExtensionInformation(),
            ))
        );
        $fileStructure->addClassStructure(
            new ClassStructure(
                $this->builderFactory
                    ->class($viewHelperInformation->getClassname())
                    ->makeFinal()
                    ->extend('AbstractTagBasedViewHelper')
                    ->getNode(),
            )
        );
        $fileStructure->addMethodStructure(
            new MethodStructure(
                $this->builderFactory
                    ->method('initializeArguments')
                    ->makePublic()
                    ->setReturnType('void')
                    ->getNode()
            )
        );
        $fileStructure->addMethodStructure(
            new MethodStructure(
                $this->builderFactory
                    ->method('render')
                    ->makePublic()
                    ->setReturnType('string')
                    ->addStmt(new Return_($this->builderFactory->val('ViewHelper ' . $viewHelperInformation->getClassname() . ' content. ')))
                    ->getNode()
            )
        );
    }
}
