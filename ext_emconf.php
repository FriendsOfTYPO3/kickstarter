<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Extension Kickstarter',
    'description' => 'Kickstart TYPO3 Extension',
    'category' => 'module',
    'author' => 'Kickstarter Development Team',
    'author_email' => 'friendsof@typo3.org',
    'state' => 'beta',
    'author_company' => '',
    'version' => '0.3.5',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'install' => '13.4.3-13.4.99',
        ],
        'conflicts' => [
            'make' => '*',
        ],
        'suggests' => [
        ],
    ],
];
