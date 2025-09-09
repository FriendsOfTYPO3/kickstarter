<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Stmt\Expression;

/**
 * Contains the AST of a StaticCall node
 */
class ExpressionStructure extends AbstractStructure
{
    public function __construct(private readonly Expression $node) {}

    public function getNode(): Expression
    {
        return $this->node;
    }

    public function getName(): string
    {
        return property_exists($this->node->expr, 'name')
            ? $this->node->expr->name->toString()
            : '';
    }
}
