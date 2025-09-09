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
use FriendsOfTYPO3\Kickstarter\Enums\ValidatorType;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\ValidatorInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ValidatorCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ValidatorCommand extends Command
{
    use AskForExtensionKeyTrait;
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly ValidatorCreatorService $validatorCreatorService,
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
            'We are here to assist you in creating a new TYPO3 validator. ',
            'Now, we will ask you a few questions to customize the validator according to your needs.',
            'Please take your time to answer them.',
            'See https://docs.typo3.org/permalink/t3coreapi:extbase-domain-validator on how implement its functionality.',
        ]);

        $validatorInformation = $this->askForValidatorInformation($commandContext);
        $this->validatorCreatorService->create($validatorInformation);
        $this->printCreatorInformation($validatorInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForValidatorInformation(CommandContext $commandContext): ValidatorInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        $name = $this->askForValidatorName($io);
        $type = $this->askForValidatorType($io);
        $model = null;
        if ($type === ValidatorType::MODEL) {
            $model = $this->askForValidatorModel($io, $extensionInformation);
        }

        return new ValidatorInformation(
            $extensionInformation,
            $name,
            $type,
            $model
        );
    }

    private function askForValidatorType(SymfonyStyle $io): ValidatorType
    {
        $choices = array_map(
            fn(ValidatorType $type) => $type->value,
            ValidatorType::cases()
        );

        $selected = $io->choice(
            'Please select the type of your Validator',
            $choices
        );

        // Convert selected string back to enum
        return ValidatorType::from($selected);
    }

    private function askForValidatorName(SymfonyStyle $io): string
    {
        $defaultName = null;
        do {
            $name = (string)$io->ask(
                'Please provide the name of your Validator',
                $defaultName,
            );

            if (preg_match('/^\d/', $name)) {
                $io->error('Validator name should not start with a number.');
                $defaultName = $this->tryToCorrectClassName($name, 'Validator');
                $validValidatorName = false;
            } elseif (preg_match('/[^a-zA-Z0-9]/', $name)) {
                $io->error('Validator name contains invalid chars. Please provide just letters and numbers.');
                $defaultName = $this->tryToCorrectClassName($name, 'Validator');
                $validValidatorName = false;
            } elseif (preg_match('/^[a-z0-9]+$/', $name)) {
                $io->error('Validator must be written in UpperCamelCase like BlogExampleValidator.');
                $defaultName = $this->tryToCorrectClassName($name, 'Validator');
                $validValidatorName = false;
            } elseif (!str_ends_with($name, 'Validator')) {
                $io->error('Validator must end with "Validator".');
                $defaultName = $this->tryToCorrectClassName($name, 'Validator');
                $validValidatorName = false;
            } else {
                $validValidatorName = true;
            }
        } while (!$validValidatorName);

        return $name;
    }

    private function askForValidatorModel(SymfonyStyle $io, ExtensionInformation $extensionInformation): string
    {
        $extbaseModelClassnames = $extensionInformation->getExtbaseModelClassnames();
        if ($extbaseModelClassnames === []) {
            $io->error([
                'Your extension does not contain any Extbase models.',
                'Please create at least one Extbase model with \'typo3 make:model\' before creating a validator of type model.',
            ]);
            die();
        }
        return (string)$io->choice(
            'Select the Extbase model classes you want to validate',
            $extbaseModelClassnames
        );
    }
}
