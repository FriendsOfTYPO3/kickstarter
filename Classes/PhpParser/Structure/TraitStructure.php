<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Stmt\TraitUse;

/**
 * Contains the AST of a TraitUse node
 */
class TraitStructure extends AbstractStructure
{
    public function __construct(private readonly TraitUse $node) {}

    public function getNode(): TraitUse
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->node->traits[0]->toString();
    }
}
