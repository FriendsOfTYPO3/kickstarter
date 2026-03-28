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
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

#[AsCommand('make:view-helper', 'Create a new ViewHelper. See also https://docs.typo3.org/permalink/t3coreapi:fluid-custom-viewhelper')]
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
            extensionInformation: $extensionInformation,
            name: $name,
            arguments: $this->askForArguments($commandContext->getIo()),
        );
    }

    private function askForArguments(SymfonyStyle $io): array
    {
        $arguments = [];

        $typeMap = [
            'string' => 'string',
            'int' => 'int',
            'bool' => 'bool',
            'float' => 'float',
            'array' => 'array',
            'mixed' => 'mixed',
            'DateTimeInterface' => \DateTimeInterface::class,
            'FileReference (TYPO3)' => FileReference::class,
            'UploadedFile (PSR-7)' => UploadedFileInterface::class,
            'ObjectStorage (Extbase)' => ObjectStorage::class,
            'Custom class' => 'custom',
        ];

        while (true) {
            while (true) {
                $name = (string)$io->ask('Argument name (leave empty to finish)');

                if ($name === '') {
                    break 2; // exit BOTH loops correctly
                }

                // already valid (strict Fluid-style)
                if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $name)) {
                    break;
                }

                // try to suggest correction
                $suggestion = $this->toLowerCamelCase($name);

                if ($suggestion === '' || !preg_match('/^[a-z][a-zA-Z0-9]*$/', $suggestion)) {
                    $io->error('Invalid argument name. Use lowerCamelCase (e.g. "emailAddress").');
                    continue;
                }

                if ($io->confirm(sprintf('Invalid argument name. Use "%s" instead?', $suggestion), true)) {
                    $name = $suggestion;
                    break;
                }

                // otherwise: loop again
            }

            // prevent duplicates
            if (in_array($name, array_column($arguments, 0), true)) {
                $io->error('Argument already exists.');
                continue;
            }

            // type selection
            $typeLabel = $io->choice('Type', array_keys($typeMap), 'string');
            $type = $typeMap[$typeLabel];

            if ($type === 'custom') {
                $type = $this->askForFqn($io);
            }

            $description = (string)$io->ask('Description');
            $required = $io->confirm('Required?', true);

            $argument = [$name, $type, $description, $required];

            // default only if NOT required
            if (!$required) {
                $defaultInput = $io->ask('Default value (leave empty for none)');
                if ($defaultInput !== null && $defaultInput !== '') {
                    $argument[] = $this->normalizeDefaultValue($defaultInput, $type);
                }
            }

            $arguments[] = $argument;
        }

        return $arguments;
    }

    private function askForFqn(SymfonyStyle $io): string
    {
        do {
            $fqn = (string)$io->ask('Enter fully qualified class name (e.g. \\Vendor\\Package\\Model\\Foo)');

            $isValid = preg_match('/^\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*$/', $fqn);

            if (!$isValid) {
                $io->error('Invalid class name. Must be a valid FQN.');
            }
        } while (!$isValid);

        return ltrim($fqn, '\\'); // normalize
    }

    private function toLowerCamelCase(string $input): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9]+/', ' ', $input);

        $words = explode(' ', trim($clean));
        $words = array_filter($words);

        if ($words === []) {
            return '';
        }

        $first = strtolower(array_shift($words));
        $rest = array_map(fn($w): string => ucfirst(strtolower($w)), $words);

        return $first . implode('', $rest);
    }

    private function normalizeDefaultValue(string $value, string $type): mixed
    {
        return match ($type) {
            'integer', 'int' => (int)$value,
            'float' => (float)$value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
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
