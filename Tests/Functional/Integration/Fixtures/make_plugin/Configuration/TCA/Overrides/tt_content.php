<?php

declare(strict_types=1);
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
ExtensionUtility::registerPlugin(
    'MyExtension',
    'NewsList',
    'News Listing',
    'ext-my-extension-plugin',
    'plugins',
    'Displays a list of news records with filtering options.',
);
