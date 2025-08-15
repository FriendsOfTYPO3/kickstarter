<?php

use MyVendor\MyExtension\Controller\TestController;
return [
    'web_MyExtension' => [
        'parent' => 'web',
        'position' => 'bottom',
        'access' => 'user',
        'workspaces' => '*',
        'labels' => [
            'title' => 'My Extension Module',
            'description' => 'Full description of the module functionality',
            'shortDescription' => 'Short description of the module',
        ],
        'iconIdentifier' => 'my_extension-module-icon',
        'extensionName' => 'my_extension',
        'controllerActions' => [
            TestController::class => 'index, test',
        ],
    ],
];
