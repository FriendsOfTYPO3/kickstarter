<?php

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Enums\FileModificationType;
use FriendsOfTYPO3\Kickstarter\Information\ModuleInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ModuleCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ModuleCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    #[Test]
    #[DataProvider('moduleExtbaseCreationProvider')]
    public function testItCreatesModuleExtbase(
        string $extensionKey,
        string $composerPackageName,
        string $expectedDir,
        string $inputPath,
        array $createdFileModifications,
        string $path,
        string $shortDescription,
        string $description,
        string $access,
        string $workspaces,
        string $title,
        string $iconIdentifier,
        string $identifier,
        bool $extbase,
        string $parent,
        string $position,
        array $referencedControllerActions,
        array $referencedRoutes,
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
        $moduleInfo = new ModuleInformation(
            extensionInformation: $this->getExtensionInformation($extensionKey, $composerPackageName, $extensionPath),
            path: $path,
            shortDescription: $shortDescription,
            description: $description,
            access: $access,
            workspaces: $workspaces,
            extensionName: $extensionKey,
            title: $title,
            iconIdentifier: $iconIdentifier,
            identifier: $identifier,
            isExtbaseModule: $extbase,
            parent: $parent,
            position: $position,
            referencedControllerActions: $referencedControllerActions,
            referencedRoutes: $referencedRoutes,
        );
        if ($inputPath !== '') {
            FileSystemHelper::copyDirectory($inputPath, $generatedPath);
        }

        $creatorService = $this->get(ModuleCreatorService::class);
        $creatorService->create($moduleInfo);

        self::assertCount(count($createdFileModifications), $moduleInfo->getCreatorInformation()->getFileModifications());
        foreach ($createdFileModifications as $createdFileModification) {
            self::assertEquals($createdFileModification, $moduleInfo->getCreatorInformation()->getFileModifications()[0]->getFileModificationType());
        }

        // Compare generated files with fixtures
        $this->assertDirectoryEquals($expectedDir, $generatedPath);
    }

    public static function moduleExtbaseCreationProvider(): array
    {
        return [
            'add_module' => [
                'extensionKey' => 'my_extension',
                'composerPackageName' => 'my-vendor/my-extension',
                'expectedDir' => __DIR__ . '/Fixtures/my_extension_with_module',
                'inputPath' => __DIR__ . '/Fixtures/input/my_extension_with_controller',
                'createdFileModifications' => [
                    FileModificationType::CREATED,
                ],
                'path' => '/module/web/myextension',
                'shortDescription' => 'Short description of the module',
                'description' => 'Full description of the module functionality',
                'access' => 'user',
                'workspaces' => '*',
                'title' => 'My Extension Module',
                'iconIdentifier' => 'my_extension-module-icon',
                'identifier' => 'web_MyExtension',
                'extbase' => true,
                'parent' => 'web',
                'position' => 'bottom',
                'referencedControllerActions' => [
                    'TestController' => ['indexAction', 'testAction'],
                ],
                'referencedRoutes' => [],
            ],
        ];
    }
}
