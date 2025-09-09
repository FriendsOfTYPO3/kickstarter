<?php

declare(strict_types=1);

/*
 * This file is part of the package my-vendor/yyy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
namespace MyVendor\MyExtension\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
class TestController extends ActionController
{
    public function indexAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }
    public function testAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }
}
