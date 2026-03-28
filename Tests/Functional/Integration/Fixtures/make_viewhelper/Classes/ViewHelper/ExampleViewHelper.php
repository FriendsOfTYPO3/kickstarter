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
    }
    public function render(): string
    {
        return 'ViewHelper ExampleViewHelper content. ';
    }
}
