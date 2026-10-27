<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Creator\SitePackage\BackendLayoutCreator;
use FriendsOfTYPO3\Kickstarter\Enums\FileModificationType;
use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Information\SiteSetInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SitePackageCreatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SitePackageCreatorServiceTest extends AbstractServiceCreatorTestCase
{
    private const EXTENSION_KEY = 'my_site_package';

    private const COMPOSER_PACKAGE_NAME = 'my-vendor/my-site-package';

    private const SITE_SET_PATH = 'Main';

    #[Test]
    #[DataProvider('sitePackageCreationProvider')]
    public function testItCreatesExpectedSitePackage(bool $useFluidStyledContent, string $expectedDir): void
    {
        $sitePackageInformation = $this->createSitePackageInformation($useFluidStyledContent);

        $this->get(SitePackageCreatorService::class)->create($sitePackageInformation);

        $this->assertDirectoryEquals($expectedDir, $sitePackageInformation->getExtensionInformation()->getExtensionPath());
    }

    public static function sitePackageCreationProvider(): array
    {
        return [
            'with fluid_styled_content' => [
                'useFluidStyledContent' => true,
                'expectedDir' => __DIR__ . '/Fixtures/make_site_package',
            ],
            'without fluid_styled_content' => [
                'useFluidStyledContent' => false,
                'expectedDir' => __DIR__ . '/Fixtures/make_site_package_without_fluid_styled_content',
            ],
        ];
    }

    public static function fluidStyledContentProvider(): array
    {
        return [
            'with fluid_styled_content' => ['useFluidStyledContent' => true],
            'without fluid_styled_content' => ['useFluidStyledContent' => false],
        ];
    }

    #[Test]
    #[DataProvider('fluidStyledContentProvider')]
    public function testItWritesEveryFileItReports(bool $useFluidStyledContent): void
    {
        $sitePackageInformation = $this->createSitePackageInformation($useFluidStyledContent);

        $this->get(SitePackageCreatorService::class)->create($sitePackageInformation);

        $fileModifications = $sitePackageInformation->getCreatorInformation()->getFileModifications();
        self::assertNotSame([], $fileModifications);

        foreach ($fileModifications as $fileModification) {
            self::assertContains(
                $fileModification->getFileModificationType(),
                [FileModificationType::CREATED, FileModificationType::MODIFIED],
                sprintf('%s: %s', $fileModification->getPath(), $fileModification->getMessage() ?? '')
            );
            self::assertFileExists($fileModification->getPath());
        }
    }

    /**
     * A content area addressed by a Fluid template has to be a column the page layout declares.
     * An identifier no layout declares is not an empty column, it is an error on every page the
     * template renders.
     */
    #[Test]
    #[DataProvider('fluidStyledContentProvider')]
    public function testEveryRenderedContentAreaIsDeclaredByThePageLayout(bool $useFluidStyledContent): void
    {
        $sitePackageInformation = $this->createSitePackageInformation($useFluidStyledContent);

        $this->get(SitePackageCreatorService::class)->create($sitePackageInformation);

        $backendLayout = (string)file_get_contents(
            $sitePackageInformation->getSiteSetBackendLayoutPath() . BackendLayoutCreator::LAYOUT_IDENTIFIER . '.tsconfig'
        );
        preg_match_all('/identifier\s*=\s*(\S+)/', $backendLayout, $matches);
        $declaredIdentifiers = $matches[1];
        self::assertNotSame([], $declaredIdentifiers);

        $addressedIdentifiers = [];
        $templates = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sitePackageInformation->getTemplatePath(), \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        /** @var \SplFileInfo $template */
        foreach ($templates as $template) {
            if ($template->isDir() || !str_ends_with($template->getFilename(), '.html')) {
                continue;
            }
            preg_match_all('/\{content\.([^}]+)}/', (string)file_get_contents($template->getPathname()), $matches);
            $addressedIdentifiers = [...$addressedIdentifiers, ...$matches[1]];
        }

        self::assertNotSame([], $addressedIdentifiers);
        foreach ($addressedIdentifiers as $addressedIdentifier) {
            self::assertContains($addressedIdentifier, $declaredIdentifiers);
        }
    }

    private function createSitePackageInformation(bool $useFluidStyledContent): SitePackageInformation
    {
        $extensionPath = $this->instancePath . '/' . self::EXTENSION_KEY . '/';
        if (is_dir($extensionPath)) {
            GeneralUtility::rmdir($extensionPath, true);
        }
        GeneralUtility::mkdir_deep($extensionPath);

        $creatorInformation = new CreatorInformation();

        $extensionInformation = new ExtensionInformation(
            extensionKey: self::EXTENSION_KEY,
            composerPackageName: self::COMPOSER_PACKAGE_NAME,
            title: 'My Site Package',
            description: 'Site package of My Site Package.',
            version: '0.0.1',
            category: 'templates',
            state: 'alpha',
            author: 'John Doe',
            authorEmail: 'john@example.com',
            authorCompany: '',
            namespaceForAutoload: 'MyVendor\\MySitePackage\\',
            extensionPath: $extensionPath,
            creatorInformation: $creatorInformation,
        );

        return new SitePackageInformation(
            $extensionInformation,
            new SiteSetInformation(
                $extensionInformation,
                self::COMPOSER_PACKAGE_NAME,
                self::SITE_SET_PATH,
                'My Site Package',
                $useFluidStyledContent ? ['typo3/fluid-styled-content'] : [],
                false,
                $creatorInformation,
            ),
            $useFluidStyledContent,
            $creatorInformation,
        );
    }
}
