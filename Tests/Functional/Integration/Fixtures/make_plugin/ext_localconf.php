<?php

declare(strict_types=1);
use MyVendor\MyExtension\Controller\TestController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
ExtensionUtility::configurePlugin(
    'MyExtension',
    'NewsList',
    [
        TestController::class => 'list, show',
    ],
    [
        TestController::class => 'list',
    ],
);
