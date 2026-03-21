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
use FriendsOfTYPO3\Kickstarter\Information\ViewHelperInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ViewHelperCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
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

#[AsCommand('make:validator', 'Create a new ViewHelper. See also https://docs.typo3.org/permalink/t3coreapi:fluid-custom-viewhelper')]
#[AsNonSchedulableCommand]
class ViewHelperCommand extends Command
{
    use AskForExtensionKeyTrait;
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly ViewHelperCreatorService $viewHelperCreatorService,
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
            'We are here to assist you in creating a new Fluid ViewHelper. ',
            'https://docs.typo3.org/permalink/t3coreapi:fluid-custom-viewhelper on how implement its functionality.',
        ]);

        $viewHelperInformation = $this->askForViewHelperInformation($commandContext);
        $this->viewHelperCreatorService->create($viewHelperInformation);
        $this->printCreatorInformation($viewHelperInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForViewHelperInformation(CommandContext $commandContext): ViewHelperInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        $name = $this->askForViewHelperName($io);

        return new ViewHelperInformation(
            $extensionInformation,
            $name,
        );
    }

    private function askForViewHelperName(SymfonyStyle $io): string
    {
        $defaultName = null;
        do {
            $name = (string)$io->ask(
                'Please provide the name of your ViewHelper',
                $defaultName,
            );

            if (preg_match('/^\d/', $name)) {
                $io->error('ViewHelper name should not start with a number.');
                $defaultName = $this->tryToCorrectClassName($name, '');
                $validValidatorName = false;
            } elseif (preg_match('/[^a-zA-Z0-9]/', $name)) {
                $io->error('ViewHelper name contains invalid chars. Please provide just letters and numbers.');
                $defaultName = $this->tryToCorrectClassName($name, '');
                $validValidatorName = false;
            } elseif (preg_match('/^[a-z0-9]+$/', $name)) {
                $io->error('ViewHelper must be written in UpperCamelCase like BlogExampleValidator.');
                $defaultName = $this->tryToCorrectClassName($name, '');
                $validValidatorName = false;
            } else {
                $validValidatorName = true;
            }
        } while (!$validValidatorName);

        return $name;
    }
}
