<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'My Extension',
    'description' => 'This is a test extension',
    'category' => 'plugin',
    'state' => 'stable',
    'author' => 'John Doe',
    'author_email' => 'john@example.com',
    'author_company' => 'MyCompany',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
