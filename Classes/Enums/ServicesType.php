<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Enums;

enum ServicesType: string
{
    case PHP = 'php';
    case YAML = 'yaml';

    public function getFileName(): string
    {
        return match ($this) {
            self::PHP => 'Services.php',
            self::YAML => 'Services.yaml',
        };
    }
}
