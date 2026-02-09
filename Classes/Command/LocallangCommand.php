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
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Locallang\LocallangFileNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Locallang\LocallangTransUnitIdQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\LocallangInformation;
use FriendsOfTYPO3\Kickstarter\Information\TransUnitInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\LocallangCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

#[AsCommand('make:locallang', 'Create a locallang_something.xlf file to handle translations.')]
#[AsNonSchedulableCommand]
class LocallangCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;

    public function __construct(
        private readonly LocallangCreatorService $locallangCreatorService,
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
            'We are here to assist you in creating or extending a language file.',
        ]);

        $locallangInformation = $this->askForLocallangInformation($commandContext);

        $this->locallangCreatorService->create($locallangInformation);
        $this->printCreatorInformation($locallangInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForLocallangInformation(CommandContext $commandContext): LocallangInformation
    {
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );
        $fileName = $commandContext->getIo()->choice(
            'Which file do you want to create or extend? ',
            ['locallang.xlf', 'locallang_db.xlf', 'other'],
            0
        );
        if ($fileName === 'other') {
            $fileName = (string)$this->questionCollection->askQuestion(
                LocallangFileNameQuestion::ARGUMENT_NAME,
                $commandContext,
            );
        }
        $transUnits = [];
        do {
            $sourceString = (string)$commandContext->getIo()->ask('Enter the display text for the term to be added to ' . $fileName);
            $transUnitId = (string)$this->questionCollection->askQuestion(
                LocallangTransUnitIdQuestion::ARGUMENT_NAME,
                $commandContext,
                $sourceString
            );
            $transUnits[] = new TransUnitInformation($transUnitId, $sourceString);
        } while ($commandContext->getIo()->confirm('Do you want to add another term?', false));

        return new LocallangInformation(
            $extensionInformation,
            $fileName,
            new \DateTime(),
            $transUnits
        );
    }
}
