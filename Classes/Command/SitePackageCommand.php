<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command;

use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SitePackageCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SitePackageCommand extends Command
{
    use CreatorInformationTrait;

    public function __construct(
        private readonly SitePackageCreatorService $sitePackageCreatorService,
        private readonly QuestionCollection $questionCollection,
    ) {
        parent::__construct();
    }

    public function getTitle(SymfonyStyle $io, mixed $extConf): string
    {
        $io->text([
            'The title of the TYPO3 site package will be used to automatically create an extension key and composer name used to identify the extension.',
        ]);

        do {
            $title = (string)$io->ask('Please provide the title of your site package', 'My Site Package');

            if (strlen(trim($title)) < 3) {
                $io->warning('The title must be at least 3 characters long.');
                $title = '';
                continue;
            }

            $extensionKey = $this->generateExtensionKeyFromTitle($title);
            $targetPath = rtrim($extConf->getExportDirectory(), '/') . '/' . $extensionKey;

            if (is_dir($targetPath)) {
                $io->warning(sprintf(
                    'A TYPO3 site package with the key "%s" already exists at %s. Please choose a different title.',
                    $extensionKey,
                    $targetPath
                ));
                $title = '';
            }
        } while ($title === '');
        return $title;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandContext = new CommandContext($input, $output);
        $io = $commandContext->getIo();
        $io->title('Welcome to the TYPO3 Extension Builder');

        $io->text([
            'We are here to assist you in creating a new TYPO3 extension.',
            'Now, we will ask you a few questions to customize the extension according to your needs.',
            'Please take your time to answer them.',
        ]);

        $io->title('Questions to load a new TYPO3 SitePackage from https://get.typo3.org');

        $sitePackageInformation = $this->askForSitePackageInformation($io);

        $this->sitePackageCreatorService->create($sitePackageInformation);

        $this->printInstallationInstructions($io, $sitePackageInformation);

        $this->printCreatorInformation($sitePackageInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    public function printInstallationInstructions(SymfonyStyle $io, SitePackageInformation $sitePackageInformation): void
    {
        $path = $sitePackageInformation->getExtensionInformation()->getExtensionPath();
        if (Environment::isComposerMode()) {
            if (str_contains($path, 'typo3temp')) {
                $io->writeln([
                    '<info>Move the extension to a directory outside the web root (e.g., "packages").</info>',
                    '',
                    'Then add the path to your composer.json using:',
                    sprintf(
                        '<comment>composer config repositories.%1$s path packages/%1$s</comment>',
                        $sitePackageInformation->getExtensionInformation()->getExtensionKey()
                    ),
                    '',
                ]);
            }

            $io->writeln([
                '<info>Install the extension with Composer using:</info>',
                sprintf(
                    '<comment>composer req %s:@dev</comment>',
                    $sitePackageInformation->getExtensionInformation()->getComposerPackageName()
                ),
                '',
            ]);
            return;
        }

        // Classic mode
        if (!str_contains($path, 'typo3conf/ext')) {
            $io->writeln([
                '<info>Move the extension to the directory "typo3conf/ext/".</info>',
                '',
            ]);
        }

        $io->writeln([
            '<info>Activate the extension in the TYPO3 backend under:</info>',
            '<comment>Admin Tools → Extension Manager</comment>',
            sprintf(
                '<comment>(%s)</comment>',
                $sitePackageInformation->getExtensionInformation()->getComposerPackageName()
            ),
            '',
        ]);
    }

    private function askForSitePackageInformation(SymfonyStyle $io): SitePackageInformation
    {
        $extConf = GeneralUtility::makeInstance(ExtConf::class);
        $io->info([
            'The extension will be exported to directory: ' . $extConf->getExportDirectory(),
            'You can configure the export directory in extension settings (available in InstallTool)',
        ]);

        $title = $this->getTitle($io, $extConf);

        $io->text([
            'The description describes your new extension in short. It should not exceed more than two sentences.',
            'This will help users in TER (https://extensions.typo3.org) to get the point of what your extension does/provides',
        ]);
        $description = (string)$io->ask('Description');

        $io->text([
            'Who is the author of this extension?',
            'Please enter the name of that person with first- and lastname.',
            'Do not enter company. It will be asked some questions later.',
        ]);
        $author = (string)$io->ask('Author name', 'J. Doe');

        $io->text([
            'Please enter the email of the author (see above)',
            'It must be a valid email address.',
        ]);
        $authorEmail = $this->askForEmail($io, 'j.doe@example.org');

        $io->text([
            'Enter the company name of the author, the company name will also be used for the vendor part of the Composer name.',
        ]);
        $authorCompany = (string)$io->ask('Company name', 'my-vendor');

        $io->text([
            'To find PHP classes much faster in your extension TYPO3 uses the auto-loading',
            'mechanism of composer (https://getcomposer.org/doc/01-basic-usage.md#autoloading)',
            'Please enter the PSR-4 autoload namespace for your extension',
        ]);

        $extensionInformation =  new ExtensionInformation(
            'site_package',
            'my-vendor/site-package',
            $title,
            $description,
            '0.0.1',
            'fe',
            'excludeFromUpdates',
            $author,
            $authorEmail,
            $authorCompany,
            '',
            $extConf->getExportDirectory(),
        );
        return new SitePackageInformation(
            $extensionInformation,
            $this->askForSitePackageType($io),
            $this->askForHomepage($io),
        );
    }

    private function generateExtensionKeyFromTitle(string $title): string
    {
        // Lowercase first
        $key = strtolower($title);

        // Replace any sequence of non-alphanumeric characters with underscore
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        // Collapse multiple underscores
        $key = preg_replace('/_+/', '_', $key);

        // Trim underscores from start and end
        $key = trim($key, '_');

        // Remove only leading digits (not letters!)
        $key = preg_replace('/^\d+/', '', $key);

        // Fallback if empty
        if ($key === '') {
            return 'site_package';
        }

        return $key;
    }

    private function askForSitePackageType(SymfonyStyle $io): string
    {
        $choices = [
            'site_package_tutorial' => 'Site Package Tutorial (Educational)',
            'bootstrap_package'     => 'Bootstrap Package',
            'fluid_styled_content'  => 'Fluid Styled Content',
        ];

        $io->text([
            'Choose the base package type. Must be one of:',
            implode(', ', array_keys($choices)),
        ]);

        // Default: "Site Package Tutorial"
        $selectedLabel = $io->choice('Base package type', array_values($choices), $choices['site_package_tutorial']);

        // Map label back to API value
        return array_search($selectedLabel, $choices, true) ?: 'site_package_tutorial';
    }

    private function askForHomepage(SymfonyStyle $io): string
    {
        $io->text([
            'Optionally, you can provide a homepage URL for the author/company.',
            'This will be added to the metadata of the generated site package.',
        ]);

        return (string)$io->ask('Homepage URL', 'https://www.example.org', function ($value): string {
            $url = trim((string)$value);
            if ($url === '' || filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
            throw new \RuntimeException('Invalid URL. Please enter a valid URL starting with http:// or https://', 7249869161);
        });
    }

    private function askForEmail(SymfonyStyle $io, string $default): string
    {
        $email = $default;
        do {
            $email = (string)$io->ask('Email address', $email);
            if ($email !== '' && !GeneralUtility::validEmail($email)) {
                $io->error('You have entered an invalid email address.');
                $validEmail = false;
            } else {
                $validEmail = true;
            }
        } while (!$validEmail);

        return $email;
    }
}
