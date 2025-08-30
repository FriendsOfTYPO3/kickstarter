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

#[AutoconfigureTag('ext-kickstarter.inputHandler.locallang_trans_unit_id')]
class LocallangTransUnitIdValidator implements ValidatorInterface
{
    public function __invoke(mixed $answer): string
    {
        if (!is_string($answer) || $answer === '') {
            throw new \RuntimeException(
                'Identifier must be a non-empty string',
                1753823960,
            );
        }

        $length = mb_strlen($answer);
        if ($length < 1 || $length > 100) {
            throw new \RuntimeException(
                'Identifier length must be between 1 and 100 characters',
                1753823962,
            );
        }

        // Allowed: lowercase letters, digits; separators: dot/underscore/hyphen
        // No leading/trailing/consecutive separators
        // Regex: head token, then zero+ (sep + token)
        $ok = preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $answer);
        if (in_array($ok, [0, false], true)) {
            throw new \RuntimeException(
                'Identifier may contain lowercase letters and digits, optionally separated by ".", "_" or "-"; no leading, trailing, or repeated separators are allowed',
                1753823964,
            );
        }

        return $answer;
    }
}
