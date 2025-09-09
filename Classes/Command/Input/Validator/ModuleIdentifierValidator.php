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

#[AutoconfigureTag('ext-kickstarter.inputHandler.module-identifier')]
class ModuleIdentifierValidator implements ValidatorInterface
{
    public function __invoke(mixed $answer): string
    {
        // Simple check for empty input
        if (($answer ?? '') === '') {
            throw new \RuntimeException('Module identifier cannot be empty.', 5943380797);
        }

        if (preg_match('/^\d/', $answer)) {
            throw new \RuntimeException('Module identifier must not start with a number.', 8733431359);
        }
        if (in_array(preg_match('#\w+#', $answer), [0, false], true)) {
            throw new \RuntimeException('Module identifier only use alphanumerics an underscore.', 3949700152);
        }

        return $answer;
    }
}
