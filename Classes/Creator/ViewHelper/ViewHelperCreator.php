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
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
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

        $this->addInitializeArgumentsMethod($viewHelperInformation, $fileStructure);
        $methodBuilder = $this->builderFactory
            ->method('render')
            ->makePublic()
            ->setReturnType('string');

        foreach ($this->buildArgumentAssignments($viewHelperInformation->getArguments()) as $stmt) {
            $methodBuilder->addStmt($stmt);
        }

        $methodBuilder->addStmt(
            $this->buildRenderReturn(
                $viewHelperInformation->getArguments(),
                $viewHelperInformation->getClassname()
            )
        );
        $fileStructure->addMethodStructure(
            new MethodStructure($methodBuilder->getNode())
        );
    }

    private function buildRegisterArgumentStatements(array $arguments): array
    {
        $statements = [];

        foreach ($arguments as $argument) {
            [$name, $type, $description, $required] = $argument;
            $defaultValue = $argument[4] ?? null;

            $args = [
                $this->builderFactory->val($name),
                $this->builderFactory->val($type),
                $this->builderFactory->val($description),
                $this->builderFactory->val($required),
            ];

            if (array_key_exists(4, $argument)) {
                $args[] = $this->builderFactory->val($defaultValue);
            }

            $statements[] = new Expression(
                new MethodCall(
                    new Variable('this'),
                    'registerArgument',
                    array_map(fn(Expr $arg): Arg => new Arg($arg), $args)
                )
            );
        }

        return $statements;
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
        $methodBuilder = $this->builderFactory
            ->method('initializeArguments')
            ->makePublic()
            ->setReturnType('void');

        foreach ($this->buildRegisterArgumentStatements($viewHelperInformation->getArguments()) as $stmt) {
            $methodBuilder = $methodBuilder->addStmt($stmt);
        }

        $fileStructure->addMethodStructure(
            new MethodStructure($methodBuilder->getNode())
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

    private function buildArgumentAssignments(array $arguments): array
    {
        $statements = [];

        foreach ($arguments as $argument) {
            $name = $argument[0];

            $statements[] = new Expression(
                new Assign(
                    new Variable($name),
                    new ArrayDimFetch(
                        new PropertyFetch(
                            new Variable('this'),
                            'arguments'
                        ),
                        $this->builderFactory->val($name)
                    )
                )
            );
        }

        return $statements;
    }

    private function buildRenderReturn(array $arguments, string $className): Return_
    {
        if ($arguments === []) {
            return new Return_(
                $this->builderFactory->val(
                    'ViewHelper ' . $className . ' content. '
                )
            );
        }

        $formatParts = [];
        $vars = [];

        foreach ($arguments as $argument) {
            $name = $argument[0];

            $formatParts[] = $name . ': %s';
            $vars[] = new Variable($name);
        }

        $formatString = sprintf(
            'ViewHelper %s content. The following arguments where passed: %s',
            $className,
            implode(', ', $formatParts)
        );

        return new Return_(
            new FuncCall(
                new Name('sprintf'),
                array_merge(
                    [new Arg($this->builderFactory->val($formatString))],
                    array_map(fn($var): Arg => new Arg($var), $vars)
                )
            )
        );
    }

    public function addInitializeArgumentsMethod(ViewHelperInformation $viewHelperInformation, FileStructure $fileStructure): void
    {
        $methodBuilder = $this->builderFactory
            ->method('initializeArguments')
            ->makePublic()
            ->setReturnType('void');

        foreach ($this->buildRegisterArgumentStatements($viewHelperInformation->getArguments()) as $stmt) {
            $methodBuilder->addStmt($stmt);
        }

        $fileStructure->addMethodStructure(
            new MethodStructure($methodBuilder->getNode())
        );
    }
}
