<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\SiteSettingsDefinitionCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SiteSettingsDefinitionCommandTest extends FunctionalTestCase
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
    public function executeFailsWhenNoSiteSetExists(): void
    {
        $command = $this->get(SiteSettingsDefinitionCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('contains no site set yet. Use command typo3 make:site-set to create a site set first.', $display);
    }

    #[Test]
    public function executeCreatesSiteSettingsDefinitionSuccessfully(): void
    {
        // Create an existing site set
        $setPath = $this->testExtensionDir . 'Configuration/Sets/my-extension/';
        GeneralUtility::mkdir_deep($setPath);
        file_put_contents($setPath . 'config.yaml', "name: vendor/my-extension\nlabel: 'My Extension'\n");

        $command = $this->get(SiteSettingsDefinitionCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'my-extension',
            'general',
            'General Settings',
            '',
            '',
            'no',
            'my.setting',
            'My Setting Label',
            'string',
            'default_value',
            '',
            'no',
            '',
            'general',
            '',
            'no',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('Enter settings key (alphanumeric with dots allowed, e.g. myExtension.storagePid)', $display);

        $settingsFile = $setPath . 'settings.definitions.yaml';
        self::assertFileExists($settingsFile);

        $content = (string)file_get_contents($settingsFile);
        self::assertStringContainsString('general:', $content);
        self::assertStringContainsString('my.setting:', $content);
    }

    #[Test]
    public function executeDisplaysLowerCamelCaseExtensionKeyInSettingsKeyPrompt(): void
    {
        $exportDir = GeneralUtility::makeInstance(ExtConf::class)->getExportDirectory();
        $extDir = $exportDir . '/sr_feuser_register/';
        GeneralUtility::mkdir_deep($extDir . 'Configuration/Sets/default/');
        file_put_contents($extDir . 'composer.json', json_encode([
            'name' => 'vendor/sr-feuser-register',
            'autoload' => [
                'psr-4' => [
                    'Vendor\\SrFeuserRegister\\' => 'Classes',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($extDir . 'Configuration/Sets/default/config.yaml', "name: vendor/sr-feuser-register\nlabel: 'Register'\n");

        $command = $this->get(SiteSettingsDefinitionCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'sr_feuser_register',
            'default',
            'general',
            'General',
            '',
            '',
            'no',
            'srFeuserRegister.storagePid',
            'Storage PID',
            'int',
            '0',
            '',
            'no',
            'general',
            '',
            'no',
        ]);

        $exitCode = $commandTester->execute([]);
        self::assertSame(Command::SUCCESS, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('Enter settings key (alphanumeric with dots allowed, e.g. srFeuserRegister.storagePid)', $display);

        GeneralUtility::rmdir($extDir, true);
    }
}
