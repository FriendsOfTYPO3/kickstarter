<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Configuration;

use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExtConfTest extends FunctionalTestCase
{
    public ExtensionConfiguration|MockObject $extensionConfigurationMock;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
    ];

    protected array $testExtensionsToLoad = [
        'friendsoftypo3/kickstarter',
    ];

    protected function setUp(): void
    {
        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);

        Environment::initialize(
            $this->createMock(ApplicationContext::class),
            cli: true,
            composerMode: true,
            projectPath: '',
            publicPath: '',
            varPath: '',
            configPath: 'config',
            currentScript: '',
            os: 'UNIX',
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->extensionConfigurationMock,
        );
    }

    #[Test]
    public function getExportDirectoryInitiallyReturnsTypo3TempPath(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            '/typo3temp/kickstarter',
            $subject->getExportDirectory(),
        );
    }

    public static function exportDirectoryDataProvider(): array
    {
        return [
            'Directory with empty string' => ['', '/typo3temp/kickstarter'],
            'Directory with zero' => ['0', '/typo3temp/kickstarter'],
            'Directory with no slashes' => ['packages', '/packages'],
            'Directory with starting slash' => ['/packages', '/packages'],
            'Directory with leading slash' => ['packages/', '/packages'],
            'Directory wrapped with slashes' => ['/packages/', '/packages'],
        ];
    }

    #[Test]
    #[DataProvider(
        methodName: 'exportDirectoryDataProvider',
    )]
    public function getExportDirectoryWithExportDirectoryReturnsExportDirectory(
        string $configuredExportDirectory,
        string $expectedExportDirectory,
    ): void {
        $config = [
            'exportDirectory' => $configuredExportDirectory,
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            $expectedExportDirectory,
            $subject->getExportDirectory(),
        );
    }

    #[Test]
    public function isActivateModuleInitiallyFalse(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertFalse(
            $subject->isActivateModule(),
        );
    }

    #[Test]
    public function isActivateModuleWithTrueStillTrue(): void
    {
        $config = [
            'activateModule' => true,
        ];
        $subject = new ExtConf(...$config);

        self::assertTrue(
            $subject->isActivateModule(),
        );
    }

    #[Test]
    public function isActivateModuleWithFalseStillFalse(): void
    {
        $config = [
            'activateModule' => false,
        ];
        $subject = new ExtConf(...$config);

        self::assertFalse(
            $subject->isActivateModule(),
        );
    }
}
