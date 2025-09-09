<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Configuration;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;

/**
 * This class will streamline the values from extension manager configuration
 */
#[Autoconfigure(constructor: 'create')]
final readonly class ExtConf
{
    public const EXT_KEY = 'kickstarter';

    public const LAST_EXTENSION_REGISTRY_KEY = 'lastExtension';

    private const DEFAULT_SETTINGS = [
        // general
        'exportDirectory' => '',
        'activateModule' => false,
    ];

    public function __construct(
        // general
        private string $exportDirectory = self::DEFAULT_SETTINGS['exportDirectory'],
        private bool $activateModule = self::DEFAULT_SETTINGS['activateModule'],
    ) {}

    public static function create(ExtensionConfiguration $extensionConfiguration): self
    {
        $extensionSettings = self::DEFAULT_SETTINGS;

        // Overwrite default extension settings with values from EXT_CONF
        try {
            $extensionSettings = array_merge(
                $extensionSettings,
                $extensionConfiguration->get(self::EXT_KEY),
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
        }

        return new self(
            // general
            exportDirectory: (string)$extensionSettings['exportDirectory'],
            activateModule: (bool)$extensionSettings['activateModule'],
        );
    }

    public function getExportDirectory(): string
    {
        $exportDirectory = trim($this->exportDirectory);

        if ($exportDirectory === '' || $exportDirectory === '0') {
            // Fall back to typo3temp/kickstarter
            return sprintf(
                '/%s/%s/',
                trim(Environment::getPublicPath(), '/'),
                'typo3temp/kickstarter',
            );
        }

        // sprintf() in ExtensionInformation will add trailing slash
        return Environment::getProjectPath() . '/' . rtrim($exportDirectory, '/');
    }

    public function isActivateModule(): bool
    {
        return $this->activateModule;
    }
}
