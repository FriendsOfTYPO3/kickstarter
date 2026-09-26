<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\MiddlewareCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MiddlewareCommandTest extends FunctionalTestCase
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
    public function executeCreatesMiddlewareSuccessfullyWithoutBeforeAfter(): void
    {
        $command = $this->get(MiddlewareCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'CustomMiddleware',
            'frontend',
            'myextension/custom',
            'none',
            'none',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        // Verify generated PHP middleware class file
        $middlewareClassFile = $this->testExtensionDir . 'Classes/Middleware/CustomMiddleware.php';
        self::assertFileExists($middlewareClassFile);

        $classContent = (string)file_get_contents($middlewareClassFile);
        self::assertStringContainsString('declare(strict_types=1);', $classContent);
        self::assertStringContainsString('namespace Vendor\MyExtension\Middleware;', $classContent);
        self::assertStringContainsString('class CustomMiddleware implements MiddlewareInterface', $classContent);
        self::assertStringContainsString(
            'public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface',
            $classContent
        );

        // Verify generated Configuration/RequestMiddlewares.php file
        $requestMiddlewaresFile = $this->testExtensionDir . 'Configuration/RequestMiddlewares.php';
        self::assertFileExists($requestMiddlewaresFile);

        /** @var array<string, array<string, array{target: string, before: array<string>, after: array<string>}>> $config */
        $config = require $requestMiddlewaresFile;
        self::assertIsArray($config);
        self::assertArrayHasKey('frontend', $config);
        self::assertArrayHasKey('myextension/custom', $config['frontend']);
        self::assertSame('Vendor\MyExtension\Middleware\CustomMiddleware', $config['frontend']['myextension/custom']['target']);
        self::assertSame([], $config['frontend']['myextension/custom']['before']);
        self::assertSame([], $config['frontend']['myextension/custom']['after']);
    }

    #[Test]
    public function executeWarnsAndRetriesWhenMiddlewareIdentifierAlreadyExists(): void
    {
        $existingMiddlewares = $this->get(MiddlewareStackResolver::class)->resolve('frontend');
        $duplicateIdentifier = 'typo3/cms-frontend/site';
        self::assertTrue($existingMiddlewares->offsetExists($duplicateIdentifier));

        $command = $this->get(MiddlewareCommand::class);
        $commandTester = new CommandTester($command);

        // 1st attempt for identifier enters the existing identifier 'typo3/cms-frontend/site'
        // 2nd attempt enters the valid unique identifier 'myextension/custom'
        $commandTester->setInputs([
            'my_extension',
            'CustomMiddleware',
            'frontend',
            $duplicateIdentifier,
            'myextension/custom',
            'none',
            'none',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString(
            sprintf('The identifier "%s" already exists in the configuration!', $duplicateIdentifier),
            $display
        );

        $requestMiddlewaresFile = $this->testExtensionDir . 'Configuration/RequestMiddlewares.php';
        self::assertFileExists($requestMiddlewaresFile);

        /** @var array<string, array<string, array{target: string, before: array<string>, after: array<string>}>> $config */
        $config = require $requestMiddlewaresFile;
        self::assertArrayHasKey('myextension/custom', $config['frontend']);
    }

    #[Test]
    public function executeConfiguresBeforeAndAfterMiddlewares(): void
    {
        $existingMiddlewares = $this->get(MiddlewareStackResolver::class)->resolve('frontend');
        self::assertTrue($existingMiddlewares->offsetExists('typo3/cms-frontend/site'));
        self::assertTrue($existingMiddlewares->offsetExists('typo3/cms-frontend/timetracker'));

        $command = $this->get(MiddlewareCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'CustomMiddleware',
            'frontend',
            'myextension/custom',
            'typo3/cms-frontend/site',
            'typo3/cms-frontend/timetracker',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $requestMiddlewaresFile = $this->testExtensionDir . 'Configuration/RequestMiddlewares.php';
        self::assertFileExists($requestMiddlewaresFile);

        /** @var array<string, array<string, array{target: string, before: array<string>, after: array<string>}>> $config */
        $config = require $requestMiddlewaresFile;
        self::assertIsArray($config);
        self::assertArrayHasKey('frontend', $config);
        self::assertArrayHasKey('myextension/custom', $config['frontend']);
        self::assertSame(['typo3/cms-frontend/site'], $config['frontend']['myextension/custom']['before']);
        self::assertSame(['typo3/cms-frontend/timetracker'], $config['frontend']['myextension/custom']['after']);
    }

    #[Test]
    public function executeCreatesMiddlewareForBackendStack(): void
    {
        $command = $this->get(MiddlewareCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'BackendCustomMiddleware',
            'backend',
            'myextension/backend-custom',
            'none',
            'none',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $middlewareClassFile = $this->testExtensionDir . 'Classes/Middleware/BackendCustomMiddleware.php';
        self::assertFileExists($middlewareClassFile);

        $requestMiddlewaresFile = $this->testExtensionDir . 'Configuration/RequestMiddlewares.php';
        self::assertFileExists($requestMiddlewaresFile);

        /** @var array<string, array<string, array{target: string, before: array<string>, after: array<string>}>> $config */
        $config = require $requestMiddlewaresFile;
        self::assertIsArray($config);
        self::assertArrayHasKey('backend', $config);
        self::assertArrayHasKey('myextension/backend-custom', $config['backend']);
    }
}
