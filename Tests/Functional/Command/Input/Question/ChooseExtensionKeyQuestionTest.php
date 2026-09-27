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
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
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
        $package = $this->createMock(PackageInterface::class);
        $package->method('getValueFromComposerManifest')->willReturn([
            'type' => 'typo3-cms-extension',
            'extra' => [
                'typo3/cms' => [
                    'extension-key' => 'installed_ext',
                ],
            ],
        ]);
        $metaData = $this->createMock(MetaData::class);
        $metaData->method('getPackageType')->willReturn('typo3-cms-extension');
        $package->method('getPackageMetaData')->willReturn($metaData);
        $package->method('getPackageKey')->willReturn('installed_ext');

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getAvailablePackages')->willReturn([$package]);

        $question = new ChooseExtensionKeyQuestion(
            $this->get(Registry::class),
            $this->get(ExtensionConfiguration::class),
            $packageManager
        );
        $commandContext = $this->createCommandContext("installed_ext\n");

        $chosenExtension = $question->ask($commandContext);

        self::assertSame('installed_ext', $chosenExtension);
    }

    #[Test]
    public function askResolvesComposerNameDefaultToExtensionKey(): void
    {
        $package = $this->createMock(PackageInterface::class);
        $package->method('getValueFromComposerManifest')->willReturn([
            'type' => 'typo3-cms-extension',
            'extra' => [
                'typo3/cms' => [
                    'extension-key' => 'installed_ext',
                ],
            ],
        ]);
        $metaData = $this->createMock(MetaData::class);
        $metaData->method('getPackageType')->willReturn('typo3-cms-extension');
        $package->method('getPackageMetaData')->willReturn($metaData);
        $package->method('getPackageKey')->willReturn('installed_ext');

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getAvailablePackages')->willReturn([$package]);
        $packageManager->method('getPackageKeyFromComposerName')
            ->willReturn('installed_ext');

        $question = new ChooseExtensionKeyQuestion(
            $this->get(Registry::class),
            $this->get(ExtensionConfiguration::class),
            $packageManager
        );
        $commandContext = $this->createCommandContext("\n");

        $chosenExtension = $question->ask($commandContext, 'vendor/installed-ext');

        self::assertSame('installed_ext', $chosenExtension);
    }

    #[Test]
    public function askExcludesKickstarterExtensionFromChoices(): void
    {
        $exportDir = GeneralUtility::makeInstance(ExtConf::class)->getExportDirectory();
        $this->testExtensionDir = $exportDir . '/my_export_ext/';
        GeneralUtility::mkdir_deep($this->testExtensionDir);
        file_put_contents($this->testExtensionDir . 'composer.json', json_encode([
            'name' => 'vendor/my-export-ext',
        ], JSON_THROW_ON_ERROR));

        $bufferedOutput = new BufferedOutput();
        $question = $this->get(ChooseExtensionKeyQuestion::class);
        $commandContext = $this->createCommandContext("my_export_ext\n", $bufferedOutput);

        $chosenExtension = $question->ask($commandContext);

        self::assertSame('my_export_ext', $chosenExtension);
        self::assertStringNotContainsString('kickstarter', $bufferedOutput->fetch());
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

    #[Test]
    public function askUsesExtensionKeyFromComposerJsonEvenWhenFolderHasHyphen(): void
    {
        $exportDir = GeneralUtility::makeInstance(ExtConf::class)->getExportDirectory();
        $this->testExtensionDir = $exportDir . '/my-site-package/';
        GeneralUtility::mkdir_deep($this->testExtensionDir);
        file_put_contents($this->testExtensionDir . 'composer.json', json_encode([
            'name' => 'vendor/my-site-package',
            'extra' => [
                'typo3/cms' => [
                    'extension-key' => 'my_site_package',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $question = $this->get(ChooseExtensionKeyQuestion::class);
        $commandContext = $this->createCommandContext("my_site_package\n");

        $chosenExtension = $question->ask($commandContext);

        self::assertSame('my_site_package', $chosenExtension);
    }

    #[Test]
    public function getExtensionPathResolvesFolderWithHyphenWhenComposerJsonHasMatchingExtensionKey(): void
    {
        $exportDir = GeneralUtility::makeInstance(ExtConf::class)->getExportDirectory();
        $this->testExtensionDir = $exportDir . '/custom-hyphen-dir/';
        GeneralUtility::mkdir_deep($this->testExtensionDir);
        file_put_contents($this->testExtensionDir . 'composer.json', json_encode([
            'name' => 'vendor/custom-hyphen-dir',
            'extra' => [
                'typo3/cms' => [
                    'extension-key' => 'custom_ext_key',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $consumer = new class () {
            use ExtensionInformationTrait {
                getExtensionPath as public;
                getExportExtensionPath as public;
            }
        };

        $resolvedPath = $consumer->getExtensionPath('custom_ext_key');
        self::assertSame(rtrim($this->testExtensionDir, '/') . '/', $resolvedPath);
    }

    private function createCommandContext(string $input, ?BufferedOutput $bufferedOutput = null): CommandContext
    {
        $stream = fopen('php://memory', 'r+', false);
        fwrite($stream, $input);
        rewind($stream);

        $arrayInput = new ArrayInput([]);
        $arrayInput->setStream($stream);

        return new CommandContext($arrayInput, $bufferedOutput ?? new BufferedOutput());
    }
}
