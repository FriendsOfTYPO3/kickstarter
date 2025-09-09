<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Normalizer;

use FriendsOfTYPO3\Kickstarter\Command\Input\Decorator\DecoratorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.locallang_trans_unit_id')]
class LocallangTransUnitIdNormalizer implements NormalizerInterface, DecoratorInterface
{
    public function __invoke(?string $userInput = null): string
    {
        if ($userInput === null || $userInput === '') {
            return '';
        }

        // Lowercase
        $out = mb_strtolower($userInput);

        // Replace any invalid chars with underscore
        $out = preg_replace('/[^a-z0-9._-]/u', '_', $out) ?? '';

        // Collapse consecutive separators
        $out = preg_replace('/\.+/', '.', $out) ?? '';
        $out = preg_replace('/_+/', '_', $out) ?? '';
        $out = preg_replace('/-+/', '-', $out) ?? '';

        // Remove leading/trailing separators
        $out = trim($out, '._-');

        return $out;
    }
}
