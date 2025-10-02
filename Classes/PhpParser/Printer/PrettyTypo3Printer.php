<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Printer;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\PrettyPrinter\Standard;

class PrettyTypo3Printer extends Standard
{
    /**
     * Add a new line before each comment
     */
    protected function pComments(array $comments): string
    {
        return $this->nl . parent::pComments($comments);
    }

    /**
     * Pretty prints an array of nodes and implodes the printed values with commas.
     *
     * @param Node[] $nodes Array of Nodes to be printed
     * @return string Comma separated pretty printed nodes
     */
    protected function pCommaSeparated(array $nodes): string
    {
        if ($this->shouldRenderNodesInMultiline($nodes)) {
            // We have to manually add trailing NL. Only leading NL will be done within this method. Don't know why.
            return $this->pCommaSeparatedMultiline($nodes, true) . $this->nl;
        }

        return $this->pImplode($nodes, ', ');
    }

    private function shouldRenderNodesInMultiline(array $nodes): bool
    {
        // Early return on empty nodes
        if ($nodes === []) {
            return false;
        }

        // If the arguments of method/function arguments are too long, activate multiline
        $maxLineLength = 100;
        if ($this->getLineLengthOfNodes($nodes) > $maxLineLength) {
            return true;
        }

        // If all nodes are of type Array_ we try to render TCA, which is a long array. Yes: multiline
        $allNodesOfTypeArray = true;
        foreach ($nodes as $node) {
            if (!$node instanceof ArrayItem) {
                $allNodesOfTypeArray = false;
                break;
            }
        }

        if ($allNodesOfTypeArray) {
            return true;
        }

        // For existing code, we should check if arguments are already in multiline. If yes, keep it.
        $startLine = reset($nodes)->getAttribute('startLine');
        $endLine = end($nodes)->getAttribute('endLine');

        return $startLine !== $endLine;
    }

    /**
     * Overwrites the original function to remove one space after 'declare('
     */
    protected function pStmt_Declare(Declare_ $node): string
    {
        return 'declare(' . $this->pCommaSeparated($node->declares) . ')'
            . ($node->stmts !== null ? ' {' . $this->pStmts($node->stmts) . $this->nl . '}' : ';');
    }

    protected function pStmt_ClassMethod(ClassMethod $node): string
    {
        return $this->pAttrGroups($node->attrGroups)
            . $this->pModifiers($node->flags)
            . 'function ' . ($node->byRef ? '&' : '') . $node->name
            . '(' . $this->pMaybeMultiline($node->params) . ')'
            . ($node->returnType !== null ? ': ' . $this->p($node->returnType) : '') // Removed extra space
            . ($node->stmts !== null
                ? $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}'
                : ';');
    }

    /**
     * Sum up all indents, methods, arguments and return type length to get the full length of code.
     */
    private function getLineLengthOfNodes(array $nodes): int
    {
        // Play around with these settings
        $indentLength = 4;
        $methodNameLength = 25;
        $methodReturnTypeLength = 15;

        $lineLength = $indentLength + $methodNameLength + $methodReturnTypeLength;
        foreach ($nodes as $node) {
            if ($node instanceof Arg) {
                // Adding 2 because of ", " between each argument
                $lineLength += strlen($this->p($node)) + 2;
            }
        }

        return $lineLength;
    }
}
