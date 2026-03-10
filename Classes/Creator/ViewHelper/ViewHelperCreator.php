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
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\PropertyStructure;
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
use PhpParser\Node\Expr\StaticCall;
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
                sprintf('ViewHelpers can only be created, not modified. The file %s already exists and cannot be overridden. ', $filePath)
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
        $this->buildClassBoilerplate($fileStructure, $viewHelperInformation, AbstractViewHelper::class, 'AbstractViewHelper');
        $this->addInitializeArgumentsMethod($viewHelperInformation, $fileStructure, false);
        $this->addPlainRenderMethod($viewHelperInformation, $fileStructure);
    }

    private function createTagBasedViewHelper(FileStructure $fileStructure, ViewHelperInformation $viewHelperInformation): void
    {
        $this->buildClassBoilerplate($fileStructure, $viewHelperInformation, AbstractTagBasedViewHelper::class, 'AbstractTagBasedViewHelper');
        $this->addTagNameProperty($fileStructure, $viewHelperInformation->getTagName());
        $this->addInitializeArgumentsMethod($viewHelperInformation, $fileStructure, true);
        $this->addTagBasedRenderMethod($fileStructure);
    }

    private function buildClassBoilerplate(FileStructure $fileStructure, ViewHelperInformation $info, string $useClass, string $parentClass): void
    {
        $fileStructure->addUseStructure(new UseStructure($this->nodeFactory->createUseImport($useClass)));
        $fileStructure->addNamespaceStructure(new NamespaceStructure($this->nodeFactory->createNamespace(
            $info->getNamespace(),
            $info->getExtensionInformation()
        )));
        $fileStructure->addClassStructure(new ClassStructure(
            $this->builderFactory->class($info->getClassname())->makeFinal()->extend($parentClass)->getNode()
        ));
    }

    private function addTagNameProperty(FileStructure $fileStructure, string $tagName): void
    {
        $effectiveTag = $tagName !== '' ? $tagName : 'div';
        $property = $this->builderFactory
            ->property('tagName')
            ->makeProtected()
            ->setType('string')
            ->setDefault($effectiveTag)
            ->getNode();

        $fileStructure->addPropertyStructure(new PropertyStructure($property));
    }

    public function addInitializeArgumentsMethod(
        ViewHelperInformation $viewHelperInformation,
        FileStructure $fileStructure,
        bool $isTagBased = false
    ): void {
        $methodBuilder = $this->builderFactory
            ->method('initializeArguments')
            ->makePublic()
            ->setReturnType('void');

        if ($isTagBased) {
            $methodBuilder->addStmt(new Expression(
                new StaticCall(new Name('parent'), 'initializeArguments')
            ));
        }

        foreach ($this->buildRegisterArgumentStatements($viewHelperInformation->getArguments()) as $stmt) {
            $methodBuilder->addStmt($stmt);
        }

        $fileStructure->addMethodStructure(new MethodStructure($methodBuilder->getNode()));
    }

    private function addPlainRenderMethod(ViewHelperInformation $info, FileStructure $fileStructure): void
    {
        $methodBuilder = $this->builderFactory
            ->method('render')
            ->makePublic()
            ->setReturnType('string');

        foreach ($this->buildArgumentAssignments($info->getArguments()) as $stmt) {
            $methodBuilder->addStmt($stmt);
        }
        $methodBuilder->addStmt($this->buildRenderReturn($info->getArguments(), $info->getClassname()));

        $fileStructure->addMethodStructure(new MethodStructure($methodBuilder->getNode()));
    }

    private function addTagBasedRenderMethod(FileStructure $fileStructure): void
    {
        $tagProperty = new PropertyFetch(new Variable('this'), 'tag');
        $renderChildren = new MethodCall(new Variable('this'), 'renderChildren');
        $setContent = new Expression(new MethodCall($tagProperty, 'setContent', [new Arg($renderChildren)]));
        $renderTag = new Return_(new MethodCall($tagProperty, 'render'));

        $method = $this->builderFactory
            ->method('render')
            ->makePublic()
            ->setReturnType('string')
            ->addStmt($setContent)
            ->addStmt($renderTag)
            ->getNode();

        $fileStructure->addMethodStructure(new MethodStructure($method));
    }

    private function buildRegisterArgumentStatements(array $arguments): array
    {
        return array_map($this->buildSingleRegisterArgumentStatement(...), $arguments);
    }

    private function buildSingleRegisterArgumentStatement(array $argument): Expression
    {
        [$name, $type, $description, $required] = $argument;
        $args = [
            $this->builderFactory->val($name),
            $this->builderFactory->val($type),
            $this->builderFactory->val($description),
            $this->builderFactory->val($required),
        ];
        if (array_key_exists(4, $argument)) {
            $args[] = $this->builderFactory->val($argument[4]);
        }

        return new Expression(new MethodCall(new Variable('this'), 'registerArgument', array_map(fn(Expr $a): Arg => new Arg($a), $args)));
    }

    private function buildArgumentAssignments(array $arguments): array
    {
        return array_map(fn(array $arg): Expression => new Expression(
            new Assign(
                new Variable($arg[0]),
                new ArrayDimFetch(new PropertyFetch(new Variable('this'), 'arguments'), $this->builderFactory->val($arg[0]))
            )
        ), $arguments);
    }

    private function buildRenderReturn(array $arguments, string $className): Return_
    {
        if ($arguments === []) {
            return new Return_($this->builderFactory->val('ViewHelper ' . $className . ' content. '));
        }

        $formatParts = array_map(fn(array $arg): string => $arg[0] . ': %s', $arguments);
        $vars = array_map(fn(array $arg): Arg => new Arg(new Variable($arg[0])), $arguments);
        $formatString = sprintf('ViewHelper %s content. The following arguments were passed: %s', $className, implode(', ', $formatParts));

        return new Return_(
            new FuncCall(new Name('sprintf'), array_merge([new Arg($this->builderFactory->val($formatString))], $vars))
        );
    }
}
