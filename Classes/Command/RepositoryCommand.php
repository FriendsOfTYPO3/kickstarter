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
use FriendsOfTYPO3\Kickstarter\Information\RepositoryInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\RepositoryCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

#[AsCommand('make:repository', 'Add an extbase repository to your TYPO3 extension.')]
#[AsNonSchedulableCommand]
class RepositoryCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly RepositoryCreatorService $repositoryCreatorService,
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
            'We are here to assist you in creating a new TYPO3 Extbase Repository.',
            'Now, we will ask you a few questions to customize the repository according to your needs.',
            'Please take your time to answer them.',
        ]);

        $repositoryInformation = $this->askForRepositoryInformation($commandContext);
        $this->repositoryCreatorService->create($repositoryInformation);
        $this->printCreatorInformation($repositoryInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForRepositoryInformation(CommandContext $commandContext): RepositoryInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        return new RepositoryInformation(
            $extensionInformation,
            $this->askForRepositoryClassName($io),
        );
    }

    private function askForRepositoryClassName(SymfonyStyle $io): string
    {
        $defaultRepositoryClassName = null;

        do {
            $repositoryClassName = (string)$io->ask(
                'Please provide the class name of your new Extbase Repository',
                $defaultRepositoryClassName,
            );

            if ($repositoryClassName === '') {
                $io->error('Class name can not be empty.');
                $validRepositoryClassName = false;
            } elseif (preg_match('/^\d/', $repositoryClassName)) {
                $io->error('Class name should not start with a number.');
                $defaultRepositoryClassName = $this->tryToCorrectClassName($repositoryClassName, 'Repository');
                $validRepositoryClassName = false;
            } elseif (preg_match('/[^a-zA-Z0-9]/', $repositoryClassName)) {
                $io->error('Class name contains invalid chars. Please provide just letters and numbers.');
                $defaultRepositoryClassName = $this->tryToCorrectClassName($repositoryClassName, 'Repository');
                $validRepositoryClassName = false;
            } elseif (preg_match('/^[A-Z][a-zA-Z0-9]+$/', $repositoryClassName) === 0) {
                $io->error('Action must be written in UpperCamelCase like "CarRepository".');
                $defaultRepositoryClassName = $this->tryToCorrectClassName($repositoryClassName, 'Repository');
                $validRepositoryClassName = false;
            } elseif (!str_ends_with($repositoryClassName, 'Repository')) {
                $io->error('Class name must end with "Repository".');
                $defaultRepositoryClassName = $this->tryToCorrectClassName($repositoryClassName, 'Repository');
                $validRepositoryClassName = false;
            } else {
                $validRepositoryClassName = true;
            }
        } while (!$validRepositoryClassName);

        return $repositoryClassName;
    }
}
