<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

use FriendsOfTYPO3\Kickstarter\Enums\ServicesType;

readonly class ServicesConfigInformation
{
    public function __construct(
        private ExtensionInformation $extensionInformation,
        private ServicesType $type = ServicesType::YAML,
        private bool $autowire = true,
        private bool $autoconfigure = true,
        private bool $public = false,
        private bool $excludeModels = true,
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    public function getType(): ServicesType
    {
        return $this->type;
    }

    public function isAutowire(): bool
    {
        return $this->autowire;
    }

    public function isAutoconfigure(): bool
    {
        return $this->autoconfigure;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function isExcludeModels(): bool
    {
        return $this->excludeModels;
    }

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getFilename(): string
    {
        return $this->type->getFileName();
    }

    public function getRepositoryFilePath(): string
    {
        return $this->getPath() . $this->getFilename();
    }

    public function getPath(): string
    {
        return $this->extensionInformation->getConfigurationPath();
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }

    /**
     * Build the data structure for Services.yaml as an array.
     */
    public function toServicesArray(): array
    {
        $namespacePrefix = $this->extensionInformation->getNamespacePrefix();

        $serviceDefinition = [
            'resource' => '../Classes/*',
        ];

        if ($this->excludeModels) {
            $serviceDefinition['exclude'] = [
                '../Classes/Domain/Model/*',
            ];
        }

        return [
            'services' => [
                '_defaults' => [
                    'autowire' => $this->autowire,
                    'autoconfigure' => $this->autoconfigure,
                    'public' => $this->public,
                ],
                $namespacePrefix => $serviceDefinition,
            ],
        ];
    }

    /**
     * Convenience factory for tests
     * Accepts either enum or string for "type" (e.g. 'yaml', 'php').
     * Unknown keys are ignored.
     */
    public static function fromArray(
        array $info,
        ExtensionInformation $extensionInformation,
    ): self {
        $type = $info['type'] ?? ServicesType::YAML;
        $autowire = array_key_exists('autowire', $info) ? (bool)$info['autowire'] : true;
        $autoconfigure = array_key_exists('autoconfigure', $info) ? (bool)$info['autoconfigure'] : true;
        $public = array_key_exists('public', $info) && (bool)$info['public'];
        $excludeModels = array_key_exists('excludeModels', $info) ? (bool)$info['excludeModels'] : true;

        return new self(
            extensionInformation: $extensionInformation,
            type: $type,
            autowire: $autowire,
            autoconfigure: $autoconfigure,
            public: $public,
            excludeModels: $excludeModels,
        );
    }
}
