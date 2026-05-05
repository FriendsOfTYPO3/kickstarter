<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Information\PluginInformation;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class PluginCreatorService
{
    public function __construct(
        #[AutowireIterator('ext-kickstarter.creator.plugin.extbase')]
        private iterable $extbasePluginCreators,
        #[AutowireIterator('ext-kickstarter.creator.plugin.native')]
        private iterable $nativePluginCreators,
    ) {}

    public function create(PluginInformation $pluginInformation): void
    {
        if ($pluginInformation->isExtbasePlugin()) {
            foreach ($this->extbasePluginCreators as $creator) {
                $creator->create($pluginInformation);
            }
        } else {
            foreach ($this->nativePluginCreators as $creator) {
                $creator->create($pluginInformation);
            }
        }
    }
}
