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

        if ($modelInformation->isAbstractEntity()) {
            $fileStructure->addUseStructure(
                new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\DomainObject\AbstractEntity'))
            );
            $fileStructure->addClassStructure(
                new ClassStructure(
                    $this->builderFactory
                        ->class($modelInformation->getModelClassName())
                        ->extend('AbstractEntity')
                        ->makeFinal()
                        ->getNode(),
                )
            );
        } else {
            $fileStructure->addUseStructure(
                new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\DomainObject\AbstractValueObject'))
            );
            $fileStructure->addClassStructure(
                new ClassStructure(
                    $this->builderFactory
                        ->class($modelInformation->getModelClassName())
                        ->extend('AbstractValueObject')
                        ->makeFinal()
                        ->getNode(),
                )
            );
        }

        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $modelInformation->getNamespace(),
                $modelInformation->getExtensionInformation(),
            ))
        );

        $initializableProps = [];

        foreach ($modelInformation->getProperties() as $property) {
            if (($property['initializeObject'] ?? null) === true) {
                $initializableProps[] = $property;
            }
            if ($property['dataType'] === ObjectStorage::class) {
                $fileStructure->addUseStructure(
                    new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\Persistence\ObjectStorage'))
                );
                $property['dataType'] = 'ObjectStorage';
            }

            if ($property['dataType'] === \DateTime::class) {
                $fileStructure->addUseStructure(
                    new UseStructure($this->nodeFactory->createUseImport('DateTime'))
                );
                $property['dataType'] = 'DateTime';
            }

            $propertyBuilder = $this->builderFactory
                ->property($property['propertyName'])
                ->makeProtected()
                ->setType($property['dataType']);

            if (array_key_exists('defaultValue', $property)) {
                $propertyBuilder->setDefault(
                    $this->nodeFactory->createValue($property['defaultValue'])
                );
            }
            $fileStructure->addPropertyStructure(new PropertyStructure(
                $propertyBuilder->getNode()
            ));
            $fileStructure->addMethodStructure(new MethodStructure(
                $this->builderFactory
                    ->method('get' . ucfirst($property['propertyName']))
                    ->makePublic()
                    ->setReturnType($property['dataType'])
                    ->addStmt(new Return_(
                        $this->builderFactory->propertyFetch($this->builderFactory->var('this'), $property['propertyName'])
                    ))
                    ->getNode()
            ));
            $fileStructure->addMethodStructure(new MethodStructure(
                $this->builderFactory
                    ->method('set' . ucfirst($property['propertyName']))
                    ->makePublic()
                    ->setReturnType('void')
                    ->addParam($this->builderFactory->param($property['propertyName'])->setType($property['dataType']))
                    ->addStmt(new Assign(
                        $this->builderFactory->propertyFetch($this->builderFactory->var('this'), $property['propertyName']),
                        $this->builderFactory->var($property['propertyName'])
                    ))
                    ->getNode()
            ));
        }
        if (!empty($initializableProps)) {
            $this->addInitializeObjectMethod($fileStructure, $initializableProps);
        }
    }

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

        $initStmts = [];
        foreach ($initializableProps as $initProp) {
            $type = $initProp['dataType'];
            $name = $initProp['propertyName'];

            $expr = match ($type) {
                ObjectStorage::class => new \PhpParser\Node\Expr\New_(
                    new \PhpParser\Node\Name('ObjectStorage')
                ),
                'DateTime' => new \PhpParser\Node\Expr\New_(
                    new \PhpParser\Node\Name('DateTime')
                ),
                default => null,
            };

            if ($expr !== null) {
                $initStmts[] = new Assign(
                    $this->builderFactory->propertyFetch(
                        $this->builderFactory->var('this'),
                        $name
                    ),
                    $expr
                );
            }
        }

        $fileStructure->addMethodStructure(new MethodStructure(
            $this->builderFactory
                ->method('initializeObject')
                ->makePublic()
                ->setReturnType('void')
                ->addStmts($initStmts)
                ->getNode()
        ));
    }
}
