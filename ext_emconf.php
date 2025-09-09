<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Extension Kickstarter',
    'description' => 'Kickstart TYPO3 Extension',
    'category' => 'module',
    'author' => 'Kickstarter Development Team',
    'author_email' => 'friendsof@typo3.org',
    'state' => 'beta',
    'author_company' => '',
    'version' => '0.3.4',
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
