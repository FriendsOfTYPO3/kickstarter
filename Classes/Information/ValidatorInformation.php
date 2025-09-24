<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

use FriendsOfTYPO3\Kickstarter\Enums\ValidatorType;

readonly class ValidatorInformation
{
    private const VALIDATOR_PATH = 'Classes/Domain/Validator/';

    public function __construct(
        private ExtensionInformation $extensionInformation,
        private string $validatorName,
        private ValidatorType $validatorType,
        private ?string $modelName = null,
        private CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    public function getExtensionInformation(): ExtensionInformation
    {
        return $this->extensionInformation;
    }

    public function getValidatorName(): string
    {
        return $this->validatorName;
    }

    public function getValidatorType(): ValidatorType
    {
        return $this->validatorType;
    }

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function getModelFullyQualifiedName(): ?string
    {
        return $this->extensionInformation->getNamespacePrefix() . ModelInformation::NAME_SPACE_PART . '\\' . $this->modelName;
    }

    public function getValidatorFilename(): string
    {
        return $this->validatorName . '.php';
    }

    public function getValidatorFilePath(): string
    {
        return $this->getValidatorPath() . $this->getValidatorFilename();
    }

    public function getValidatorPath(): string
    {
        return $this->extensionInformation->getExtensionPath() . self::VALIDATOR_PATH;
    }

    public function getNamespace(): string
    {
        return $this->extensionInformation->getNamespacePrefix() . 'Domain\\Validator';
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }
}
