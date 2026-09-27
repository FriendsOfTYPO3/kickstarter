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
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\SiteSetInformation;
use FriendsOfTYPO3\Kickstarter\Information\SiteSettingsDefinitionInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SiteSettingsDefinitionCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Settings\CategoryDefinition;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand('make:site-settings-definition', 'Adds a site settings definition to your TYPO3 extension.')]
#[AsNonSchedulableCommand]
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
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
                (string)($commandContext->getInput()->getArgument('extension_key') ?? ''),
            ),
            $commandContext
        );
        if (count($extensionInformation->getSets()) < 1) {
            $io->warning('Extension ' . $extensionInformation->getExtensionKey() . ' contains no site set yet. Use command typo3 make:site-set to create a site set first.');
            return Command::FAILURE;
        }
        $siteSettingsDefinitionInformation = $this->askForSiteSettingsDefinitionInformation($commandContext, $extensionInformation);
        $this->siteSettingsDefinitionCreatorService->create($siteSettingsDefinitionInformation);
        $this->printCreatorInformation($siteSettingsDefinitionInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForSiteSettingsDefinitionInformation(
        CommandContext $commandContext,
        ExtensionInformation $extensionInformation
    ): SiteSettingsDefinitionInformation {
        $io = $commandContext->getIo();

        $siteSet = new SiteSetInformation(
            $extensionInformation,
            '',
            $this->askForSiteSetPath($commandContext, $extensionInformation),
        );

        return new SiteSettingsDefinitionInformation(
            $extensionInformation,
            $siteSet,
            $categories = $this->askForCategories($io),
            $this->askForSettings($io, $extensionInformation, $categories),
        );
    }

    private function askForSiteSetPath(
        CommandContext $commandContext,
        ExtensionInformation $extensionInformation
    ): string {
        $io = $commandContext->getIo();
        $sets = $extensionInformation->getSets();
        return $io->choice('Choose the site set', $sets, $sets[0]);
    }

    private function askForCategories(SymfonyStyle $io): array
    {
        $categories = [];

        $io->title('Category Setup');
        $io->writeln('You must enter at least one category.');

        do {
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

    /**
     * @param list<CategoryDefinition> $categories
     * @return list<SettingDefinition>
     */
    private function askForSettings(
        SymfonyStyle $io,
        ExtensionInformation $extensionInformation,
        array $categories = []
    ): array {
        $settings = [];

        $io->title('Settings definition Setup');
        $io->writeln('You must enter at least one setting.');
        $io->writeln('Each setting has: key, type, default value, label, optional description, readonly flag, optional enum, optional category, and optional tags.');

        do {
            $settings[] = $this->buildSingleSetting($io, $extensionInformation, $settings, $categories);
            $addMore = $io->confirm('Do you want to add another setting definition?', false);
        } while ($addMore || $settings === []);

        return $settings;
    }

    /**
     * @param list<SettingDefinition> $settings
     * @param list<CategoryDefinition> $categories
     */
    private function buildSingleSetting(
        SymfonyStyle $io,
        ExtensionInformation $extensionInformation,
        array $settings,
        array $categories
    ): SettingDefinition {
        $key = $this->askForSettingKey($io, $extensionInformation, $settings);
        $label = (string)$io->ask('Enter setting label', null, function (?string $value): string {
            if (in_array($value, [null, '', '0'], true)) {
                throw new \RuntimeException('Label cannot be empty.', 8317461797);
            }
            return $value;
        });
        $type = (string)$io->choice('Select setting type', $this->getSettingTypes(), 'string');
        $defaultInput = $io->ask('Enter default value (leave empty for false, 0 or empty string)');

        return new SettingDefinition(
            key: $key,
            type: $type,
            default: $this->castDefaultValue($defaultInput, $type),
            label: $label,
            description: (string)$io->ask('Enter setting description (optional)'),
            readonly: $io->confirm('Is this setting readonly?', false),
            enum: $this->askForEnumValues($io, $type),
            category: $this->askForCategory($io, $categories),
        );
    }

    /**
     * @param list<SettingDefinition> $settings
     */
    private function askForSettingKey(
        SymfonyStyle $io,
        ExtensionInformation $extensionInformation,
        array $settings
    ): string {
        $examplePrefix = GeneralUtility::underscoredToLowerCamelCase($extensionInformation->getExtensionKey());
        return (string)$io->ask(
            sprintf('Enter settings key (alphanumeric with dots allowed, e.g. %s.storagePid)', $examplePrefix),
            null,
            function (?string $value) use ($settings): string {
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
            }
        );
    }

    /**
     * @return list<string|int|float|bool|array|null>
     */
    private function askForEnumValues(SymfonyStyle $io, string $type): array
    {
        if ($type !== 'string' || !$io->confirm('Does this setting have a fixed set of allowed values (enum)?', false)) {
            return [];
        }

        $enum = [];
        $io->writeln('Enter allowed values one by one. Leave empty to finish.');
        while (true) {
            $val = $io->ask('Enum value (empty to stop)');
            if ($val === null || $val === '') {
                break;
            }
            $enum[] = $this->castDefaultValue($val, $type);
        }

        return $enum;
    }

    /**
     * @param list<CategoryDefinition> $categories
     */
    private function askForCategory(SymfonyStyle $io, array $categories): ?string
    {
        if ($categories === []) {
            return null;
        }

        $categoryChoices = array_merge(['none'], array_map(fn($c) => $c->key, $categories));
        $categoryKey = $io->choice('Assign to a category (or choose "none")', $categoryChoices, 'none');

        return $categoryKey !== 'none' ? $categoryKey : null;
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
            'array' => array_map(trim(...), explode(',', $input)),
            default => $input,
        };
    }
}
