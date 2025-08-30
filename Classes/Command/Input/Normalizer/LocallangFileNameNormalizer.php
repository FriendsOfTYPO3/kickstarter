<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Normalizer;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.locallang_file_name')]
class LocallangFileNameNormalizer implements NormalizerInterface
{
    public function __invoke(?string $userInput): string
    {
        if ($userInput === null || $userInput === '') {
            return 'locallang.xlf';
        }

        $cleanedUserInput = strtolower($userInput);

        // Remove invalid chars
        $cleanedUserInput = preg_replace('/[^a-z0-9_]/', '', $cleanedUserInput);

        // Remove leading and trailing "_"
        $cleanedUserInput = trim($cleanedUserInput, '_');

        // Ensure it starts with "locallang_"
        if (!str_starts_with($cleanedUserInput, 'locallang_')) {
            $cleanedUserInput = 'locallang_' . $cleanedUserInput;
        }

        // Ensure it ends with ".xlf"
        if (!str_ends_with($cleanedUserInput, '.xlf')) {
            $cleanedUserInput .= '.xlf';
        }

        return $cleanedUserInput;
    }
}
