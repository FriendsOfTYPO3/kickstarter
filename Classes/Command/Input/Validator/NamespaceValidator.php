<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Validator;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\NamespaceQuestion;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.namespace')]
class NamespaceValidator implements ValidatorInterface
{
    /** @see https://regex101.com/r/oNvIYg/1 */
    private const NAMESPACE_REGEX = '#^[A-Z][a-zA-Z0-9]*\\\\[A-Z][a-zA-Z0-9]*\\\\$#';

    public function getArgumentName(): string
    {
        return NamespaceQuestion::ARGUMENT_NAME;
    }

    public function __invoke(mixed $answer): string
    {
        if (!is_string($answer)) {
            throw new \RuntimeException(
                'Namespace must be a string',
                1753984305,
            );
        }
        if (preg_match(self::NAMESPACE_REGEX, $answer) !== 1) {
            throw new \RuntimeException(
                'You have entered an invalid namespace.',
                1753984306,
            );
        }

        return $answer;
    }
}
