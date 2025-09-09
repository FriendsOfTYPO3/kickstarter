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
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ModelClassNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\ModelInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ModelCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ModelCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    private const DATA_TYPES = [
        'array',
        'bool',
        'float',
        'int',
        'string',
        'object',
        \DateTime::class,
        ObjectStorage::class,
    ];

    public function __construct(
        private readonly ModelCreatorService $modelCreatorService,
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
            ),
            $commandContext
        );

        $mappedTableName = $this->askForMappedTableName($commandContext, $extensionInformation);

        $modelClassName =
            str_replace(sprintf(
                'tx_%s_domain_model_',
                str_replace('_', '', $extensionInformation->getExtensionKey())
            ), '', $mappedTableName);
        do {
            $modelClassName = (string)$this->questionCollection->askQuestion(
                ModelClassNameQuestion::ARGUMENT_NAME,
                $commandContext,
                $modelClassName
            );
            $modelInformation = new ModelInformation(
                $extensionInformation,
                $modelClassName,
            );
        } while (file_exists($modelInformation->getModelFilePath()) && !$io->confirm('Model ' . $modelClassName . ' already exists. Do you want to extend it?'));

        return new ModelInformation(
            $extensionInformation,
            $modelClassName,
            $mappedTableName,
            $io->confirm('Should your model be created as entity? Else, it will be created as value object.'),
            $this->askForProperties($commandContext, $mappedTableName, $extensionInformation),
        );
    }

    private function askForMappedTableName(
        CommandContext $commandContext,
        ExtensionInformation $extensionInformation
    ): string {
        $io = $commandContext->getIo();
        $configuredTcaTables = $extensionInformation->getConfiguredTcaTables();

        if ($configuredTcaTables === []) {
            $io->error([
                'There are no TCA tables configured within your extension.',
                'Please create a TCA table with command "make:table" first.',
            ]);
            die();
        }

        $io->info([
            'Your domain model has to be mapped to a TCA table.',
            'In following list you see the configured TCA tables within your extension.',
        ]);

        return $io->choice(
            'Chose the TCA table you want to map your model to',
            $configuredTcaTables,
        );
    }

    private function askForProperties(
        CommandContext $commandContext,
        string $mappedTableName,
        ExtensionInformation $extensionInformation
    ): array {
        $io = $commandContext->getIo();
        $properties = [];
        $tableTca = $extensionInformation->getTcaForTable($mappedTableName);

        // Step 1: Handle domain fields
        $domainColumns = $extensionInformation->getDomainColumnNamesFromTca($tableTca);
        $domainFieldChoice = $io->choice(
            'Which domain fields should be included',
            ['All', 'Choose manually'],
            'All'
        );

        if ($domainFieldChoice === 'All') {
            $selectedDomainColumns = $domainColumns;
        } else {
            $selectedDomainColumns = $io->choice(
                'Select the domain fields you want to include in your model',
                $domainColumns,
                null,
                true
            );
        }

        // Step 2: Ask about system fields (all, none, or custom)
        $systemColumns = $extensionInformation->getSystemColumnNamesFromTca($tableTca);
        $systemFieldChoice = $io->choice(
            'Which system fields should be included',
            ['All', 'None', 'Choose manually'],
            'None'
        );

        $selectedSystemColumns = [];
        if ($systemFieldChoice === 'All') {
            $selectedSystemColumns = $systemColumns;
        } elseif ($systemFieldChoice === 'Choose manually') {
            $selectedSystemColumns = $io->choice(
                'Select the system fields to include',
                $systemColumns,
                null,
                true
            );
        }

        // Combine selected columns
        $selectedColumns = array_merge($selectedDomainColumns, $selectedSystemColumns);

        // Process selected columns into properties
        foreach ($selectedColumns as $columnName) {
            $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);

            $dataType = $io->choice(
                sprintf('Which data type you prefer for your property: "%s"', $propertyName),
                self::DATA_TYPES,
                'string'
            );

            // Basic meta
            $properties[$columnName] = [
                'propertyName' => $propertyName,
                'dataType' => $dataType,
            ];

            // handle object-initializable types
            if (!in_array($dataType, ['int', 'float', 'string', 'bool', 'array'], true)) {
                $properties[$columnName]['initializeObject'] = true;
                continue;
            }

            // 1) read TCA default, 2) convert to native, 3) ask user (pre-filled)
            $tcaDefault     = (string)($tableTca['columns'][$columnName]['config']['default'] ?? '');
            $defaultValue   = $this->askForDefaultValue($commandContext, $propertyName, $dataType, $tcaDefault);

            $properties[$columnName]['defaultValue'] = $defaultValue;
        }

        return $properties;
    }

    private function askForDefaultValue(
        CommandContext $commandContext,
        string $propertyName,
        string $dataType,
        ?string $suggestedDefault = null
    ): mixed {
        $io = $commandContext->getIo();
        return match ($dataType) {
            'int' => (int)$io->ask(sprintf("Default value for '%s' (int)", $propertyName), $suggestedDefault ?? '0'),
            'float' => (float)$io->ask(sprintf("Default value for '%s' (float)", $propertyName), $suggestedDefault ?? '0.0'),
            'bool' => $io->confirm(sprintf("Default value for '%s' (bool)?", $propertyName), (bool)$suggestedDefault),
            'string' => (string)$io->ask(sprintf("Default value for '%s' (string)", $propertyName), $suggestedDefault ?? ''),
            'array' => json_decode(
                (string)$io->ask(sprintf("Default value for '%s' (array, JSON format)", $propertyName), $suggestedDefault !== null && $suggestedDefault !== '' && $suggestedDefault !== '0' ? json_encode($suggestedDefault) : '[]'),
                true
            ),
            default => null,
        };
    }
}
