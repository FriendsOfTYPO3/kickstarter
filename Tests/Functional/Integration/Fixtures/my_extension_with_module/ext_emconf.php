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
            'typo3' => '14.0.0-14.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
