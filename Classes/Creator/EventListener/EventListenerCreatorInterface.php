<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\EventListener;

use FriendsOfTYPO3\Kickstarter\Information\EventListenerInformation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.creator.event-listener')]
interface EventListenerCreatorInterface
{
    public function create(EventListenerInformation $eventListenerInformation): void;
}
