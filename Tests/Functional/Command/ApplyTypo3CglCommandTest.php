<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\ApplyTypo3CglCommand;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Service\Creator\RepositoryCreatorService;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ApplyTypo3CglCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'frontend',
        'backend',
    ];

    protected array $testExtensionsToLoad = [
        'friendsoftypo3/kickstarter',
    ];

    private bool $originalComposerMode = false;

    private string $originalProjectPath = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalComposerMode = Environment::isComposerMode();
        $this->originalProjectPath = Environment::getProjectPath();
    }

    protected function tearDown(): void
    {
        Environment::initialize(
            Environment::getContext(),
            $this->originalComposerMode,
            Environment::isCli(),
            $this->originalProjectPath,
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            'UNIX'
        );

        parent::tearDown();
    }

    #[Test]
    public function executeFailsWhenNotRunningInComposerMode(): void
    {
        $repositoryCreatorMock = $this->createMock(RepositoryCreatorService::class);
        $realCommand = $this->get(ApplyTypo3CglCommand::class);
        $property = new \ReflectionProperty(ApplyTypo3CglCommand::class, 'questionCollection');
        /** @var QuestionCollection $questionCollection */
        $questionCollection = $property->getValue($realCommand);

        $command = new ApplyTypo3CglCommand($repositoryCreatorMock, $questionCollection);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('This command requires TYPO3 to be running in Composer mode.', $display);
    }

    #[Test]
    public function executeFailsWhenComposerJsonNotFoundInComposerMode(): void
    {
        $tempProjectPath = Environment::getVarPath() . '/tests/temp-project-' . uniqid();
        GeneralUtility::mkdir_deep($tempProjectPath);

        Environment::initialize(
            Environment::getContext(),
            true,
            Environment::isCli(),
            $tempProjectPath,
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            'UNIX'
        );

        $repositoryCreatorMock = $this->createMock(RepositoryCreatorService::class);
        $realCommand = $this->get(ApplyTypo3CglCommand::class);
        $property = new \ReflectionProperty(ApplyTypo3CglCommand::class, 'questionCollection');
        /** @var QuestionCollection $questionCollection */
        $questionCollection = $property->getValue($realCommand);

        $command = new ApplyTypo3CglCommand($repositoryCreatorMock, $questionCollection);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        GeneralUtility::rmdir($tempProjectPath, true);

        self::assertSame(Command::FAILURE, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('No composer.json found.', $display);
    }

    #[Test]
    public function executeFailsWhenPhpCsFixerBinaryNotFound(): void
    {
        $tempProjectPath = Environment::getVarPath() . '/tests/temp-project-' . uniqid();
        GeneralUtility::mkdir_deep($tempProjectPath);

        file_put_contents(
            $tempProjectPath . '/composer.json',
            json_encode(['config' => ['bin-dir' => $tempProjectPath . '/nonexistent-bin']], JSON_THROW_ON_ERROR)
        );

        Environment::initialize(
            Environment::getContext(),
            true,
            Environment::isCli(),
            $tempProjectPath,
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            'UNIX'
        );

        $repositoryCreatorMock = $this->createMock(RepositoryCreatorService::class);
        $realCommand = $this->get(ApplyTypo3CglCommand::class);
        $property = new \ReflectionProperty(ApplyTypo3CglCommand::class, 'questionCollection');
        /** @var QuestionCollection $questionCollection */
        $questionCollection = $property->getValue($realCommand);

        $command = new ApplyTypo3CglCommand($repositoryCreatorMock, $questionCollection);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        GeneralUtility::rmdir($tempProjectPath, true);

        self::assertSame(Command::FAILURE, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('No php-cs-fixer found', $display);
    }
}
