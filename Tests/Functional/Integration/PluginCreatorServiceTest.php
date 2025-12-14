<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Information\PluginInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\PluginCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PluginCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('servicesConfigCreationProvider')]
    public function testItCreatesExpectedServicesConfig(
        array $info,
        string $extensionKey,
        string $composerPackageName,
        string $expectedDir,
        string $inputPath = '',
        int $expectedCount = 10,
    ): void {
        $extensionPath = $this->instancePath . '/' . $extensionKey . '/';
        $generatedPath = $this->instancePath . '/' . $extensionKey . '/';

        if (file_exists($generatedPath)) {
            GeneralUtility::rmdir($generatedPath, true);
        }
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        // Create the SiteSetInformation object (assuming it mirrors ExtensionInformation)
        $pluginInfo = PluginInformation::fromArray(
            $info,
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(PluginCreatorService::class);
        $creatorService->create($pluginInfo);

        self::assertCount($expectedCount, $pluginInfo->getCreatorInformation()->getFileModifications());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function servicesConfigCreationProvider(): array
    {
        return [
            'make_services_yaml' => [
                'info' => [
                    'extbasePlugin' => true,
                    'pluginLabel' => 'News Listing',
                    'pluginName' => 'NewsList',
                    'pluginDescription' => 'Displays a list of news records with filtering options.',
                    'referencedControllerActions' => [
                        'TestController' => [
                            'cached' => ['listAction', 'showAction'],
                            'uncached' => ['listAction'],
                        ],
                    ],
                    'typoScriptCreation' => true,
                    'set' => 'main',
                    'templatePath' => 'EXT:my_extension/Resources/Private/',
                    'templates' => [
                        'EXT:my_extension/Resources/Private/Templates/Test/List.fluid.html',
                        'EXT:my_extension/Resources/Private/Templates/Test/Show.fluid.html',
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_plugin',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_controller',
                'expectedCount' => 12,
            ],
        ];
    }
}
