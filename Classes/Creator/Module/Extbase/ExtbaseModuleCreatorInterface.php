<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Creator\Module\Extbase;

use StefanFroemken\ExtKickstarter\Information\ModuleInformation;

interface ExtbaseModuleCreatorInterface
{
    public function create(ModuleInformation $moduleInformation): void;
}
