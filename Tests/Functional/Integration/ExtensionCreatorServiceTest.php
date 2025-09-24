<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\ServicesConfigInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ExtensionCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ExtensionCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('extensionCreationProvider')]
    public function testItCreatesExpectedExtensionFiles(
        string $extensionKey,
        string $composerPackageName,
        string $title,
        string $description,
        string $version,
        string $category,
        string $state,
        string $author,
        string $authorEmail,
        string $authorCompany,
        string $namespaceForAutoload,
        string $expectedDir,
        array $expectedFiles,
    ): void {
        // Build paths based on $this->instancePath
        $extensionPath = $this->instancePath . '/' . $extensionKey . '/';
        $generatedPath = $this->instancePath . '/' . $extensionKey . '/';

        // Build the ExtensionInformation object here
        $extensionInfo = new ExtensionInformation(
            $extensionKey,
            $composerPackageName,
            $title,
            $description,
            $version,
            $category,
            $state,
            $author,
            $authorEmail,
            $authorCompany,
            $namespaceForAutoload,
            $extensionPath
        );

        mkdir($extensionInfo->getExtensionPath(), 0777, true);

        $creatorService = $this->get(ExtensionCreatorService::class);
        $creatorService->create($extensionInfo, new ServicesConfigInformation($extensionInfo));

        self::assertDirectoryExists($generatedPath);

        // Check all expected files dynamically
        foreach ($expectedFiles as $file) {
            self::assertFileExists($generatedPath . '/' . $file, 'Missing expected file: ' . $file);
        }

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function extensionCreationProvider(): array
    {
        return [
            'default extension' => [
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'title' => 'My Extension',
                'description' => 'This is a test extension',
                'version' => '1.0.0',
                'category' => 'plugin',
                'state' => 'stable',
                'author' => 'John Doe',
                'authorEmail' => 'john@example.com',
                'authorCompany' => 'MyCompany',
                'namespaceForAutoload' => 'Vendor\\MyExtension\\',
                'expectedDir' => __DIR__ . '/Fixtures/expected_extension',
                'expectedFiles' => ['ext_emconf.php', 'README.md'],
            ],
        ];
    }
}
