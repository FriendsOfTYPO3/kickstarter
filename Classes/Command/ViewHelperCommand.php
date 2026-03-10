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

#[AsCommand('make:viewhelper', 'Create a new ViewHelper. See also https://docs.typo3.org/permalink/t3coreapi:fluid-custom-viewhelper')]
#[AsNonSchedulableCommand]
class ViewHelperCommand extends Command
{
    use AskForExtensionKeyTrait;
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    private const TYPE_MAP = [
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
            'https://docs.typo3.org/permalink/t3coreapi:fluid-custom-viewhelper on how to implement its functionality.',
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
                (string)($commandContext->getInput()->getArgument('extension_key') ?? ''),
            ),
            $commandContext
        );

        $name = $this->askForViewHelperName($io);
        $tagBased = $io->confirm('Should this ViewHelper be tag-based (rendering an HTML tag)?', false);
        $tagName = $tagBased ? (string)$io->ask('Enter tag name (e.g. "div", "span", "a")', 'div') : '';

        return new ViewHelperInformation(
            extensionInformation: $extensionInformation,
            name: $name,
            tagBased: $tagBased,
            tagName: $tagName,
            arguments: $this->askForArguments($io),
        );
    }

    private function askForArguments(SymfonyStyle $io): array
    {
        $arguments = [];
        while ($argument = $this->askForSingleArgument($io, array_column($arguments, 0))) {
            $arguments[] = $argument;
        }

        return $arguments;
    }

    private function askForSingleArgument(SymfonyStyle $io, array $existingNames): ?array
    {
        $name = $this->askForArgumentName($io, $existingNames);
        if ($name === '') {
            return null;
        }

        $type = $this->askForArgumentType($io);
        $description = (string)$io->ask('Description');
        $required = $io->confirm('Required?', true);

        $argument = [$name, $type, $description, $required];
        $default = $this->askForDefaultValue($io, $type, $required);
        if ($default !== null) {
            $argument[] = $default;
        }

        return $argument;
    }

    private function askForArgumentName(SymfonyStyle $io, array $existingNames): string
    {
        while (true) {
            $name = (string)$io->ask('Argument name (leave empty to finish)');
            if ($name === '') {
                return '';
            }
            if (in_array($name, $existingNames, true)) {
                $io->error('Argument already exists.');
                continue;
            }
            if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $name)) {
                return $name;
            }
            $suggestion = $this->toLowerCamelCase($name);
            if ($suggestion !== '' && preg_match('/^[a-z][a-zA-Z0-9]*$/', $suggestion)
                && $io->confirm(sprintf('Invalid argument name. Use "%s" instead?', $suggestion), true)) {
                return $suggestion;
            }
            $io->error('Invalid argument name. Use lowerCamelCase (e.g. "emailAddress").');
        }
    }

    private function askForArgumentType(SymfonyStyle $io): string
    {
        $typeLabel = $io->choice('Type', array_keys(self::TYPE_MAP), 'string');
        $type = self::TYPE_MAP[$typeLabel];

        return $type === 'custom' ? $this->askForFqn($io) : $type;
    }

    private function askForDefaultValue(SymfonyStyle $io, string $type, bool $required): mixed
    {
        if ($required) {
            return null;
        }
        $defaultInput = $io->ask('Default value (leave empty for none)');
        if ($defaultInput === null || $defaultInput === '') {
            return null;
        }

        return $this->normalizeDefaultValue($defaultInput, $type);
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

        return ltrim($fqn, '\\');
    }

    private function toLowerCamelCase(string $input): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9]+/', ' ', $input);
        $words = array_values(array_filter(explode(' ', trim((string)$clean))));
        if ($words === []) {
            return '';
        }
        $first = strtolower(array_shift($words));
        $rest = array_map(fn(string $w): string => ucfirst(strtolower($w)), $words);

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
            $name = (string)$io->ask('Please provide the name of your ViewHelper', $defaultName);
            $isValid = $this->validateViewHelperName($name, $io, $defaultName);
        } while (!$isValid);

        // Strip "ViewHelper" suffix if user included it
        if (str_ends_with($name, 'ViewHelper')) {
            return substr($name, 0, -10);
        }

        return $name;
    }

    private function validateViewHelperName(string $name, SymfonyStyle $io, ?string &$defaultName): bool
    {
        if (preg_match('/^\d/', $name)) {
            $io->error('ViewHelper name should not start with a number.');
            $defaultName = $this->tryToCorrectClassName($name, '');
            return false;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $name)) {
            $io->error('ViewHelper name contains invalid chars. Please provide just letters and numbers.');
            $defaultName = $this->tryToCorrectClassName($name, '');
            return false;
        }
        if (preg_match('/^[a-z0-9]+$/', $name)) {
            $io->error('ViewHelper must be written in UpperCamelCase like Example or Gravatar.');
            $defaultName = $this->tryToCorrectClassName($name, '');
            return false;
        }

        return true;
    }
}
