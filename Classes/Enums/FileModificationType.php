<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Enums;

enum FileModificationType
{
    case CREATED;
    case CREATION_FAILED;
    case MODIFIED;
    case NOT_MODIFIED;
    case MODIFICATION_FAILED;
    case ABORTED;
}
