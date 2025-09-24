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

class CreatorInformation
{
    /**
     * @param FileModificationInformation[] $fileModifications
     */
    public function __construct(
        private array $fileModifications = [],
    ) {}

    public function getFileModifications(): array
    {
        return $this->fileModifications;
    }

    public function fileAdded(string $path): void
    {
        $this->fileModifications[] = new FileModificationInformation($path, FileModificationType::CREATED);
    }

    public function fileModified(string $path): void
    {
        $this->fileModifications[] = new FileModificationInformation($path, FileModificationType::MODIFIED);
    }

    public function writingFileFailed(string $path, ?string $message = null): void
    {
        $this->fileModifications[] = new FileModificationInformation(
            $path,
            FileModificationType::ABORTED,
            $message ?? 'The file cannot be written. Check file permissions etc. '
        );
    }

    public function fileExists(string $path, ?string $message = null): void
    {
        $this->fileModifications[] = new FileModificationInformation(
            $path,
            FileModificationType::CREATION_FAILED,
            $message ?? 'The file cannot be modified. '
        );
    }

    public function fileModificationFailed(string $path, ?string $message = null): void
    {
        $this->fileModifications[] = new FileModificationInformation(
            $path,
            FileModificationType::MODIFICATION_FAILED,
            $message ?? 'The file cannot be modified. '
        );
    }

    public function fileNotModified(string $path, ?string $message = null): void
    {
        $this->fileModifications[] = new FileModificationInformation(
            $path,
            FileModificationType::NOT_MODIFIED,
            $message ?? 'The file does not need to be modified. '
        );
    }
}
