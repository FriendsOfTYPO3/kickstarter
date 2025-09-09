<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Middleware;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\MiddleWareInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\MethodStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MiddlewareCreator implements MiddlewareCreatorInterface
{
    use FileStructureBuilderTrait;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager
    ) {}

    public function create(MiddlewareInformation $middlewareInformation): void
    {
        GeneralUtility::mkdir_deep($middlewareInformation->getPath());

        $filePath = $middlewareInformation->getFilePath();
        $fileStructure = $this->buildFileStructure($filePath);

        if (is_file($filePath)) {
            $middlewareInformation->getCreatorInformation()->fileExists(
                $filePath,
                sprintf(
                    'Middleware classes can only be created, not modified. The file %s already exists and cannot be overridden. ',
                    $middlewareInformation->getFilename()
                )
            );
            return;
        }
        $this->addClassNodes($fileStructure, $middlewareInformation);
        $this->fileManager->createFile($filePath, $fileStructure->getFileContents(), $middlewareInformation->getCreatorInformation());
    }

    private function addClassNodes(FileStructure $fileStructure, MiddlewareInformation $middlewareInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );
        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $middlewareInformation->getNamespace(),
                $middlewareInformation->getExtensionInformation(),
            ))
        );
        $fileStructure->addMethodStructure(
            new MethodStructure($this->nodeFactory->createMiddlewareProcessMethod())
        );

        $fileStructure->addClassStructure(
            new ClassStructure($this->nodeFactory->createMiddlewareClass($middlewareInformation->getClassName()))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Psr\Http\Message\ResponseInterface'))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Psr\Http\Server\RequestHandlerInterface'))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Psr\Http\Server\MiddlewareInterface'))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Psr\Http\Message\ServerRequestInterface'))
        );
    }
}
