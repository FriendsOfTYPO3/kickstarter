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
use FriendsOfTYPO3\Kickstarter\Information\ModelInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ModelCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ModelCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('modelCreationProvider')]
    public function testItCreatesExpectedModel(
        string $modelClassName,
        ?string $mappedTableName,
        ?bool $abstractEntity,
        array $properties,
        string $extensionKey,
        string $composerPackageName,
        string $expectedDir,
        string $inputPath,
        array $createdFileModifications,
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
        $siteSetInfo = new ModelInformation(
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
            $modelClassName,
            $mappedTableName,
            $abstractEntity,
            $properties
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(ModelCreatorService::class);
        $creatorService->create($siteSetInfo);

        self::assertCount(count($createdFileModifications), $siteSetInfo->getCreatorInformation()->getFileModifications());
        foreach ($createdFileModifications as $createdFileModification) {
            self::assertEquals($createdFileModification, $siteSetInfo->getCreatorInformation()->getFileModifications()[0]->getFileModificationType());
        }

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function modelCreationProvider(): array
    {
        return [
            'add_model_with_mapping' => [
                'modelClassName' => 'MyOtherModel',
                'mappedTableName' => 'tx_myextension_domain_model_mymodel',
                'abstractEntity' => true,
                'properties' => [
                    [
                        'propertyName' => 'yyy',
                        'dataType' => 'string',
                        'defaultValue' => '',
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_model_and_mapping',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_model',
                'createdFileModifications' => [
                    FileModificationType::CREATED,
                    FileModificationType::CREATED,
                ],
            ],
            'add_model_with_initializeObject' => [
                'modelClassName' => 'MyOtherModel',
                'mappedTableName' => 'tx_myextension_domain_model_mymodel',
                'abstractEntity' => true,
                'properties' => [
                    [
                        'propertyName' => 'yyy',
                        'dataType' => ObjectStorage::class,
                        'initializeObject' => true,
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_model_and_initializeObject',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_model',
                'createdFileModifications' => [
                    FileModificationType::CREATED,
                    FileModificationType::CREATED,
                ],
            ],
            'modify_model_add_property' => [
                'modelClassName' => 'MyModel',
                'mappedTableName' => 'tx_myextension_domain_model_mymodel',
                'abstractEntity' => true,
                'properties' => [
                    [
                        'propertyName' => 'xxx',
                        'dataType' => 'string',
                        'defaultValue' => '',
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_model_modified',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_model',
                'createdFileModifications' => [
                    FileModificationType::MODIFIED,
                ],
            ],
            'modify_model_add_property_datetime' => [
                'modelClassName' => 'MyModel',
                'mappedTableName' => 'tx_myextension_domain_model_mymodel',
                'abstractEntity' => true,
                'properties' => [
                    [
                        'propertyName' => 'xxx',
                        'dataType' => \DateTime::class,
                        'initializeObject' => true,
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_model_modified_datetime',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_model',
                'createdFileModifications' => [
                    FileModificationType::MODIFIED,
                ],
            ],
            'add_model_for_core_table' => [
                'modelClassName' => 'Group',
                'mappedTableName' => 'fe_groups',
                'abstractEntity' => true,
                'properties' => [
                    [
                        'propertyName' => 'title',
                        'dataType' => 'string',
                        'defaultValue' => '',
                    ],
                    [
                        'propertyName' => 'description',
                        'dataType' => 'string',
                        'defaultValue' => '',
                    ],
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_model_fe_group',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
                'createdFileModifications' => [
                    FileModificationType::CREATED,
                    FileModificationType::CREATED,
                ],
            ],
        ];
    }
}
