<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\ControllerCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ControllerCommandTest extends FunctionalTestCase
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
        GeneralUtility::mkdir_deep($this->testExtensionDir);

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
    }

    protected function tearDown(): void
    {
        if ($this->testExtensionDir !== '' && is_dir($this->testExtensionDir)) {
            GeneralUtility::rmdir($this->testExtensionDir, true);
        }

        parent::tearDown();
    }

    #[Test]
    public function executeCreatesExtbaseControllerSuccessfully(): void
    {
        $command = $this->get(ControllerCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'ItemController',
            'yes',
            'listAction',
            'no',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $controllerFile = $this->testExtensionDir . 'Classes/Controller/ItemController.php';
        self::assertFileExists($controllerFile);

        $content = (string)file_get_contents($controllerFile);
        self::assertStringContainsString('declare(strict_types=1);', $content);
        self::assertStringContainsString('namespace Vendor\MyExtension\Controller;', $content);
        self::assertStringContainsString('class ItemController extends ActionController', $content);
        self::assertStringContainsString('public function listAction(): ResponseInterface', $content);
    }

    #[Test]
    public function executeCreatesNonExtbaseControllerSuccessfully(): void
    {
        $command = $this->get(ControllerCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'PlainController',
            'no',
            'indexAction',
            'no',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $controllerFile = $this->testExtensionDir . 'Classes/Controller/PlainController.php';
        self::assertFileExists($controllerFile);

        $content = (string)file_get_contents($controllerFile);
        self::assertStringContainsString('declare(strict_types=1);', $content);
        self::assertStringContainsString('namespace Vendor\MyExtension\Controller;', $content);
        self::assertStringContainsString('class PlainController', $content);
        self::assertStringNotContainsString('extends ActionController', $content);
    }
}
