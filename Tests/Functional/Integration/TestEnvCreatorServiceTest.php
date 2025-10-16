<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Information\TestEnvInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\TestEnvCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TestEnvCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('dataProvider')]
    public function testItCreatesExpectedTest(
        string $extensionKey,
        string $composerPackageName,
        string $expectedDir,
        string $inputPath,
        int $fileCount,
    ): void {
        $extensionPath = $this->instancePath . '/' . $extensionKey . '/';
        $generatedPath = $this->instancePath . '/' . $extensionKey . '/';

        if (file_exists($generatedPath)) {
            GeneralUtility::rmdir($generatedPath, true);
        }
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $siteSetInfo = new TestEnvInformation(
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(TestEnvCreatorService::class);
        $creatorService->create($siteSetInfo);

        self::assertCount($fileCount, $siteSetInfo->getCreatorInformation()->getFileModifications());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function dataProvider(): array
    {
        return [
            'make_testenv' => [
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_testenv',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
                'fileCount' => 11,
            ],
        ];
    }
}
