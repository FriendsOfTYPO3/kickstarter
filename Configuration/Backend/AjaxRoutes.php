<?php

declare(strict_types=1);

use FriendsOfTYPO3\Kickstarter\Controller\KickstartController;

return [
    'kickstarter_build' => [
        'path' => '/ext-kickstarter/build',
        'methods' => ['POST'],
        'target' => KickstartController::class . '::build',
    ],
];
