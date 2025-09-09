<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Visitor;

use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassConstStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ExpressionStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FunctionStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\MethodStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\PropertyStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ReturnStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\TraitStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

/**
 * This visitor walks through all kind of detected PHP Parser nodes of any kind of PHP file
 * and transfers the nodes to FileStructure object, to have a grouped and sorted interpretation
 * of the analyzed file.
 */
final class FileVisitor extends NodeVisitorAbstract
{
    private readonly FileStructure $fileStructure;

    public function __construct()
    {
        $this->fileStructure = new FileStructure();
    }

    /**
     * @return Node[]|null
     */
    public function beforeTraverse(array $nodes): ?array
    {
        return null;
    }

    /**
     * @return int|Node|Node[]|null
     */
    public function enterNode($node): int|Node|array|null
    {
        if ($node instanceof Declare_) {
            $this->fileStructure->addDeclareStructure(new DeclareStructure($node));
        } elseif ($node instanceof Namespace_) {
            $this->fileStructure->addNamespaceStructure(new NamespaceStructure($node));
        } elseif ($node instanceof Class_) {
            $this->fileStructure->addClassStructure(new ClassStructure($node));
        } elseif ($node instanceof TraitUse) {
            $this->fileStructure->addTraitStructure(new TraitStructure($node));
        } elseif ($node instanceof Use_) {
            $this->fileStructure->addUseStructure(new UseStructure($node));
        } elseif ($node instanceof ClassConst) {
            $this->fileStructure->addClassConstStructure(new ClassConstStructure($node));
        } elseif ($node instanceof ClassMethod) {
            $this->fileStructure->addMethodStructure(new MethodStructure($node));
        } elseif ($node instanceof Property) {
            $this->fileStructure->addPropertyStructure(new PropertyStructure($node));
        } elseif ($node instanceof Function_) {
            $this->fileStructure->addFunctionStructure(new FunctionStructure($node));
        } elseif ($node instanceof Expression) {
            $this->fileStructure->addExpressionStructure(new ExpressionStructure($node));
        } elseif ($node instanceof Return_) {
            $this->fileStructure->addReturnStructure(new ReturnStructure($node));
        }

        return null;
    }

    public function getFileStructure(): FileStructure
    {
        return $this->fileStructure;
    }
}
