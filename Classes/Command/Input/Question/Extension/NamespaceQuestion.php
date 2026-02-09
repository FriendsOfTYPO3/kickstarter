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
readonly class NamespaceQuestion extends AbstractQuestion
{
    public const ARGUMENT_NAME = 'namespace';

    private const QUESTION = [
        'PSR-4 AutoLoading Namespace',
    ];

    private const DESCRIPTION = [
        'To find PHP classes much faster in your extension TYPO3 uses the auto-loading',
        'mechanism of composer (https://getcomposer.org/doc/01-basic-usage.md#autoloading)',
        'Please enter the PSR-4 autoload namespace for your extension',
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
