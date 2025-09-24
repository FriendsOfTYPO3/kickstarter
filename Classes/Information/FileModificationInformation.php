<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

use FriendsOfTYPO3\Kickstarter\Enums\FileModificationType;

readonly class FileModificationInformation
{
    public function __construct(
        private string $path,
        private FileModificationType $fileModificationType,
        private string $message = '',
    ) {}

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFileModificationType(): FileModificationType
    {
        return $this->fileModificationType;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
