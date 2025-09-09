<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Stmt\Declare_;

/**
 * Contains the AST of a Declare_ node
 */
class DeclareStructure extends AbstractStructure
{
    public function __construct(
        private readonly Declare_ $node
    ) {}

    public function getNode(): Declare_
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->node->declares[0]->key->toString();
    }
}
