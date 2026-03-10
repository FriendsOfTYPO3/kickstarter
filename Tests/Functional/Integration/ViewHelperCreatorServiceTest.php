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
use FriendsOfTYPO3\Kickstarter\Information\ViewHelperInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ViewHelperCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ViewHelperCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('viewHelperCreationProvider')]
    public function testItCreatesExpectedViewHelpers(
        string $extensionKey,
        string $composerPackageName,
        string $expectedDir,
        string $inputPath,
        string $name,
        bool $tagBased = false,
        string $tagName = '',
        array $arguments = [],
        bool $escapeOutput = false,
    ): void {
        $extensionPath = $this->instancePath . '/' . $extensionKey . '/';
        $generatedPath = $this->instancePath . '/' . $extensionKey . '/';

        if (file_exists($generatedPath)) {
            GeneralUtility::rmdir($generatedPath, true);
        }
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $viewHelperInfo = new ViewHelperInformation(
            extensionInformation: $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
            name: $name,
            tagBased: $tagBased,
            tagName: $tagName,
            arguments: $arguments,
            escapeOutput: $escapeOutput,
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(ViewHelperCreatorService::class);
        $creatorService->create($viewHelperInfo);

        self::assertCount(1, $viewHelperInfo->getCreatorInformation()->getFileModifications());
        self::assertEquals(FileModificationType::CREATED, $viewHelperInfo->getCreatorInformation()->getFileModifications()[0]->getFileModificationType());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function viewHelperCreationProvider(): array
    {
        return [
            'make_viewhelper' => [
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_viewhelper',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
                'name' => 'Example',
            ],
        ];
    }
}
