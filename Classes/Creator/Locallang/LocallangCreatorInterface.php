<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Locallang;

use FriendsOfTYPO3\Kickstarter\Information\LocallangInformation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.creator.locallang')]
interface LocallangCreatorInterface
{
    public function create(LocallangInformation $locallangInformation): void;
}
