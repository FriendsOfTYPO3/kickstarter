<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Kickstarter\Traits;

use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Enums\FileModificationType;
use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;

trait CreatorInformationTrait
{
    private function printCreatorInformation(CreatorInformation $creatorInformation, CommandContext $commandContext): void
    {
        $io = $commandContext->getIo();
        foreach ($creatorInformation->getFileModifications() as $fileModification) {
            match ($fileModification->getFileModificationType()) {
                FileModificationType::CREATED => $io->success('File ' . $fileModification->getPath() . ' was created. '),
                FileModificationType::MODIFIED => $io->success('File ' . $fileModification->getPath() . ' was modified. '),
                FileModificationType::NOT_MODIFIED => $io->warning('File ' . $fileModification->getPath() . ' does not need to be modified:  ' . $fileModification->getMessage()),
                FileModificationType::CREATION_FAILED => $io->error('File ' . $fileModification->getPath() . ' could not be created: ' . $fileModification->getMessage()),
                FileModificationType::MODIFICATION_FAILED => $io->error('File ' . $fileModification->getPath() . ' could not be modified: ' . $fileModification->getMessage()),
                default => $io->error('Something went wrong: ' . $fileModification->getMessage()),
            };
        }
    }
}
