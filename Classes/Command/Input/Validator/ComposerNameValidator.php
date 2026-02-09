<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Validator;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\ComposerNameQuestion;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.composer_name')]
class ComposerNameValidator implements ValidatorInterface
{
    private const COMPOSER_NAME_REGEX = '#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#';

    public function getArgumentName(): string
    {
        return ComposerNameQuestion::ARGUMENT_NAME;
    }

    public function __invoke(mixed $answer): string
    {
        if (!is_string($answer)) {
            throw new \RuntimeException(
                'Composer name must be a string',
                1753959716,
            );
        }

        if (preg_match(self::COMPOSER_NAME_REGEX, $answer) !== 1) {
            throw new \RuntimeException(
                'Invalid composer package name. Package name must follow a specific pattern (see: https://getcomposer.org/doc/04-schema.md#name)',
                1753959716,
            );
        }

        return $answer;
    }
}
