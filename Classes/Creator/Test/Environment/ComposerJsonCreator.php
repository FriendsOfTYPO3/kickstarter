<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Test\Environment;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\TestEnvInformation;

class ComposerJsonCreator implements TestEnvCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(TestEnvInformation $testEnvInformation): void
    {
        $composerJsonFilepath = $testEnvInformation->getExtensionInformation()->getExtensionPath() . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonFilepath), true);

        if (is_file($composerJsonFilepath)) {
            $this->fileManager->modifyFile(
                $composerJsonFilepath,
                $this->updateComposerJson($composerConfig),
                $testEnvInformation->getCreatorInformation(),
            );
            return;
        }
        $this->fileManager->createFile(
            $composerJsonFilepath,
            $this->updateComposerJson($composerConfig),
            $testEnvInformation->getCreatorInformation(),
        );
    }

    private function updateComposerJson(array $composerConfig): string
    {
        if (!isset($composerConfig['require-dev']['ergebnis/composer-normalize'])) {
            $composerConfig['require-dev']['ergebnis/composer-normalize'] = '^2.44';
        }

        if (!isset($composerConfig['require-dev']['phpstan/phpstan'])) {
            $composerConfig['require-dev']['phpstan/phpstan'] = '^2.1.25';
        }

        if (!isset($composerConfig['require-dev']['phpunit/phpunit'])) {
            $composerConfig['require-dev']['phpunit/phpunit'] = '^11.2.5';
        }

        if (!isset($composerConfig['require-dev']['typo3/coding-standards'])) {
            $composerConfig['require-dev']['typo3/coding-standards'] = '^0.8';
        }

        if (!isset($composerConfig['require-dev']['typo3/testing-framework'])) {
            $composerConfig['require-dev']['typo3/testing-framework'] = '^9.0.1';
        }

        ksort($composerConfig['require-dev']);

        if (!isset($composerConfig['config']['allow-plugins']['ergebnis/composer-normalize'])) {
            $composerConfig['config']['allow-plugins']['ergebnis/composer-normalize'] = true;
        }

        ksort($composerConfig['config']['allow-plugins']);

        if (!isset($composerConfig['config']['bin-dir'])) {
            $composerConfig['config']['bin-dir'] = '.Build/bin';
        }

        if (!isset($composerConfig['config']['vendor-dir'])) {
            $composerConfig['config']['vendor-dir'] = '.Build/vendor';
        }

        ksort($composerConfig['config']);

        if (!isset($composerConfig['extra']['typo3/cms']['app-dir'])) {
            $composerConfig['extra']['typo3/cms']['app-dir'] = '.Build';
        }

        if (!isset($composerConfig['extra']['typo3/cms']['web-dir'])) {
            $composerConfig['extra']['typo3/cms']['web-dir'] = '.Build/public';
        }

        ksort($composerConfig['extra']['typo3/cms']);

        return json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
