<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\CommandCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CommandCommandTest extends FunctionalTestCase
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
    public function executeCreatesCommandSuccessfullyWithoutAliases(): void
    {
        $command = $this->get(CommandCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'my_extension:do-something',
            'DoSomethingCommand',
            'Description of do something command',
            'no',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $commandClassFile = $this->testExtensionDir . 'Classes/Command/DoSomethingCommand.php';
        self::assertFileExists($commandClassFile);

        $classContent = (string)file_get_contents($commandClassFile);
        self::assertStringContainsString('declare(strict_types=1);', $classContent);
        self::assertStringContainsString('namespace Vendor\MyExtension\Command;', $classContent);
        self::assertStringContainsString('final class DoSomethingCommand extends Command', $classContent);
        self::assertStringContainsString('name: \'myextension:dosomething\'', $classContent);
        self::assertStringContainsString('description: \'Description of do something command\'', $classContent);

        // Services.yaml is not created by make:command, as #[AsCommand] is autoconfigured
    }

    #[Test]
    public function executeCreatesCommandWithAliases(): void
    {
        $command = $this->get(CommandCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'my_extension:custom-task',
            'CustomTaskCommand',
            'Custom task description',
            'yes',
            'my_extension:task-alias',
            'no',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $commandClassFile = $this->testExtensionDir . 'Classes/Command/CustomTaskCommand.php';
        self::assertFileExists($commandClassFile);

        $classContent = (string)file_get_contents($commandClassFile);
        self::assertStringContainsString('aliases: [', $classContent);
        self::assertStringContainsString('\'myextension:taskalias\'', $classContent);
    }
}
