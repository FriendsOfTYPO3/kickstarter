<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Service\Creator;

use StefanFroemken\ExtKickstarter\Information\ModuleInformation;

readonly class ModuleCreatorService
{
    public function __construct(
        private iterable $extbaseModuleCreators,
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
