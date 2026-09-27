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
            '',
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

    #[Test]
    public function executeCreatesModelWithTcaAwareSuggestedTypesAndDefaults(): void
    {
        $tcaContent = <<<'PHP'
<?php
return [
    'ctrl' => [
        'title' => 'Product',
        'label' => 'title',
    ],
    'columns' => [
        'is_active' => [
            'label' => 'Is active',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'price' => [
            'label' => 'Price',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'default' => 9.99,
            ],
        ],
        'created_at' => [
            'label' => 'Created at',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'status' => [
            'label' => 'Status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Draft', 'value' => 0],
                    ['label' => 'Published', 'value' => 1],
                ],
                'default' => 0,
            ],
        ],
    ],
];
PHP;
        file_put_contents($this->testExtensionDir . 'Configuration/TCA/tx_myextension_domain_model_product.php', $tcaContent);

        $command = $this->get(ModelCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'my_extension',
            'tx_myextension_domain_model_product',
            'Product',
            'yes',
            'All',
            'None',
            // created_at: datetime type (default in list), no default asked
            \DateTime::class,
            // is_active: bool type (default in list), confirm default (true from TCA 1)
            'bool',
            'yes',
            // price: float type (default in list), ask default (9.99 from TCA)
            'float',
            '9.99',
            // status: int type (default in list for numeric select), ask default (0 from TCA)
            'int',
            '0',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $modelFile = $this->testExtensionDir . 'Classes/Domain/Model/Product.php';
        self::assertFileExists($modelFile);

        $content = (string)file_get_contents($modelFile);
        self::assertStringContainsString('use DateTime;', $content);
        self::assertStringContainsString('protected ?DateTime $createdAt = null;', $content);
        self::assertStringContainsString('public function getCreatedAt(): ?DateTime', $content);
        self::assertStringContainsString('public function setCreatedAt(?DateTime $createdAt): void', $content);
        self::assertStringContainsString('protected bool $isActive = true;', $content);
        self::assertStringContainsString('protected float $price = 9.99;', $content);
        self::assertStringContainsString('protected int $status = 0;', $content);
        self::assertStringContainsString('public function initializeObject(): void', $content);
    }
}
