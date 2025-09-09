<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Controller\KickstartController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$extConf = GeneralUtility::makeInstance(ExtConf::class);
if ($extConf->isActivateModule()) {
    /**
     * Definitions for modules provided by EXT:kickstarter
     */
    return [
        'system_kickstarter' => [
            'parent' => 'system',
            'position' => [
                'after' => '*',
            ],
            'access' => 'admin',
            'path' => '/module/kickstarter/overview',
            'icon' => 'EXT:kickstarter/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:kickstarter/Resources/Private/Language/locallang_kickstarter.xlf',
            'routes' => [
                '_default' => [
                    'target' => KickstartController::class . '::processRequest',
                ],
            ],
        ],
    ];
}

return [];
