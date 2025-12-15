<?php

declare(strict_types=1);

/*
 * This file is part of the package my-vendor/my-extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
namespace MyVendor\MyExtension\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
final class Group extends AbstractEntity
{
    protected string $title = '';
    protected string $description = '';
    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
