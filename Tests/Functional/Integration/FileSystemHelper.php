<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

class FileSystemHelper
{
    /**
     * Other than GeneralUtility::copyDirectory() this method supports absolute directories.
     */
    public static function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            throw new \RuntimeException(sprintf('Source directory does not exist: %s', $source), 9172752536);
        }

        // Normalize paths
        $source = rtrim($source, DIRECTORY_SEPARATOR);
        $destination = rtrim($destination, DIRECTORY_SEPARATOR);

        // Ensure destination exists
        if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new \RuntimeException(sprintf('Cannot create destination directory: %s', $destination), 6862256191);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            // Calculate relative path manually
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            $target = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0777, true) && !is_dir($target)) {
                    throw new \RuntimeException(sprintf('Cannot create directory: %s', $target), 4035817898);
                }
            } elseif (!copy($item->getPathname(), $target)) {
                throw new \RuntimeException(sprintf('Failed to copy file: %s', $item->getPathname()), 2700761414);
            }
        }
    }
}
