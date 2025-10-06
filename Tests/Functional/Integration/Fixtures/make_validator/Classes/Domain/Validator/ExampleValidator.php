<?php

declare(strict_types=1);

/*
 * This file is part of the package my-vendor/my-extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
namespace MyVendor\MyExtension\Domain\Validator;

use MyVendor\MyExtension\Domain\Model\SomeModel;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
final class ExampleValidator extends AbstractValidator
{
    protected function isValid(mixed $value): void
    {
        if (!$value instanceof SomeModel) {
            return;
        }
        $this->addError(
            'Validator needs to be implemented. See https://docs.typo3.org/permalink/t3coreapi:extbase-domain-validator for details.',
            1759753265,
        );
    }
}
