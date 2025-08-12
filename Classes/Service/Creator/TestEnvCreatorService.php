<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Information\TestEnvInformation;

readonly class TestEnvCreatorService
{
    public function __construct(
        private iterable $testEnvCreators,
    ) {}

    public function create(TestEnvInformation $testEnvInformation): void
    {
        foreach ($this->testEnvCreators as $creator) {
            $creator->create($testEnvInformation);
        }
    }
}
