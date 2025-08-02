<?php

namespace StefanFroemken\ExtKickstarter\Tests\Functional\Integration;

use StefanFroemken\ExtKickstarter\Information\ExtensionInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractServiceCreatorTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'ext_kickstarter',
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
                $this->getTrimmedFileContent($file->getPathname()),
                $this->getTrimmedFileContent($actualFile),
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
