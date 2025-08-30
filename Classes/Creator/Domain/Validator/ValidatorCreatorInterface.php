<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Domain\Validator;

use FriendsOfTYPO3\Kickstarter\Information\ValidatorInformation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('ext-kickstarter.creator.domain.validator')]
interface ValidatorCreatorInterface
{
    public function create(ValidatorInformation $validatorInformation): void;
}
