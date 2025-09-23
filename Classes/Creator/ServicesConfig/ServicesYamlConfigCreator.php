<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\ServicesConfig;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Enums\ServicesType;
use FriendsOfTYPO3\Kickstarter\Information\ServicesConfigInformation;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ServicesYamlConfigCreator implements ServicesConfigCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager
    ) {}

    public function create(ServicesConfigInformation $info): void
    {
        if ($info->getType() !== ServicesType::YAML) {
            return;
        }

        $classesPath = $info->getExtensionInformation()->getClassesPath();
        GeneralUtility::mkdir_deep($classesPath);
        $this->fileManager->createOrModifyFile($classesPath . '.gitkeep', '', $info->getCreatorInformation());

        $servicesConfigPath = $info->getPath();
        GeneralUtility::mkdir_deep($servicesConfigPath);

        $servicesFile = $servicesConfigPath . 'Services.yaml';
        $incoming = $info->toServicesArray();
        $namespacePrefix = $info->getExtensionInformation()->getNamespacePrefix();

        $existing = [];
        if (file_exists($servicesFile)) {
            try {
                $existing = Yaml::parseFile($servicesFile) ?? [];
            } catch (ParseException) {
                // If YAML is broken, fall back to regenerating only your nodes
                $existing = [];
            }
        }

        $merged = $this->mergeServicesYaml($existing, $incoming, $namespacePrefix);

        // Pretty + deterministic dump
        $yaml = rtrim(Yaml::dump($merged, 8, 2), "\r\n") . "\n";

        $this->fileManager->createOrModifyFile(
            $servicesFile,
            $yaml,
            $info->getCreatorInformation()
        );
    }

    private function mergeServicesYaml(
        array $existing,
        array $incoming,
        string $namespacePrefix
    ): array {
        // Start from existing; fill in if missing
        $merged = $existing;
        $merged['services'] ??= [];

        // 1) _defaults: overlay incoming onto existing (existing keys preserved unless incoming sets them)
        $existingDefaults = $merged['services']['_defaults'] ?? [];
        $incomingDefaults = $incoming['services']['_defaults'] ?? [];
        $merged['services']['_defaults'] = array_replace($existingDefaults, $incomingDefaults);

        // 2) Namespace block: merge resource + exclude
        $existingNs = $merged['services'][$namespacePrefix] ?? [];
        $incomingNs = $incoming['services'][$namespacePrefix] ?? [];

        // resource: incoming wins if provided, otherwise keep existing
        if (array_key_exists('resource', $incomingNs)) {
            $existingNs['resource'] = $incomingNs['resource'];
        }

        // exclude: normalize to lists, then union (keeps existing custom excludes too)
        $toList = static function ($v): array {
            if ($v === null) {
                return [];
            }
            if (is_string($v)) {
                return [$v];
            }
            return is_array($v) ? array_values($v) : [];
        };
        $exExisting = $toList($existingNs['exclude'] ?? null);
        $exIncoming = $toList($incomingNs['exclude'] ?? null);

        // If incoming explicitly wants no excludes (empty array), respect that; else merge unique
        $mergedExclude = $exIncoming === [] && array_key_exists('exclude', $incomingNs)
            ? []
            : array_values(array_unique(array_merge($exExisting, $exIncoming)));

        if ($mergedExclude !== []) {
            $existingNs['exclude'] = $mergedExclude;
        } else {
            unset($existingNs['exclude']);
        }

        $merged['services'][$namespacePrefix] = $existingNs;

        return $merged;
    }
}
