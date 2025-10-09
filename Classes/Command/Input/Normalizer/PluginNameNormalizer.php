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

#[AutoconfigureTag('ext-kickstarter.inputHandler.plugin-name')]
class PluginNameNormalizer implements NormalizerInterface
{
    public function __invoke(?string $userInput): string
    {
        return ucfirst(preg_replace('#[^a-zA-Z0-9]+#', '', $userInput ?? ''));
    }
}
