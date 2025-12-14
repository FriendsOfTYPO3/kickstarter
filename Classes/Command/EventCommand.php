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
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\EventClassNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\EventInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\EventCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

#[AsCommand('make:event', 'Create a new Event for your TYPO3 extension.')]
#[AsNonSchedulableCommand]
class EventCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;

    public function __construct(
        private readonly EventCreatorService $eventCreatorService,
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
            'We are here to assist you in creating a new TYPO3 Event.',
            'Now, we will ask you a few questions to customize the event according to your needs.',
            'Please take your time to answer them.',
        ]);

        $eventInformation = $this->askForEventInformation($commandContext);

        $this->eventCreatorService->create($eventInformation);
        $this->printCreatorInformation($eventInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForEventInformation(CommandContext $commandContext): EventInformation
    {
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );
        $eventClassName = (string)$this->questionCollection->askQuestion(
            EventClassNameQuestion::ARGUMENT_NAME,
            $commandContext,
        );

        return new EventInformation(
            $extensionInformation,
            $eventClassName,
        );
    }
}
