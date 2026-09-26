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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

#[AsCommand('make:typeconverter', 'Create a new Extbase Type Converter to your TYPO3 extension.')]
#[AsNonSchedulableCommand]
class TypeConverterCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly TypeConverterCreatorService $typeConverterCreatorService,
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
            'We are here to assist you in creating a new TYPO3 Type Converter.',
            'Now, we will ask you a few questions to customize the type converter according to your needs.',
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
                (string)($commandContext->getInput()->getArgument('extension_key') ?? ''),
            ),
            $commandContext
        );

        return new TypeConverterInformation(
            $extensionInformation,
            $this->askForTypeConverterClassName($io),
            (int)$io->ask('Set priority', '10'),
            $this->askForSourceTypes($io),
            (string)$io->ask('Set target data type. Can be any PHP data type or object/model (in that case FQCN: "\MyVendor\MyExt\Domain\Model\Car")'),
        );
    }

    private function askForSourceTypes(SymfonyStyle $io): string
    {
        $defaultSourceTypes = 'integer, string, array';

        do {
            $input = (string)$io->ask('Set source data type(s)', $defaultSourceTypes);
            $normalized = TypeConverterInformation::parseAndNormalizeSourceTypes($input);
            $invalid = TypeConverterInformation::findInvalidSourceTypes($normalized);

            if ($this->validateSourceTypesInput($io, $normalized, $invalid)) {
                return implode(', ', $normalized);
            }
        } while (true);
    }

    /**
     * @param list<string> $normalized
     * @param list<string> $invalid
     */
    private function validateSourceTypesInput(SymfonyStyle $io, array $normalized, array $invalid): bool
    {
        if ($normalized === []) {
            $io->error('Please provide at least one source data type.');
            return false;
        }

        if ($invalid !== []) {
            $io->error(sprintf(
                'Invalid source type(s): "%s". Allowed source types are: %s.',
                implode('", "', $invalid),
                implode(', ', TypeConverterInformation::ALLOWED_SOURCE_TYPES),
            ));
            return false;
        }

        return true;
    }

    private function askForTypeConverterClassName(SymfonyStyle $io): string
    {
        $defaultClassName = null;

        do {
            $className = (string)$io->ask(
                'Please provide the class name of your new Type Converter',
                $defaultClassName,
            );

            if ($this->isValidTypeConverterClassName($io, $className, $defaultClassName)) {
                return $className;
            }
        } while (true);
    }

    private function isValidTypeConverterClassName(SymfonyStyle $io, string $className, ?string &$defaultClassName): bool
    {
        if (preg_match('/^\d/', $className)) {
            $io->error('Class name should not start with a number.');
        } elseif (preg_match('/[^a-zA-Z0-9]/', $className)) {
            $io->error('Class name contains invalid chars. Please provide just letters and numbers.');
        } elseif (preg_match('/^[A-Z][a-zA-Z0-9]+$/', $className) === 0) {
            $io->error('Class name must be written in UpperCamelCase like "FileUploadConverter".');
        } elseif (!str_ends_with($className, 'Converter')) {
            $io->error('Class name must end with "Converter".');
        } else {
            return true;
        }

        $defaultClassName = $this->tryToCorrectClassName($className, 'Converter');
        return false;
    }
}
