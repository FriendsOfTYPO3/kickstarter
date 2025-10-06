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
use FriendsOfTYPO3\Kickstarter\Information\SiteSetInformation;
use FriendsOfTYPO3\Kickstarter\Information\SiteSettingsDefinitionInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SiteSettingsDefinitionCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Settings\CategoryDefinition;
use TYPO3\CMS\Core\Settings\SettingDefinition;

class SiteSettingsDefinitionCommand extends Command
{
    use AskForExtensionKeyTrait;
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly SiteSettingsDefinitionCreatorService $siteSettingsDefinitionCreatorService,
        #[AutowireLocator('settings.type')]
        private ServiceLocator $types,
        private readonly QuestionCollection $questionCollection,
    ) {
        parent::__construct();
    }

    /**
     * @return string[]
     */
    public function getSettingTypes(): array
    {
        return array_keys($this->types->getProvidedServices());
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
            'We are here to assist you in creating a new TYPO3 Site Set.',
            'Now, we will ask you a few questions to customize the siteSet according to your needs.',
            'Please take your time to answer them.',
        ]);

        $siteSettingsDefinitionInformation = $this->askForSiteSettingsDefinitionInformation($commandContext);
        $this->siteSettingsDefinitionCreatorService->create($siteSettingsDefinitionInformation);
        $this->printCreatorInformation($siteSettingsDefinitionInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForSiteSettingsDefinitionInformation(CommandContext $commandContext): SiteSettingsDefinitionInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        return new SiteSettingsDefinitionInformation(
            $extensionInformation,
            new SiteSetInformation(
                $extensionInformation,
                '',
                $this->askForSiteSetPath($io),
            ),
            $categories = $this->askForCategories($io),
            $this->askForSettings($io, $categories),
        );
    }

    private function askForSiteSetPath(SymfonyStyle $io): string
    {
        $default = '';
        do {
            $siteSetPath = $io->ask('Please enter the site set directory name (must exist)', $default);

            if ($siteSetPath === null) {
                $io->error('The site set path cannot be empty.');
                continue;
            }

            if (preg_match('~[\\\\/]~', $siteSetPath)) {
                $io->error('The site set path must not contain slashes.');
                continue;
            }

            // Valid input
            return $siteSetPath;

        } while (true);
    }

    private function askForCategories(SymfonyStyle $io): array
    {
        $categories = [];

        $io->title('Category Setup');
        $io->writeln('You must enter at least one category.');

        do {

            // --- Key ---
            $key = $io->ask(
                'Enter category key (alphanumeric and dots allowed, e.g., BlogExample.pages)',
                null,
                function (?string $value) use ($categories): string {
                    if (in_array($value, [null, '', '0'], true)) {
                        throw new \RuntimeException('Key cannot be empty.', 4823022337);
                    }
                    if (in_array(preg_match('/^[a-zA-Z0-9]+(?:\.[a-zA-Z0-9]+)*$/', $value), [0, false], true)) {
                        throw new \RuntimeException('Key must be alphanumeric and may include dots as separators.', 2134072936);
                    }
                    foreach ($categories as $c) {
                        if ($c->key === $value) {
                            throw new \RuntimeException(sprintf("The key '%s' is already used. Keys must be unique.", $value), 4017589124);
                        }
                    }
                    return $value;
                }
            );

            // --- Label ---
            $label = $io->ask('Enter category label', null, function (?string $value): string {
                if (in_array($value, [null, '', '0'], true)) {
                    throw new \RuntimeException('Label cannot be empty.', 8647488631);
                }
                return $value;
            });

            // --- Optional fields ---
            $description = $io->ask('Enter category description (optional)');
            $icon = $io->ask('Enter category icon (optional)');

            // --- Parent Selection ---
            $parent = null;
            if ($categories !== []) {
                $parentChoices = array_merge(['none'], array_map(static fn($c): string => $c->key, $categories));
                $parentKey = $io->choice('Select a parent category by key or choose "none"', $parentChoices, 'none');

                if ($parentKey !== 'none') {
                    // Ensure no circular references
                    if ($this->wouldCreateCircularReference($categories, $parentKey, $key)) {
                        $io->warning(sprintf("Using '%s' as a parent would create a circular reference. Parent not assigned.", $parentKey));
                    } else {
                        $parent = $parentKey;
                    }
                }
            }

            // --- Add Category ---
            $categories[] = new CategoryDefinition(
                key: $key,
                label: $label,
                description: $description ?: null,
                icon: $icon ?: null,
                parent: $parent
            );

            // --- Continue? ---
            $addMore = $io->confirm('Do you want to add another category?', false);
        } while ($addMore || $categories === []);

        return $categories;
    }

    /**
     * Prevents circular references by checking if $parentKey is an ancestor of $childKey.
     */
    private function wouldCreateCircularReference(array $categories, string $parentKey, string $childKey): bool
    {
        $lookup = [];
        foreach ($categories as $c) {
            $lookup[$c->key] = $c->parent;
        }

        // Simulate assigning the parent
        $lookup[$childKey] = $parentKey;

        // Walk up the chain
        $current = $parentKey;
        while ($current !== null) {
            if ($current === $childKey) {
                return true;
            }
            $current = $lookup[$current] ?? null;
        }

        return false;
    }

    private function askForSettings(SymfonyStyle $io, array $categories = []): array
    {
        $settings = [];

        $io->title('Settings definition Setup');
        $io->writeln('You must enter at least one setting.');
        $io->writeln('Each setting has: key, type, default value, label, optional description, readonly flag, optional enum, optional category, and optional tags.');

        do {
            // --- Key ---
            $key = $io->ask('Enter settings key (alphanumeric with dots allowed)', null, function (?string $value) use ($settings): string {
                if (in_array($value, [null, '', '0'], true)) {
                    throw new \RuntimeException('Key cannot be empty.', 7392794136);
                }
                if (in_array(preg_match('/^[a-zA-Z0-9]+(?:\.[a-zA-Z0-9]+)*$/', $value), [0, false], true)) {
                    throw new \RuntimeException('Key must be alphanumeric and may include dots as separators.', 6396797527);
                }
                foreach ($settings as $s) {
                    if ($s->key === $value) {
                        throw new \RuntimeException(sprintf("The key '%s' is already used. Keys must be unique.", $value), 2170736662);
                    }
                }
                return $value;
            });

            // --- Label ---
            $label = $io->ask('Enter setting label', null, function (?string $value): string {
                if (in_array($value, [null, '', '0'], true)) {
                    throw new \RuntimeException('Label cannot be empty.', 8317461797);
                }
                return $value;
            });

            // --- Type ---
            $type = $io->choice('Select setting type', $this->getSettingTypes(), 'string');

            // --- Default ---
            $defaultInput = $io->ask('Enter default value (leave empty for null)');
            $default = $this->castDefaultValue($defaultInput, $type);

            // --- Description ---
            $description = $io->ask('Enter setting description (optional)');

            // --- Readonly ---
            $readonly = $io->confirm('Is this setting readonly?', false);

            // --- Enum ---
            $enum = [];
            if ($type === 'string' && $io->confirm('Does this setting have a fixed set of allowed values (enum)?', false)) {
                $io->writeln('Enter allowed values one by one. Leave empty to finish.');
                while (true) {
                    $val = $io->ask('Enum value (empty to stop)');
                    if ($val === null || $val === '') {
                        break;
                    }
                    $enum[] = $this->castDefaultValue($val, $type);
                }
            }

            // --- Category ---
            $category = null;
            if ($categories !== []) {
                $categoryChoices = array_merge(['none'], array_map(fn($c) => $c->key, $categories));
                $categoryKey = $io->choice('Assign to a category (or choose "none")', $categoryChoices, 'none');
                $category = $categoryKey !== 'none' ? $categoryKey : null;
            }

            // --- Add Setting ---
            $settings[] = new SettingDefinition(
                key: $key,
                type: $type,
                default: $default,
                label: $label,
                description: $description,
                readonly: $readonly,
                enum: $enum,
                category: $category,
            );

            $addMore = $io->confirm('Do you want to add another setting definition?', false);
        } while ($addMore || $settings === []);

        return $settings;
    }

    /**
     * Helper: Casts default or enum values according to type.
     */
    private function castDefaultValue(?string $input, string $type): string|int|float|bool|array|null
    {
        if ($input === null || $input === '') {
            return null;
        }

        return match ($type) {
            'int' => (int)$input,
            'float' => (float)$input,
            'bool' => filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'array' => array_map('trim', explode(',', $input)),
            default => $input,
        };
    }
}
