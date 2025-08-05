<?php

namespace StefanFroemken\ExtKickstarter\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ExtKickstarter\Enums\FileModificationType;
use StefanFroemken\ExtKickstarter\Information\ModelInformation;
use StefanFroemken\ExtKickstarter\Information\SiteSetInformation;
use StefanFroemken\ExtKickstarter\Service\Creator\ModelCreatorService;
use StefanFroemken\ExtKickstarter\Service\Creator\SiteSetCreatorService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ModelCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('modelCreationProvider')]
    public function testItCreatesExpectedModel(
        string  $modelClassName,
        ?string $mappedTableName,
        ?bool   $abstractEntity,
        array   $properties,
        string  $extensionKey,
        string  $composerPackageName,
        string  $expectedDir,
        string  $inputPath,
        array   $createdFileModifications,
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
                    ]
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_model_and_mapping',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_model',
                'createdFileModifications' => [
                    FileModificationType::CREATED,
                    FileModificationType::CREATED,
                ]
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
                    ]
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_model_and_initializeObject',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_model',
                'createdFileModifications' => [
                    FileModificationType::CREATED,
                    FileModificationType::CREATED,
                ]
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
                    ]
                ],
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_model_modified',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_model',
                'createdFileModifications' => [
                    FileModificationType::MODIFIED,
                ]
            ],
        ];
    }
}
