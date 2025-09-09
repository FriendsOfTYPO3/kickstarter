<?php

declare(strict_types=1);

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
