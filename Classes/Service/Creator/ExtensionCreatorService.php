<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Creator\Extension\ExtensionCreatorInterface;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\ServicesConfigInformation;

readonly class ExtensionCreatorService
{
    /**
     * @param iterable<ExtensionCreatorInterface> $extensionCreators
     */
    public function __construct(
        private iterable $extensionCreators,
        private iterable $servicesConfigCreators,
    ) {}

    public function create(ExtensionInformation $extensionInformation, ServicesConfigInformation $servicesConfigInformation): void
    {
        foreach ($this->extensionCreators as $creator) {
            $creator->create($extensionInformation);
        }
        foreach ($this->servicesConfigCreators as $creator) {
            $creator->create($servicesConfigInformation);
        }
    }
}
