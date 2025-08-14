<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Question;

use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.command.extension.question')]
readonly class CommandClassNameQuestion extends AbstractQuestion
{
    public const ARGUMENT_NAME = 'command-class-name';

    private const QUESTION = [
        'Please provide the class name of your new Command',
    ];

    private const DESCRIPTION = [];

    public function __construct(
        private iterable $inputHandlers,
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
        $commandContext->getIo()->text($this->getDescription());

        return $this->askQuestion(
            $this->createSymfonyQuestion($this->inputHandlers, $default),
            $commandContext
        );
    }
}
