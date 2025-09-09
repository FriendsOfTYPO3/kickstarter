<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Stmt\Use_;

/**
 * Contains the AST of a Use_ node
 */
class UseStructure extends AbstractStructure
{
    public function __construct(
        private readonly Use_ $node
    ) {}

    public function getNode(): Use_
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->node->uses[0]->name->toString();
    }
}
