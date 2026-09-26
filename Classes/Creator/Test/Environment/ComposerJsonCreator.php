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
        $composerConfig['require-dev']['ergebnis/composer-normalize'] ??= '^2.44';

        $composerConfig['require-dev']['phpstan/phpstan'] ??= '^2.1.25';

        $composerConfig['require-dev']['phpunit/phpunit'] ??= '^11.2.5';

        $composerConfig['require-dev']['typo3/coding-standards'] ??= '^0.8';

        $composerConfig['require-dev']['typo3/testing-framework'] ??= '^9.0.1';

        ksort($composerConfig['require-dev']);

        $composerConfig['config']['allow-plugins']['ergebnis/composer-normalize'] ??= true;

        ksort($composerConfig['config']['allow-plugins']);

        if (isset($composerConfig['extra']['typo3/cms']['Package']['providesPackages'])
            && $composerConfig['extra']['typo3/cms']['Package']['providesPackages'] === []
        ) {
            $composerConfig['extra']['typo3/cms']['Package']['providesPackages'] = (object)[];
        }

        $composerConfig['config']['bin-dir'] ??= '.Build/bin';

        $composerConfig['config']['vendor-dir'] ??= '.Build/vendor';

        ksort($composerConfig['config']);

        $composerConfig['extra']['typo3/cms']['app-dir'] ??= '.Build';

        $composerConfig['extra']['typo3/cms']['web-dir'] ??= '.Build/public';

        ksort($composerConfig['extra']['typo3/cms']);

        return json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
