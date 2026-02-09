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
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Command\CommandAliasQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Command\CommandClassNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Command\CommandNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\CommandInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\CommandCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

#[AsCommand('make:command', 'Add a console command to your TYPO3 extension.')]
#[AsNonSchedulableCommand]
class CommandCommand extends Command
{
    use AskForExtensionKeyTrait;
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly CommandCreatorService $commandCreatorService,
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
            'We are here to assist you in creating a new TYPO3 Command.',
            'Now, we will ask you a few questions to customize the Command according to your needs.',
            'Please take your time to answer them.',
        ]);

        $commandInformation = $this->askForCommandInformation($commandContext);
        $this->commandCreatorService->create($commandInformation);
        $this->printCreatorInformation($commandInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForCommandInformation(CommandContext $commandContext): CommandInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );
        $commandName = (string)$this->questionCollection->askQuestion(
            CommandNameQuestion::ARGUMENT_NAME,
            $commandContext,
            $extensionInformation->getExtensionKey() . ':doSomething'
        );
        $className =  (string)$this->questionCollection->askQuestion(
            CommandClassNameQuestion::ARGUMENT_NAME,
            $commandContext,
            $commandName
        );

        return new CommandInformation(
            $extensionInformation,
            $className,
            $commandName,
            (string)$io->ask('Provide a description for your command'),
            $this->askForCommandAliases($commandContext),
        );
    }

    private function askForCommandAliases(CommandContext $commandContext): array
    {
        $commandAliases = [];
        while ($commandContext->getIo()->confirm('Do you want to add (another) command alias? ', false)) {
            $commandAliases[] = (string)$this->questionCollection->askQuestion(
                CommandAliasQuestion::ARGUMENT_NAME,
                $commandContext,
            );
        }

        return $commandAliases;
    }
}
