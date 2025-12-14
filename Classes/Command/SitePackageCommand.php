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
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SitePackageCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Core\Environment;

#[AsCommand('make:site-package', 'Creates a basic site package')]
#[AsNonSchedulableCommand]
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
        $io->warning('Creating a site package for TYPO3 v14 is not yet supported.');
        return Command::FAILURE;

        /*
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
        */
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
}
