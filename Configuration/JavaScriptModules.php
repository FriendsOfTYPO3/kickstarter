<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'imports' => [
        '@friendsoftypo3/kickstarter/' => [
            'path' => 'EXT:kickstarter/Resources/Public/JavaScript/',
            // Exclude files of the following folders from being import-mapped
            'exclude' => [
                'EXT:kickstarter/Resources/Public/JavaScript/Contrib/',
            ],
        ],
    ],
];
