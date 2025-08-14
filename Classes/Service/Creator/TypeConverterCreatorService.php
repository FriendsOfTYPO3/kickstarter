<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Information\TypeConverterInformation;

class TypeConverterCreatorService
{
    public function __construct(
        private readonly iterable $typeConverterCreators,
    ) {}

    public function create(TypeConverterInformation $typeConverterInformation): void
    {
        foreach ($this->typeConverterCreators as $creator) {
            $creator->create($typeConverterInformation);
        }
    }
}
