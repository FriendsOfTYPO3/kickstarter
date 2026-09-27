<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Question;

use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;

#[AutoconfigureTag('ext-kickstarter.command.question.apply-typo3-cgl')]
#[AutoconfigureTag('ext-kickstarter.command.question.command')]
#[AutoconfigureTag('ext-kickstarter.command.question.controller')]
#[AutoconfigureTag('ext-kickstarter.command.question.event')]
#[AutoconfigureTag('ext-kickstarter.command.question.event-listener')]
#[AutoconfigureTag('ext-kickstarter.command.question.locallang')]
#[AutoconfigureTag('ext-kickstarter.command.question.middleware')]
#[AutoconfigureTag('ext-kickstarter.command.question.model')]
#[AutoconfigureTag('ext-kickstarter.command.question.module')]
#[AutoconfigureTag('ext-kickstarter.command.question.plugin')]
#[AutoconfigureTag('ext-kickstarter.command.question.repository')]
#[AutoconfigureTag('ext-kickstarter.command.question.services-yaml')]
#[AutoconfigureTag('ext-kickstarter.command.question.site-set')]
#[AutoconfigureTag('ext-kickstarter.command.question.site-settings-definition')]
#[AutoconfigureTag('ext-kickstarter.command.question.table')]
#[AutoconfigureTag('ext-kickstarter.command.question.test-env')]
#[AutoconfigureTag('ext-kickstarter.command.question.type-converter')]
#[AutoconfigureTag('ext-kickstarter.command.question.upgrade-wizard')]
#[AutoconfigureTag('ext-kickstarter.command.question.validator')]
#[AutoconfigureTag('ext-kickstarter.command.question.view-helper')]
readonly class ChooseExtensionKeyQuestion extends AbstractQuestion
{
    public const ARGUMENT_NAME = 'choose_extension';

    private const QUESTION = [
        'Which extension should be modified?',
    ];

    private const DESCRIPTION = [
        'Building a new TYPO3 extension needs a unique identifier, the so called extension key. See:',
        'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/BestPractises/ExtensionKey.html',
    ];

    public function getArgumentName(): string
    {
        return self::ARGUMENT_NAME;
    }

    public function __construct(
        private Registry $registry,
        private ExtensionConfiguration $extensionConfiguration,
        private PackageManager $packageManager,
    ) {}

    protected function getDescription(): array
    {
        return self::DESCRIPTION;
    }

    protected function getQuestion(): array
    {
        return self::QUESTION;
    }

    public function ask(CommandContext $commandContext, ?string $default = null): mixed
    {
        $availableExtensions = $this->getAvailableExtensions();
        $commandContext->getIo()->text($this->getDescription());

        if ($availableExtensions === []) {
            $path = ExtConf::create($this->extensionConfiguration)->getExportDirectory();
            $commandContext->getIo()->error('No extensions found at path ' . $path . ' or in PackageManager.');
            $commandContext->getIo()->info('Create an extension using command make:extension or make:site-package first. ');
            die();
        }

        $defaultChoice = $this->resolveDefaultChoice($default, $availableExtensions);
        $extensionKey = $this->askQuestion(
            $this->createSymfonyChoiceQuestion([], $availableExtensions, $defaultChoice),
            $commandContext
        );
        $this->registry->set(ExtConf::EXT_KEY, ExtConf::LAST_EXTENSION_REGISTRY_KEY, $extensionKey);

        return $extensionKey;
    }

    private function resolveDefaultChoice(?string $default, array $availableExtensions): ?string
    {
        $default = $default !== null && trim($default) !== '' ? trim($default) : null;
        if ($default !== null && str_contains($default, '/')) {
            try {
                $default = $this->packageManager->getPackageKeyFromComposerName($default);
            } catch (\Throwable) {
                // Keep default as is if resolution fails
            }
        }

        $candidate = $default ?? (string)$this->registry->get(ExtConf::EXT_KEY, ExtConf::LAST_EXTENSION_REGISTRY_KEY);
        $candidate = trim($candidate);

        if ($candidate !== '' && in_array($candidate, $availableExtensions, true)) {
            return $candidate;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getAvailableExtensions(): array
    {
        $path = ExtConf::create($this->extensionConfiguration)->getExportDirectory();
        $extensions = array_merge(
            $this->getExtensionsFromExportDirectory($path),
            $this->getExtensionsFromPackageManager(),
        );

        $extensions = array_values(array_unique($extensions));
        sort($extensions, SORT_STRING | SORT_FLAG_CASE);

        return $extensions;
    }

    /**
     * @return list<string>
     */
    private function getExtensionsFromExportDirectory(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $extensions = [];
        $entries = scandir($path) ?: [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($fullPath) && file_exists($fullPath . DIRECTORY_SEPARATOR . 'composer.json')) {
                $extensionKey = $this->extractExtensionKeyFromComposerJson($fullPath . DIRECTORY_SEPARATOR . 'composer.json', $entry);
                if ($extensionKey !== null) {
                    $extensions[] = $extensionKey;
                }
            }
        }

        return $extensions;
    }

    private function extractExtensionKeyFromComposerJson(string $composerJsonPath, string $fallbackDirectoryName): ?string
    {
        try {
            $content = (string)file_get_contents($composerJsonPath);
            $manifest = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        $extensionKey = $manifest['extra']['typo3/cms']['extension-key'] ?? null;
        if (!is_string($extensionKey) || $extensionKey === '') {
            $extensionKey = $fallbackDirectoryName;
        }

        if (is_string($extensionKey) && $this->isValidExtensionKey($extensionKey)) {
            return $extensionKey;
        }

        return null;
    }

    private function isValidExtensionKey(string $extensionKey): bool
    {
        return !str_contains($extensionKey, '-') && preg_match('/^[a-z0-9_]+$/', $extensionKey) === 1;
    }

    /**
     * @return list<string>
     */
    private function getExtensionsFromPackageManager(): array
    {
        $extensions = [];

        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $packageType = $package->getPackageMetaData()->getPackageType()
                ?? $package->getValueFromComposerManifest('type');

            if ($packageType === 'typo3-cms-extension') {
                $extensionKey = $this->extractExtensionKeyFromPackage($package);
                if ($extensionKey !== null) {
                    $extensions[] = $extensionKey;
                }
            }
        }

        return $extensions;
    }

    private function extractExtensionKeyFromPackage(PackageInterface $package): ?string
    {
        $manifest = $package->getValueFromComposerManifest();
        $extensionKey = null;

        if (is_object($manifest) && isset($manifest->extra->{'typo3/cms'}->{'extension-key'})) {
            $extensionKey = (string)$manifest->extra->{'typo3/cms'}->{'extension-key'};
        } elseif (is_array($manifest) && isset($manifest['extra']['typo3/cms']['extension-key'])) {
            $extensionKey = (string)$manifest['extra']['typo3/cms']['extension-key'];
        }

        if ($extensionKey === null || $extensionKey === '') {
            $extensionKey = $package->getPackageKey();
        }

        if (is_string($extensionKey) && $this->isValidExtensionKey($extensionKey)) {
            return $extensionKey;
        }

        return null;
    }
}
