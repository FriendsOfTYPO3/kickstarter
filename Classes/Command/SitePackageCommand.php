<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command;

use FriendsOfTYPO3\Kickstarter\Command\Input\Decorator\NamespaceDecorator;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\ComposerNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\EmailQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\ExtensionKeyQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\NamespaceQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\VersionQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Creator\SitePackage\SitePackageDownloader;
use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;
use FriendsOfTYPO3\Kickstarter\Information\DownloadedSitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageDownloadInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Information\SiteSetInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SitePackageCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\SiteSetQuestionsTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Registry;

#[AsCommand('make:site-package', 'Create a new TYPO3 site package: an extension carrying the site set, page layout and Fluid templates of a site.')]
#[AsNonSchedulableCommand]
class SitePackageCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use SiteSetQuestionsTrait;

    /**
     * The site set providing lib.contentElement and the rendering of the content elements TYPO3
     * ships. A site package either depends on it or renders every content element itself.
     */
    private const FLUID_STYLED_CONTENT_SET = 'typo3/fluid-styled-content';

    private const DEFAULT_VENDOR = 'my-vendor';

    private const DEFAULT_TITLE = 'My Site Package';

    private const DEFAULT_VERSION = '0.0.1';

    /**
     * Directory name of the main site set. It is not named after the site package: an additional
     * set beside this one gets a name saying what it does, and this one carries the site.
     */
    private const DEFAULT_SITE_SET_PATH = 'Main';

    /**
     * A site package provides the templates of a site, so it is neither a plugin nor a backend
     * module. It also is not a distribution, as it ships no content of its own.
     */
    private const CATEGORY = 'templates';

    private const STATE = 'alpha';

    /**
     * A site package is either generated from the site set conventions of the running TYPO3
     * version, or fetched from the site package builder at get.typo3.org.
     */
    private const SOURCE_GENERATE = 'generate';

    private const SOURCE_DOWNLOAD = 'download';

    public function __construct(
        private readonly SitePackageCreatorService $sitePackageCreatorService,
        private readonly QuestionCollection $questionCollection,
        private readonly NamespaceDecorator $namespaceDecorator,
        private readonly Registry $registry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandContext = new CommandContext($input, $output);
        $io = $commandContext->getIo();
        $io->title('Welcome to the TYPO3 Extension Builder');

        $io->text([
            'We are here to assist you in creating a new TYPO3 site package.',
            'A site package is an extension carrying the site set of your site: its TypoScript, its',
            'page layouts and the Fluid templates the pages are rendered with.',
        ]);

        $source = $this->askForSource($io);

        $io->title('Questions to build a new TYPO3 site package');

        return $source === self::SOURCE_DOWNLOAD
            ? $this->downloadSitePackage($commandContext)
            : $this->generateSitePackage($commandContext);
    }

    /**
     * Generates the site package from the site set conventions of the running TYPO3 version.
     */
    private function generateSitePackage(CommandContext $commandContext): int
    {
        $io = $commandContext->getIo();

        $io->text([
            'Now, we will ask you a few questions. Most answers are derived from the first ones, so',
            'this is shorter than it looks.',
        ]);

        $sitePackageInformation = $this->askForSitePackageInformation($commandContext);
        if (!$sitePackageInformation instanceof SitePackageInformation) {
            return Command::FAILURE;
        }

        $this->sitePackageCreatorService->create($sitePackageInformation);

        $this->printCreatorInformation($sitePackageInformation->getCreatorInformation(), $commandContext);

        $extensionInformation = $sitePackageInformation->getExtensionInformation();
        $io->success(sprintf('The site package was saved to path %s', $extensionInformation->getExtensionPath()));

        $this->printInstallationInstructions(
            $io,
            $extensionInformation->getExtensionPath(),
            $extensionInformation->getExtensionKey(),
            $extensionInformation->getComposerPackageName()
        );
        $this->printSiteSetInstructions($io, $sitePackageInformation);

        $this->registry->set(ExtConf::EXT_KEY, ExtConf::LAST_EXTENSION_REGISTRY_KEY, $extensionInformation->getExtensionKey());

        return Command::SUCCESS;
    }

    /**
     * Fetches the site package from the builder at get.typo3.org. The builder names the extension,
     * so the values the other source derives from the title are read back from what it delivered.
     */
    private function downloadSitePackage(CommandContext $commandContext): int
    {
        $io = $commandContext->getIo();

        $typo3Version = $this->resolveBuilderTypo3Version($io);
        if ($typo3Version === null) {
            $io->error([
                sprintf(
                    'The site package builder does not build for TYPO3 %s. It offers %s.',
                    (new Typo3Version())->getBranch(),
                    implode(', ', SitePackageDownloader::TYPO3_VERSIONS)
                ),
                'Generate the site package locally instead.',
            ]);
            return Command::FAILURE;
        }

        $downloadInformation = $this->askForSitePackageDownloadInformation($commandContext, $typo3Version);
        if (!$downloadInformation instanceof SitePackageDownloadInformation) {
            return Command::FAILURE;
        }

        $downloadedSitePackage = $this->sitePackageCreatorService->download($downloadInformation);

        $this->printCreatorInformation($downloadInformation->getCreatorInformation(), $commandContext);

        if (!$downloadedSitePackage instanceof DownloadedSitePackageInformation) {
            $io->error('No site package was downloaded.');
            return Command::FAILURE;
        }

        $io->success(sprintf('The site package was saved to path %s', $downloadedSitePackage->getExtensionPath()));

        $this->printInstallationInstructions(
            $io,
            $downloadedSitePackage->getExtensionPath(),
            $downloadedSitePackage->getExtensionKey(),
            $downloadedSitePackage->getComposerPackageName()
        );
        $this->printDownloadedSiteSetInstructions($io, $downloadedSitePackage);

        $this->registry->set(
            ExtConf::EXT_KEY,
            ExtConf::LAST_EXTENSION_REGISTRY_KEY,
            $downloadedSitePackage->getExtensionKey()
        );

        return Command::SUCCESS;
    }

    public function printInstallationInstructions(
        SymfonyStyle $io,
        string $path,
        string $extensionKey,
        string $composerPackageName
    ): void {
        if (Environment::isComposerMode()) {
            if (str_contains($path, 'typo3temp')) {
                $io->writeln([
                    '<info>Move the site package to a directory outside the web root (e.g., "packages").</info>',
                    '',
                    'Then add the path to your composer.json using:',
                    sprintf(
                        '<comment>composer config repositories.%1$s path packages/%1$s</comment>',
                        $extensionKey
                    ),
                    '',
                ]);
            }

            $io->writeln([
                '<info>Install the site package with Composer using:</info>',
                sprintf('<comment>composer req %s:@dev</comment>', $composerPackageName),
                '',
            ]);
            return;
        }

        // Classic mode
        if (!str_contains($path, 'typo3conf/ext')) {
            $io->writeln([
                '<info>Move the site package to the directory "typo3conf/ext/".</info>',
                '',
            ]);
        }

        $io->writeln([
            '<info>Activate the site package in the TYPO3 backend under:</info>',
            '<comment>Admin Tools → Extension Manager</comment>',
            sprintf('<comment>(%s)</comment>', $composerPackageName),
            '',
        ]);
    }

    private function printSiteSetInstructions(SymfonyStyle $io, SitePackageInformation $sitePackageInformation): void
    {
        $siteSetInformation = $sitePackageInformation->getSiteSetInformation();

        $io->writeln([
            '<info>Then let your site depend on the site set in</info>',
            '<comment>config/sites/<identifier>/config.yaml</comment>',
            '',
            '<comment>dependencies:</comment>',
            sprintf('<comment>  - %s</comment>', $siteSetInformation->getIdentifier()),
            '',
            'A site depending on a site set is a TypoScript root, so its root page needs no',
            'sys_template record anymore.',
            '',
            sprintf(
                'Select the page layout "%s" on your pages, or leave the layout empty: both are',
                $siteSetInformation->getLabel()
            ),
            'rendered by Resources/Private/Templates/Pages/Default.fluid.html.',
            '',
        ]);

        if (!$sitePackageInformation->isUseFluidStyledContent()) {
            $io->warning([
                'This site package does not depend on ' . self::FLUID_STYLED_CONTENT_SET . ', so the content',
                'elements TYPO3 ships stay without a rendering definition and are rendered as nothing.',
                'Add one per content element you want to offer, in',
                'Configuration/Sets/' . $siteSetInformation->getPath() . '/TypoScript/content.typoscript,',
                'together with its template in Resources/Private/Templates/Content/.',
            ]);
        }

        $io->writeln([
            '<info>You can add more site settings to the set with:</info>',
            '<comment>vendor/bin/typo3 make:site-settings-definition</comment>',
            '',
        ]);
    }

    /**
     * Returns null when the user declined to replace a site package already sitting in the export
     * directory.
     */
    private function askForSitePackageInformation(CommandContext $commandContext): ?SitePackageInformation
    {
        $io = $commandContext->getIo();

        $title = $this->askForTitle($io);
        $extensionKey = $this->generateExtensionKeyFromTitle($title);

        $composerPackageName = (string)$this->questionCollection->askQuestion(
            ComposerNameQuestion::ARGUMENT_NAME,
            $commandContext,
            self::DEFAULT_VENDOR . '/' . str_replace('_', '-', $extensionKey),
        );

        $io->text([
            'Who is the author of this site package?',
            'Please enter the name of that person with first- and lastname.',
        ]);
        $author = (string)$io->ask('Author name');

        $authorEmail = (string)$this->questionCollection->askQuestion(
            EmailQuestion::ARGUMENT_NAME,
            $commandContext,
        );

        $useFluidStyledContent = $this->askForFluidStyledContent($io);

        // Everything below follows from the answers above. The user can take it over as it is, or
        // adjust each value.
        $description = sprintf('Site package of %s.', $title);
        $version = self::DEFAULT_VERSION;
        $namespacePrefix = ($this->namespaceDecorator)($composerPackageName);
        $authorCompany = '';
        $siteSetIdentifier = $composerPackageName;
        $siteSetPath = self::DEFAULT_SITE_SET_PATH;
        $siteSetLabel = $title;

        $io->info([
            'The remaining values are derived from your answers:',
            'Extension key: ' . $extensionKey,
            'Description: ' . $description,
            'Version: ' . $version,
            'Autoload namespace: ' . $namespacePrefix,
            'Site set identifier: ' . $siteSetIdentifier,
            'Site set directory: ' . ExtensionInformation::SITE_SET_PATH . $siteSetPath . '/',
            'Site set label: ' . $siteSetLabel,
        ]);

        if ($io->confirm('Do you want to adjust these values?', false)) {
            $extensionKey = (string)$this->questionCollection->askQuestion(
                ExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
                $extensionKey,
            );

            $io->text([
                'The description describes your new site package in short.',
                'It should not exceed more than two sentences.',
            ]);
            $description = (string)$io->ask('Description', $description);

            $version = (string)$this->questionCollection->askQuestion(
                VersionQuestion::ARGUMENT_NAME,
                $commandContext,
                $version,
            );

            $namespacePrefix = (string)$this->questionCollection->askQuestion(
                NamespaceQuestion::ARGUMENT_NAME,
                $commandContext,
                $composerPackageName,
            );

            $io->text([
                'Enter the company name of the author (see above).',
                'As a private/personnel developer you can leave that blank.',
            ]);
            $authorCompany = (string)$io->ask('Company name');

            $siteSetIdentifier = $this->askForSiteSetIdentifier($io, $composerPackageName);
            $siteSetPath = $this->askForSiteSetPath($io, $siteSetIdentifier, $siteSetPath);
            $siteSetLabel = $this->askForSiteSetLabel($io, $siteSetPath, $title);
        }

        if (!$this->confirmExportDirectoryIsFree($io, $extensionKey)) {
            return null;
        }

        // One CreatorInformation for every part of the site package, so the report at the end
        // covers all files at once.
        $creatorInformation = new CreatorInformation();

        $extensionInformation = new ExtensionInformation(
            extensionKey: $extensionKey,
            composerPackageName: $composerPackageName,
            title: $title,
            description: $description,
            version: $version,
            category: self::CATEGORY,
            state: self::STATE,
            author: $author,
            authorEmail: $authorEmail,
            authorCompany: $authorCompany,
            namespaceForAutoload: $namespacePrefix,
            extensionPath: $this->createExtensionPath($extensionKey, true),
            creatorInformation: $creatorInformation,
        );

        return new SitePackageInformation(
            $extensionInformation,
            new SiteSetInformation(
                $extensionInformation,
                $siteSetIdentifier,
                $siteSetPath,
                $siteSetLabel,
                $useFluidStyledContent ? [self::FLUID_STYLED_CONTENT_SET] : [],
                false,
                $creatorInformation,
            ),
            $useFluidStyledContent,
            $creatorInformation,
        );
    }

    private function askForSource(SymfonyStyle $io): string
    {
        $io->text([
            'There are two ways to get one. Generating it builds the site package here, from the',
            'site set conventions of the TYPO3 version you are running, with nothing downloaded.',
            'The builder at get.typo3.org answers with a ready made skeleton instead, which is the',
            'same one its web form delivers.',
        ]);

        return (string)$io->choice(
            'Where should the site package come from?',
            [
                self::SOURCE_GENERATE => 'Generate it here',
                self::SOURCE_DOWNLOAD => 'Download it from get.typo3.org',
            ],
            self::SOURCE_GENERATE
        );
    }

    /**
     * The builder validates the TYPO3 version against a list of its own and rejects every other
     * value, so the running version is mapped onto that list. Returns null when the builder builds
     * for no version of the running major release.
     */
    private function resolveBuilderTypo3Version(SymfonyStyle $io): ?string
    {
        $branch = (new Typo3Version())->getBranch();

        if (in_array($branch, SitePackageDownloader::TYPO3_VERSIONS, true)) {
            return $branch;
        }

        // The builder learns one version per maintained release, so it lags behind a new minor
        // release. The newest version it offers for this major release still fits the installation.
        [$major] = explode('.', $branch);
        $candidates = array_values(array_filter(
            SitePackageDownloader::TYPO3_VERSIONS,
            static fn(string $version): bool => str_starts_with($version, $major . '.')
        ));

        if ($candidates === []) {
            return null;
        }

        usort($candidates, version_compare(...));
        $version = (string)array_pop($candidates);

        $io->note(sprintf(
            'The site package builder does not build for TYPO3 %s. Asking it for %s instead, the newest version it offers for TYPO3 %s.',
            $branch,
            $version,
            $major
        ));

        return $version;
    }

    private function askForBasePackage(SymfonyStyle $io): string
    {
        $io->text([
            'The builder starts from one of two skeletons.',
            'fluid_styled_content ships a site set depending on EXT:fluid_styled_content, one page',
            'layout and the Fluid files rendering it.',
            'bootstrap_package ships a site set depending on bootstrap-package/full instead, so it',
            'requires EXT:bootstrap_package, which is not part of TYPO3.',
        ]);

        return (string)$io->choice(
            'Which base package should the site package be built from?',
            SitePackageDownloader::BASE_PACKAGES,
            SitePackageDownloader::BASE_PACKAGES[0]
        );
    }

    /**
     * The builder requires a company and derives the vendor part of the Composer package name from
     * it, so unlike the other make commands it cannot stay empty.
     */
    private function askForAuthorCompany(SymfonyStyle $io): string
    {
        $io->text([
            'Enter the company name of the author. The builder requires it and turns it into the',
            'vendor part of the Composer package name, so enter your own name if you work without',
            'a company.',
        ]);

        do {
            $authorCompany = trim((string)$io->ask('Company name'));

            if (strlen($authorCompany) < 3) {
                $io->warning('The company name must be at least 3 characters long.');
                $authorCompany = '';
            }
        } while ($authorCompany === '');

        return $authorCompany;
    }

    /**
     * Returns null when the user declined to replace a site package already sitting in the export
     * directory.
     */
    private function askForSitePackageDownloadInformation(
        CommandContext $commandContext,
        string $typo3Version
    ): ?SitePackageDownloadInformation {
        $io = $commandContext->getIo();

        $basePackage = $this->askForBasePackage($io);
        $title = $this->askForTitle($io);

        $io->text([
            'The description describes your new site package in short.',
            'It should not exceed more than two sentences.',
        ]);
        $description = (string)$io->ask('Description', sprintf('Site package of %s.', $title));

        $io->text([
            'Who is the author of this site package?',
            'Please enter the name of that person with first- and lastname.',
        ]);
        $author = (string)$io->ask('Author name');

        $authorEmail = (string)$this->questionCollection->askQuestion(
            EmailQuestion::ARGUMENT_NAME,
            $commandContext,
        );

        $authorCompany = $this->askForAuthorCompany($io);

        $homepage = trim((string)$io->ask('Homepage of the author (optional)', ''));

        // The builder derives the extension key from the title the same way, so the export
        // directory can be settled before the request. What it delivers decides the final path.
        if (!$this->confirmExportDirectoryIsFree($io, $this->generateExtensionKeyFromTitle($title))) {
            return null;
        }

        return new SitePackageDownloadInformation(
            basePackage: $basePackage,
            typo3Version: $typo3Version,
            title: $title,
            description: $description,
            author: $author,
            authorEmail: $authorEmail,
            authorCompany: $authorCompany,
            homepage: $homepage,
            creatorInformation: new CreatorInformation(),
        );
    }

    private function printDownloadedSiteSetInstructions(
        SymfonyStyle $io,
        DownloadedSitePackageInformation $downloadedSitePackage
    ): void {
        if ($downloadedSitePackage->getSiteSetIdentifier() === '') {
            return;
        }

        $io->writeln([
            '<info>Then let your site depend on the site set in</info>',
            '<comment>config/sites/<identifier>/config.yaml</comment>',
            '',
            '<comment>dependencies:</comment>',
            sprintf('<comment>  - %s</comment>', $downloadedSitePackage->getSiteSetIdentifier()),
            '',
            'A site depending on a site set is a TypoScript root, so its root page needs no',
            'sys_template record anymore.',
            '',
            sprintf(
                'The site set of this site package lives in Configuration/Sets/%s/.',
                $downloadedSitePackage->getSiteSetPath()
            ),
            '',
        ]);

        $io->note([
            'This site package comes from the builder at get.typo3.org, so it follows the layout of',
            'that builder rather than the one make:site-package generates. Its settings are values',
            'in settings.yaml without a settings.definitions.yaml, so they stay out of the site',
            'settings editor in the backend.',
        ]);
    }

    private function askForTitle(SymfonyStyle $io): string
    {
        $io->text([
            'The title of the site package is used to derive its extension key and its composer',
            'package name, and it becomes the label of the site set.',
        ]);

        do {
            $title = trim((string)$io->ask('Please provide the title of your site package', self::DEFAULT_TITLE));

            if (strlen($title) < 3) {
                $io->warning('The title must be at least 3 characters long.');
                $title = '';
                continue;
            }

            if ($this->generateExtensionKeyFromTitle($title) === '') {
                $io->warning('No extension key can be derived from this title. Please use letters and digits.');
                $title = '';
            }
        } while ($title === '');

        return $title;
    }

    private function askForFluidStyledContent(SymfonyStyle $io): bool
    {
        $io->text([
            'The content elements TYPO3 ships - text, textmedia, the menus - are rendered by',
            'EXT:fluid_styled_content. Depending on its site set keeps every one of them working,',
            'and the templates of your site package are added on top of the ones it provides.',
            'Without that dependency your site package renders every content element itself. It',
            'still defines lib.contentElement, because every Extbase plugin is rendered through it.',
        ]);

        return $io->confirm(
            sprintf('Depend on the site set %s to render content elements?', self::FLUID_STYLED_CONTENT_SET),
            true
        );
    }

    /**
     * A site package is created, not modified: the site set and its settings definition refuse to
     * overwrite what is already there. So a previous export of the same extension key is removed
     * first, after the user confirmed it.
     */
    private function confirmExportDirectoryIsFree(SymfonyStyle $io, string $extensionKey): bool
    {
        $extensionPath = $this->getExportExtensionPath($extensionKey);

        $io->info([
            'The site package will be exported to directory: ' . $extensionPath,
            'You can configure the export directory in extension settings (available in InstallTool)',
        ]);

        if (!is_dir($extensionPath)) {
            return true;
        }

        $io->warning([
            'There is already an extension at location: "' . $extensionPath . '".',
            'While creating a new site package, we will remove the previous extension and create a new one.',
        ]);

        if ($io->confirm('Please confirm, that you want to remove the previous extension and create a new one.', false)) {
            return true;
        }

        $io->note('No site package was created.');

        return false;
    }

    private function generateExtensionKeyFromTitle(string $title): string
    {
        // Lowercase first
        $key = strtolower($title);

        // Replace any sequence of non-alphanumeric characters with underscore
        $key = (string)preg_replace('/[^a-z0-9]+/', '_', $key);

        // Collapse multiple underscores
        $key = (string)preg_replace('/_+/', '_', $key);

        // Trim underscores from start and end
        $key = trim($key, '_');

        // Remove only leading digits (not letters!)
        return (string)preg_replace('/^\d+/', '', $key);
    }
}
