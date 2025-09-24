<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use FriendsOfTYPO3\Kickstarter\Controller\KickstartController;

return [
    'kickstarter_build' => [
        'path' => '/ext-kickstarter/build',
        'methods' => ['POST'],
        'target' => KickstartController::class . '::build',
    ],
];
