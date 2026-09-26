<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\TypeConverterCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TypeConverterCommandTest extends FunctionalTestCase
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
    public function executeCreatesTypeConverterSuccessfully(): void
    {
        $command = $this->get(TypeConverterCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'ItemConverter',
            '20',
            'string',
            'int',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $converterFile = $this->testExtensionDir . 'Classes/Property/TypeConverter/ItemConverter.php';
        self::assertFileExists($converterFile);

        $content = (string)file_get_contents($converterFile);
        self::assertStringContainsString('declare(strict_types=1);', $content);
        self::assertStringContainsString('namespace Vendor\MyExtension\Property\TypeConverter;', $content);
        self::assertStringContainsString('final class ItemConverter extends AbstractTypeConverter', $content);
        self::assertStringContainsString('public function convertFrom(', $content);
        self::assertStringContainsString('): ?int', $content);

        $servicesFile = $this->testExtensionDir . 'Configuration/Services.yaml';
        self::assertFileExists($servicesFile);

        $servicesContent = (string)file_get_contents($servicesFile);
        self::assertStringContainsString('sources: string', $servicesContent);
    }

    #[Test]
    public function executeCreatesTypeConverterWithObjectTargetAndFqcnImport(): void
    {
        $command = $this->get(TypeConverterCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'ProductConverter',
            '10',
            'string',
            '\Vendor\MyExtension\Domain\Model\Product',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $converterFile = $this->testExtensionDir . 'Classes/Property/TypeConverter/ProductConverter.php';
        self::assertFileExists($converterFile);

        $content = (string)file_get_contents($converterFile);
        self::assertStringContainsString('use Vendor\MyExtension\Domain\Model\Product;', $content);
        self::assertStringContainsString('): ?Product', $content);

        $servicesFile = $this->testExtensionDir . 'Configuration/Services.yaml';
        self::assertFileExists($servicesFile);

        $servicesContent = (string)file_get_contents($servicesFile);
        self::assertStringContainsString('target: \Vendor\MyExtension\Domain\Model\Product', $servicesContent);
    }

    #[Test]
    public function executeCreatesTypeConverterWithAliasesAndNormalizesSources(): void
    {
        $command = $this->get(TypeConverterCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'NumberConverter',
            '10',
            'int, bool, string',
            'int',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $converterFile = $this->testExtensionDir . 'Classes/Property/TypeConverter/NumberConverter.php';
        self::assertFileExists($converterFile);

        $servicesFile = $this->testExtensionDir . 'Configuration/Services.yaml';
        self::assertFileExists($servicesFile);

        $servicesContent = (string)file_get_contents($servicesFile);
        self::assertStringContainsString("sources: 'integer, boolean, string'", $servicesContent);
    }

    #[Test]
    public function executeRetriesWhenInvalidSourceTypeProvided(): void
    {
        $command = $this->get(TypeConverterCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'DateConverter',
            '15',
            'DateTime, object',
            'string, int',
            'string',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Invalid source type(s): "datetime", "object"', $output);

        $converterFile = $this->testExtensionDir . 'Classes/Property/TypeConverter/DateConverter.php';
        self::assertFileExists($converterFile);

        $servicesFile = $this->testExtensionDir . 'Configuration/Services.yaml';
        self::assertFileExists($servicesFile);

        $servicesContent = (string)file_get_contents($servicesFile);
        self::assertStringContainsString("sources: 'string, integer'", $servicesContent);
    }

    #[Test]
    public function executeRetriesWhenEmptySourceTypeProvided(): void
    {
        $command = $this->get(TypeConverterCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'TextConverter',
            '10',
            ',',
            'string',
            'string',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Please provide at least one source data type.', $output);

        $converterFile = $this->testExtensionDir . 'Classes/Property/TypeConverter/TextConverter.php';
        self::assertFileExists($converterFile);

        $servicesFile = $this->testExtensionDir . 'Configuration/Services.yaml';
        self::assertFileExists($servicesFile);

        $servicesContent = (string)file_get_contents($servicesFile);
        self::assertStringContainsString('sources: string', $servicesContent);
    }
}
