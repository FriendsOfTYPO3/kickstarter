<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Creator\Locallang\LocallangCreatorInterface;
use FriendsOfTYPO3\Kickstarter\Information\LocallangInformation;

readonly class LocallangCreatorService
{
    /**
     * @param iterable<LocallangCreatorInterface> $locallangCreators
     */
    public function __construct(
        private iterable $locallangCreators,
    ) {}

    public function create(LocallangInformation $locallangInformation): void
    {
        foreach ($this->locallangCreators as $creator) {
            $creator->create($locallangInformation);
        }
    }
}
