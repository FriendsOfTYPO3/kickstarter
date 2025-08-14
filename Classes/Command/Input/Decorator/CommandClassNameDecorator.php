<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Command\Input\Decorator;

use StefanFroemken\ExtKickstarter\Command\Input\Decorator\DecoratorInterface;
use StefanFroemken\ExtKickstarter\Command\Input\Normalizer\CommandClassNameNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.inputHandler.command-class')]
class CommandClassNameDecorator implements DecoratorInterface
{
    public function __construct(
        private CommandClassNameNormalizer $commandClassNameNormalizer
    )
    {
    }

    public function __invoke(?string $defaultValue = null): string
    {
        $className = $defaultValue??'';
        if(str_contains($className, ':')){
            $className = substr($className, strpos($className, ':') + 1);
        }
        return $this->commandClassNameNormalizer->__invoke($className);
    }
}
