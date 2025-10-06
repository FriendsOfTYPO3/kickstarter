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
use FriendsOfTYPO3\Kickstarter\Information\TypeConverterInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\TypeConverterCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use FriendsOfTYPO3\Kickstarter\Validator\PhpClassNameValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TypeConverterCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly TypeConverterCreatorService $typeConverterCreatorService,
        private readonly QuestionCollection $questionCollection,
        private readonly PhpClassNameValidator $classNameValidator,
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
            'We are here to assist you in creating a new TYPO3 Event Listener.',
            'Now, we will ask you a few questions to customize the event listener according to your needs.',
            'Please take your time to answer them.',
        ]);

        $typeConverterInformation = $this->askForTypeConverterInformation($commandContext);
        $this->typeConverterCreatorService->create($typeConverterInformation);
        $this->printCreatorInformation($typeConverterInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForTypeConverterInformation(CommandContext $commandContext): TypeConverterInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        return new TypeConverterInformation(
            $extensionInformation,
            $this->askForTypeConverterClassName($io),
            (int)$io->ask('Set priority', '10'),
            (string)$io->ask('Set source data type(s)', 'int,string,array'),
            (string)$io->ask('Set target data type. Can be any PHP data type or object/model (in that case FQCN: "\MyVendor\MyExt\Domain\Model\Car")'),
        );
    }

    private function askForTypeConverterClassName(SymfonyStyle $io): string
    {
        $defaultTypeConverterClassName = null;

        do {
            $typeConverterClassName = (string)$io->ask(
                'Please provide the class name of your new Type Converter',
                $defaultTypeConverterClassName,
            );

            if ($typeConverterClassName === '') {
                $io->error('Class name can not be empty.');
                $validTypeConverterClassName = false;
            } elseif (!$this->classNameValidator->validate($typeConverterClassName)) {
                $io->error('Class name is not a valid php class name.');
                $validTypeConverterClassName = false;
            } elseif (preg_match('/^[A-Z][a-zA-Z0-9]+$/', $typeConverterClassName) === 0) {
                $io->error('Class name must be written in UpperCamelCase like "FileUploadConverter".');
                $defaultTypeConverterClassName = $this->tryToCorrectClassName($typeConverterClassName, 'Converter');
                $validTypeConverterClassName = false;
            } elseif (!str_ends_with($typeConverterClassName, 'Converter')) {
                $io->error('Class name must end with "Converter".');
                $defaultTypeConverterClassName = $this->tryToCorrectClassName($typeConverterClassName, 'Converter');
                $validTypeConverterClassName = false;
            } else {
                $validTypeConverterClassName = true;
            }
        } while (!$validTypeConverterClassName);

        return $typeConverterClassName;
    }
}
