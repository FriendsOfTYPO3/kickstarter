<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser\Structure;

use PhpParser\Node\Stmt\Property;

/**
 * Contains the AST of a Property node
 */
class PropertyStructure extends AbstractStructure
{
    public function __construct(
        private readonly Property $node
    ) {}

    public function getNode(): Property
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->node->props[0]->name->toString();
    }
}
