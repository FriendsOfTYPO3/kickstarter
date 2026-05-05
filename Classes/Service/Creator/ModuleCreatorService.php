<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Information\ModuleInformation;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class ModuleCreatorService
{
    public function __construct(
        #[AutowireIterator('ext-kickstarter.creator.module.extbase')]
        private iterable $extbaseModuleCreators,
        #[AutowireIterator('ext-kickstarter.creator.module.native')]
        private iterable $nativeModuleCreators,
    ) {}

    public function create(ModuleInformation $moduleInformation): void
    {
        if ($moduleInformation->isExtbaseModule()) {
            foreach ($this->extbaseModuleCreators as $creator) {
                $creator->create($moduleInformation);
            }
        } else {
            foreach ($this->nativeModuleCreators as $creator) {
                $creator->create($moduleInformation);
            }
        }
    }
}
