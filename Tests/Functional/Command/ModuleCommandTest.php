<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\ModuleCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ModuleCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'frontend',
        'backend',
    ];

    protected array $testExtensionsToLoad = [
        'friendsoftypo3/kickstarter',
    ];

    private string $testExtensionDir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $exportDir = GeneralUtility::makeInstance(ExtConf::class)->getExportDirectory();
        $this->testExtensionDir = $exportDir . '/my_extension/';
        if (is_dir($this->testExtensionDir)) {
            GeneralUtility::rmdir($this->testExtensionDir, true);
        }
        GeneralUtility::mkdir_deep($this->testExtensionDir . 'Classes/Controller/');

        $composerData = [
            'name' => 'vendor/my-extension',
            'autoload' => [
                'psr-4' => [
                    'Vendor\\MyExtension\\' => 'Classes',
                ],
            ],
        ];

        file_put_contents(
            $this->testExtensionDir . 'composer.json',
            json_encode($composerData, JSON_THROW_ON_ERROR)
        );

        $controllerContent = <<<'PHP'
<?php
declare(strict_types=1);

namespace Vendor\MyExtension\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class MyModuleController extends ActionController
{
    public function indexAction(): void
    {
    }
}
PHP;
        file_put_contents($this->testExtensionDir . 'Classes/Controller/MyModuleController.php', $controllerContent);
    }

    protected function tearDown(): void
    {
        if ($this->testExtensionDir !== '' && is_dir($this->testExtensionDir)) {
            GeneralUtility::rmdir($this->testExtensionDir, true);
        }

        parent::tearDown();
    }

    #[Test]
    public function executeCreatesBackendModuleSuccessfully(): void
    {
        $command = $this->get(ModuleCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'yes',
            'web',
            'web_myextension',
            'bottom',
            'user',
            '*',
            '/module/web/myextension',
            'My Module Title',
            'My Module Description',
            'Short description',
            'actions-system-extension-configure',
            'MyModuleController',
            'indexAction',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $modulesFile = $this->testExtensionDir . 'Configuration/Backend/Module.php';
        self::assertFileExists($modulesFile);

        $content = (string)file_get_contents($modulesFile);
        self::assertStringContainsString('web_myextension', $content);
        self::assertStringContainsString('My Module Title', $content);
    }
}
