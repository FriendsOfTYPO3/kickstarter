<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\ViewHelperCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ViewHelperCommandTest extends FunctionalTestCase
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
    public function executeCreatesSimpleViewHelperWithoutArgumentsSuccessfully(): void
    {
        $command = $this->get(ViewHelperCommand::class);
        self::assertSame('make:viewhelper', $command->getName());
        self::assertSame([], $command->getAliases());
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'Gravatar',
            'no', // not tag-based
            '',   // finish arguments
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $viewHelperFile = $this->testExtensionDir . 'Classes/ViewHelpers/GravatarViewHelper.php';
        self::assertFileExists($viewHelperFile);

        $content = (string)file_get_contents($viewHelperFile);
        self::assertStringContainsString('declare(strict_types=1);', $content);
        self::assertStringContainsString('namespace Vendor\MyExtension\ViewHelpers;', $content);
        self::assertStringContainsString('use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;', $content);
        self::assertStringContainsString('final class GravatarViewHelper extends AbstractViewHelper', $content);
        self::assertStringContainsString('public function initializeArguments(): void', $content);
        self::assertStringContainsString('public function render(): string', $content);
        self::assertStringContainsString('return \'ViewHelper GravatarViewHelper content. \';', $content);
    }

    #[Test]
    public function executeCreatesViewHelperWithArgumentsSuccessfully(): void
    {
        $command = $this->get(ViewHelperCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'Gravatar',
            'no', // not tag-based
            'emailAddress',
            'string',
            'The email address to resolve the gravatar for',
            'yes',
            'size',
            'int',
            'The size of the gravatar, ranging from 1 to 512',
            'no',
            '80',
            '', // finish arguments
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $viewHelperFile = $this->testExtensionDir . 'Classes/ViewHelpers/GravatarViewHelper.php';
        self::assertFileExists($viewHelperFile);

        $content = (string)file_get_contents($viewHelperFile);
        self::assertStringContainsString('declare(strict_types=1);', $content);
        self::assertStringContainsString('namespace Vendor\MyExtension\ViewHelpers;', $content);
        self::assertStringContainsString('use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;', $content);
        self::assertStringContainsString('final class GravatarViewHelper extends AbstractViewHelper', $content);
        self::assertStringContainsString('public function initializeArguments(): void', $content);

        // Required argument: emailAddress
        self::assertStringContainsString('\'emailAddress\',', $content);
        self::assertStringContainsString('\'string\',', $content);
        self::assertStringContainsString('\'The email address to resolve the gravatar for\',', $content);
        self::assertStringContainsString('true,', $content);

        // Optional argument with default value: size
        self::assertStringContainsString('\'size\',', $content);
        self::assertStringContainsString('\'int\',', $content);
        self::assertStringContainsString('\'The size of the gravatar, ranging from 1 to 512\',', $content);
        self::assertStringContainsString('false,', $content);
        self::assertStringContainsString('80,', $content);

        // Assignments and render method
        self::assertStringContainsString('$emailAddress = $this->arguments[\'emailAddress\'];', $content);
        self::assertStringContainsString('$size = $this->arguments[\'size\'];', $content);
        self::assertStringContainsString('sprintf(', $content);
        self::assertStringContainsString('ViewHelper GravatarViewHelper content. The following arguments were passed: emailAddress: %s, size: %s', $content);
    }

    #[Test]
    public function executeCreatesViewHelperWithCorrectedArgumentNameAndCustomClass(): void
    {
        $command = $this->get(ViewHelperCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'UserAvatar',
            'no',          // not tag-based
            'user-object', // invalid lowerCamelCase, triggers correction suggestion
            'yes',         // confirm suggested correction "userObject"
            'Custom class',
            '\\Vendor\\MyExtension\\Domain\\Model\\User',
            'The user entity',
            'yes',
            '', // finish arguments
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $viewHelperFile = $this->testExtensionDir . 'Classes/ViewHelpers/UserAvatarViewHelper.php';
        self::assertFileExists($viewHelperFile);

        $content = (string)file_get_contents($viewHelperFile);
        self::assertStringContainsString('final class UserAvatarViewHelper extends AbstractViewHelper', $content);
        self::assertStringContainsString('\'userObject\',', $content);
        self::assertStringContainsString('\'Vendor\\MyExtension\\Domain\\Model\\User\',', $content);
        self::assertStringContainsString('\'The user entity\',', $content);
        self::assertStringContainsString('true,', $content);
        self::assertStringContainsString('$userObject = $this->arguments[\'userObject\'];', $content);
    }

    #[Test]
    public function executeCreatesTagBasedViewHelperSuccessfully(): void
    {
        $command = $this->get(ViewHelperCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'Badge',
            'yes',  // tag-based
            'span', // tag name
            '',     // finish arguments
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $viewHelperFile = $this->testExtensionDir . 'Classes/ViewHelpers/BadgeViewHelper.php';
        self::assertFileExists($viewHelperFile);

        $content = (string)file_get_contents($viewHelperFile);
        self::assertStringContainsString('declare(strict_types=1);', $content);
        self::assertStringContainsString('namespace Vendor\MyExtension\ViewHelpers;', $content);
        self::assertStringContainsString('use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;', $content);
        self::assertStringContainsString('final class BadgeViewHelper extends AbstractTagBasedViewHelper', $content);
        self::assertStringContainsString('protected string $tagName = \'span\';', $content);
        self::assertStringContainsString('public function initializeArguments(): void', $content);
        self::assertStringContainsString('parent::initializeArguments();', $content);
        self::assertStringContainsString('public function render(): string', $content);
        self::assertStringContainsString('$this->tag->setContent($this->renderChildren());', $content);
        self::assertStringContainsString('return $this->tag->render();', $content);
    }
}
