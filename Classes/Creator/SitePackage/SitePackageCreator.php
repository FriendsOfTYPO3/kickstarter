<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\SitePackage;

use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;

class SitePackageCreator implements SitePackageCreatorInterface
{
    private const API_URL = 'https://get.typo3.org/api/v1/sitepackage/';

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $payload = $this->buildPayload($sitePackageInformation);

        [$headers, $zipData] = $this->sendRequest(self::API_URL, $payload);

        $filename = $this->detectFilenameFromHeaders($headers);
        $extensionKey = $this->detectExtensionKeyFromFilename($filename);

        $tmpZip = $this->saveTemporaryZip($filename, $zipData);

        $basePath = rtrim($sitePackageInformation->getExtensionInformation()->getExtensionPath(), '/');
        $targetPath = str_ends_with($basePath, '/' . $extensionKey) ? $basePath : $basePath . '/' . $extensionKey;

        // Check if the directory already exists → abort if true
        if (is_dir($targetPath)) {
            $sitePackageInformation->getCreatorInformation()->fileExists(
                $targetPath,
                sprintf(
                    'Site packages can only be created, not modified. The directory %s already exists and cannot be overridden.',
                    $targetPath
                )
            );
            @unlink($tmpZip);
            return;
        }

        if ($this->extractZip($sitePackageInformation->getCreatorInformation(), $tmpZip, $targetPath)) {
            $sitePackageInformation->getCreatorInformation()->fileAdded($targetPath);

            // Update ExtensionInformation dynamically
            $extensionInfo = $sitePackageInformation->getExtensionInformation();

            // Set extension key (based on ZIP filename)
            $extensionInfo->setExtensionKey($extensionKey);

            // Set correct extension path
            $extensionInfo->setExtensionPath($targetPath);

            // Try to read real composer package name from composer.json
            $composerJsonPath = $targetPath . '/composer.json';
            if (is_file($composerJsonPath)) {
                $composerData = json_decode(file_get_contents($composerJsonPath), true);
                if (json_last_error() === JSON_ERROR_NONE && isset($composerData['name'])) {
                    $extensionInfo->setComposerPackageName($composerData['name']);
                }
            }
        }
    }

    private function sendRequest(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/zip',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException('cURL error: ' . curl_error($ch), 9535992936);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        if ($status !== 200) {
            throw new \RuntimeException(
                sprintf('Sitepackage API call failed (HTTP %d): %s', $status, in_array(trim($body), ['', '0'], true) ? '[empty response]' : trim($body)),
                9535992936
            );
        }

        return [$headers, $body];
    }

    /**
     * Extracts the filename safely from headers and ensures it ends with .zip.
     */
    private function detectFilenameFromHeaders(string $headers): string
    {
        if (preg_match('/filename="?([^";]+\.zip)"?/i', $headers, $matches)) {
            return basename($matches[1]);
        }
        return 'sitepackage.zip';
    }

    private function detectExtensionKeyFromFilename(string $filename): string
    {
        if (preg_match('/^(.+?)_\d+\.\d+\.\d+\.zip$/', $filename, $matches)) {
            return $matches[1];
        }
        return basename($filename, '.zip');
    }

    /**
     * Saves ZIP data to a temporary file, sanitizing the filename to avoid rename issues.
     */
    private function saveTemporaryZip(string $filename, string $zipData): string
    {
        // sanitize filename to prevent spaces or invalid characters from headers
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);

        $tmpFile = tempnam(sys_get_temp_dir(), 'sitepackage_');
        $tmpZip = $tmpFile . '_' . $safeFilename;

        // try rename safely, otherwise fallback
        if (!@rename($tmpFile, $tmpZip)) {
            $tmpZip = $tmpFile . '.zip';
        }

        file_put_contents($tmpZip, $zipData);
        return $tmpZip;
    }

    private function extractZip(CreatorInformation $creatorInformation, string $zipFile, string $targetPath): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            unlink($zipFile);
            $creatorInformation->writingFileFailed('Failed to open sitepackage ZIP.');
            return false;
        }

        if (!is_dir($targetPath) && !mkdir($targetPath, 0777, true) && !is_dir($targetPath)) {
            unlink($zipFile);
            $creatorInformation->writingFileFailed('Failed to create target directory: ' . $targetPath);
            return false;
        }

        $zip->extractTo($targetPath);
        $zip->close();
        unlink($zipFile);
        return true;
    }

    private function buildPayload(SitePackageInformation $info): array
    {
        $ext = $info->getExtensionInformation();

        return [
            'base_package' => $info->getType(),
            'typo3_version' => (float)$ext->getVersion() ?: 13.4,
            'title' => $ext->getTitle(),
            'description' => $ext->getDescription(),
            'repository_url' => filter_var($ext->getComposerPackageName(), FILTER_VALIDATE_URL) ?: '',
            'author' => [
                'name' => $ext->getAuthor(),
                'email' => $ext->getAuthorEmail(),
                'company' => $ext->getAuthorCompany(),
                'homepage' => $info->getHomepage() ?? '',
            ],
        ];
    }
}
