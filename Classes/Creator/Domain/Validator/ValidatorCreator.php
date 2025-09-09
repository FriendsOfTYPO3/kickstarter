<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Domain\Validator;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Enums\ValidatorType;
use FriendsOfTYPO3\Kickstarter\Information\ValidatorInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\MethodStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ValidatorCreator implements ValidatorCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
        $this->builderFactory = new BuilderFactory();
    }

    public function create(ValidatorInformation $validatorInformation): void
    {
        GeneralUtility::mkdir_deep($validatorInformation->getValidatorPath());

        $validatorFilePath = $validatorInformation->getValidatorFilePath();
        $fileStructure = $this->buildFileStructure($validatorFilePath);

        if (is_file($validatorFilePath)) {
            $validatorInformation->getCreatorInformation()->fileExists(
                $validatorFilePath,
                sprintf(
                    'Validators can only be created, not modified. The file %s already exists and cannot be overridden. ',
                    $validatorInformation->getValidatorFilename()
                )
            );
            return;
        }
        $this->addClassNodes($fileStructure, $validatorInformation);
        $this->fileManager->createFile($validatorFilePath, $fileStructure->getFileContents(), $validatorInformation->getCreatorInformation());
    }

    private function addClassNodes(FileStructure $fileStructure, ValidatorInformation $validatorInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );

        $fileStructure->addUseStructure(
            new UseStructure($this->nodeFactory->createUseImport('TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator'))
        );
        $fileStructure->addClassStructure(
            new ClassStructure(
                $this->builderFactory
                    ->class($validatorInformation->getValidatorName())
                    ->extend('AbstractValidator')
                    ->makeFinal()
                    ->getNode(),
            )
        );

        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $validatorInformation->getNamespace(),
                $validatorInformation->getExtensionInformation(),
            ))
        );

        $methodBuilder = $this->builderFactory
            ->method('isValid')
            ->makeProtected()
            ->addParam($this->builderFactory->param('value')->setType('mixed'))
            ->setReturnType('void');

        if ($validatorInformation->getValidatorType() === ValidatorType::MODEL) {
            $fileStructure->addUseStructure(
                new UseStructure($this->nodeFactory->createUseImport($validatorInformation->getModelFullyQualifiedName()))
            );
            $condition = new BooleanNot(
                new Instanceof_(
                    new Variable('value'),
                    new Name($validatorInformation->getModelName())
                )
            );

            // Build the early return statement
            $methodBuilder->addStmt(new If_($condition, [
                'stmts' => [new Return_()],
            ]));
        }

        // Build the addError() call
        $methodBuilder->addStmt(new Expression($this->builderFactory->methodCall($this->builderFactory->var('this'), 'addError', [
            'Validator needs to be implemented. See https://docs.typo3.org/permalink/t3coreapi:extbase-domain-validator for details. ',
            new LNumber(time()),
        ])));

        $fileStructure->addMethodStructure(new MethodStructure(
            $methodBuilder->getNode()
        ));
    }
}
