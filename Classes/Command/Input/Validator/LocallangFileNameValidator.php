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
            );
        }

        // Must start with "locallang" and end with ".xlf"
        if (!str_starts_with($answer, 'locallang')) {
            throw new \RuntimeException(
                'File name must start with "locallang"',
            );
        }
        if (!str_ends_with($answer, '.xlf')) {
            throw new \RuntimeException(
                'File name must have the ".xlf" extension',
            );
        }

        // Extract the part between "locallang" and ".xlf"
        $base = substr($answer, strlen('locallang'), -strlen('.xlf'));

        // Base may be empty (valid: "locallang.xlf")
        if ($base === '') {
            return $answer;
        }

        // If present, it must start with an underscore
        if (!str_starts_with($base, '_')) {
            throw new \RuntimeException(
                'Characters after "locallang" must start with an underscore',
            );
        }

        // Remove the leading underscore for further validation
        $base = substr($base, 1);

        // Only lowercase letters, digits, and underscores in the basename
        if (in_array(preg_match('/^[a-z0-9_]+$/', $base), [0, false], true)) {
            throw new \RuntimeException(
                'File name (without prefix/extension) may only contain lowercase letters, digits, and underscores',
            );
        }

        // No leading or trailing underscore and no consecutive underscores in the basename
        if ($base !== '' && ($base[0] === '_' || $base[strlen($base) - 1] === '_' || str_contains($base, '__'))) {
            throw new \RuntimeException(
                'File name (without prefix/extension) cannot start or end with an underscore and cannot contain consecutive underscores',
            );
        }

        return $answer;
    }
}
