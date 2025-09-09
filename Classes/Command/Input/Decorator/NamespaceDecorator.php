<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Decorator;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.namespace')]
class NamespaceDecorator implements DecoratorInterface
{
    public function __invoke(?string $defaultValue = null): string
    {
        if ($defaultValue === null) {
            return '';
        }

        return implode(
            '\\',
            array_map(
                fn($part): string => str_replace(
                    [
                        '-',
                        '_',
                        '.',
                    ],
                    '',
                    ucwords($part, '-_ .')
                ),
                explode('/', $defaultValue)
            )
        ) . '\\';
    }
}
