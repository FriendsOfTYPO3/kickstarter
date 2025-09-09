<?php

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Enums\FileModificationType;
use FriendsOfTYPO3\Kickstarter\Information\LocallangInformation;
use FriendsOfTYPO3\Kickstarter\Information\SiteSetInformation;
use FriendsOfTYPO3\Kickstarter\Information\TransUnitInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\LocallangCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LocallangCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('locallangCreationProvider')]
    public function testItCreatesExpectedLocallang(
        string $fileName,
        \DateTime $creationDate,
        array $transUnits,
        FileModificationType $fileModification,
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
        $siteSetInfo = new LocallangInformation(
            $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
            $fileName,
            $creationDate,
            $transUnits
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(LocallangCreatorService::class);
        $creatorService->create($siteSetInfo);

        self::assertCount(1, $siteSetInfo->getCreatorInformation()->getFileModifications());
        self::assertEquals($fileModification, $siteSetInfo->getCreatorInformation()->getFileModifications()[0]->getFileModificationType());

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function locallangCreationProvider(): array
    {
        return [
            'create_locallang' => [
                'fileName' => 'locallang.xlf',
                'creationDate' => \DateTime::createFromFormat(\DateTime::ATOM, '2025-03-05T17:08:40Z'),
                'transUnits' => [
                    new TransUnitInformation('mlang_tabs_tab', 'Ext Kickstarter'),
                    new TransUnitInformation('mlang_labels_tabdescr', 'Extension Kickstarter'),
                    new TransUnitInformation('mlang_labels_tablabel', 'Create your own Extbase-based Extension'),
                ],
                'fileModification' => FileModificationType::MODIFIED,
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_locallang',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension',
            ],
            'make_locallang_add_transunit' => [
                'fileName' => 'locallang.xlf',
                'creationDate' => \DateTime::createFromFormat(\DateTime::ATOM, '2025-03-05T17:08:40Z'),
                'transUnits' => [
                    new TransUnitInformation('new_trans_unit', 'New Trans Unit'),
                ],
                'fileModification' => FileModificationType::MODIFIED,
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/make_locallang_add_transunit',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_locallang',
            ],
        ];
    }
}
