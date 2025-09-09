<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Decorator;

use FriendsOfTYPO3\Kickstarter\Command\Input\Normalizer\MiddlewareClassNameNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.middleware-class')]
readonly class MiddlewareClassNameDecorator implements DecoratorInterface
{
    public function __construct(
        private MiddlewareClassNameNormalizer $middlewareClassNameNormalizer
    ) {}

    public function __invoke(?string $defaultValue = null): string
    {
        $className = $defaultValue ?? '';
        if (str_contains($className, '/')) {
            $className = substr($className, strpos($className, '/') + 1);
        }
        return $this->middlewareClassNameNormalizer->__invoke($className);
    }
}
