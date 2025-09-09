<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Module\Extbase;

use FriendsOfTYPO3\Kickstarter\Information\ModuleInformation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.creator.module.extbase')]
interface ExtbaseModuleCreatorInterface
{
    public function create(ModuleInformation $moduleInformation): void;
}
