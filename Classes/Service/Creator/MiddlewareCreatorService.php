<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Creator\Middleware\MiddlewareCreatorInterface;
use FriendsOfTYPO3\Kickstarter\Information\MiddlewareInformation;

readonly class MiddlewareCreatorService
{
    /**
     * @param iterable<MiddlewareCreatorInterface> $middlewareCreators
     */
    public function __construct(
        private iterable $middlewareCreators,
    ) {}

    public function create(MiddlewareInformation $middlewareInformation): void
    {
        foreach ($this->middlewareCreators as $creator) {
            $creator->create($middlewareInformation);
        }
    }
}
