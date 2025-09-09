<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Kickstarter\Creator;

use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;

class FileManager
{
    public function createFile(string $targetFile, string $content, CreatorInformation $creatorInformation): int
    {
        if (is_file($targetFile)) {
            throw new \Exception('File ' . $targetFile . ' cannot be created, it already exists', 8835975026);
        }
        $result = file_put_contents($targetFile, $content);
        if ($result === false) {
            $creatorInformation->writingFileFailed($targetFile);
        } else {
            $creatorInformation->fileAdded($targetFile);
        }
        return $result;
    }

    public function modifyFile(string $targetFile, string $content, CreatorInformation $creatorInformation): int|false
    {
        if (!is_file($targetFile)) {
            throw new \Exception('File ' . $targetFile . ' cannot be modified, it does exists', 7584800145);
        }
        $result = file_put_contents($targetFile, $content);
        if ($result === false) {
            $creatorInformation->writingFileFailed($targetFile);
        } else {
            $creatorInformation->fileModified($targetFile);
        }
        return $result;
    }

    public function createOrModifyFile(string $targetFile, string $content, CreatorInformation $creatorInformation): void
    {
        if (is_file($targetFile)) {
            $this->modifyFile($targetFile, $content, $creatorInformation);
            return;
        }

        $this->createFile($targetFile, $content, $creatorInformation);
    }
}
