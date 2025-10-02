<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Property\TypeConverter;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\TypeConverterInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\MethodStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TypeConverterCreator implements TypeConverterCreatorInterface
{
    use FileStructureBuilderTrait;

    private readonly BuilderFactory $builderFactory;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
        $this->builderFactory = new BuilderFactory();
    }

    public function create(TypeConverterInformation $typeConverterInformation): void
    {
        GeneralUtility::mkdir_deep($typeConverterInformation->getTypeConverterPath());

        $typeConverterFilePath = $typeConverterInformation->getTypeConverterFilePath();
        $fileStructure = $this->buildFileStructure($typeConverterFilePath);

        if (is_file($typeConverterFilePath)) {
            $typeConverterInformation->getCreatorInformation()->fileExists(
                $typeConverterFilePath,
                sprintf(
                    'Type converters can only be created, not modified. The file %s already exists and cannot be overridden. ',
                    $typeConverterInformation->getTypeConverterFilename()
                )
            );
            return;
        }
        $this->addClassNodes($fileStructure, $typeConverterInformation);
        $this->fileManager->createFile($typeConverterFilePath, $fileStructure->getFileContents(), $typeConverterInformation->getCreatorInformation());
    }

    private function addClassNodes(FileStructure $fileStructure, TypeConverterInformation $typeConverterInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;'))
        );
        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;'))
        );
        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $typeConverterInformation->getNamespace(),
                $typeConverterInformation->getExtensionInformation(),
            ))
        );
        $fileStructure->addClassStructure(
            new ClassStructure(
                $this->builderFactory
                    ->class($typeConverterInformation->getTypeConverterClassName())
                    ->makeFinal()
                    ->extend('AbstractTypeConverter')
                    ->getNode(),
            )
        );
        $fileStructure->addMethodStructure(
            new MethodStructure(
                $this->builderFactory
                    ->method('convertFrom')
                    ->addParam($this->builderFactory->param('source'))
                    ->addParam($this->builderFactory->param('targetType')->setType('string'))
                    ->addParam($this->builderFactory->param('convertedChildProperties')->setType('array')->setDefault([]))
                    ->addParam($this->builderFactory->param('configuration')->setType('?PropertyMappingConfigurationInterface')->setDefault(null))
                    ->makePublic()
                    ->setReturnType($typeConverterInformation->getTarget())
                    ->getNode()
            )
        );
    }
}
