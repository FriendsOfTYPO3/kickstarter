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
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\MethodStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\PropertyStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Stmt\Return_;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ModelCreator implements DomainCreatorInterface
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
        GeneralUtility::mkdir_deep($modelInformation->getModelPath());

        $modelFilePath = $modelInformation->getModelFilePath();
        $fileStructure = $this->buildFileStructure($modelFilePath);

        $this->addClassNodes($fileStructure, $modelInformation);
        $this->fileManager->createOrModifyFile($modelFilePath, $fileStructure->getFileContents(), $modelInformation->getCreatorInformation());
    }

    private function addClassNodes(FileStructure $fileStructure, ModelInformation $modelInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );

        $this->addClassDefinition($fileStructure, $modelInformation);
        $this->addNamespaceDefinition($fileStructure, $modelInformation);
        $this->addProperties($fileStructure, $modelInformation);
    }

    private function addClassDefinition(FileStructure $fileStructure, ModelInformation $modelInformation): void
    {
        $parentClass = $modelInformation->isAbstractEntity() ? 'AbstractEntity' : 'AbstractValueObject';
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\DomainObject\\' . $parentClass))
        );
        $fileStructure->addClassStructure(
            new ClassStructure(
                $this->builderFactory
                    ->class($modelInformation->getModelClassName())
                    ->extend($parentClass)
                    ->makeFinal()
                    ->getNode(),
            )
        );
    }

    private function addNamespaceDefinition(FileStructure $fileStructure, ModelInformation $modelInformation): void
    {
        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $modelInformation->getNamespace(),
                $modelInformation->getExtensionInformation(),
            ))
        );
    }

    private function addProperties(FileStructure $fileStructure, ModelInformation $modelInformation): void
    {
        $initializableProps = [];

        foreach ($modelInformation->getProperties() as $property) {
            $normalizedProperty = $this->normalizeProperty($fileStructure, $property);
            if (($normalizedProperty['initializeObject'] ?? null) === true) {
                $initializableProps[] = $normalizedProperty;
            }

            $this->addPropertyStructures($fileStructure, $normalizedProperty);
        }

        if ($initializableProps !== []) {
            $this->addInitializeObjectMethod($fileStructure, $initializableProps);
        }
    }

    /**
     * @param array<string, mixed> $property
     * @return array<string, mixed>
     */
    private function normalizeProperty(FileStructure $fileStructure, array $property): array
    {
        if ($property['dataType'] === ObjectStorage::class) {
            $fileStructure->addUseStructure(
                new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\Persistence\ObjectStorage'))
            );
            $property['dataType'] = 'ObjectStorage';
        }

        if ($property['dataType'] === \DateTime::class
            || $property['dataType'] === 'DateTime'
            || $property['dataType'] === '?DateTime'
        ) {
            $fileStructure->addUseStructure(
                new UseStructure($this->nodeFactory->createUseImport('DateTime'))
            );
            $property['dataType'] = '?DateTime';
            if (!array_key_exists('defaultValue', $property)) {
                $property['defaultValue'] = null;
            }
        }

        return $property;
    }

    /**
     * @param array<string, mixed> $property
     */
    private function addPropertyStructures(FileStructure $fileStructure, array $property): void
    {
        $propertyBuilder = $this->builderFactory
            ->property($property['propertyName'])
            ->makeProtected()
            ->setType($property['dataType']);

        if (array_key_exists('defaultValue', $property)) {
            $propertyBuilder->setDefault(
                $this->nodeFactory->createValue($property['defaultValue'])
            );
        }

        $fileStructure->addPropertyStructure(new PropertyStructure($propertyBuilder->getNode()));
        $this->addGetterMethod($fileStructure, $property['propertyName'], $property['dataType']);
        $this->addSetterMethod($fileStructure, $property['propertyName'], $property['dataType']);
    }

    private function addGetterMethod(FileStructure $fileStructure, string $propertyName, string $dataType): void
    {
        $fileStructure->addMethodStructure(new MethodStructure(
            $this->builderFactory
                ->method('get' . ucfirst($propertyName))
                ->makePublic()
                ->setReturnType($dataType)
                ->addStmt(new Return_(
                    $this->builderFactory->propertyFetch($this->builderFactory->var('this'), $propertyName)
                ))
                ->getNode()
        ));
    }

    private function addSetterMethod(FileStructure $fileStructure, string $propertyName, string $dataType): void
    {
        $fileStructure->addMethodStructure(new MethodStructure(
            $this->builderFactory
                ->method('set' . ucfirst($propertyName))
                ->makePublic()
                ->setReturnType('void')
                ->addParam($this->builderFactory->param($propertyName)->setType($dataType))
                ->addStmt(new Assign(
                    $this->builderFactory->propertyFetch($this->builderFactory->var('this'), $propertyName),
                    $this->builderFactory->var($propertyName)
                ))
                ->getNode()
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $initializableProps
     */
    public function addInitializeObjectMethod(FileStructure $fileStructure, array $initializableProps): void
    {
        $fileStructure->addMethodStructure(new MethodStructure(
            $this->builderFactory
                ->method('__construct')
                ->makePublic()
                ->addStmt(
                    new \PhpParser\Node\Expr\MethodCall(
                        new \PhpParser\Node\Expr\Variable('this'),
                        'initializeObject'
                    )
                )
                ->getNode()
        ));

        $initStmts = $this->buildInitializeStatements($initializableProps);

        $fileStructure->addMethodStructure(new MethodStructure(
            $this->builderFactory
                ->method('initializeObject')
                ->makePublic()
                ->setReturnType('void')
                ->addStmts($initStmts)
                ->getNode()
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $initializableProps
     * @return list<Assign>
     */
    private function buildInitializeStatements(array $initializableProps): array
    {
        $initStmts = [];
        foreach ($initializableProps as $initProp) {
            $expr = match ($initProp['dataType']) {
                ObjectStorage::class, 'ObjectStorage' => new \PhpParser\Node\Expr\New_(
                    new \PhpParser\Node\Name('ObjectStorage')
                ),
                'DateTime', \DateTime::class, '?DateTime' => new \PhpParser\Node\Expr\New_(
                    new \PhpParser\Node\Name('DateTime')
                ),
                default => null,
            };

            if ($expr !== null) {
                $initStmts[] = new Assign(
                    $this->builderFactory->propertyFetch(
                        $this->builderFactory->var('this'),
                        $initProp['propertyName']
                    ),
                    $expr
                );
            }
        }

        return $initStmts;
    }
}
