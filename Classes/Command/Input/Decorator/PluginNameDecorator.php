<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Decorator;

use FriendsOfTYPO3\Kickstarter\Command\Input\Normalizer\PluginNameNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AutoconfigureTag('ext-kickstarter.inputHandler.plugin-name')]
readonly class PluginNameDecorator implements DecoratorInterface
{
    public function __construct(
        private PluginNameNormalizer $pluginNameNormalizer
    ) {}

    public function __invoke(?string $defaultValue = null): string
    {
        $pluginName = GeneralUtility::underscoredToUpperCamelCase(str_replace(' ', '_', $defaultValue ?? ''));
        return $this->pluginNameNormalizer->__invoke($pluginName);
    }
}
