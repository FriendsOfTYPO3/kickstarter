<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Return_;

/**
 * Contains the AST of a Return_ node
 *
 * Needed to build the TCA table configuration
 */
class ReturnStructure extends AbstractStructure
{
    public function __construct(private readonly Return_ $node) {}

    public function getNode(): Return_
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->node->expr instanceof Expr && property_exists($this->node->expr, 'name')
            ? $this->node->expr->name->toString()
            : '';
    }
}
