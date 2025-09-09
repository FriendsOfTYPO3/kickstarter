<?php

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Question;

use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.command.extension.question')]
readonly class ModuleParentQuestion extends AbstractQuestion
{
    public const ARGUMENT_NAME = 'module_parent';

    private const QUESTION = [
        'If the module should be a submodule, the parent identifier, for example "web" has to be set here',
    ];

    private const DESCRIPTION = [
        'Building a new TYPO3 extension needs a unique identifier, the so called extension key. See:',
        'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/BestPractises/ExtensionKey.html',
    ];

    public function getArgumentName(): string
    {
        return self::ARGUMENT_NAME;
    }

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
        $commandContext->getIo()->text($this->getDescription());

        $options =  ['web', 'site', 'file', 'tools', 'system'];

        return $this->askQuestion($this->createSymfonyChoiceQuestion([], $options, $default ?? $options[0]), $commandContext);
    }
}
