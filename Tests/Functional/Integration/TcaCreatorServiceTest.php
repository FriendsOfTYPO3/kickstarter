<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Enums\TcaFieldType;
use FriendsOfTYPO3\Kickstarter\Information\TableInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\TableCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('siteSetCreationProvider')]
    public function testItCreatesExpectedSiteSet(
        string $tableName,
        string $title,
        string $label,
        array $columns,
        string $extensionKey,
        string $composerPackageName,
        string $expectedDir,
        string $inputPath = '',
        int $expectedCount = 1,
    ): void {
        $extensionPath = $this->instancePath . '/' . $extensionKey . '/';
        $generatedPath = $this->instancePath . '/' . $extensionKey . '/';

        if (file_exists($generatedPath)) {
            GeneralUtility::rmdir($generatedPath, true);
        }
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $columnsTca = [];
        foreach ($columns as $key => $column) {
            $columnsTca[$key] = [
                'label' => $column['label'],
                'type_info' => TcaFieldType::from($column['type_info']),
            ];
        }

        // Create the SiteSetInformation object (assuming it mirrors ExtensionInformation)
        $siteSetInfo = new TableInformation(
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
            $tableName,
            $title,
            $label,
            $columnsTca,
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(TableCreatorService::class);
        $creatorService->create($siteSetInfo);

        self::assertCount($expectedCount, $siteSetInfo->getCreatorInformation()->getFileModifications());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function siteSetCreationProvider(): array
    {
        return [
            'make_table_basic' => [
                'tableName' => 'tx_myextension_mytable',
                'title' => 'My Table',
                'label' => 'title',
                'columns' => [
                    'my_input' => [
                        'label' => 'My Input',
                        'config' => [
                            'type' => 'input',
                        ],
                        'type_info' => 'input',
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_table_basic',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
                'expectedCount' => 1,
            ],
            'make_table_passtrough' => [
                'tableName' => 'tx_myextension_mytable',
                'title' => 'My Table',
                'label' => 'title',
                'columns' => [
                    'my_input' => [
                        'label' => 'My Input',
                        'type_info' => 'input',
                    ],
                    'my_passthrough' => [
                        'label' => 'My Passthrough',
                        'type_info' => 'passthrough',
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_table_passtrough',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
                'expectedCount' => 2,
            ],
            'make_table_article' => [
                'tableName' => 'tx_myextension_domain_model_article',
                'title' => 'Article',
                'label' => 'title',
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                        'type_info' => 'input',
                    ],
                    'content' => [
                        'label' => 'Content',
                        'type_info' => 'text-rte',
                    ],
                    'images' => [
                        'label' => 'Images',
                        'type_info' => 'file-images',
                    ],
                    'featured' => [
                        'label' => 'Do you want to feature this blog post? ',
                        'type_info' => 'check-toggle',
                    ],
                    'published_on' => [
                        'label' => 'When was this article first published? ',
                        'type_info' => 'datetime',
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_table_article',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
                'expectedCount' => 1,
            ],
        ];
    }
}
