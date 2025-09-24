<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Enums\FileModificationType;
use FriendsOfTYPO3\Kickstarter\Information\SiteSetInformation;
use FriendsOfTYPO3\Kickstarter\Information\SiteSettingsDefinitionInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SiteSettingsDefinitionCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Settings\CategoryDefinition;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SiteSettingsDefinitionCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('siteSetCreationProvider')]
    public function testItCreatesExpectedSiteSettingsDefinition(
        array $categoryDataArray,
        array $settingDataArray,
        string $identifier,
        string $path,
        string $extensionKey,
        string $composerPackageName,
        string $expectedDir,
        string $inputPath = '',
    ): void {
        $extensionPath = $this->instancePath . '/' . $extensionKey . '/';
        $generatedPath = $this->instancePath . '/' . $extensionKey . '/';

        if (file_exists($generatedPath)) {
            GeneralUtility::rmdir($generatedPath, true);
        }
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $extensionInformation = $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath);
        $categories = [];
        foreach ($categoryDataArray as $categoryData) {
            $categories[] = CategoryDefinition::__set_state($categoryData);
        }
        $settings = [];
        foreach ($settingDataArray as $settingsData) {
            $settings[] = SettingDefinition::__set_state($settingsData);
        }
        // Create the SiteSettingsDefinitionInformation object (assuming it mirrors ExtensionInformation)
        $siteSetInfo = new SiteSettingsDefinitionInformation(
            $extensionInformation,
            new SiteSetInformation(
                $extensionInformation,
                $identifier,
                $path,
            ),
            $categories,
            $settings,
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(SiteSettingsDefinitionCreatorService::class);
        $creatorService->create($siteSetInfo);

        self::assertCount(1, $siteSetInfo->getCreatorInformation()->getFileModifications());
        self::assertEquals(FileModificationType::CREATED, $siteSetInfo->getCreatorInformation()->getFileModifications()[0]->getFileModificationType());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function siteSetCreationProvider(): array
    {
        return [
            'make_site_settings_definition' => [
                'identifier' => 'my-vendor/my-set',
                'path' => 'my_set',
                'categoryDataArray' => [
                    [
                        'key' => 'MyExample',
                        'label' => 'My Example',
                    ],
                ],
                'settingDataArray' => [
                    [
                        'key' => 'MyExample',
                        'type' => 'string',
                        'default' => '',
                        'label' => 'My Example',
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_site_settings_definition',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_set',
            ],
            'make_site_settings_definition_with_enum' => [
                'identifier' => 'my-vendor/my-set',
                'path' => 'my_set',
                'categoryDataArray' => [
                    [
                        'key' => 'MyExample',
                        'label' => 'My Example',
                    ],
                ],
                'settingDataArray' => [
                    [
                        'key' => 'MyExample',
                        'type' => 'string',
                        'default' => '',
                        'label' => 'My Example',
                        'enum' => [
                            'Winter', 'Spring', 'Summer', 'Fall',
                        ],
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_site_settings_definition_with_enum',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_set',
            ],
        ];
    }
}
