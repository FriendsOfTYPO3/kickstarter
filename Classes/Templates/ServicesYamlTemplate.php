<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Templates;

use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ServicesYamlTemplate
{
    public function getAbsoluteFileName(ExtensionInformation $extensionInformation): void
    {
        $configurationPath = $extensionInformation->getExtensionPath() . 'Configuration/';
        GeneralUtility::mkdir_deep($configurationPath);
    }

    public function getTemplate(ExtensionInformation $extensionInformation): string
    {
        return sprintf(<<<'EOT'
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  %s:
    resource: '../Classes/*'
    exclude:
    - '../Classes/Domain/Model/*'
EOT, $extensionInformation->getNamespacePrefix());
    }
}
