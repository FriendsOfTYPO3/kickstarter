<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command\Input\Question;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ChooseExtensionKeyQuestion;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ChooseExtensionKeyQuestionTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'frontend',
        'backend',
    ];

    protected array $testExtensionsToLoad = [
        'friendsoftypo3/kickstarter',
    ];

    private string $testExtensionDir = '';

    protected function tearDown(): void
    {
        if ($this->testExtensionDir !== '' && is_dir($this->testExtensionDir)) {
            GeneralUtility::rmdir($this->testExtensionDir, true);
        }

        parent::tearDown();
    }

    #[Test]
    public function askOffersInstalledExtensionFromPackageManager(): void
    {
        $question = $this->get(ChooseExtensionKeyQuestion::class);
        $commandContext = $this->createCommandContext("kickstarter\n");

        $chosenExtension = $question->ask($commandContext);

        self::assertSame('kickstarter', $chosenExtension);
    }

    #[Test]
    public function askResolvesComposerNameDefaultToExtensionKey(): void
    {
        $question = $this->get(ChooseExtensionKeyQuestion::class);
        $commandContext = $this->createCommandContext("\n");

        $chosenExtension = $question->ask($commandContext, 'friendsoftypo3/kickstarter');

        self::assertSame('kickstarter', $chosenExtension);
    }

    #[Test]
    public function askIncludesExtensionsFromExportDirectory(): void
    {
        $exportDir = GeneralUtility::makeInstance(ExtConf::class)->getExportDirectory();
        $this->testExtensionDir = $exportDir . '/my_export_ext/';
        GeneralUtility::mkdir_deep($this->testExtensionDir);
        file_put_contents($this->testExtensionDir . 'composer.json', json_encode([
            'name' => 'vendor/my-export-ext',
        ], JSON_THROW_ON_ERROR));

        $question = $this->get(ChooseExtensionKeyQuestion::class);
        $commandContext = $this->createCommandContext("my_export_ext\n");

        $chosenExtension = $question->ask($commandContext);

        self::assertSame('my_export_ext', $chosenExtension);
    }

    #[Test]
    public function getExtensionPathResolvesToVendorPathForInstalledExtension(): void
    {
        $consumer = new class () {
            use ExtensionInformationTrait {
                getExtensionPath as public;
                getExportExtensionPath as public;
            }
        };

        $packageManager = $this->get(PackageManager::class);
        $expectedPath = rtrim($packageManager->getPackage('kickstarter')->getPackagePath(), '/') . '/';

        self::assertSame($expectedPath, $consumer->getExtensionPath('kickstarter'));
    }

    #[Test]
    public function getExtensionPathFallsBackToExportDirectoryForUnknownExtension(): void
    {
        $consumer = new class () {
            use ExtensionInformationTrait {
                getExtensionPath as public;
                getExportExtensionPath as public;
            }
        };

        $exportDir = rtrim(GeneralUtility::makeInstance(ExtConf::class)->getExportDirectory(), '/');
        $expectedPath = '/' . trim($exportDir, '/') . '/non_installed_extension/';

        self::assertSame($expectedPath, $consumer->getExtensionPath('non_installed_extension'));
        self::assertSame($expectedPath, $consumer->getExportExtensionPath('non_installed_extension'));
    }

    private function createCommandContext(string $input): CommandContext
    {
        $stream = fopen('php://memory', 'r+', false);
        fwrite($stream, $input);
        rewind($stream);

        $arrayInput = new ArrayInput([]);
        if ($arrayInput instanceof StreamableInputInterface) {
            $arrayInput->setStream($stream);
        }

        return new CommandContext($arrayInput, new BufferedOutput());
    }
}
