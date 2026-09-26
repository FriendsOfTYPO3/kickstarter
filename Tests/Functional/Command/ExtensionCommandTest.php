<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\ExtensionCommand;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\ServicesConfigInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ExtensionCreatorService;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExtensionCommandTest extends FunctionalTestCase
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
    }

    protected function tearDown(): void
    {
        if ($this->testExtensionDir !== '' && is_dir($this->testExtensionDir)) {
            GeneralUtility::rmdir($this->testExtensionDir, true);
        }

        parent::tearDown();
    }

    #[Test]
    public function executeCollectsInputsAndDelegatesToExtensionCreatorService(): void
    {
        $creatorMock = $this->createMock(ExtensionCreatorService::class);
        $creatorMock->expects(self::once())
            ->method('create')
            ->with(
                self::callback(static fn(ExtensionInformation $info): bool => $info->getExtensionKey() === 'my_extension' && $info->getComposerPackageName() === 'vendor/my-extension'),
                self::isInstanceOf(ServicesConfigInformation::class)
            );

        $realCommand = $this->get(ExtensionCommand::class);
        $property = new \ReflectionProperty(ExtensionCommand::class, 'questionCollection');
        /** @var QuestionCollection $questionCollection */
        $questionCollection = $property->getValue($realCommand);

        $registry = $this->get(Registry::class);
        $command = new ExtensionCommand($creatorMock, $questionCollection, $registry);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'vendor/my-extension',
            'my_extension',
            'My Extension',
            'My extension description',
            '1.0.0',
            'plugin',
            'alpha',
            'John Doe',
            'john.doe@example.com',
            'ACME Corp',
            'Vendor\\MyExtension\\',
            'no',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('The extension was saved to path', $display);

        self::assertSame('my_extension', $registry->get(ExtConf::EXT_KEY, ExtConf::LAST_EXTENSION_REGISTRY_KEY));
    }
}
