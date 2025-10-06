<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Integration;

use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractServiceCreatorTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'kickstarter',
    ];

    protected array $coreExtensionsToLoad = [
        'install',
    ];

    protected function getTrimmedFileContent(string $actualFile): string
    {
        $content = file_get_contents($actualFile);
        if ($content === false) {
            return '';
        }
        return trim($content);
    }

    /**
     * Replace volatile bits (like Unix timestamps) with placeholders.
     */
    protected function normalizeForComparison(string $content): string
    {
        // Normalize newlines first
        $content = preg_replace('/\R/u', "\n", $content);

        // 10-digit Unix timestamps (roughly 2015..2099) → <TS>
        $content = preg_replace('/(?<!\d)(?:1[5-9]\d{8}|2\d{9})(?!\d)/', '<TS>', $content);

        // 13-digit millisecond timestamps → <TS_MS>
        $content = preg_replace('/(?<!\d)\d{13}(?!\d)/', '<TS_MS>', $content);

        return trim($content);
    }

    protected function shouldUpdateBaseline(): bool
    {
        // Environment variable support
        return getenv('UPDATE_BASELINE') === '1';
    }

    protected function assertDirectoryEquals(string $expectedDir, string $actualDir): void
    {
        if ($this->shouldUpdateBaseline()) {
            FileSystemHelper::copyDirectory($actualDir, $expectedDir);
            self::markTestSkipped('Baseline updated: expected fixtures were overwritten with new output.');
        }

        // Normal comparison when not updating baseline
        $expectedFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($expectedDir));
        foreach ($expectedFiles as $file) {
            if ($file->isDir()) {
                continue;
            }
            $relativePath = str_replace($expectedDir, '', $file->getPathname());
            $actualFile = $actualDir . $relativePath;

            self::assertFileExists($actualFile, sprintf('Missing file: %s', $relativePath));
            self::assertSame(
                $this->normalizeForComparison($this->getTrimmedFileContent($file->getPathname())),
                $this->normalizeForComparison($this->getTrimmedFileContent($actualFile)),
                sprintf('File contents differ for: %s', $relativePath)
            );
        }
    }

    protected function getExtensionInformation(string $extensionKey, string $composerName, string $extensionPath): ExtensionInformation
    {
        return new ExtensionInformation(
            $extensionKey,
            $composerName,
            '',
            '',
            '0.0.0',
            'plugin',
            'alpha',
            '',
            '',
            '',
            'MyVendor\\MyExtension\\',
            $extensionPath
        );
    }
}
