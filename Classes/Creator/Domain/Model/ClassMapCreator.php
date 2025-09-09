<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Domain\Model;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\ModelInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ReturnStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClassMapCreator implements DomainCreatorInterface
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

    public function create(ModelInformation $modelInformation): void
    {
        if ($modelInformation->isExpectedTableName()) {
            return;
        }

        GeneralUtility::mkdir_deep(dirname($modelInformation->getClassesFilePath()));

        $classesFilePath = $modelInformation->getClassesFilePath();
        $fileStructure = $this->buildFileStructure($classesFilePath);

        if (!is_file($classesFilePath)) {
            $this->addClassNodes($fileStructure);
        }

        /** @var ReturnStructure $returnStructure */
        $returnStructure = $fileStructure->getReturnStructures()->current();
        /** @var Array_ $arrayNode */
        $arrayNode = $returnStructure->getNode()->expr;

        $classMapEntry = $this->getClassMapEntry($arrayNode, $modelInformation);
        if ($classMapEntry !== null) {
            $modelInformation->getCreatorInformation()->fileModificationFailed(
                $classesFilePath,
                sprintf('A class map entry for class %s already exists. ', $modelInformation->getModelClassName())
            );
            return;
        }
        $this->createNewClassMap($fileStructure, $modelInformation, $arrayNode, $classesFilePath);
    }

    private function getClassMapEntry(Array_ $arrayNode, ModelInformation $modelInformation): ?ArrayItem
    {
        $expectedFqn = ltrim($modelInformation->getNamespace() . '\\' . $modelInformation->getModelClassName(), '\\');
        $expectedShort = $modelInformation->getModelClassName();

        foreach ($arrayNode->items as $arrayItem) {
            $key = $arrayItem->key;

            // Case 1: ClassConstFetch (e.g. Test::class or \Vendor\Test::class)
            if ($key instanceof ClassConstFetch && $key->name->toString() === 'class') {
                $nodeClass = $key->class;

                if ($nodeClass instanceof FullyQualified && $nodeClass->toString() === $expectedFqn) {
                    return $arrayItem;
                }

                if ($nodeClass instanceof Name && $nodeClass->toString() === $expectedShort) {
                    return $arrayItem;
                }
            }

            // Case 2: String_ key containing FQN (e.g. 'Test\Test\Domain\Model\Abc')
            if ($key instanceof String_ && trim($key->value, '\\') === $expectedFqn) {
                return $arrayItem;
            }
        }

        return null;
    }

    private function addClassNodes(FileStructure $fileStructure): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );
        $fileStructure->addReturnStructure(
            new ReturnStructure(
                new Return_(
                    $this->builderFactory->val([])
                )
            )
        );
    }

    private function createNewClassMap(FileStructure $fileStructure, ModelInformation $modelInformation, Array_ $arrayNode, string $classesFilePath): void
    {
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport(
                $modelInformation->getNamespace() . '\\' . $modelInformation->getModelClassName()
            ))
        );

        $arrayNode->items[] = new ArrayItem(
            $this->builderFactory->val([
                'tableName' => $modelInformation->getMappedTableName(),
            ]),
            $this->builderFactory->classConstFetch($modelInformation->getModelClassName(), 'class'),
        );
        $this->fileManager->createOrModifyFile($classesFilePath, $fileStructure->getFileContents(), $modelInformation->getCreatorInformation());
    }
}
