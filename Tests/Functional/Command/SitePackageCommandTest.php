<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\Input\Decorator\NamespaceDecorator;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Command\SitePackageCommand;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SitePackageCreatorService;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SitePackageCommandTest extends FunctionalTestCase
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
        $this->testExtensionDir = $exportDir . '/my_site_package/';
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
    public function executeDerivesSitePackageFromTitleAndComposerName(): void
    {
        $sitePackageInformation = null;
        $creatorMock = $this->createMock(SitePackageCreatorService::class);
        $creatorMock->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (SitePackageInformation $information) use (&$sitePackageInformation): bool {
                $sitePackageInformation = $information;
                return true;
            }));

        $commandTester = new CommandTester($this->createCommand($creatorMock));
        $commandTester->setInputs([
            'My Site Package',
            'vendor/my-site-package',
            'John Doe',
            'john.doe@example.com',
            // Depend on the site set of EXT:fluid_styled_content
            'yes',
            // Do not adjust the derived values
            'no',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertInstanceOf(SitePackageInformation::class, $sitePackageInformation);

        $extensionInformation = $sitePackageInformation->getExtensionInformation();
        self::assertSame('my_site_package', $extensionInformation->getExtensionKey());
        self::assertSame('vendor/my-site-package', $extensionInformation->getComposerPackageName());
        self::assertSame('My Site Package', $extensionInformation->getTitle());
        self::assertSame('templates', $extensionInformation->getCategory());
        self::assertSame('Vendor\\MySitePackage\\', $extensionInformation->getNamespaceForAutoload());

        $siteSetInformation = $sitePackageInformation->getSiteSetInformation();
        self::assertSame('vendor/my-site-package', $siteSetInformation->getIdentifier());
        self::assertSame('Main', $siteSetInformation->getPath());
        self::assertSame('My Site Package', $siteSetInformation->getLabel());
        self::assertSame(['typo3/fluid-styled-content'], $siteSetInformation->getDependencies());
        self::assertTrue($sitePackageInformation->isUseFluidStyledContent());

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('The site package was saved to path', $display);
        self::assertStringContainsString('- vendor/my-site-package', $display);

        self::assertSame(
            'my_site_package',
            $this->get(Registry::class)->get(ExtConf::EXT_KEY, ExtConf::LAST_EXTENSION_REGISTRY_KEY)
        );
    }

    #[Test]
    public function executeLetsUserAdjustDerivedValuesAndDropFluidStyledContent(): void
    {
        $sitePackageInformation = null;
        $creatorMock = $this->createMock(SitePackageCreatorService::class);
        $creatorMock->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (SitePackageInformation $information) use (&$sitePackageInformation): bool {
                $sitePackageInformation = $information;
                return true;
            }));

        $commandTester = new CommandTester($this->createCommand($creatorMock));
        $commandTester->setInputs([
            'My Site Package',
            'vendor/my-site-package',
            'John Doe',
            'john.doe@example.com',
            // Render the content elements without EXT:fluid_styled_content
            'no',
            // Adjust the derived values
            'yes',
            'my_site_package',
            'A site package with a custom description',
            '1.0.0',
            'Vendor\\MySitePackage\\',
            'ACME Corp',
            'vendor/my-site-package-main',
            'main',
            'Main',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertInstanceOf(SitePackageInformation::class, $sitePackageInformation);

        $extensionInformation = $sitePackageInformation->getExtensionInformation();
        self::assertSame('A site package with a custom description', $extensionInformation->getDescription());
        self::assertSame('1.0.0', $extensionInformation->getVersion());
        self::assertSame('ACME Corp', $extensionInformation->getAuthorCompany());

        $siteSetInformation = $sitePackageInformation->getSiteSetInformation();
        self::assertSame('vendor/my-site-package-main', $siteSetInformation->getIdentifier());
        self::assertSame('main', $siteSetInformation->getPath());
        self::assertSame('Main', $siteSetInformation->getLabel());
        self::assertSame([], $siteSetInformation->getDependencies());
        self::assertFalse($sitePackageInformation->isUseFluidStyledContent());

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('does not depend on typo3/fluid-styled-content', $display);
    }

    #[Test]
    public function executeAbortsWhenUserDeclinesToReplaceAnExistingExtension(): void
    {
        GeneralUtility::mkdir_deep($this->testExtensionDir);

        $creatorMock = $this->createMock(SitePackageCreatorService::class);
        $creatorMock->expects(self::never())->method('create');

        $commandTester = new CommandTester($this->createCommand($creatorMock));
        $commandTester->setInputs([
            'My Site Package',
            'vendor/my-site-package',
            'John Doe',
            'john.doe@example.com',
            'yes',
            'no',
            // Do not remove the extension already sitting in the export directory
            'no',
        ]);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertDirectoryExists($this->testExtensionDir);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('No site package was created.', $display);
    }

    private function createCommand(SitePackageCreatorService $creatorService): SitePackageCommand
    {
        $realCommand = $this->get(SitePackageCommand::class);
        $property = new \ReflectionProperty(SitePackageCommand::class, 'questionCollection');
        /** @var QuestionCollection $questionCollection */
        $questionCollection = $property->getValue($realCommand);

        return new SitePackageCommand(
            $creatorService,
            $questionCollection,
            new NamespaceDecorator(),
            $this->get(Registry::class)
        );
    }
}
