<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Question;

use FriendsOfTYPO3\Kickstarter\Command\Input\AutoComplete\AutoCompleteInterface;
use FriendsOfTYPO3\Kickstarter\Command\Input\Decorator\DecoratorInterface;
use FriendsOfTYPO3\Kickstarter\Command\Input\Normalizer\NormalizerInterface;
use FriendsOfTYPO3\Kickstarter\Command\Input\Validator\ValidatorInterface;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

abstract readonly class AbstractQuestion implements QuestionInterface
{
    abstract protected function getDescription(): array;

    abstract protected function getQuestion(): array;

    public function getArgumentName(): string
    {
        $calledClass = static::class;

        if (!defined($calledClass . '::ARGUMENT_NAME')) {
            throw new \RuntimeException(
                sprintf('Class %s must define the constant ARGUMENT_NAME.', $calledClass),
                3362475211
            );
        }

        return $calledClass::ARGUMENT_NAME;
    }

    protected function getDefault(): ?string
    {
        return null;
    }

    protected function createSymfonyQuestion(iterable $inputHandlers, ?string $default = null): Question
    {
        foreach ($inputHandlers as $inputHandler) {
            if ($inputHandler instanceof DecoratorInterface) {
                $default = $inputHandler($default);
                break;
            }
        }

        $symfonyQuestion = new Question(
            implode(' ', $this->getQuestion()),
            $default ?? $this->getDefault(),
        );

        foreach ($inputHandlers as $inputHandler) {
            if ($inputHandler instanceof ValidatorInterface) {
                $symfonyQuestion->setValidator($inputHandler);
                continue;
            }

            if ($inputHandler instanceof NormalizerInterface) {
                $symfonyQuestion->setNormalizer($inputHandler);
                continue;
            }

            if ($inputHandler instanceof AutoCompleteInterface) {
                $symfonyQuestion->setAutocompleterCallback($inputHandler);
            }
        }

        return $symfonyQuestion;
    }

    protected function createSymfonyChoiceQuestion(iterable $inputHandlers, array $choices, ?string $default = null): ChoiceQuestion
    {
        $symfonyQuestion = new ChoiceQuestion(
            implode(' ', $this->getQuestion()),
            $choices,
            $default ?? $this->getDefault(),
        );

        foreach ($inputHandlers as $inputHandler) {
            if ($inputHandler instanceof ValidatorInterface) {
                $symfonyQuestion->setValidator($inputHandler);
                continue;
            }
        }

        return $symfonyQuestion;
    }

    protected function askQuestion(Question $question, CommandContext $commandContext): mixed
    {
        return (new SymfonyQuestionHelper())->ask(
            $commandContext->getInput(),
            $commandContext->getOutput(),
            $question
        );
    }
}
