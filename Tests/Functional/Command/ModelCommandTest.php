<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\ModelCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ModelCommandTest extends FunctionalTestCase
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
        GeneralUtility::mkdir_deep($this->testExtensionDir . 'Configuration/TCA/');

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

        $tcaContent = <<<'PHP'
<?php
return [
    'ctrl' => [
        'title' => 'Item',
        'label' => 'title',
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
];
PHP;
        file_put_contents($this->testExtensionDir . 'Configuration/TCA/tx_myextension_domain_model_item.php', $tcaContent);
    }

    protected function tearDown(): void
    {
        if ($this->testExtensionDir !== '' && is_dir($this->testExtensionDir)) {
            GeneralUtility::rmdir($this->testExtensionDir, true);
        }

        parent::tearDown();
    }

    #[Test]
    public function executeCreatesModelFromExtensionTcaTableSuccessfully(): void
    {
        $command = $this->get(ModelCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'tx_myextension_domain_model_item',
            'Item',
            'yes',
            'All',
            'None',
            'string',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $modelFile = $this->testExtensionDir . 'Classes/Domain/Model/Item.php';
        self::assertFileExists($modelFile);

        $content = (string)file_get_contents($modelFile);
        self::assertStringContainsString('declare(strict_types=1);', $content);
        self::assertStringContainsString('namespace Vendor\MyExtension\Domain\Model;', $content);
        self::assertStringContainsString('class Item extends AbstractEntity', $content);
        self::assertStringContainsString('protected string $title', $content);
        self::assertStringContainsString('public function getTitle(): string', $content);
        self::assertStringContainsString('public function setTitle(string $title): void', $content);
    }
}
