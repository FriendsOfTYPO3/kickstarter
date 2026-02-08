<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\AbstractQuestion;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.command.question.extension')]
readonly class ComposerNameQuestion extends AbstractQuestion
{
    public const ARGUMENT_NAME = 'composer_name';

    private const QUESTION = [
        'Composer package name',
    ];

    private const DESCRIPTION = [
        'To build a new TYPO3 extension, we need to use Composer to manage dependencies.',
        'Composer is like a package manager for PHP projects.',
        'For more information about Composer, visit https://getcomposer.org/',
        'Example: my-vendor/my-extension',
    ];

    public function __construct(
        private iterable $inputHandlers,
    ) {}

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

        return $this->askQuestion(
            $this->createSymfonyQuestion($this->inputHandlers, $default),
            $commandContext
        );
    }
}
