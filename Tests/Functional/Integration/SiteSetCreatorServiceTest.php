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
use FriendsOfTYPO3\Kickstarter\Service\Creator\SiteSetCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SiteSetCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('siteSetCreationProvider')]
    public function testItCreatesExpectedSiteSet(
        string $identifier,
        string $path,
        string $label,
        array $dependencies,
        bool $hidden,
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
        $siteSetInfo = new SiteSetInformation(
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
            $identifier,
            $path,
            $label,
            $dependencies,
            $hidden
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(SiteSetCreatorService::class);
        $creatorService->create($siteSetInfo);

        self::assertCount(1, $siteSetInfo->getCreatorInformation()->getFileModifications());
        self::assertEquals(FileModificationType::CREATED, $siteSetInfo->getCreatorInformation()->getFileModifications()[0]->getFileModificationType());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function siteSetCreationProvider(): array
    {
        return [
            'make_siteset' => [
                'identifier' => 'my-vendor/my-set',
                'path' => 'my_set',
                'label' => 'My Set',
                'dependencies' => [
                    'my-vendor/my-other-set',
                    'other-namespace/fancy-carousel',
                ],
                'hidden' => false,
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_site_set',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
            'make_site_set_hidden' => [
                'identifier' => 'my-vendor/my-set',
                'path' => 'my_set',
                'label' => 'My Set',
                'dependencies' => [],
                'hidden' => true,
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_site_set_hidden',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
            'make_site_set_no_dependency' => [
                'identifier' => 'my-vendor/my-set',
                'path' => 'my_set',
                'label' => 'My Set',
                'dependencies' => [],
                'hidden' => false,
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_site_set_no_dependency',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
        ];
    }

    #[Test]
    #[DataProvider('siteSetOverridingProvider')]
    public function testSiteSetConfigNotModified(
        string $identifier,
        string $path,
        string $label,
        array $dependencies,
        bool $hidden,
        string $extensionKey,
        string $composerPackageName,
        string $inputPath = '',
    ): void {
        // Build paths based on $this->instancePath
        $extensionPath = $this->instancePath . '/' . $extensionKey . '/';
        $generatedPath = $this->instancePath . '/' . $extensionKey . '/';

        if (file_exists($generatedPath)) {
            GeneralUtility::rmdir($generatedPath, true);
        }
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
            self::assertFileExists($generatedPath . 'composer.json');
        }

        // Create the SiteSetInformation object (assuming it mirrors ExtensionInformation)
        $siteSetInfo = new SiteSetInformation(
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
            $identifier,
            $path,
            $label,
            $dependencies,
            $hidden
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(SiteSetCreatorService::class);
        $creatorService->create($siteSetInfo);

        self::assertCount(1, $siteSetInfo->getCreatorInformation()->getFileModifications());
        self::assertEquals(FileModificationType::MODIFICATION_FAILED, $siteSetInfo->getCreatorInformation()->getFileModifications()[0]->getFileModificationType());
    }

    public static function siteSetOverridingProvider(): array
    {
        return [
            'make_siteset' => [
                'identifier' => 'my-vendor/my-set',
                'path' => 'my_set',
                'label' => 'My Set',
                'dependencies' => [
                    'my-vendor/my-other-set',
                    'other-namespace/fancy-carousel',
                ],
                'hidden' => false,
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_set',
            ],
        ];
    }
}
