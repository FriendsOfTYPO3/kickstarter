<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Stmt\ClassConst;

/**
 * Contains the AST of a ClassConst node
 */
class ClassConstStructure extends AbstractStructure
{
    public function __construct(private readonly ClassConst $node) {}

    public function getNode(): ClassConst
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->node->consts[0]->name->toString();
    }
}
