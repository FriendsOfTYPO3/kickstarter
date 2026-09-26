<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\ServicesYamlCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ServicesYamlCommandTest extends FunctionalTestCase
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
    public function executeCreatesBasicServicesYamlSuccessfully(): void
    {
        $command = $this->get(ServicesYamlCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'yes',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $servicesFile = $this->testExtensionDir . 'Configuration/Services.yaml';
        self::assertFileExists($servicesFile);

        $content = (string)file_get_contents($servicesFile);
        self::assertStringContainsString('services:', $content);
        self::assertStringContainsString('Vendor\MyExtension\:', $content);
        self::assertStringContainsString('autowire: true', $content);
        self::assertStringContainsString('autoconfigure: true', $content);
    }

    #[Test]
    public function executeCreatesCustomServicesYamlSuccessfully(): void
    {
        $command = $this->get(ServicesYamlCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'no',
            'yes',
            'yes',
            'no',
            'yes',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $servicesFile = $this->testExtensionDir . 'Configuration/Services.yaml';
        self::assertFileExists($servicesFile);

        $content = (string)file_get_contents($servicesFile);
        self::assertStringContainsString('services:', $content);
        self::assertStringContainsString('Vendor\MyExtension\:', $content);
    }
}
