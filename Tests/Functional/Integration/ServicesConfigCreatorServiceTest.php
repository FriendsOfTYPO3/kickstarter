<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Information\ServicesConfigInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ServicesConfigCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ServicesConfigCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('servicesConfigCreationProvider')]
    public function testItCreatesExpectedServicesConfig(
        array $info,
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

        // Create the SiteSetInformation object (assuming it mirrors ExtensionInformation)
        $info = ServicesConfigInformation::fromArray(
            $info,
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(ServicesConfigCreatorService::class);
        $creatorService->create($info);

        self::assertCount(2, $info->getCreatorInformation()->getFileModifications());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function servicesConfigCreationProvider(): array
    {
        return [
            'make_services_yaml' => [
                'info' => [],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_services_yaml',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
            'make_services_yaml_no_exclude' => [
                'info' => [
                    'excludeModels' => false,
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_services_yaml_no_exclude',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
            'make_services_yaml_all_false' => [
                'info' => [
                    'autowire' => false,
                    'autoconfigure' => false,
                    'public' => false,
                    'excludeModels' => false,
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_services_yaml_all_false',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
            'make_services_yaml_public_true' => [
                'info' => [
                    'public' => true,
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_services_yaml_public_true',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
            'override_services_yaml_public_true' => [
                'info' => [
                    'public' => true,
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/modify_services_yaml_public_true',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_services_yaml',
            ],
        ];
    }
}
