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
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Model\ModelClassNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Enums\ModelPropertyType;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\ModelInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ModelCreatorService;
use FriendsOfTYPO3\Kickstarter\Service\ExternalTcaTableResolver;
use FriendsOfTYPO3\Kickstarter\Service\TcaToModelPropertyTypeResolver;
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand('make:model', 'Add an Extbase model to your TYPO3 extension.')]
#[AsNonSchedulableCommand]
class ModelCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    private const CHOICE_USE_EXTERNAL = 'Use an existing table from TYPO3 Core or another extension...';

    private const CHOICE_CANCEL = 'Cancel (create table first)';

    public function __construct(
        private readonly ModelCreatorService $modelCreatorService,
        private readonly QuestionCollection $questionCollection,
        private readonly ExternalTcaTableResolver $externalTcaTableResolver,
        private readonly TcaToModelPropertyTypeResolver $tcaToModelPropertyTypeResolver,
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
            'We are here to assist you in creating a new TYPO3 Extbase Model.',
            'Now, we will ask you a few questions to customize the model according to your needs.',
            'Please take your time to answer them.',
        ]);

        $modelInformation = $this->askForModelInformation($commandContext);
        $this->modelCreatorService->create($modelInformation);
        $this->printCreatorInformation($modelInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForModelInformation(CommandContext $commandContext): ModelInformation
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

        $mappedTableName = $this->askForMappedTableName($commandContext, $extensionInformation);
        $modelClassName = $this->resolveModelClassName($commandContext, $extensionInformation, $mappedTableName);

        return new ModelInformation(
            $extensionInformation,
            $modelClassName,
            $mappedTableName,
            $io->confirm('Should your model be created as entity? Else, it will be created as value object.'),
            $this->askForProperties($commandContext, (string)$mappedTableName, $extensionInformation),
        );
    }

    private function resolveModelClassName(
        CommandContext $commandContext,
        ExtensionInformation $extensionInformation,
        ?string $mappedTableName
    ): string {
        $io = $commandContext->getIo();
        $prefix = sprintf('tx_%s_domain_model_', str_replace('_', '', $extensionInformation->getExtensionKey()));
        $modelClassName = str_replace($prefix, '', (string)$mappedTableName);

        do {
            $modelClassName = (string)$this->questionCollection->askQuestion(
                ModelClassNameQuestion::ARGUMENT_NAME,
                $commandContext,
                $modelClassName
            );
            $modelInformation = new ModelInformation($extensionInformation, $modelClassName);
        } while (file_exists($modelInformation->getModelFilePath())
            && !$io->confirm('Model ' . $modelClassName . ' already exists. Do you want to extend it?'));

        return $modelClassName;
    }

    private function askForMappedTableName(
        CommandContext $commandContext,
        ExtensionInformation $extensionInformation
    ): ?string {
        $configuredTcaTables = $extensionInformation->getConfiguredTcaTables();
        $externalTcaTables = $this->externalTcaTableResolver->getExternalTcaTables($extensionInformation);

        if ($configuredTcaTables === []) {
            return $this->askForExternalTable($commandContext->getIo(), $externalTcaTables);
        }

        return $this->askForConfiguredOrExternalTable(
            $commandContext->getIo(),
            $configuredTcaTables,
            $externalTcaTables
        );
    }

    /**
     * @param list<string> $externalTcaTables
     */
    private function askForExternalTable(SymfonyStyle $io, array $externalTcaTables): ?string
    {
        $io->warning([
            'There are no TCA tables configured within your extension.',
            'You might want to create a TCA table with "make:table" first.',
            'Alternatively, you may map your model to an existing table from TYPO3 Core or another extension.',
        ]);

        $choices = $externalTcaTables;
        $choices[] = self::CHOICE_CANCEL;

        $choice = $io->choice('Choose an existing table (TYPO3 Core or other extension), or cancel', $choices);

        return $choice === self::CHOICE_CANCEL ? null : $choice;
    }

    /**
     * @param list<string> $configuredTcaTables
     * @param list<string> $externalTcaTables
     */
    private function askForConfiguredOrExternalTable(
        SymfonyStyle $io,
        array $configuredTcaTables,
        array $externalTcaTables
    ): ?string {
        $io->info([
            'Your domain model must be mapped to a TCA table.',
            'You can either use a table from this extension or an existing table from TYPO3 Core or another extension.',
        ]);

        $choices = $configuredTcaTables;
        if ($externalTcaTables !== []) {
            $choices[] = self::CHOICE_USE_EXTERNAL;
        }
        $choices[] = self::CHOICE_CANCEL;

        $choice = $io->choice('Choose the TCA table for your model', $choices);
        if ($choice === self::CHOICE_CANCEL) {
            return null;
        }

        if ($choice === self::CHOICE_USE_EXTERNAL) {
            return $io->choice('Choose the existing table (TYPO3 Core or other extension)', $externalTcaTables);
        }

        return $choice;
    }

    private function askForProperties(
        CommandContext $commandContext,
        string $mappedTableName,
        ExtensionInformation $extensionInformation
    ): array {
        $io = $commandContext->getIo();
        $tableTca = $extensionInformation->getTcaForTable($mappedTableName);

        $selectedDomainColumns = $this->askForDomainColumns($io, $extensionInformation, $tableTca);
        $selectedSystemColumns = $this->askForSystemColumns($io, $extensionInformation, $tableTca);

        $selectedColumns = array_merge($selectedDomainColumns, $selectedSystemColumns);
        $properties = [];

        foreach ($selectedColumns as $columnName) {
            $columnConfig = (array)($tableTca['columns'][$columnName]['config'] ?? []);
            $properties[$columnName] = $this->buildProperty($commandContext, $columnName, $columnConfig);
        }

        return $properties;
    }

    /**
     * @param array<string, mixed> $tableTca
     * @return list<string>
     */
    private function askForDomainColumns(
        SymfonyStyle $io,
        ExtensionInformation $extensionInformation,
        array $tableTca
    ): array {
        $domainColumns = $extensionInformation->getDomainColumnNamesFromTca($tableTca);
        $choice = $io->choice(
            'Which domain fields should be included',
            ['All', 'Choose manually'],
            'All'
        );

        if ($choice === 'All') {
            return $domainColumns;
        }

        return $io->choice(
            'Select the domain fields you want to include in your model',
            $domainColumns,
            null,
            true
        );
    }

    /**
     * @param array<string, mixed> $tableTca
     * @return list<string>
     */
    private function askForSystemColumns(
        SymfonyStyle $io,
        ExtensionInformation $extensionInformation,
        array $tableTca
    ): array {
        $systemColumns = $extensionInformation->getSystemColumnNamesFromTca($tableTca);
        $choice = $io->choice(
            'Which system fields should be included',
            ['All', 'None', 'Choose manually'],
            'None'
        );

        if ($choice === 'All') {
            return $systemColumns;
        }
        if ($choice === 'Choose manually') {
            return $io->choice(
                'Select the system fields to include',
                $systemColumns,
                null,
                true
            );
        }

        return [];
    }

    /**
     * @param array<string, mixed> $columnConfig
     * @return array<string, mixed>
     */
    private function buildProperty(
        CommandContext $commandContext,
        string $columnName,
        array $columnConfig
    ): array {
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $dataType = $this->askForDataType($commandContext->getIo(), $propertyName, $columnConfig);
        $type = ModelPropertyType::from($dataType);

        $property = [
            'propertyName' => $propertyName,
            'dataType' => $dataType,
        ];

        if ($type->needsInitialization()) {
            $property['initializeObject'] = true;
        }

        $suggestedDefault = $this->tcaToModelPropertyTypeResolver->resolveDefaultValue($columnConfig, $type);
        $property['defaultValue'] = $this->askForDefaultValue($commandContext, $propertyName, $type, $suggestedDefault);

        return $property;
    }

    /**
     * @param array<string, mixed> $columnConfig
     */
    private function askForDataType(SymfonyStyle $io, string $propertyName, array $columnConfig): string
    {
        $allowedTypes = $this->tcaToModelPropertyTypeResolver->resolvePropertyTypes($columnConfig);
        $allValues = ModelPropertyType::values();
        $choices = $allowedTypes;

        if ($allowedTypes !== $allValues) {
            $choices[] = 'Other (choose from all types)...';
        }

        $chosenType = $io->choice(
            sprintf('Which data type you prefer for your property: "%s"', $propertyName),
            $choices,
            $allowedTypes[0]
        );

        if ($chosenType === 'Other (choose from all types)...') {
            return $io->choice(
                sprintf('Choose any data type for your property: "%s"', $propertyName),
                $allValues,
                $allowedTypes[0]
            );
        }

        return $chosenType;
    }

    private function askForDefaultValue(
        CommandContext $commandContext,
        string $propertyName,
        ModelPropertyType $type,
        mixed $suggestedDefault = null
    ): mixed {
        $io = $commandContext->getIo();
        if (!$type->supportsDefault()) {
            return null;
        }

        $suggestion = $suggestedDefault ?? $type->suggestedDefault();
        if ($type === ModelPropertyType::BOOL) {
            return $io->confirm(
                sprintf("Default value for '%s' (bool)?", $propertyName),
                (bool)$suggestion
            );
        }

        if ($type === ModelPropertyType::ARRAY) {
            $defaultJson = is_array($suggestion) ? json_encode($suggestion, JSON_UNESCAPED_SLASHES) : '[]';
            return $type->coerceDefault(
                (string)$io->ask(
                    sprintf("Default value for '%s' (array, JSON format)", $propertyName),
                    $defaultJson
                )
            );
        }

        return $type->coerceDefault(
            $io->ask(
                sprintf("Default value for '%s' (%s)", $propertyName, $type->value),
                (string)$suggestion
            )
        );
    }
}
