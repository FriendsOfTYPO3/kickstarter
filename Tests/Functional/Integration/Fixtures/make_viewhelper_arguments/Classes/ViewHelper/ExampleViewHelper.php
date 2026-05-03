<?php

declare(strict_types=1);

/*
 * This file is part of the package my-vendor/my-extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
namespace MyVendor\MyExtension\ViewHelper;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
final class ExampleViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument(
            'emailAddress',
            'string',
            'The email address to resolve the gravatar for',
            true,
        );
        $this->registerArgument(
            'size',
            'integer',
            'The size of the gravatar, ranging from 1 to 512',
            false,
            80,
        );
    }
    public function render(): string
    {
        $emailAddress = $this->arguments['emailAddress'];
        $size = $this->arguments['size'];
        return sprintf(
            'ViewHelper ExampleViewHelper content. The following arguments where passed: emailAddress: %s, size: %s',
            $emailAddress,
            $size,
        );
    }
}
