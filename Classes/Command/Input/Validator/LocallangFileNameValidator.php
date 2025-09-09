<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Validator;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.locallang_filename')]
class LocallangFileNameValidator implements ValidatorInterface
{
    public function __invoke(mixed $answer): string
    {
        if (!is_string($answer) || $answer === '') {
            throw new \RuntimeException(
                'File name must be a non-empty string',
                4257489513,
            );
        }

        if (!str_ends_with($answer, '.xlf')) {
            throw new \RuntimeException(
                'File name must have the ".xlf" extension',
                8832874759,
            );
        }

        $base = substr($answer, 0, -strlen('.xlf'));

        // Only lowercase letters, digits, and underscores in the basename
        if (in_array(preg_match('/^[a-z0-9_]+$/', $base), [0, false], true)) {
            throw new \RuntimeException(
                'File name (without prefix/extension) may only contain lowercase letters, digits, and underscores',
                5854095828,
            );
        }

        // No leading or trailing underscore and no consecutive underscores in the basename
        if ($base !== '' && ($base[0] === '_' || $base[strlen($base) - 1] === '_' || str_contains($base, '__'))) {
            throw new \RuntimeException(
                'File name cannot start or end with an underscore and cannot contain consecutive underscores',
                3696054208,
            );
        }

        return $answer;
    }
}
