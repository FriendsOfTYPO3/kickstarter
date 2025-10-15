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
use FriendsOfTYPO3\Kickstarter\Enums\TcaFieldType;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\TableInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\TableCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TableCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;

    private const SWITCH_BASIC       = '⇢ Switch to: basic types';

    private const SWITCH_RELATIONAL  = '⇢ Switch to: relational types';

    private const SWITCH_ADDITIONAL  = '⇢ Switch to: additional types';

    private const SWITCH_SYSTEM      = '⇢ Switch to: system types';

    private const SWITCH_ALL         = '⇢ Switch to: all types';

    public function __construct(
        private readonly TableCreatorService $tableCreatorService,
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
            'We are here to assist you in creating a new TCA table.',
            'Now, we will ask you a few questions to customize the controller according to your needs.',
            'Please take your time to answer them.',
        ]);

        $tableInformation = $this->askForTableInformation($commandContext);
        $this->tableCreatorService->create($tableInformation);
        $this->printCreatorInformation($tableInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForTableInformation(CommandContext $commandContext): TableInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        return new TableInformation(
            $extensionInformation,
            $this->askForTableName($io, $extensionInformation),
            (string)$io->ask('Please provide a table title'),
            'uid', // Until now, we do not have any defined columns. We set label to "uid" first as it is mandatory
            $this->askForTableColumns($io),
        );
    }

    private function askForTableName(SymfonyStyle $io, ExtensionInformation $extensionInformation): string
    {
        $prefix = $extensionInformation->getTableNamePrefix();

        do {
            $tableName = strtolower((string)$io->ask(
                'Please provide the table name. Usually the table name starts with: ' . $prefix
            ));

            // 1. Check if name is empty
            if (trim($tableName) === '') {
                $io->error('Table name must not be empty.');
                $validTableName = false;
                continue;
            }

            // 2. Check if name equals the prefix only (e.g., "tx_yyy_domain_model_")
            if ($tableName === $prefix) {
                $io->error('Table name must not be only the prefix: ' . $prefix);
                $validTableName = false;
                continue;
            }

            // 3. If name starts with prefix, accept it directly
            if (str_starts_with($tableName, $prefix)) {
                return $tableName;
            }

            // 4. Suggest prefix + name if user entered only suffix
            $suggestedName = $prefix . $tableName;
            $isTableNameConfirmed = $io->confirm(
                'Would you like to adopt the suggested table name: ' . $suggestedName . '?'
            );

            if ($isTableNameConfirmed) {
                return $suggestedName;
            }

            $validTableName = true; // user declined suggestion but provided valid name
        } while (!$validTableName);

        return $tableName;
    }

    private function askForTableColumns(SymfonyStyle $io): array
    {
        $tableColumns = [];
        $validTableColumnName = false;
        $defaultColumnName = null;

        do {
            $tableColumnName = (string)$io->ask('Enter column name we should create for you', $defaultColumnName);

            if (trim($tableColumnName) === '') {
                $io->error('Table column name must not be empty.');
                $defaultColumnName = $this->tryToCorrectColumnName($tableColumnName);
                $validTableColumnName = false;
            } elseif (preg_match('/^\d/', $tableColumnName)) {
                $io->error('Table column should not start with a number.');
                $defaultColumnName = $this->tryToCorrectColumnName($tableColumnName);
                $validTableColumnName = false;
            } elseif (preg_match('/[^a-z0-9_]/', $tableColumnName)) {
                $io->error('Table column name contains invalid chars. Please provide just letters, numbers and underscores.');
                $defaultColumnName = $this->tryToCorrectColumnName($tableColumnName);
                $validTableColumnName = false;
            } else {
                $tableColumns[$tableColumnName]['label'] = $io->ask(
                    'Please provide a label for the column',
                    ucwords(str_replace('_', ' ', $tableColumnName))
                );
                $tableColumns[$tableColumnName]['type_info'] = TcaFieldType::from($this->askForTableColumnConfiguration($io));
                if ($io->confirm('Do you want to add another table column?')) {
                    continue;
                }
                $validTableColumnName = true;
            }
        } while (!$validTableColumnName);

        return $tableColumns;
    }

    private function tryToCorrectColumnName(string $givenColumnName): string
    {
        // Change dash to underscore
        $cleanedColumnName = str_replace('-', '_', $givenColumnName);

        // Change column name to lower camel case. Add underscores before upper case letters. BlogExample => blog_example
        $cleanedColumnName = GeneralUtility::camelCaseToLowerCaseUnderscored($cleanedColumnName);

        // Remove invalid chars
        return preg_replace('/[^a-zA-Z0-9_]/', '', $cleanedColumnName);
    }

    private function askForTableColumnConfiguration(SymfonyStyle $io): string
    {
        $mode = 'basic';

        while (true) {
            [$choices, $default] = $this->choicesForMode($mode);

            $answer = $io->choice(
                sprintf('Choose TCA column type (%s)', $mode),
                $choices,
                $default
            );

            // Handle “switch” selections
            switch ($answer) {
                case self::SWITCH_BASIC:      $mode = 'basic';
                    continue 2;
                case self::SWITCH_RELATIONAL: $mode = 'relational';
                    continue 2;
                case self::SWITCH_ADDITIONAL: $mode = 'additional';
                    continue 2;
                case self::SWITCH_SYSTEM:     $mode = 'system';
                    continue 2;
                case self::SWITCH_ALL:        $mode = 'all';
                    continue 2;
            }

            // A real type was chosen
            return $answer;
        }
    }

    /**
     * Build the list of visible choices for a given mode, plus the default.
     *
     * @return array{0: list<string>, 1: string|null}
     */
    private function choicesForMode(string $mode): array
    {
        $switches = [
            self::SWITCH_BASIC,
            self::SWITCH_RELATIONAL,
            self::SWITCH_ADDITIONAL,
            self::SWITCH_SYSTEM,
            self::SWITCH_ALL,
        ];

        // Helper to map enum cases to their string values
        $toValues = fn(array $cases): array => array_map(fn(TcaFieldType $c) => $c->value, $cases);

        switch ($mode) {
            case 'basic':
                $choices = [
                    ...$toValues(TcaFieldType::INPUT->basicFields()),
                    ...$switches,
                ];
                $default = TcaFieldType::INPUT->value; // 'input'
                break;

            case 'relational':
                $choices = [
                    ...$toValues(TcaFieldType::INPUT->relationalFields()),
                    ...$switches,
                ];
                $default = null;
                break;

            case 'additional':
                $choices = [
                    ...$toValues(TcaFieldType::INPUT->additionalFields()),
                    ...$switches,
                ];
                $default = null;
                break;

            case 'system':
                $choices = [
                    ...$toValues(TcaFieldType::INPUT->systemFields()),
                    ...$switches,
                ];
                $default = null;
                break;

            case 'all':
            default:
                $choices = [
                    ...TcaFieldType::values(),
                    ...$switches,
                ];
                $default = null;
                break;
        }

        return [$choices, $default];
    }
}
