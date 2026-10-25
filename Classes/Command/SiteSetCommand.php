<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ChooseExtensionKeyQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\SiteSetInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SiteSetCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\SiteSetQuestionsTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

#[AsCommand('make:site-set', 'Adds a site set configuration to your TYPO3 extension.')]
#[AsNonSchedulableCommand]
class SiteSetCommand extends Command
{
    use AskForExtensionKeyTrait;
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use SiteSetQuestionsTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly SiteSetCreatorService $siteSetCreatorService,
        private readonly QuestionCollection $questionCollection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'extension_key',
            InputArgument::OPTIONAL,
            'Provide the extension key you want to extend',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandContext = new CommandContext($input, $output);
        $io = $commandContext->getIo();
        $io->title('Welcome to the TYPO3 Extension Builder');

        $io->text([
            'We are here to assist you in creating a new TYPO3 Site Set.',
            'Now, we will ask you a few questions to customize the siteSet according to your needs.',
            'Please take your time to answer them.',
        ]);

        $siteSetInformation = $this->askForSiteSetInformation($commandContext);
        $this->siteSetCreatorService->create($siteSetInformation);
        $this->printCreatorInformation($siteSetInformation->getCreatorInformation(), $commandContext);

        $io->text([
            'You can include the site set in your site configuration with',
            'dependencies:',
            '  - ' . $siteSetInformation->getIdentifier(),
            '',
            'You can add a site setting configuration with command',
            'make:site-settings-definition',
        ]);

        return Command::SUCCESS;
    }

    private function askForSiteSetInformation(CommandContext $commandContext): SiteSetInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
                (string)($commandContext->getInput()->getArgument('extension_key') ?? ''),
            ),
            $commandContext
        );

        return new SiteSetInformation(
            $extensionInformation,
            $identifier = $this->askForSiteSetIdentifier($io, $extensionInformation->getComposerPackageName()),
            $siteSetPath = $this->askForSiteSetPath($io, $identifier),
            $this->askForSiteSetLabel($io, $siteSetPath),
            $this->askForDependencies($io),
            $io->confirm('Should the created site set be hidden?', false),
        );
    }

    private function askForDependencies(
        SymfonyStyle $io
    ): array {
        $dependencies = [];
        $pattern = '~^[a-z0-9._-]+/[a-z0-9._-]+$~'; // Composer package name pattern

        $io->writeln('Enter site set identifiers for dependencies (press Enter with empty input to finish).');

        while (true) {
            $dep = $io->ask('Dependency (vendor/package)');

            // Empty input ends the loop
            if ($dep === null) {
                break;
            }

            // Validate format
            if (in_array(preg_match($pattern, $dep), [0, false], true)) {
                $io->error('Invalid site set identifier. Use the format "vendor/site-set", lowercase letters, digits, dots, underscores, or hyphens only.');
                continue;
            }

            // Avoid duplicates
            if (in_array($dep, $dependencies, true)) {
                $io->warning(sprintf('"%s" is already in the list, skipped.', $dep));
                continue;
            }

            $dependencies[] = $dep;
        }

        return $dependencies;
    }
}
