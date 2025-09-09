<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Stmt\ClassMethod;

/**
 * Contains the AST of a ClassMethod node
 */
class MethodStructure extends AbstractStructure
{
    public function __construct(
        private readonly ClassMethod $node
    ) {}

    public function getNode(): ClassMethod
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->node->name->toString();
    }
}
