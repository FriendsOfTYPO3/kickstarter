<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Stmt\Function_;

/**
 * Contains the AST of a Function_ node
 */
class FunctionStructure extends AbstractStructure
{
    public function __construct(
        private readonly Function_ $node
    ) {}

    public function getNode(): Function_
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->node->name->toString();
    }
}
