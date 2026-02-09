<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Validator;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\VersionQuestion;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.version')]
class VersionValidator implements ValidatorInterface
{
    private const VERSION_REGEX = '#^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$#';

    public function getArgumentName(): string
    {
        return VersionQuestion::ARGUMENT_NAME;
    }

    public function __invoke(mixed $answer): string
    {
        if (!is_string($answer)) {
            throw new \RuntimeException(
                'Version must be a string',
                1753983393,
            );
        }
        if (in_array(preg_match(self::VERSION_REGEX, $answer), [0, false], true)) {
            throw new \RuntimeException(
                'Invalid version string. The version must match a specific pattern (see: https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string)',
                1753983423,
            );
        }

        return $answer;
    }
}
