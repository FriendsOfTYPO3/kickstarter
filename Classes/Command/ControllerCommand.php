<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ActionMethodNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ChooseExtensionKeyQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ControllerClassNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\ControllerInformation;
use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ControllerCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ControllerCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use FileStructureBuilderTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly ControllerCreatorService $controllerCreatorService,
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
            'We are here to assist you in creating a new TYPO3 controller.',
            'Now, we will ask you a few questions to customize the controller according to your needs.',
            'Please take your time to answer them.',
        ]);

        $controllerInformation = $this->askForControllerInformation($commandContext);
        $this->controllerCreatorService->create($controllerInformation);
        $this->printCreatorInformation($controllerInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForControllerInformation(CommandContext $commandContext): ControllerInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        $className = (string)$this->questionCollection->askQuestion(
            ControllerClassNameQuestion::ARGUMENT_NAME,
            $commandContext,
        );
        $io->text('Class name ' . $className . ' will be used');

        return new ControllerInformation(
            $extensionInformation,
            $io->confirm('Do you prefer to create an Extbase based controller?'),
            $className,
            $this->askForActionMethodNames($commandContext),
            new CreatorInformation(),
        );
    }

    private function askForActionMethodNames(CommandContext $commandContext): array
    {
        $actionMethods = [];
        $defaultActionName = 'indexAction';

        do {
            $actionMethods[] = (string)$this->questionCollection->askQuestion(
                ActionMethodNameQuestion::ARGUMENT_NAME,
                $commandContext,
                $defaultActionName,
            );
            $commandContext->getIo()->text('Action ' . end($actionMethods) . ' was added.');
        } while ($commandContext->getIo()->confirm('Do you want to add another action method?', false));

        return $actionMethods;
    }
}
