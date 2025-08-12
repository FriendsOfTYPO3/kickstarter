<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Information\ValidatorInformation;

readonly class ValidatorCreatorService
{
    public function __construct(
        private iterable $validatorCreators,
    ) {}

    public function create(ValidatorInformation $validatorInformation): void
    {
        foreach ($this->validatorCreators as $creator) {
            $creator->create($validatorInformation);
        }
    }
}
