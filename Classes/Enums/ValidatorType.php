<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Enums;

enum ValidatorType: string
{
    case MODEL = 'Model';
    case PROPERTY = 'Property';
}
