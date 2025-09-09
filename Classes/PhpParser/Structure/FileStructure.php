<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use FriendsOfTYPO3\Kickstarter\PhpParser\Printer\PrettyTypo3Printer;
use FriendsOfTYPO3\Kickstarter\PhpParser\StructureObjectStorage;

/**
 * Contains the AST of any kind of PHP file grouped by nodes.
 * Use the add*Methods to add further nodes to the AST.
 */
readonly class FileStructure
{
    private PrettyTypo3Printer $printer;

    private StructureObjectStorage $declareStructures;

    private StructureObjectStorage $namespaceStructures;

    private StructureObjectStorage $classStructures;

    private StructureObjectStorage $traitStructures;

    private StructureObjectStorage $useStructures;

    private StructureObjectStorage $classConstStructures;

    private StructureObjectStorage $methodStructures;

    private StructureObjectStorage $propertyStructures;

    private StructureObjectStorage $functionStructures;

    private StructureObjectStorage $expressionStructures;

    private StructureObjectStorage $returnStructures;

    public function __construct()
    {
        $this->printer = new PrettyTypo3Printer([
            'shortArraySyntax' => true,
        ]);

        $this->declareStructures = new StructureObjectStorage();
        $this->namespaceStructures = new StructureObjectStorage();
        $this->classStructures = new StructureObjectStorage();
        $this->traitStructures = new StructureObjectStorage();
        $this->useStructures = new StructureObjectStorage();
        $this->classConstStructures = new StructureObjectStorage();
        $this->methodStructures = new StructureObjectStorage();
        $this->propertyStructures = new StructureObjectStorage();
        $this->functionStructures = new StructureObjectStorage();
        $this->expressionStructures = new StructureObjectStorage();
        $this->returnStructures = new StructureObjectStorage();
    }

    /**
     * @return StructureObjectStorage<DeclareStructure>
     */
    public function getDeclareStructures(): StructureObjectStorage
    {
        return $this->declareStructures;
    }

    public function addDeclareStructure(DeclareStructure $declareStructure): void
    {
        if (!$this->declareStructures->hasNodeWithName($declareStructure->getName())) {
            $this->declareStructures->attach($declareStructure);
        }
    }

    public function getNamespaceStructure(): ?NamespaceStructure
    {
        $this->namespaceStructures->rewind();

        return $this->namespaceStructures->valid() ? $this->namespaceStructures->current() : null;
    }

    public function addNamespaceStructure(NamespaceStructure $namespaceStructure): void
    {
        // In TYPO3 each PHP file has exactly ONE class
        // If there are more, we skip further namespaces completely
        if ($this->namespaceStructures->count() === 0) {
            $this->namespaceStructures->attach($namespaceStructure);
        }
    }

    public function getClassStructure(): ?ClassStructure
    {
        $this->classStructures->rewind();

        return $this->classStructures->valid() ? $this->classStructures->current() : null;
    }

    public function addClassStructure(ClassStructure $classStructure): void
    {
        // In TYPO3 each PHP file has exactly ONE class
        // If there are more, we skip further classes completely
        if ($this->classStructures->count() === 0) {
            $this->classStructures->attach($classStructure);
        }
    }

    /**
     * @return StructureObjectStorage<TraitStructure>
     */
    public function getTraitStructures(): StructureObjectStorage
    {
        return $this->traitStructures;
    }

    public function addTraitStructure(TraitStructure $traitStructure): void
    {
        if (!$this->traitStructures->hasNodeWithName($traitStructure->getName())) {
            $this->traitStructures->attach($traitStructure);
        }
    }

    /**
     * @return StructureObjectStorage<UseStructure>
     */
    public function getUseStructures(): StructureObjectStorage
    {
        return $this->useStructures;
    }

    public function addUseStructure(UseStructure $useStructure): void
    {
        if (!$this->useStructures->hasNodeWithName($useStructure->getName())) {
            $this->useStructures->attach($useStructure);
        }
    }

    /**
     * @return StructureObjectStorage<ClassConstStructure>
     */
    public function getClassConstStructures(): StructureObjectStorage
    {
        return $this->classConstStructures;
    }

    public function addClassConstStructure(ClassConstStructure $classConstStructure): void
    {
        if (!$this->classConstStructures->hasNodeWithName($classConstStructure->getName())) {
            $this->classConstStructures->attach($classConstStructure);
        }
    }

    /**
     * @return StructureObjectStorage<MethodStructure>
     */
    public function getMethodStructures(): StructureObjectStorage
    {
        return $this->methodStructures;
    }

    public function addMethodStructure(MethodStructure $methodStructure): void
    {
        if (!$this->methodStructures->hasNodeWithName($methodStructure->getName())) {
            $this->methodStructures->attach($methodStructure);
        }
    }

    /**
     * @return StructureObjectStorage<PropertyStructure>
     */
    public function getPropertyStructures(): StructureObjectStorage
    {
        return $this->propertyStructures;
    }

    public function addPropertyStructure(PropertyStructure $propertyStructure): void
    {
        if (!$this->propertyStructures->hasNodeWithName($propertyStructure->getName())) {
            $this->propertyStructures->attach($propertyStructure);
        }
    }

    /**
     * @return StructureObjectStorage<FunctionStructure>
     */
    public function getFunctionStructures(): StructureObjectStorage
    {
        return $this->functionStructures;
    }

    public function addFunctionStructure(FunctionStructure $functionStructure): void
    {
        if (!$this->functionStructures->hasNodeWithName($functionStructure->getName())) {
            $this->functionStructures->attach($functionStructure);
        }
    }

    /**
     * @return StructureObjectStorage<ExpressionStructure>
     */
    public function getExpressionStructures(): StructureObjectStorage
    {
        return $this->expressionStructures;
    }

    public function addExpressionStructure(ExpressionStructure $expressionStructure): void
    {
        $this->expressionStructures->attach($expressionStructure);
    }

    /**
     * @return StructureObjectStorage<ReturnStructure>
     */
    public function getReturnStructures(): StructureObjectStorage
    {
        return $this->returnStructures;
    }

    public function addReturnStructure(ReturnStructure $returnStructure): void
    {
        $this->returnStructures->attach($returnStructure);
    }

    /**
     * Collect all registered nodes in a specific order and render/print new file content
     */
    public function getFileContents(): string
    {
        // PhpParser does a rtrim on PHP content. To be more PSR-compatible, we add NL to the end of all files.
        return $this->printer->prettyPrintFile($this->getStmts());
    }

    private function getStmts(): array
    {
        $stmts = [];
        array_push($stmts, ...$this->getDeclareStructures()->getStmts());

        if (
            $this->getNamespaceStructure() instanceof NamespaceStructure
            && $this->getClassStructure() instanceof ClassStructure
        ) {
            // Add nodes for classes

            // Reset sub nodes
            $namespaceNode = $this->getNamespaceStructure()->getNode();
            $namespaceNode->stmts = [];
            $classNode = $this->getClassStructure()->getNode();
            $classNode->stmts = [];

            // Collect nodes for class itself
            $classStmts = [];
            array_push($classStmts, ...$this->getTraitStructures()->getStmts(true));
            array_push($classStmts, ...$this->getClassConstStructures()->getStmts());
            array_push($classStmts, ...$this->getPropertyStructures()->getStmts());
            array_push($classStmts, ...$this->getMethodStructures()->getStmts());

            $classNode->stmts = $classStmts;

            // Collect nodes for class namespace
            $namespaceStmts = [];
            array_push($namespaceStmts, ...$this->getUseStructures()->getStmts(true));
            $namespaceStmts[] = $classNode;

            $namespaceNode->stmts = $namespaceStmts;

            $stmts[] = $namespaceNode;
        } else {
            // Add nodes for non-classes
            array_push($stmts, ...$this->getUseStructures()->getStmts(true));
            array_push($stmts, ...$this->getExpressionStructures()->getStmts());
            array_push($stmts, ...$this->getFunctionStructures()->getStmts());
            array_push($stmts, ...$this->getReturnStructures()->getStmts());
        }

        return $stmts;
    }
}
