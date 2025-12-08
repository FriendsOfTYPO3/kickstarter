<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Extension;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ExpressionStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Stmt\Expression;

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
                    'typo3' => '14.0.0-14.3.99',
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
