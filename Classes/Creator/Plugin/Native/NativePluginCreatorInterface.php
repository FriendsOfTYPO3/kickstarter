<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Plugin\Native;

use FriendsOfTYPO3\Kickstarter\Information\PluginInformation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.creator.plugin.native')]
interface NativePluginCreatorInterface
{
    public function create(PluginInformation $pluginInformation): void;
}
