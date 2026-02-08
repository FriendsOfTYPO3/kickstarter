<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Question\Locallang;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\AbstractQuestion;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.command.question.locallang')]
readonly class LocallangTransUnitIdQuestion extends AbstractQuestion
{
    public const ARGUMENT_NAME = 'locallang_trans_unit_id';

    private const QUESTION = [
        'Enter an identifier for the term to be translated. ',
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
