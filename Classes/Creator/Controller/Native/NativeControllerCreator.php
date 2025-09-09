<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Controller\Native;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\ControllerInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\MethodStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NativeControllerCreator implements NativeControllerCreatorInterface
{
    use FileStructureBuilderTrait;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager
    ) {}

    public function create(ControllerInformation $controllerInformation): void
    {
        GeneralUtility::mkdir_deep($controllerInformation->getControllerPath());

        $controllerFile = $controllerInformation->getControllerFilePath();
        $fileStructure = $this->buildFileStructure($controllerFile);

        // Add all methods. "->add*" will NOT overwrite existing action methods
        foreach ($controllerInformation->getActionMethodNames() as $actionMethodName) {
            $fileStructure->addMethodStructure(
                new MethodStructure($this->nodeFactory->createControllerActionMethod($actionMethodName))
            );
        }

        if (is_file($controllerFile)) {
            $this->fileManager->modifyFile($controllerFile, $fileStructure->getFileContents(), $controllerInformation->getCreatorInformation());
            return;
        }
        $this->addClassNodes($fileStructure, $controllerInformation);
        $this->fileManager->createFile($controllerFile, $fileStructure->getFileContents(), $controllerInformation->getCreatorInformation());

    }

    private function addClassNodes(FileStructure $fileStructure, ControllerInformation $controllerInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );
        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $controllerInformation->getNamespace(),
                $controllerInformation->getExtensionInformation(),
            ))
        );
        $fileStructure->addClassStructure(
            new ClassStructure($this->nodeFactory->createClass($controllerInformation->getControllerName()))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('Psr\Http\Message\ResponseInterface'))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Core\Http\HtmlResponse'))
        );
    }
}
