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
use FriendsOfTYPO3\Kickstarter\Enums\ValidatorType;
use FriendsOfTYPO3\Kickstarter\Information\ValidatorInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ValidatorCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ValidatorCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('siteSetCreationProvider')]
    public function testItCreatesExpectedSiteSet(
        string $validatorName,
        string $validatorType,
        string $modelName,
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
        $siteSetInfo = new ValidatorInformation(
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
            $validatorName,
            ValidatorType::from($validatorType),
            $modelName,
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(ValidatorCreatorService::class);
        $creatorService->create($siteSetInfo);

        self::assertCount(1, $siteSetInfo->getCreatorInformation()->getFileModifications());
        self::assertEquals(FileModificationType::CREATED, $siteSetInfo->getCreatorInformation()->getFileModifications()[0]->getFileModificationType());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function siteSetCreationProvider(): array
    {
        return [
            'make_validator' => [
                'validatorName' => 'ExampleValidator',
                'validatorType' => 'Model',
                'modelName' => 'SomeModel',
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_validator',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
        ];
    }
}
