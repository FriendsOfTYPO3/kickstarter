<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command\Input\Validator;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\Extension\EmailQuestion;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AutoconfigureTag('ext-kickstarter.inputHandler.email')]
class EmailValidator implements ValidatorInterface
{
    public function getArgumentName(): string
    {
        return EmailQuestion::ARGUMENT_NAME;
    }

    public function __invoke(mixed $answer): string
    {
        if ($answer === null) {
            return '';
        }
        if (!is_string($answer)) {
            throw new \RuntimeException(
                'Email must be a string',
                1753983786,
            );
        }
        if ($answer !== '' && !GeneralUtility::validEmail($answer)) {
            throw new \RuntimeException(
                'You have entered an invalid email address.',
                1753983789,
            );
        }

        return $answer;
    }
}
