<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Kickstarter\Traits;

use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Enums\FileModificationType;
use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

trait CreatorInformationTrait
{
    private function printCreatorInformation(CreatorInformation $creatorInformation, CommandContext $commandContext): void
    {
        $io = $commandContext->getIo();
        $fileMessages = [];
        foreach ($creatorInformation->getFileModifications() as $fileModification) {
            match ($fileModification->getFileModificationType()) {
                FileModificationType::CREATED => $fileMessages[ContextualFeedbackSeverity::OK->name][] = 'File ' . $fileModification->getPath() . ' was created.',
                FileModificationType::MODIFIED => $fileMessages[ContextualFeedbackSeverity::OK->name][] = 'File ' . $fileModification->getPath() . ' was modified.',
                FileModificationType::NOT_MODIFIED => $fileMessages[ContextualFeedbackSeverity::WARNING->name][] = 'File ' . $fileModification->getPath() . ' does not need to be modified: ' . $fileModification->getMessage(),
                FileModificationType::CREATION_FAILED => $fileMessages[ContextualFeedbackSeverity::ERROR->name][] = 'File ' . $fileModification->getPath() . ' could not be created: ' . $fileModification->getMessage(),
                FileModificationType::MODIFICATION_FAILED => $fileMessages[ContextualFeedbackSeverity::ERROR->name][] = 'File ' . $fileModification->getPath() . ' could not be modified: ' . $fileModification->getMessage(),
                default => $fileMessages[ContextualFeedbackSeverity::ERROR->name][] = 'Something went wrong: ' . $fileModification->getMessage(),
            };
        }

        foreach ($fileMessages as $severity => $fileMessagesWithSeverity) {
            match ($severity) {
                ContextualFeedbackSeverity::OK->name => $io->success($fileMessagesWithSeverity),
                ContextualFeedbackSeverity::WARNING->name => $io->warning($fileMessagesWithSeverity),
                ContextualFeedbackSeverity::ERROR->name => $io->error($fileMessagesWithSeverity),
                default => $io->note($fileMessagesWithSeverity),
            };
        }
    }
}
